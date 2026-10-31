<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $departure_id
 * @property int $provider_id
 * @property int $user_id
 * @property int $travelers
 * @property string $status
 * @property int $total
 * @property string $reference
 * @property Carbon $service_date
 * @property Carbon $expires_at
 * @property Departure $departure
 */
#[Fillable(['reference', 'user_id', 'departure_id', 'provider_id', 'idempotency_key', 'service_title', 'provider_name', 'service_date', 'travelers', 'unit_price', 'total', 'currency', 'terms', 'status', 'expires_at', 'confirmed_at', 'cancelled_at', 'cancellation_reason'])]
class Booking extends Model
{
    protected function casts(): array
    {
        return ['total' => 'integer', 'unit_price' => 'integer', 'travelers' => 'integer', 'service_date' => 'date:Y-m-d', 'expires_at' => 'datetime', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    /** @return HasMany<PaymentAttempt, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    /** @return BelongsTo<Departure, $this> */
    public function departure(): BelongsTo
    {
        return $this->belongsTo(Departure::class);
    }

    /** @return BelongsTo<Provider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
