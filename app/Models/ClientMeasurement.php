<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientMeasurement extends Model
{
    protected $fillable = [
        'client_id',
        'weight',
        'body_fat',
        'muscle_mass',
        'notes',
        'measured_at',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'body_fat' => 'decimal:2',
        'muscle_mass' => 'decimal:2',
        'measured_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
