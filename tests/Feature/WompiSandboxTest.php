<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Booking;
use App\Models\Departure;
use App\Models\PaymentAttempt;
use App\Models\Provider;
use App\Models\TourismService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WompiSandboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config([
            'integrations.wompi.enabled' => true,
            'integrations.wompi.environment' => 'sandbox',
            'integrations.wompi.public_key' => 'pub_test_fixture',
            'integrations.wompi.private_key' => 'prv_test_fixture',
            'integrations.wompi.integrity_secret' => 'test_integrity_fixture',
            'integrations.wompi.events_secret' => 'test_events_fixture',
        ]);
    }

    private function account(Role $role = Role::Customer): User
    {
        $user = User::factory()->create(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()]);
        $user->assignRole($role->value);

        return $user;
    }

    private function booking(User $user, string $status = 'confirmed'): Booking
    {
        $provider = Provider::create(['name' => 'Operador de prueba', 'contact_email' => 'prueba@example.test', 'status' => 'active']);
        $service = TourismService::create(['provider_id' => $provider->id, 'title' => 'Actividad propia', 'category' => 'actividades', 'destination' => 'Popayán', 'description' => 'Descripción de la actividad propia de prueba.', 'status' => 'published']);
        $departure = Departure::create(['tourism_service_id' => $service->id, 'service_date' => today()->addDays(5), 'capacity' => 4, 'unit_price' => 50000, 'terms' => 'Condiciones de la actividad propia de prueba.', 'is_open' => true]);

        $departure->forceFill(['reserved' => 2])->save();

        return Booking::create(['reference' => Str::uuid()->toString(), 'user_id' => $user->id, 'departure_id' => $departure->id, 'provider_id' => $provider->id, 'idempotency_key' => Str::uuid()->toString(), 'service_title' => $service->title, 'provider_name' => $provider->name, 'service_date' => $departure->service_date, 'travelers' => 2, 'unit_price' => 50000, 'total' => 100000, 'currency' => 'COP', 'terms' => $departure->terms, 'status' => $status, 'expires_at' => now()->addDay()]);
    }

    private function payment(): PaymentAttempt
    {
        $user = $this->account();
        $booking = $this->booking($user);
        $this->actingAs($user)->post('/reservas/'.$booking->id.'/pago-prueba')->assertSessionHasNoErrors();

        return PaymentAttempt::where('booking_id', $booking->id)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function transaction(PaymentAttempt $payment, string $status = 'APPROVED'): array
    {
        return ['id' => 'test-transaction-123', 'reference' => $payment->reference, 'amount_in_cents' => $payment->amount_in_cents, 'currency' => 'COP', 'status' => $status];
    }

    /** @param array<string, mixed> $transaction
     * @return array<string, mixed>
     */
    private function event(array $transaction): array
    {
        $timestamp = now()->timestamp;
        $properties = ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'];
        $checksum = hash('sha256', $transaction['id'].$transaction['status'].$transaction['amount_in_cents'].$timestamp.'test_events_fixture');

        return ['event' => 'transaction.updated', 'environment' => 'test', 'data' => ['transaction' => $transaction], 'timestamp' => $timestamp, 'signature' => ['properties' => $properties, 'checksum' => $checksum]];
    }

    public function test_checkout_uses_server_amount_and_only_exposes_public_key_and_signature(): void
    {
        $user = $this->account();
        $booking = $this->booking($user);
        $this->actingAs($user)->post('/reservas/'.$booking->id.'/pago-prueba', ['amount_in_cents' => 1, 'status' => 'APPROVED'])->assertSessionHasNoErrors();
        $payment = PaymentAttempt::firstOrFail();
        $expected = hash('sha256', $payment->reference.'10000000COPtest_integrity_fixture');
        $this->get('/pagos/'.$payment->id)->assertInertia(fn (Assert $page) => $page
            ->where('payment.environment', 'sandbox')->where('payment.amount_in_cents', 10000000)
            ->where('checkoutFields.amount-in-cents', 10000000)->where('checkoutFields.public-key', 'pub_test_fixture')
            ->where('checkoutFields.signature:integrity', $expected)->missing('checkoutFields.private_key')->missing('checkoutFields.integrity_secret'));
        $this->assertDatabaseHas('payment_attempts', ['id' => $payment->id, 'status' => 'CREATED']);
        Http::assertNothingSent();
    }

    public function test_repeated_checkout_requests_reuse_the_active_attempt(): void
    {
        $payment = $this->payment();
        $this->post('/reservas/'.$payment->booking_id.'/pago-prueba')->assertRedirect('/pagos/'.$payment->id);
        $this->assertDatabaseCount('payment_attempts', 1);
    }

    public function test_disabled_missing_or_production_credentials_cannot_start_checkout(): void
    {
        $user = $this->account();
        $booking = $this->booking($user);
        $this->actingAs($user);
        config(['integrations.wompi.enabled' => false]);
        $this->post('/reservas/'.$booking->id.'/pago-prueba')->assertSessionHasErrors('payment');
        config(['integrations.wompi.enabled' => true, 'integrations.wompi.private_key' => '']);
        $this->post('/reservas/'.$booking->id.'/pago-prueba')->assertSessionHasErrors('payment');
        config(['integrations.wompi.private_key' => 'prv_prod_fixture', 'integrations.wompi.environment' => 'production']);
        $this->post('/reservas/'.$booking->id.'/pago-prueba')->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payment_attempts', 0);
        Http::assertNothingSent();
    }

    public function test_checkout_and_payment_pages_are_private_and_require_a_confirmed_future_booking(): void
    {
        $payment = $this->payment();
        $this->actingAs($this->account())->post('/reservas/'.$payment->booking_id.'/pago-prueba')->assertForbidden();
        $this->get('/pagos/'.$payment->id)->assertForbidden();
        $user = $this->account();
        $pending = $this->booking($user, 'pending');
        $this->actingAs($user)->post('/reservas/'.$pending->id.'/pago-prueba')->assertSessionHasErrors('payment');
        $past = $this->booking($user);
        $past->update(['service_date' => today()]);
        $this->post('/reservas/'.$past->id.'/pago-prueba')->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payment_attempts', 1);
    }

    public function test_return_url_parameters_never_approve_payment(): void
    {
        $payment = $this->payment();
        $this->get('/pagos/'.$payment->id.'?id=forged&status=APPROVED')->assertOk();
        $this->assertDatabaseHas('payment_attempts', ['id' => $payment->id, 'status' => 'CREATED', 'gateway_transaction_id' => null]);
        Http::assertNothingSent();
    }

    public function test_verified_webhook_fetches_private_api_and_is_idempotent(): void
    {
        $payment = $this->payment();
        $transaction = $this->transaction($payment);
        Http::fake(['sandbox.wompi.co/v1/transactions/*' => Http::response(['data' => $transaction])]);
        $event = $this->event($transaction);
        $this->postJson('/webhooks/wompi/sandbox', $event)->assertOk();
        $this->postJson('/webhooks/wompi/sandbox', $event)->assertOk();
        $this->assertDatabaseHas('payment_attempts', ['id' => $payment->id, 'status' => 'APPROVED', 'gateway_transaction_id' => 'test-transaction-123']);
        $this->assertDatabaseHas('bookings', ['id' => $payment->booking_id, 'status' => 'confirmed']);
        $this->assertDatabaseCount('payment_events', 1);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer prv_test_fixture') && $request->url() === 'https://sandbox.wompi.co/v1/transactions/test-transaction-123');
        $this->post('/reservas/'.$payment->booking_id.'/pago-prueba')->assertSessionHasErrors('payment');
    }

    public function test_forged_signatures_and_production_events_are_rejected_without_api_calls(): void
    {
        $payment = $this->payment();
        $event = $this->event($this->transaction($payment));
        $event['signature']['checksum'] = str_repeat('0', 64);
        $this->postJson('/webhooks/wompi/sandbox', $event)->assertUnauthorized();
        $event = $this->event($this->transaction($payment));
        $event['environment'] = 'prod';
        $this->postJson('/webhooks/wompi/sandbox', $event)->assertStatus(400);
        $event['environment'] = 'test';
        $event['signature']['properties'] = [];
        $this->postJson('/webhooks/wompi/sandbox', $event)->assertStatus(400);
        Http::assertNothingSent();
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_unsigned_reference_and_currency_fields_cannot_redirect_a_signed_event(): void
    {
        $first = $this->payment();
        $second = $this->payment();
        $original = $this->transaction($first);
        $event = $this->event($original);
        $event['data']['transaction']['reference'] = $second->reference;
        $event['data']['transaction']['currency'] = 'USD';
        Http::fake(['sandbox.wompi.co/v1/transactions/*' => Http::response(['data' => $original])]);
        $this->postJson('/webhooks/wompi/sandbox', $event)->assertOk();
        $this->assertDatabaseHas('payment_attempts', ['id' => $first->id, 'status' => 'APPROVED']);
        $this->assertDatabaseHas('payment_attempts', ['id' => $second->id, 'status' => 'CREATED']);
    }

    public function test_wrong_amounts_or_currency_from_gateway_do_not_credit_the_attempt(): void
    {
        $payment = $this->payment();
        foreach (['amount', 'currency'] as $case) {
            $transaction = $this->transaction($payment);
            if ($case === 'amount') {
                $transaction['amount_in_cents'] = 1;
            }
            if ($case === 'currency') {
                $transaction['currency'] = 'USD';
            }
            Http::fake(['sandbox.wompi.co/v1/transactions/*' => Http::response(['data' => $transaction])]);
            $this->postJson('/webhooks/wompi/sandbox', $this->event($transaction))->assertStatus(409);
        }
        $this->assertDatabaseHas('payment_attempts', ['id' => $payment->id, 'status' => 'CREATED']);
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_gateway_failure_is_retryable_and_does_not_change_payment_state(): void
    {
        $payment = $this->payment();
        Http::fake(['sandbox.wompi.co/v1/transactions/*' => Http::response([], 503)]);
        $this->postJson('/webhooks/wompi/sandbox', $this->event($this->transaction($payment)))->assertStatus(503);
        $this->assertDatabaseHas('payment_attempts', ['id' => $payment->id, 'status' => 'CREATED']);
        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_delayed_pending_events_do_not_regress_an_approved_payment(): void
    {
        $payment = $this->payment();
        Http::fake(['sandbox.wompi.co/v1/transactions/*' => Http::sequence()->push(['data' => $this->transaction($payment)])->push(['data' => $this->transaction($payment, 'PENDING')])]);
        $this->postJson('/webhooks/wompi/sandbox', $this->event($this->transaction($payment)))->assertOk();
        $this->postJson('/webhooks/wompi/sandbox', $this->event($this->transaction($payment, 'PENDING')))->assertOk();
        $this->assertDatabaseHas('payment_attempts', ['id' => $payment->id, 'status' => 'APPROVED']);
    }

    public function test_declined_attempt_can_be_retried_with_a_new_reference(): void
    {
        $payment = $this->payment();
        $transaction = $this->transaction($payment, 'DECLINED');
        Http::fake(['sandbox.wompi.co/v1/transactions/*' => Http::response(['data' => $transaction])]);
        $this->postJson('/webhooks/wompi/sandbox', $this->event($transaction))->assertOk();
        $this->post('/reservas/'.$payment->booking_id.'/pago-prueba')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payment_attempts', 2);
        $this->assertNotSame($payment->reference, PaymentAttempt::latest('id')->firstOrFail()->reference);
    }

    public function test_integrations_and_finance_views_are_authorized_and_never_expose_secrets(): void
    {
        $this->actingAs($this->account())->get('/gestion/integraciones')->assertForbidden();
        $this->get('/gestion/pagos')->assertForbidden();
        $this->actingAs($this->account(Role::Finance))->get('/gestion/pagos')->assertOk();
        $this->get('/gestion/integraciones')->assertForbidden();
        $this->actingAs($this->account(Role::SuperAdmin))->get('/gestion/integraciones')->assertInertia(fn (Assert $page) => $page
            ->where('wompi.ready', true)->where('travelProviders.0.name', 'Netactica Colombia')->where('travelProviders.1.name', 'Avianca Link')
            ->missing('wompi.private_key')->missing('wompi.integrity_secret')->missing('wompi.events_secret'));
        Http::assertNothingSent();
    }

    public function test_event_properties_can_change_order_and_include_additional_signed_fields(): void
    {
        $payment = $this->payment();
        $transaction = $this->transaction($payment);
        $event = $this->event($transaction);
        $event['signature']['properties'] = ['transaction.currency', 'transaction.amount_in_cents', 'transaction.id', 'transaction.status'];
        $event['signature']['checksum'] = hash('sha256', 'COP'.$transaction['amount_in_cents'].$transaction['id'].$transaction['status'].$event['timestamp'].'test_events_fixture');
        Http::fake(['sandbox.wompi.co/v1/transactions/*' => Http::response(['data' => $transaction])]);
        $this->postJson('/webhooks/wompi/sandbox', $event)->assertOk();
        $this->assertDatabaseHas('payment_attempts', ['id' => $payment->id, 'status' => 'APPROVED']);
    }

    public function test_late_sandbox_approval_does_not_restore_a_cancelled_booking(): void
    {
        $payment = $this->payment();
        $this->patch('/reservas/'.$payment->booking_id.'/cancelar', ['reason' => 'Cancelación de prueba'])->assertSessionHasNoErrors();
        $this->get('/pagos/'.$payment->id)->assertInertia(fn (Assert $page) => $page->where('checkoutFields', null));
        $transaction = $this->transaction($payment);
        Http::fake(['sandbox.wompi.co/v1/transactions/*' => Http::response(['data' => $transaction])]);
        $this->postJson('/webhooks/wompi/sandbox', $this->event($transaction))->assertOk();
        $this->assertDatabaseHas('bookings', ['id' => $payment->booking_id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('departures', ['id' => $payment->booking->departure_id, 'reserved' => 0]);
        $this->assertDatabaseHas('payment_attempts', ['id' => $payment->id, 'status' => 'APPROVED']);
    }

    public function test_disabling_new_checkouts_still_accepts_verified_inflight_events(): void
    {
        $payment = $this->payment();
        config(['integrations.wompi.enabled' => false]);
        $transaction = $this->transaction($payment);
        Http::fake(['sandbox.wompi.co/v1/transactions/*' => Http::response(['data' => $transaction])]);
        $this->postJson('/webhooks/wompi/sandbox', $this->event($transaction))->assertOk();
        $this->assertDatabaseHas('payment_attempts', ['id' => $payment->id, 'status' => 'APPROVED']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.sandbox_updated', 'actor_id' => null]);
    }
}
