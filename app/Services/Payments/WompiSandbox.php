<?php

namespace App\Services\Payments;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\PaymentAttempt;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WompiSandbox
{
    /** @return list<string> */
    public function missingConfiguration(): array
    {
        $missing = [];
        foreach (['public_key' => 'pub_test_', 'private_key' => 'prv_test_', 'integrity_secret' => 'test_integrity_', 'events_secret' => 'test_events_'] as $key => $prefix) {
            $value = config('integrations.wompi.'.$key);
            if (! is_string($value) || ! str_starts_with($value, $prefix) || strlen($value) <= strlen($prefix)) {
                $missing[] = 'WOMPI_'.strtoupper($key);
            }
        }
        if (config('integrations.wompi.environment') !== 'sandbox') {
            $missing[] = 'WOMPI_ENVIRONMENT=sandbox';
        }

        return $missing;
    }

    public function ready(): bool
    {
        return (bool) config('integrations.wompi.enabled') && $this->missingConfiguration() === [];
    }

    public function begin(Booking $booking): PaymentAttempt
    {
        if (! $this->ready()) {
            throw ValidationException::withMessages(['payment' => 'Wompi sandbox aún no está configurado y habilitado.']);
        }

        return DB::transaction(function () use ($booking): PaymentAttempt {
            $locked = Booking::lockForUpdate()->findOrFail($booking->id);
            if ($locked->status !== 'confirmed' || $locked->service_date->lte(today())) {
                throw ValidationException::withMessages(['payment' => 'Solo se pueden probar pagos para reservas locales confirmadas con fecha futura.']);
            }
            if ($locked->payments()->where('status', 'APPROVED')->exists()) {
                throw ValidationException::withMessages(['payment' => 'Esta reserva ya tiene un pago de prueba aprobado.']);
            }
            $active = $locked->payments()->whereIn('status', ['CREATED', 'PENDING'])->latest('id')->first();
            if ($active) {
                return $active;
            }
            $payment = PaymentAttempt::create([
                'booking_id' => $locked->id,
                'reference' => Str::uuid()->toString(),
                'environment' => 'sandbox',
                'amount_in_cents' => $locked->total * 100,
                'currency' => 'COP',
                'status' => 'CREATED',
            ]);
            AuditLog::record('payment.sandbox_created', $payment);

            return $payment;
        }, 5);
    }

    /** @return array<string, string|int> */
    public function checkoutFields(PaymentAttempt $payment): array
    {
        abort_unless($this->ready() && $payment->environment === 'sandbox', 503);
        abort_unless($payment->status === 'CREATED' && $payment->booking->status === 'confirmed' && $payment->booking->service_date->gt(today()), 409);

        return [
            'public-key' => config('integrations.wompi.public_key'),
            'currency' => $payment->currency,
            'amount-in-cents' => $payment->amount_in_cents,
            'reference' => $payment->reference,
            'signature:integrity' => hash('sha256', $payment->reference.$payment->amount_in_cents.$payment->currency.config('integrations.wompi.integrity_secret')),
            'redirect-url' => route('payments.show', $payment),
        ];
    }

    /** @param array<string, mixed> $event */
    public function handleEvent(array $event): void
    {
        abort_unless($this->missingConfiguration() === [], 503);
        abort_unless(($event['environment'] ?? null) === 'test' && ($event['event'] ?? null) === 'transaction.updated', 400);
        $properties = data_get($event, 'signature.properties');
        $checksum = data_get($event, 'signature.checksum');
        $timestamp = $event['timestamp'] ?? null;
        abort_unless(is_array($properties) && count($properties) <= 30 && in_array('transaction.id', $properties, true) && is_int($timestamp) && is_string($checksum) && preg_match('/^[a-fA-F0-9]{64}$/', $checksum), 400);
        $concatenated = '';
        foreach ($properties as $property) {
            abort_unless(is_string($property) && strlen($property) <= 150, 400);
            $value = data_get($event, 'data.'.$property);
            abort_unless(is_string($value) || is_int($value), 400);
            $concatenated .= $value;
        }
        $expected = hash('sha256', $concatenated.$timestamp.config('integrations.wompi.events_secret'));
        abort_unless(hash_equals($expected, strtolower($checksum)), 401);
        $checksum = strtolower($checksum);
        if (DB::table('payment_events')->where('checksum', $checksum)->exists()) {
            return;
        }
        $id = data_get($event, 'data.transaction.id');
        abort_unless(is_string($id) && preg_match('/^[a-zA-Z0-9_-]{1,150}$/', $id), 400);

        // Resolve unsigned fields (reference/currency) against the merchant's authenticated API.
        try {
            $response = Http::acceptJson()->withToken(config('integrations.wompi.private_key'))
                ->connectTimeout(5)->timeout(10)->withoutRedirecting()
                ->get('https://sandbox.wompi.co/v1/transactions/'.rawurlencode($id));
        } catch (ConnectionException) {
            abort(503, 'No fue posible verificar la transacción con Wompi.');
        }
        abort_unless($response->successful(), 503, 'No fue posible verificar la transacción con Wompi.');
        $transaction = $response->json('data');
        abort_unless(is_array($transaction) && ($transaction['id'] ?? null) === $id, 502);
        $reference = $transaction['reference'] ?? null;
        $status = $transaction['status'] ?? null;
        abort_unless(is_string($reference) && in_array($status, ['PENDING', 'APPROVED', 'DECLINED', 'ERROR', 'VOIDED'], true), 502);

        DB::transaction(function () use ($transaction, $reference, $status, $id, $checksum): void {
            $payment = PaymentAttempt::where('reference', $reference)->lockForUpdate()->first();
            abort_unless($payment !== null && $payment->environment === 'sandbox', 404);
            abort_unless(($transaction['currency'] ?? null) === $payment->currency && ($transaction['amount_in_cents'] ?? null) === $payment->amount_in_cents, 409);
            abort_unless($payment->gateway_transaction_id === null || $payment->gateway_transaction_id === $id, 409);
            if (DB::table('payment_events')->where('checksum', $checksum)->exists()) {
                return;
            }
            $regression = $payment->status === 'VOIDED'
                || ($payment->status === 'APPROVED' && ! in_array($status, ['APPROVED', 'VOIDED'], true))
                || (in_array($payment->status, ['DECLINED', 'ERROR'], true) && $status === 'PENDING');
            if (! $regression && $payment->status !== $status) {
                $payment->update(['status' => $status, 'gateway_transaction_id' => $id]);
                AuditLog::record('payment.sandbox_updated', $payment, ['status' => $status], system: true);
            }
            DB::table('payment_events')->insert(['payment_attempt_id' => $payment->id, 'checksum' => $checksum, 'created_at' => now()]);
        }, 5);
    }
}
