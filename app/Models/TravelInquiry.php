<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property TourismService|null $service
 */
#[Fillable(['user_id', 'tourism_service_id', 'travel_date', 'travelers', 'message', 'status', 'response', 'handled_by'])]
class TravelInquiry extends Model
{
    protected function casts(): array
    {
        return ['travel_date' => 'date:Y-m-d'];
    }

    /** @return BelongsTo<TourismService, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(TourismService::class, 'tourism_service_id');
    }

    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
