<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total',
        'points_earned',
        'status',
        'points_awarded',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'points_earned' => 'integer',
            'points_awarded' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Calcular puntos a ganar basado en el total
     * 1 punto por cada $10 COP
     */
    public static function calculatePoints($total)
    {
        return floor($total / 10);
    }
}
