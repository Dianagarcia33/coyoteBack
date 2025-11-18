<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientGoal extends Model
{
    protected $fillable = [
        'client_id',
        'title',
        'description',
        'target_weight',
        'target_metric',
        'target_value',
        'start_date',
        'target_date',
        'status',
        'progress',
    ];

    protected $casts = [
        'start_date' => 'date',
        'target_date' => 'date',
        'target_weight' => 'decimal:2',
        'target_value' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
