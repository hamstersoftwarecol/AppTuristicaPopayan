<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Departure;
use App\Models\TourismService;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function reserve(User $user, int $departureId, int $travelers, string $key, string $quoteToken): Booking
    {
        if ($travelers < 1 || $travelers > 100) {
            throw ValidationException::withMessages(['travelers' => 'Selecciona entre 1 y 100 viajeros.']);
        }
        $key = strtolower($key);
        $this->expireDeparture($departureId);
        $existing = Booking::where('user_id', $user->id)->where('idempotency_key', $key)->first();
        if ($existing) {
            return $this->replayed($existing, $departureId, $travelers);
        }

        try {
            return DB::transaction(function () use ($user, $departureId, $travelers, $key, $quoteToken): Booking {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $departure = Departure::lockForUpdate()->findOrFail($departureId);
                $this->releaseExpired($departure);
                $existing = Booking::where('user_id', $user->id)->where('idempotency_key', $key)->first();
                if ($existing) {
                    return $this->replayed($existing, $departureId, $travelers);
                }
                $publicService = TourismService::published()->whereKey($departure->tourism_service_id)->lockForUpdate()->first();
                if (! $departure->is_open || ! $publicService || ! in_array($publicService->category, ['actividades', 'paquetes'], true) || $departure->service_date->lte(today())) {
                    throw ValidationException::withMessages(['departure_id' => 'Esta salida ya no está disponible para reservar.']);
                }
                if (! hash_equals($departure->quoteToken(), $quoteToken)) {
                    throw ValidationException::withMessages(['quote_token' => 'La tarifa o las condiciones cambiaron. Recarga la ficha y revísalas antes de reservar.']);
                }
                // The conditional update enforces capacity even on drivers without row locks.
                $claimed = Departure::whereKey($departure->id)->whereRaw('reserved + ? <= capacity', [$travelers])->increment('reserved', $travelers);
                if ($claimed !== 1) {
                    throw ValidationException::withMessages(['travelers' => 'No quedan cupos suficientes. Elige otra fecha o reduce los viajeros.']);
                }
                $booking = Booking::create([
                    'reference' => Str::uuid()->toString(),
                    'user_id' => $user->id,
                    'departure_id' => $departure->id,
                    'provider_id' => $publicService->provider_id,
                    'idempotency_key' => $key,
                    'service_title' => $publicService->title,
                    'provider_name' => $publicService->provider->name,
                    'service_date' => $departure->service_date,
                    'travelers' => $travelers,
                    'unit_price' => $departure->unit_price,
                    'total' => $departure->unit_price * $travelers,
                    'currency' => 'COP',
                    'terms' => $departure->terms,
                    'status' => 'pending',
                    'expires_at' => now()->addHours(24)->min($departure->service_date),
                ]);
                AuditLog::record('booking.requested', $booking, ['travelers' => $travelers, 'departure_id' => $departure->id]);
                app(AccountNotifier::class)->booking($booking);

                return $booking;
            }, 5);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = Booking::where('user_id', $user->id)->where('idempotency_key', $key)->first();
            if (! $existing) {
                throw $exception;
            }

            return $this->replayed($existing, $departureId, $travelers);
        }
    }

    public function confirm(Booking $booking): Booking
    {
        $this->expireDeparture($booking->departure_id);

        return DB::transaction(function () use ($booking): Booking {
            $departure = Departure::lockForUpdate()->findOrFail($booking->departure_id);
            $this->releaseExpired($departure);
            $locked = Booking::lockForUpdate()->findOrFail($booking->id);
            if ($locked->status === 'confirmed') {
                return $locked;
            }
            if ($locked->status !== 'pending' || $locked->expires_at->lte(now()) || $locked->service_date->lte(today())) {
                throw ValidationException::withMessages(['booking' => 'La solicitud venció, fue cancelada o la fecha ya comenzó.']);
            }
            if (! TourismService::published()->whereKey($departure->tourism_service_id)->exists()) {
                throw ValidationException::withMessages(['booking' => 'Publica el servicio y activa su proveedor antes de confirmar.']);
            }
            $locked->update(['status' => 'confirmed', 'confirmed_at' => now()]);
            AuditLog::record('booking.confirmed', $locked);
            app(AccountNotifier::class)->booking($locked);

            return $locked;
        }, 5);
    }

    public function cancel(Booking $booking, string $reason): Booking
    {
        $this->expireDeparture($booking->departure_id);

        return DB::transaction(function () use ($booking, $reason): Booking {
            $departure = Departure::lockForUpdate()->findOrFail($booking->departure_id);
            $this->releaseExpired($departure);
            $locked = Booking::lockForUpdate()->findOrFail($booking->id);
            if (in_array($locked->status, ['cancelled', 'expired'], true)) {
                return $locked;
            }
            if ($locked->service_date->lte(today())) {
                throw ValidationException::withMessages(['booking' => 'La salida ya comenzó. Solicita ayuda al equipo de soporte.']);
            }
            $locked->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => $reason]);
            Departure::whereKey($departure->id)->decrement('reserved', $locked->travelers);
            AuditLog::record('booking.cancelled', $locked);
            app(AccountNotifier::class)->booking($locked);

            return $locked;
        }, 5);
    }

    public function expireDeparture(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $this->releaseExpired(Departure::lockForUpdate()->findOrFail($id));
        }, 5);
    }

    // Call inside a transaction with the departure locked. All writers use the same lock order.
    private function releaseExpired(Departure $departure): void
    {
        $expired = Booking::where('departure_id', $departure->id)->where('status', 'pending')->where('expires_at', '<=', now())->lockForUpdate()->get();
        foreach ($expired as $booking) {
            $booking->update(['status' => 'expired']);
            Departure::whereKey($departure->id)->decrement('reserved', $booking->travelers);
            AuditLog::record('booking.expired', $booking);
            app(AccountNotifier::class)->booking($booking);
        }
    }

    private function replayed(Booking $booking, int $departureId, int $travelers): Booking
    {
        if ($booking->departure_id !== $departureId || $booking->travelers !== $travelers) {
            throw ValidationException::withMessages(['idempotency_key' => 'Esta solicitud ya se utilizó con otros datos. Recarga la ficha para iniciar una nueva.']);
        }

        return $booking;
    }
}
