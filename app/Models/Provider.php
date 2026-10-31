<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $owner_id
 * @property string $name
 * @property string $status
 */
#[Fillable(['owner_id', 'name', 'contact_email', 'phone', 'website', 'status', 'notes'])]
class Provider extends Model
{
    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
