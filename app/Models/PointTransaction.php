<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'points',
        'type',
        'source',
        'reference_id',
        'reference_type',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación polimórfica para referencia (producto, orden, etc.)
    public function reference()
    {
        return $this->morphTo();
    }
}
