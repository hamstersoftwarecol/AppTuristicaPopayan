<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $provider_id
 * @property string $title
 * @property string $category
 * @property string $status
 * @property Provider $provider
 */
#[Fillable(['provider_id', 'title', 'category', 'destination', 'description', 'conditions', 'reference_price', 'currency', 'status'])]
class TourismService extends Model
{
    /** @return BelongsTo<Provider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /** @return HasMany<Departure, $this> */
    public function departures(): HasMany
    {
        return $this->hasMany(Departure::class);
    }

    /** @param Builder<TourismService> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published')->whereHas('provider', fn (Builder $provider) => $provider->where('status', 'active'));
    }
}
