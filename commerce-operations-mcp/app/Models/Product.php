<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = ['sku', 'name', 'category', 'unit_price', 'is_active'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function dailySales(): HasMany
    {
        return $this->hasMany(DailyProductSale::class);
    }

    public function inventorySnapshots(): HasMany
    {
        return $this->hasMany(InventorySnapshot::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
