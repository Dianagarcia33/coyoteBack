<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductHistory extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'description',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Producto relacionado
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Usuario que realizó el cambio
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
