<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'transaction_id',
        'epayco_ref',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Verificar si el pago está aprobado
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Verificar si el pago está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verificar si se puede reembolsar
     */
    public function canBeRefunded(): bool
    {
        return $this->status === 'approved';
    }
}
