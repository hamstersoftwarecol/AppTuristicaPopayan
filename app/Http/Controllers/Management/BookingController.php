<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $admin = $request->user()?->can('bookings.manage');
        abort_unless($admin || $request->user()?->can('inventory.manage-own'), 403);
        $filters = $request->validate(['status' => ['nullable', 'in:pending,confirmed,cancelled,expired']]);

        return Inertia::render('Bookings', [
            'bookings' => Booking::with('customer:id,name,email')
                ->when(! $admin, fn ($query) => $query->whereHas('provider', fn ($query) => $query->where('owner_id', $request->user()->id)))
                ->when($filters['status'] ?? null, function ($query, $status): void {
                    if ($status === 'expired') {
                        $query->where(fn ($query) => $query->where('status', 'expired')->orWhere(fn ($query) => $query->where('status', 'pending')->where('expires_at', '<=', now())));
                    } elseif ($status === 'pending') {
                        $query->where('status', 'pending')->where('expires_at', '>', now());
                    } else {
                        $query->where('status', $status);
                    }
                })
                ->latest()->paginate(15)->withQueryString(),
            'managed' => true,
            'wompiReady' => false,
            'filters' => $filters,
        ]);
    }

    public function confirm(Booking $booking, BookingService $service): RedirectResponse
    {
        Gate::authorize('manage', $booking);
        $service->confirm($booking);

        return back()->with('success', 'Reserva confirmada para el inventario local. El cobro sigue pendiente.');
    }
}
