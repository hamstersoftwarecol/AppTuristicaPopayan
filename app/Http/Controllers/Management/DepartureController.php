<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Departure;
use App\Models\TourismService;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DepartureController extends Controller
{
    public function index(Request $request, TourismService $service): Response
    {
        Gate::authorize('update', $service);
        abort_unless(in_array($service->category, ['actividades', 'paquetes'], true), 422, 'Las salidas locales están disponibles para actividades y paquetes.');

        return Inertia::render('management/Departures', [
            'service' => $service->only(['id', 'title']),
            'departures' => Departure::where('tourism_service_id', $service->id)->withCount('bookings')
                ->withSum(['bookings as occupied' => fn ($query) => $query->where(fn ($query) => $query->where('status', 'confirmed')->orWhere(fn ($query) => $query->where('status', 'pending')->where('expires_at', '>', now())))], 'travelers')
                ->orderByDesc('service_date')->paginate(20),
            'canOpen' => $request->user()?->can('services.manage'),
        ]);
    }

    public function store(Request $request, TourismService $service): RedirectResponse
    {
        Gate::authorize('update', $service);
        $data = $this->validated($request, $service);
        DB::transaction(function () use ($service, $data): void {
            $departure = Departure::create([...$data, 'tourism_service_id' => $service->id]);
            AuditLog::record('departure.created', $departure, ['is_open' => $departure->is_open]);
        });

        return back()->with('success', 'Salida creada. Solo las salidas abiertas de servicios publicados se pueden reservar.');
    }

    public function update(Request $request, TourismService $service, Departure $departure, BookingService $bookings): RedirectResponse
    {
        Gate::authorize('update', $service);
        abort_unless($departure->tourism_service_id === $service->id, 404);
        $data = $this->validated($request, $service, $departure);
        $bookings->expireDeparture($departure->id);
        DB::transaction(function () use ($data, $departure): void {
            $locked = Departure::lockForUpdate()->findOrFail($departure->id);
            if ($locked->bookings()->exists() && $locked->service_date->toDateString() !== $data['service_date']) {
                throw ValidationException::withMessages(['service_date' => 'Una salida con historial no puede cambiar de fecha. Crea otra salida.']);
            }
            if ($data['capacity'] < $locked->reserved) {
                throw ValidationException::withMessages(['capacity' => 'La capacidad no puede ser menor que los cupos retenidos o confirmados.']);
            }
            $locked->update($data);
            AuditLog::record('departure.updated', $locked, ['is_open' => $locked->is_open, 'capacity' => $locked->capacity]);
        }, 5);

        return back()->with('success', 'Salida actualizada. Las reservas existentes conservan su tarifa y condiciones.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, TourismService $service, ?Departure $departure = null): array
    {
        abort_unless(in_array($service->category, ['actividades', 'paquetes'], true), 422);
        $admin = $request->user()?->can('services.manage');
        $data = $request->validate([
            'service_date' => ['required', 'date_format:Y-m-d', 'after:today', Rule::unique('departures')->where('tourism_service_id', $service->id)->ignore($departure)],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'unit_price' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'terms' => ['required', 'string', 'min:20', 'max:10000'],
            'is_open' => ['required', 'boolean'],
        ]);
        if ($request->boolean('is_open') && (! $admin || ! TourismService::published()->whereKey($service->id)->exists())) {
            throw ValidationException::withMessages(['is_open' => 'Administración debe aprobar la salida, publicar el servicio y activar su proveedor.']);
        }

        return $data;
    }
}
