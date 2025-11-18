<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'slot_id',
        'client_id',
        'payment_id',
        'amount',
        'status',
        'confirmed_at',
        'rejected_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'professional_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function slot()
    {
        return $this->belongsTo(AvailableSlot::class, 'slot_id');
    }

    public function availableSlot()
    {
        return $this->belongsTo(AvailableSlot::class, 'slot_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Verificar si puede ser cancelada (más de 24h antes)
     */
    public function canBeCancelled()
    {
        $slot = $this->slot;
        $sessionDateTime = \Carbon\Carbon::parse($slot->date . ' ' . $slot->start_time);
        $hoursUntilSession = now()->diffInHours($sessionDateTime, false);
        
        return $hoursUntilSession >= 24;
    }

    /**
     * Verificar si la confirmación expiró (48h)
     */
    public function isConfirmationExpired()
    {
        if ($this->status !== 'pending_confirmation') {
            return false;
        }
        
        return $this->created_at->diffInHours(now()) >= 48;
    }
}
