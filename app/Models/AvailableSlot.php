<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailableSlot extends Model
{
    protected $fillable = [
        'professional_id',
        'date',
        'start_time',
        'end_time',
        'type',
        'modality',
        'max_participants',
        'current_participants',
        'price',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'price' => 'decimal:2',
        'current_participants' => 'integer',
        'max_participants' => 'integer',
    ];

    public function professional()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'slot_id');
    }

    /**
     * Verificar si el slot está disponible
     */
    public function isAvailable()
    {
        if ($this->type === 'individual') {
            return $this->current_participants === 0 && $this->status === 'available';
        }
        
        return $this->current_participants < $this->max_participants && $this->status === 'available';
    }

    /**
     * Verificar si hay conflicto de horarios (solapamiento)
     * Retorna true si hay un slot que se solapa PERO que no es exactamente igual
     */
    public static function hasConflict($professionalId, $date, $startTime, $endTime, $excludeSlotId = null)
    {
        $query = self::where('professional_id', $professionalId)
            ->where('date', $date)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                // Hay solapamiento si el slot existente empieza antes de que termine el nuevo
                // Y termina después de que empiece el nuevo
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            // EXCLUIR slots con horario exactamente igual (se pueden unir a esos)
            ->where(function($q) use ($startTime, $endTime) {
                $q->where('start_time', '!=', $startTime)
                  ->orWhere('end_time', '!=', $endTime);
            });

        if ($excludeSlotId) {
            $query->where('id', '!=', $excludeSlotId);
        }

        return $query->exists();
    }
}
