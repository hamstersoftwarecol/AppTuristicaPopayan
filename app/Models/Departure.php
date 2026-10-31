<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tourism_service_id
 * @property Carbon $service_date
 * @property int $capacity
 * @property int $reserved
 * @property int $unit_price
 * @property string $terms
 * @property bool $is_open
 * @property TourismService $service
 */
#[Fillable(['tourism_service_id', 'service_date', 'capacity', 'unit_price', 'terms', 'is_open'])]
class Departure extends Model
{
    protected function casts(): array
    {
        return ['service_date' => 'date:Y-m-d', 'is_open' => 'boolean'];
    }

    public function quoteToken(): string
    {
        return hash_hmac('sha256', json_encode([
            $this->id, $this->service_date->toDateString(), $this->unit_price,
            $this->terms, $this->service->title, $this->service->provider->name,
        ], JSON_THROW_ON_ERROR), config('app.key'));
    }

    /** @return BelongsTo<TourismService, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(TourismService::class, 'tourism_service_id');
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
