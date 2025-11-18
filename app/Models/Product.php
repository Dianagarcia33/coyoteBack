<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'stock',
        'sku',
        'images',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'stock' => 'integer',
        'images' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Categoría del producto
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Historial de cambios del producto
     */
    public function history(): HasMany
    {
        return $this->hasMany(ProductHistory::class);
    }

    /**
     * Generar slug automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            
            // Registrar creación
            static::created(function ($product) {
                $product->logHistory('created', null, $product->toArray(), 'Producto creado');
            });
        });

        static::updating(function ($product) {
            $original = $product->getOriginal();
            $changes = $product->getDirty();
            
            if (!empty($changes)) {
                $oldValues = [];
                $newValues = [];
                
                foreach ($changes as $key => $value) {
                    $oldValues[$key] = $original[$key];
                    $newValues[$key] = $value;
                }
                
                $product->logHistory('updated', $oldValues, $newValues, 'Producto actualizado');
            }
        });
    }

    /**
     * Registrar cambio en el historial
     */
    public function logHistory(string $action, ?array $oldValues, ?array $newValues, string $description): void
    {
        ProductHistory::create([
            'product_id' => $this->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
        ]);
    }

    /**
     * Verificar si hay stock disponible
     */
    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }

    /**
     * Reducir stock
     */
    public function decrementStock(int $quantity): void
    {
        $oldStock = $this->stock;
        $this->decrement('stock', $quantity);
        
        $this->logHistory(
            'stock_decreased',
            ['stock' => $oldStock],
            ['stock' => $this->stock],
            "Stock reducido en {$quantity} unidades"
        );
    }

    /**
     * Aumentar stock
     */
    public function incrementStock(int $quantity): void
    {
        $oldStock = $this->stock;
        $this->increment('stock', $quantity);
        
        $this->logHistory(
            'stock_increased',
            ['stock' => $oldStock],
            ['stock' => $this->stock],
            "Stock aumentado en {$quantity} unidades"
        );
    }
}
