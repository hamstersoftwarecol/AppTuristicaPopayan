<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $booking_id
 * @property string $reference
 * @property string $status
 * @property string $environment
 * @property int $amount_in_cents
 * @property string $currency
 * @property string|null $gateway_transaction_id
 * @property Booking $booking
 */
#[Fillable(['booking_id', 'reference', 'environment', 'amount_in_cents', 'currency', 'status', 'gateway_transaction_id'])]
class PaymentAttempt extends Model
{
    protected function casts(): array
    {
        return ['amount_in_cents' => 'integer'];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
