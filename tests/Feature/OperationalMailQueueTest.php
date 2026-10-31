<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Departure;
use App\Models\Provider;
use App\Models\TourismService;
use App\Models\User;
use App\Services\AccountNotifier;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationalMailQueueTest extends TestCase
{
    use DatabaseMigrations;

    private function booking(): Booking
    {
        $user = User::factory()->create();
        $provider = Provider::create(['name' => 'Operador', 'contact_email' => 'operador@example.test', 'status' => 'active']);
        $service = TourismService::create(['provider_id' => $provider->id, 'title' => 'Salida', 'category' => 'actividades', 'destination' => 'Popayán', 'description' => 'Condiciones de prueba de envío de avisos.', 'status' => 'published']);
        $departure = Departure::create(['tourism_service_id' => $service->id, 'service_date' => today()->addDays(5), 'capacity' => 2, 'unit_price' => 50000, 'terms' => 'Condiciones de la prueba de avisos.', 'is_open' => true]);

        return Booking::create(['reference' => Str::uuid()->toString(), 'user_id' => $user->id, 'departure_id' => $departure->id, 'provider_id' => $provider->id, 'idempotency_key' => Str::uuid()->toString(), 'service_title' => $service->title, 'provider_name' => $provider->name, 'service_date' => $departure->service_date, 'travelers' => 1, 'unit_price' => 50000, 'total' => 50000, 'currency' => 'COP', 'terms' => $departure->terms, 'status' => 'confirmed', 'expires_at' => now()->addDay()]);
    }

    public function test_mail_is_enqueued_only_after_a_successful_commit(): void
    {
        config(['communications.operational_mail_enabled' => true, 'queue.default' => 'database']);
        $booking = $this->booking();
        DB::beginTransaction();
        app(AccountNotifier::class)->booking($booking);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('jobs', 0);
        DB::commit();
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_rollback_discards_both_the_notice_and_the_queued_email(): void
    {
        config(['communications.operational_mail_enabled' => true, 'queue.default' => 'database']);
        $booking = $this->booking();
        DB::beginTransaction();
        app(AccountNotifier::class)->booking($booking);
        $this->assertDatabaseCount('notifications', 1);
        DB::rollBack();
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('jobs', 0);
    }
}
