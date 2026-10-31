<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Booking;
use App\Models\Departure;
use App\Models\Provider;
use App\Models\TourismService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocalBookingTest extends TestCase
{
    use RefreshDatabase;

    private function account(Role $role = Role::Customer): User
    {
        $user = User::factory()->create(['two_factor_secret' => encrypt('secret'), 'two_factor_confirmed_at' => now()]);
        $user->assignRole($role->value);

        return $user;
    }

    private function departure(?User $supplier = null, int $capacity = 3): Departure
    {
        $provider = Provider::create(['name' => 'Operador propio', 'contact_email' => 'operador@example.test', 'owner_id' => $supplier?->id, 'status' => 'active']);
        $service = TourismService::create(['provider_id' => $provider->id, 'title' => 'Recorrido local', 'category' => 'actividades', 'destination' => 'Popayán', 'description' => 'Experiencia local con guía y recorrido a pie.', 'status' => 'published']);

        return Departure::create(['tourism_service_id' => $service->id, 'service_date' => today()->addDays(5)->toDateString(), 'capacity' => $capacity, 'unit_price' => 70000, 'terms' => 'Incluye guía y recorrido. Presentarse en el punto de encuentro.', 'is_open' => true]);
    }

    /** @return array<string, mixed> */
    private function payload(Departure $departure, int $travelers = 2, ?string $key = null): array
    {
        return ['travelers' => $travelers, 'idempotency_key' => $key ?? Str::uuid()->toString(), 'accept_terms' => true, 'quote_token' => $departure->quoteToken()];
    }

    public function test_customer_can_request_a_hold_and_server_ignores_supplied_price_and_status(): void
    {
        $departure = $this->departure();
        $user = $this->account();
        $this->actingAs($user)->post('/salidas/'.$departure->id.'/reservar', [...$this->payload($departure), 'total' => 1, 'status' => 'confirmed', 'user_id' => 999])->assertRedirect('/mis-reservas');
        $booking = Booking::firstOrFail();
        $this->assertSame('pending', $booking->status);
        $this->assertSame($user->id, $booking->user_id);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'total' => 140000, 'unit_price' => 70000, 'currency' => 'COP']);
        $this->assertDatabaseHas('departures', ['id' => $departure->id, 'reserved' => 2]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'booking.requested', 'subject_id' => $booking->id]);
        $this->assertTrue($booking->expires_at->lte(now()->addHours(24)));
    }

    public function test_retries_do_not_duplicate_reservations_or_decrement_capacity_twice(): void
    {
        $departure = $this->departure();
        $payload = $this->payload($departure);
        $this->actingAs($this->account())->post('/salidas/'.$departure->id.'/reservar', $payload)->assertSessionHasNoErrors();
        $this->post('/salidas/'.$departure->id.'/reservar', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('departures', ['id' => $departure->id, 'reserved' => 2]);
        $this->post('/salidas/'.$departure->id.'/reservar', [...$payload, 'travelers' => 1])->assertSessionHasErrors('idempotency_key');
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_insufficient_capacity_cannot_be_oversold(): void
    {
        $departure = $this->departure();
        $this->actingAs($this->account())->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure))->assertSessionHasNoErrors();
        $this->actingAs($this->account())->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure))->assertSessionHasErrors('travelers');
        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseHas('departures', ['id' => $departure->id, 'reserved' => 2]);
    }

    public function test_changed_quotes_require_review_and_do_not_consume_capacity(): void
    {
        $departure = $this->departure();
        $payload = $this->payload($departure);
        $departure->update(['unit_price' => 90000, 'terms' => 'Nuevas condiciones que deben aceptarse antes de reservar.']);
        $this->actingAs($this->account())->post('/salidas/'.$departure->id.'/reservar', $payload)->assertSessionHasErrors('quote_token');
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseHas('departures', ['id' => $departure->id, 'reserved' => 0]);
    }

    public function test_acceptance_and_valid_traveler_count_are_required(): void
    {
        $departure = $this->departure();
        $this->actingAs($this->account())->post('/salidas/'.$departure->id.'/reservar', [...$this->payload($departure), 'travelers' => -1, 'accept_terms' => false])->assertSessionHasErrors(['travelers', 'accept_terms']);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_closed_unpublished_inactive_and_past_departures_cannot_be_reserved(): void
    {
        $user = $this->account();
        foreach (['closed', 'draft', 'inactive', 'past', 'flight'] as $case) {
            $departure = $this->departure();
            if ($case === 'closed') {
                $departure->update(['is_open' => false]);
            }
            if ($case === 'draft') {
                $departure->service->update(['status' => 'draft']);
            }
            if ($case === 'inactive') {
                $departure->service->provider->update(['status' => 'inactive']);
            }
            if ($case === 'past') {
                $departure->update(['service_date' => today()]);
            }
            if ($case === 'flight') {
                $departure->service->update(['category' => 'vuelos']);
            }
            $this->actingAs($user)->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure))->assertSessionHasErrors('departure_id');
        }
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_only_authorized_operator_can_confirm_and_snapshots_are_preserved(): void
    {
        $supplier = $this->account(Role::Supplier);
        $departure = $this->departure($supplier);
        $customer = $this->account();
        $this->actingAs($customer)->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure));
        $booking = Booking::firstOrFail();
        $this->patch('/gestion/reservas/'.$booking->id.'/confirmar')->assertForbidden();
        $this->actingAs($this->account(Role::Supplier))->patch('/gestion/reservas/'.$booking->id.'/confirmar')->assertForbidden();
        $departure->update(['unit_price' => 100000, 'terms' => 'Condiciones nuevas para las próximas reservas.']);
        $this->actingAs($supplier)->patch('/gestion/reservas/'.$booking->id.'/confirmar')->assertSessionHasNoErrors();
        $this->patch('/gestion/reservas/'.$booking->id.'/confirmar')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed', 'total' => 140000, 'unit_price' => 70000, 'terms' => 'Incluye guía y recorrido. Presentarse en el punto de encuentro.']);
        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertDatabaseHas('departures', ['id' => $departure->id, 'reserved' => 2]);
    }

    public function test_cancellation_releases_seats_once_and_prevents_later_confirmation(): void
    {
        $supplier = $this->account(Role::Supplier);
        $departure = $this->departure($supplier);
        $user = $this->account();
        $this->actingAs($user)->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure));
        $booking = Booking::firstOrFail();
        $this->actingAs($supplier)->patch('/gestion/reservas/'.$booking->id.'/confirmar')->assertSessionHasNoErrors();
        $this->actingAs($user);
        $this->patch('/reservas/'.$booking->id.'/cancelar', ['reason' => 'Cambio de planes'])->assertSessionHasNoErrors();
        $this->patch('/reservas/'.$booking->id.'/cancelar', ['reason' => 'Cambio de planes'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('departures', ['id' => $departure->id, 'reserved' => 0]);
        $this->actingAs($supplier)->patch('/gestion/reservas/'.$booking->id.'/confirmar')->assertSessionHasErrors('booking');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'cancelled']);
    }

    public function test_expired_holds_release_seats_and_are_not_confirmable(): void
    {
        $supplier = $this->account(Role::Supplier);
        $departure = $this->departure($supplier, 2);
        $this->actingAs($this->account())->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure));
        $booking = Booking::firstOrFail();
        $this->travel(25)->hours();
        $this->actingAs($supplier)->patch('/gestion/reservas/'.$booking->id.'/confirmar')->assertSessionHasErrors('booking');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'expired']);
        $this->assertDatabaseHas('departures', ['id' => $departure->id, 'reserved' => 0]);
        $this->artisan('tourism:expire-bookings')->assertSuccessful();
        $this->assertDatabaseHas('departures', ['id' => $departure->id, 'reserved' => 0]);
        $this->actingAs($this->account())->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('departures', ['id' => $departure->id, 'reserved' => 2]);
    }

    public function test_public_availability_excludes_expired_holds_without_exposing_customers(): void
    {
        $departure = $this->departure(null, 2);
        $this->actingAs($this->account())->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure));
        $this->get('/servicios/'.$departure->tourism_service_id)->assertInertia(fn (Assert $page) => $page->where('departures.0.available', 0)->missing('departures.0.bookings'));
        $this->travel(25)->hours();
        $this->get('/servicios/'.$departure->tourism_service_id)->assertInertia(fn (Assert $page) => $page->where('departures.0.available', 2));
    }

    public function test_booking_history_and_management_are_scoped_to_their_accounts(): void
    {
        $supplier = $this->account(Role::Supplier);
        $departure = $this->departure($supplier);
        $user = $this->account();
        $this->actingAs($user)->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure));
        $booking = Booking::firstOrFail();
        $this->get('/mis-reservas')->assertInertia(fn (Assert $page) => $page->has('bookings.data', 1));
        $this->actingAs($this->account())->get('/mis-reservas')->assertInertia(fn (Assert $page) => $page->has('bookings.data', 0));
        $this->patch('/reservas/'.$booking->id.'/cancelar', ['reason' => 'Intento ajeno'])->assertForbidden();
        $this->get('/gestion/reservas')->assertForbidden();
        $this->actingAs($supplier)->get('/gestion/reservas')->assertInertia(fn (Assert $page) => $page->has('bookings.data', 1));
        $this->actingAs($this->account(Role::Supplier))->get('/gestion/reservas')->assertInertia(fn (Assert $page) => $page->has('bookings.data', 0));
    }

    public function test_capacity_cannot_be_reduced_below_reserved_seats_and_date_cannot_change(): void
    {
        $departure = $this->departure();
        $this->actingAs($this->account())->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure));
        $data = ['service_date' => $departure->service_date->toDateString(), 'capacity' => 1, 'unit_price' => 70000, 'terms' => $departure->terms, 'is_open' => true];
        $this->actingAs($this->account(Role::Admin))->put('/gestion/servicios/'.$departure->tourism_service_id.'/salidas/'.$departure->id, $data)->assertSessionHasErrors('capacity');
        $this->put('/gestion/servicios/'.$departure->tourism_service_id.'/salidas/'.$departure->id, [...$data, 'capacity' => 5, 'service_date' => today()->addDays(7)->toDateString()])->assertSessionHasErrors('service_date');
    }

    public function test_supplier_can_create_closed_slots_but_cannot_open_or_modify_foreign_slots(): void
    {
        $supplier = $this->account(Role::Supplier);
        $departure = $this->departure($supplier);
        $data = ['service_date' => today()->addDays(8)->toDateString(), 'capacity' => 4, 'unit_price' => 85000, 'terms' => 'Condiciones aprobadas para la próxima salida.', 'is_open' => false];
        $url = '/gestion/servicios/'.$departure->tourism_service_id.'/salidas';
        $this->actingAs($supplier)->post($url, $data)->assertSessionHasNoErrors();
        $this->post($url, [...$data, 'service_date' => today()->addDays(9)->toDateString(), 'is_open' => true])->assertSessionHasErrors('is_open');
        $this->actingAs($this->account(Role::Supplier))->get($url)->assertForbidden();
        $this->actingAs($this->account(Role::Admin))->get($url)->assertOk();
    }

    public function test_account_with_booking_history_is_not_deleted_or_logged_out(): void
    {
        $user = $this->account();
        $departure = $this->departure();
        $this->actingAs($user)->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure));
        $this->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrors('password');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_expiration_command_releases_only_expired_pending_holds(): void
    {
        $departure = $this->departure();
        $first = $this->account();
        $second = $this->account();
        $this->actingAs($first)->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure, 2));
        $this->actingAs($second)->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure, 1));
        $confirmed = Booking::where('user_id', $second->id)->firstOrFail();
        $this->actingAs($this->account(Role::Admin))->patch('/gestion/reservas/'.$confirmed->id.'/confirmar')->assertSessionHasNoErrors();
        $this->travel(25)->hours();
        $this->artisan('tourism:expire-bookings')->assertSuccessful();
        $this->artisan('tourism:expire-bookings')->assertSuccessful();
        $this->assertDatabaseHas('departures', ['id' => $departure->id, 'reserved' => 1]);
        $this->assertDatabaseHas('bookings', ['user_id' => $first->id, 'status' => 'expired']);
        $this->assertDatabaseHas('bookings', ['id' => $confirmed->id, 'status' => 'confirmed']);
    }

    public function test_booked_services_cannot_be_reassigned_to_another_provider_or_category(): void
    {
        $departure = $this->departure();
        $other = $this->departure();
        $this->actingAs($this->account())->post('/salidas/'.$departure->id.'/reservar', $this->payload($departure));
        $service = $departure->service;
        $data = [...$service->only(['title', 'category', 'destination', 'description', 'conditions', 'reference_price', 'status']), 'provider_id' => $other->service->provider_id, 'currency' => 'COP'];
        $this->actingAs($this->account(Role::Admin))->put('/gestion/servicios/'.$service->id, $data)->assertSessionHasErrors('provider_id');
        $this->put('/gestion/servicios/'.$service->id, [...$data, 'provider_id' => $service->provider_id, 'category' => 'vuelos'])->assertSessionHasErrors('provider_id');
        $this->assertDatabaseHas('tourism_services', ['id' => $service->id, 'provider_id' => $service->provider_id, 'category' => 'actividades']);
    }
}
