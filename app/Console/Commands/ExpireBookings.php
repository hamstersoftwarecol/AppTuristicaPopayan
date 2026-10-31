<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Console\Command;

class ExpireBookings extends Command
{
    protected $signature = 'tourism:expire-bookings';

    protected $description = 'Liberar cupos de solicitudes de reserva vencidas';

    public function handle(BookingService $service): int
    {
        $departures = Booking::where('status', 'pending')->where('expires_at', '<=', now())->distinct()->pluck('departure_id');
        foreach ($departures as $id) {
            $service->expireDeparture($id);
        }
        $this->info('Solicitudes vencidas procesadas.');

        return self::SUCCESS;
    }
}
