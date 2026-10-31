<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Booking;
use App\Models\Departure;
use App\Models\Provider;
use App\Models\TourismService;
use App\Models\TravelInquiry;
use App\Models\User;
use App\Notifications\AccountNotice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationsAndDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private function account(Role $role = Role::Customer): User
    {
        $user = User::factory()->create(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()]);
        $user->assignRole($role->value);

        return $user;
    }

    private function departure(?User $owner = null): Departure
    {
        $provider = Provider::create(['name' => 'Operador local', 'contact_email' => 'contacto@example.test', 'owner_id' => $owner?->id, 'status' => 'active']);
        $service = TourismService::create(['provider_id' => $provider->id, 'title' => 'Recorrido de prueba', 'category' => 'actividades', 'destination' => 'Popayán', 'description' => 'Un recorrido local con guía y condiciones de prueba.', 'status' => 'published']);

        return Departure::create(['tourism_service_id' => $service->id, 'service_date' => today()->addDays(5), 'capacity' => 5, 'unit_price' => 50000, 'terms' => 'Condiciones del recorrido local de prueba.', 'is_open' => true]);
    }

    /** @return array<string, mixed> */
    private function payload(Departure $departure): array
    {
        return ['travelers' => 2, 'idempotency_key' => Str::uuid()->toString(), 'accept_terms' => true, 'quote_token' => $departure->quoteToken()];
    }

    public function test_booking_transitions_create_one_notice_each_and_retries_do_not_duplicate_them(): void
    {
        config(['communications.operational_mail_enabled' => false]);
        $customer = $this->account();
        $departure = $this->departure();
        $payload = $this->payload($departure);
        $this->actingAs($customer)->post('/salidas/'.$departure->id.'/reservar', $payload)->assertSessionHasNoErrors();
        $this->post('/salidas/'.$departure->id.'/reservar', $payload)->assertSessionHasNoErrors();
        $booking = Booking::firstOrFail();
        $this->assertDatabaseCount('notifications', 1);
        $this->actingAs($this->account(Role::Admin))->patch('/gestion/reservas/'.$booking->id.'/confirmar')->assertSessionHasNoErrors();
        $this->patch('/gestion/reservas/'.$booking->id.'/confirmar')->assertSessionHasNoErrors();
        $this->actingAs($customer)->patch('/reservas/'.$booking->id.'/cancelar', ['reason' => 'Cambio de fecha'])->assertSessionHasNoErrors();
        $this->patch('/reservas/'.$booking->id.'/cancelar', ['reason' => 'Cambio de fecha'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('notifications', 3);
        $kinds = $customer->notifications()->get()->pluck('data.kind')->sort()->values()->all();
        $this->assertSame(['booking.cancelled', 'booking.confirmed', 'booking.pending'], $kinds);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_expiration_and_inquiry_response_notify_the_correct_customer(): void
    {
        $customer = $this->account();
        $departure = $this->departure();
        $this->actingAs($customer)->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure));
        $this->travel(25)->hours();
        $this->artisan('tourism:expire-bookings')->assertSuccessful();
        $this->artisan('tourism:expire-bookings')->assertSuccessful();
        $inquiry = TravelInquiry::create(['user_id' => $customer->id, 'travel_date' => today()->addDays(5), 'travelers' => 1, 'message' => 'Consulta de horarios disponibles.']);
        $this->actingAs($this->account(Role::Agent))->patch('/gestion/solicitudes/'.$inquiry->id, ['status' => 'responded', 'response' => 'Puedes consultar los horarios desde tu cuenta.'])->assertSessionHasNoErrors();
        $kinds = $customer->notifications()->get()->pluck('data.kind')->sort()->values()->all();
        $this->assertSame(['booking.expired', 'booking.pending', 'inquiry.responded'], $kinds);
    }

    public function test_inbox_and_mark_as_read_are_private_to_the_recipient(): void
    {
        $first = $this->account();
        $second = $this->account();
        $first->notifyNow(new AccountNotice('Aviso propio', 'Mensaje privado del primer cliente.', '/mis-reservas', 'test'));
        $second->notifyNow(new AccountNotice('Aviso ajeno', 'Mensaje privado del segundo cliente.', '/mis-solicitudes', 'test'));
        $own = $first->notifications()->firstOrFail();
        $other = $second->notifications()->firstOrFail();
        $this->actingAs($first)->get('/notificaciones')->assertInertia(fn (Assert $page) => $page
            ->has('notifications.data', 1)->where('notifications.data.0.id', $own->id)->where('auth.unread_notifications', 1));
        $this->patch('/notificaciones/'.$other->id.'/leer')->assertNotFound();
        $this->patch('/notificaciones/'.$own->id.'/leer')->assertRedirect();
        $this->get('/notificaciones?unread=1')->assertInertia(fn (Assert $page) => $page->has('notifications.data', 0)->where('auth.unread_notifications', 0));
        $this->assertNull($other->fresh()->read_at);
    }

    public function test_mark_all_read_only_changes_the_current_accounts_notifications(): void
    {
        $first = $this->account();
        $second = $this->account();
        foreach ([$first, $second] as $user) {
            $user->notifyNow(new AccountNotice('Aviso', 'Un cambio en tu cuenta.', '/mis-reservas', 'test'));
        }
        $this->actingAs($first)->patch('/notificaciones/leer-todas')->assertRedirect();
        $this->assertSame(0, $first->unreadNotifications()->count());
        $this->assertSame(1, $second->unreadNotifications()->count());
    }

    public function test_printable_document_is_private_and_contains_the_stored_reservation_amount(): void
    {
        $owner = $this->account(Role::Supplier);
        $customer = $this->account();
        $departure = $this->departure($owner);
        $this->actingAs($customer)->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure));
        $booking = Booking::firstOrFail();
        $departure->update(['unit_price' => 90000]);
        $url = '/reservas/'.$booking->id.'/comprobante';
        $response = $this->get($url)->assertOk()->assertSee('Comprobante de reserva local')->assertSee('Solicitud pendiente de confirmación')->assertSee('100.000')->assertSee($customer->email)->assertSee('sandbox');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
        $this->actingAs($this->account())->get($url)->assertForbidden();
        $this->actingAs($this->account(Role::Supplier))->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertOk();
        $this->actingAs($this->account(Role::Admin))->get($url)->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'booking.document_viewed', 'subject_id' => $booking->id]);
    }

    public function test_document_escapes_content_and_uses_effective_expired_status(): void
    {
        $customer = $this->account();
        $departure = $this->departure();
        $this->actingAs($customer)->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure));
        $booking = Booking::firstOrFail();
        $booking->update(['service_title' => '<img src=x onerror=alert(1)>', 'terms' => '<script>alert(1)</script>']);
        $this->travel(25)->hours();
        $this->get('/reservas/'.$booking->id.'/comprobante')->assertOk()->assertSee('Solicitud vencida')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_profile_deletion_removes_the_accounts_notices(): void
    {
        $user = $this->account();
        $user->notifyNow(new AccountNotice('Aviso', 'Un mensaje de la cuenta.', '/mis-solicitudes', 'test'));
        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
