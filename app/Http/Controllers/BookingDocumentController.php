<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Booking;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class BookingDocumentController extends Controller
{
    public function __invoke(Booking $booking): Response
    {
        Gate::authorize('view', $booking);
        $booking->load('customer:id,name,email');
        $status = $booking->status === 'pending' && $booking->expires_at->lte(now()) ? 'expired' : $booking->status;
        $labels = ['pending' => 'Solicitud pendiente de confirmación', 'confirmed' => 'Reserva local confirmada', 'cancelled' => 'Reserva cancelada', 'expired' => 'Solicitud vencida'];
        $nonce = Str::random(32);
        AuditLog::record('booking.document_viewed', $booking);

        return response()->view('bookings.document', ['booking' => $booking, 'status' => $labels[$status] ?? $status, 'nonce' => $nonce, 'generatedAt' => now()])
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Content-Security-Policy', "default-src 'none'; script-src 'nonce-{$nonce}'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
    }
}
