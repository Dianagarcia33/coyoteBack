<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSession extends Model
{
    protected $fillable = [
        'session_package_id',
        'client_id',
        'professional_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function sessionPackage()
    {
        return $this->belongsTo(SessionPackage::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function professional()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function canBeCompleted()
    {
        return $this->status === 'scheduled';
    }
}
