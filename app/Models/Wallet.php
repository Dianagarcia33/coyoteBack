<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'balance_available',
        'balance_frozen',
    ];

    protected function casts(): array
    {
        return [
            'balance_available' => 'decimal:2',
            'balance_frozen' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Agregar dinero al balance congelado (cuando se paga una sesión)
     */
    public function addFrozenBalance($amount, $type, $description, $reference = null)
    {
        $this->increment('balance_frozen', $amount);

        return $this->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_type' => 'frozen',
            'description' => $description,
            'reference_id' => $reference ? $reference->id : null,
            'reference_type' => $reference ? get_class($reference) : null,
        ]);
    }

    /**
     * Mover dinero de congelado a disponible (cuando se completa una sesión)
     */
    public function moveFrozenToAvailable($amount, $type, $description, $reference = null)
    {
        if ($this->balance_frozen < $amount) {
            return false;
        }

        $this->decrement('balance_frozen', $amount);
        $this->increment('balance_available', $amount);

        return $this->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'balance_type' => 'available',
            'description' => $description,
            'reference_id' => $reference ? $reference->id : null,
            'reference_type' => $reference ? get_class($reference) : null,
        ]);
    }
    
    /**
     * Restar del balance congelado (reembolsos)
     */
    public function subtractFrozenBalance($amount, $type = 'refund', $description = 'Reembolso', $reference = null)
    {
        if ($this->balance_frozen < $amount) {
            return false;
        }

        $this->decrement('balance_frozen', $amount);

        return $this->transactions()->create([
            'type' => $type,
            'amount' => -$amount,
            'balance_type' => 'frozen',
            'description' => $description,
            'reference_id' => $reference ? $reference->id : null,
            'reference_type' => $reference ? get_class($reference) : null,
        ]);
    }

    /**
     * Agregar dinero directo al balance disponible (pagos de productos al admin)
     */
    public function addAvailableBalance($amount, $description, $reference = null)
    {
        $this->increment('balance_available', $amount);

        return $this->transactions()->create([
            'type' => 'payment_received',
            'amount' => $amount,
            'balance_type' => 'available',
            'description' => $description,
            'reference_id' => $reference ? $reference->id : null,
            'reference_type' => $reference ? get_class($reference) : null,
        ]);
    }

    /**
     * Restar del balance disponible (retiro)
     */
    public function subtractAvailableBalance($amount, $description, $reference = null)
    {
        if ($this->balance_available < $amount) {
            return false;
        }

        $this->decrement('balance_available', $amount);

        return $this->transactions()->create([
            'type' => 'withdrawal',
            'amount' => -$amount,
            'balance_type' => 'available',
            'description' => $description,
            'reference_id' => $reference ? $reference->id : null,
            'reference_type' => $reference ? get_class($reference) : null,
        ]);
    }

    /**
     * Obtener balance total
     */
    public function getTotalBalanceAttribute()
    {
        return $this->balance_available + $this->balance_frozen;
    }
}

