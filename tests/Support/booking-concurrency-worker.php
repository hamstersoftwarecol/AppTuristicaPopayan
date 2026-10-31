<?php

use App\Models\Booking;
use App\Models\Departure;
use App\Models\Provider;
use App\Models\TourismService;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if ($argv[1] === 'seed') {
    $users = User::factory()->count(2)->create();
    $provider = Provider::create(['name' => 'Concurrency fixture', 'contact_email' => 'fixture@example.test', 'status' => 'active']);
    $service = TourismService::create(['provider_id' => $provider->id, 'title' => 'Último cupo', 'category' => 'actividades', 'destination' => 'Popayán', 'description' => 'Servicio aislado para verificar la concurrencia.', 'status' => 'published']);
    $departure = Departure::create(['tourism_service_id' => $service->id, 'service_date' => today()->addDays(5), 'capacity' => 1, 'unit_price' => 50000, 'terms' => 'Condiciones de prueba de concurrencia.', 'is_open' => true]);
    echo json_encode(['departure' => $departure->id, 'users' => $users->modelKeys()], JSON_THROW_ON_ERROR);
    exit;
}
if ($argv[1] === 'inspect') {
    echo json_encode(['reserved' => Departure::firstOrFail()->reserved, 'bookings' => Booking::count(), 'travelers' => (int) Booking::sum('travelers')], JSON_THROW_ON_ERROR);
    exit;
}

$user = User::findOrFail((int) $argv[2]);
$departure = Departure::findOrFail((int) $argv[3]);
$barrier = $argv[5];
file_put_contents($barrier.'.'.$user->id.'.ready', '1');
$deadline = microtime(true) + 10;
while (! file_exists($barrier)) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('The concurrency barrier timed out.');
    }
    usleep(1000);
}
try {
    app(BookingService::class)->reserve($user, $departure->id, 1, $argv[4], $departure->quoteToken());
    echo 'reserved';
} catch (ValidationException $exception) {
    if (! isset($exception->errors()['travelers'])) {
        throw $exception;
    }
    echo 'sold_out';
}
