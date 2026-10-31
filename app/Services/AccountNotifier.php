<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\TravelInquiry;
use App\Models\User;
use App\Notifications\AccountNotice;
use App\Notifications\AccountNoticeMail;

class AccountNotifier
{
    public function booking(Booking $booking): void
    {
        $customer = $booking->customer;
        if (! $customer) {
            return;
        }
        [$title, $message] = match ($booking->status) {
            'pending' => ['Solicitud de reserva recibida', 'Recibimos tu solicitud para '.$booking->reference.'. El proveedor debe confirmarla antes del vencimiento.'],
            'confirmed' => ['Reserva local confirmada', 'Tu reserva para '.$booking->reference.' fue confirmada. Puedes consultar el detalle y su comprobante en tu cuenta.'],
            'cancelled' => ['Reserva cancelada', 'Tu reserva para '.$booking->reference.' fue cancelada. Los cupos fueron liberados.'],
            'expired' => ['Solicitud de reserva vencida', 'La solicitud para '.$booking->reference.' venció sin confirmación. Puedes consultar otras fechas en el catálogo.'],
            default => ['', ''],
        };
        if ($title !== '') {
            $this->send($customer, $title, $message, '/mis-reservas', 'booking.'.$booking->status);
        }
    }

    public function inquiry(TravelInquiry $inquiry): void
    {
        $customer = $inquiry->customer;
        if ($customer && in_array($inquiry->status, ['responded', 'cancelled'], true)) {
            $this->send($customer, $inquiry->status === 'responded' ? 'Tu cotización tiene una respuesta' : 'Solicitud de cotización cancelada',
                'La solicitud de cotización #'.$inquiry->id.' fue actualizada. Consulta el detalle en tu cuenta.', '/mis-solicitudes', 'inquiry.'.$inquiry->status);
        }
    }

    private function send(User $user, string $title, string $message, string $path, string $kind): void
    {
        // The database notice commits or rolls back with the operation that caused it.
        $user->notifyNow(new AccountNotice($title, $message, $path, $kind));
        if (config('communications.operational_mail_enabled') && $user->hasVerifiedEmail()) {
            $user->notify(new AccountNoticeMail($title, $message, $path));
        }
    }
}
