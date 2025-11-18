<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionPackage extends Model
{
    protected $fillable = [
        'client_id',
        'professional_id',
        'service_type',
        'package_name',
        'description',
        'total_sessions',
        'completed_sessions',
        'price_per_session',
        'total_price',
        'payment_id',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'total_sessions' => 'integer',
            'completed_sessions' => 'integer',
            'price_per_session' => 'decimal:2',
            'total_price' => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function professional()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function trainingSessions()
    {
        return $this->hasMany(TrainingSession::class);
    }

    /**
     * Verificar si todas las sesiones están completadas
     */
    public function isCompleted()
    {
        return $this->completed_sessions >= $this->total_sessions;
    }

    /**
     * Obtener sesiones restantes
     */
    public function getRemainingSessionsAttribute()
    {
        return max(0, $this->total_sessions - $this->completed_sessions);
    }
}
