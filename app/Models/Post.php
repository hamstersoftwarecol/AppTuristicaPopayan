<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** @property int $id */
#[Fillable(['author_id', 'title', 'slug', 'category', 'excerpt', 'body', 'published_at'])]
class Post extends Model
{
    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }
}
