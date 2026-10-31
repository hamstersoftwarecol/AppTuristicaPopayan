<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PaymentAttempt;
use App\Services\Payments\WompiSandbox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function store(Request $request, Booking $booking, WompiSandbox $wompi): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        $payment = $wompi->begin($booking);

        return to_route('payments.show', $payment);
    }

    public function show(Request $request, PaymentAttempt $payment, WompiSandbox $wompi): Response
    {
        abort_unless($payment->booking->user_id === $request->user()->id, 403);
        $canCheckout = $wompi->ready() && $payment->environment === 'sandbox' && $payment->status === 'CREATED'
            && $payment->booking->status === 'confirmed' && $payment->booking->service_date->gt(today());

        // Return query parameters never approve a payment. Only verified gateway notifications do.
        return Inertia::render('payments/Show', [
            'payment' => $payment->only(['id', 'reference', 'environment', 'amount_in_cents', 'currency', 'status', 'gateway_transaction_id']),
            'booking' => $payment->booking->only(['id', 'reference', 'service_title', 'status']),
            'checkoutFields' => $canCheckout ? $wompi->checkoutFields($payment) : null,
        ]);
    }
}
