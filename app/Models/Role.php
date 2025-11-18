<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Los usuarios que tienen este rol
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
