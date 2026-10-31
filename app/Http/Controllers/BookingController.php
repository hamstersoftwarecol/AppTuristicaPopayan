<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Departure;
use App\Services\BookingService;
use App\Services\Payments\WompiSandbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request, WompiSandbox $wompi): Response
    {
        return Inertia::render('Bookings', [
            'wompiReady' => $wompi->ready(),
            'bookings' => Booking::with('payments:id,booking_id,status,environment')->where('user_id', $request->user()->id)->latest()->paginate(15),
            'managed' => false,
            'filters' => [],
        ]);
    }

    public function store(Request $request, Departure $departure, BookingService $service): RedirectResponse
    {
        $request->validate([
            'travelers' => ['required', 'integer', 'min:1', 'max:100'],
            'idempotency_key' => ['required', 'uuid'],
            'accept_terms' => ['required', 'accepted'],
            'quote_token' => ['required', 'string', 'size:64'],
        ]);
        $service->reserve($request->user(), $departure->id, $request->integer('travelers'), $request->string('idempotency_key')->toString(), $request->string('quote_token')->toString());

        return to_route('bookings.index')->with('success', 'Solicitud registrada. El proveedor debe confirmarla antes de su vencimiento. No se realizó ningún cobro.');
    }

    public function cancel(Request $request, Booking $booking, BookingService $service): RedirectResponse
    {
        Gate::authorize('cancel', $booking);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $service->cancel($booking, $data['reason']);

        return back()->with('success', 'La reserva fue cerrada y sus cupos quedaron disponibles. No se procesaron cobros ni reembolsos.');
    }
}
