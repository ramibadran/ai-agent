<?php

namespace App\Models;

use Database\Factories\InventorySnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventorySnapshot extends Model
{
    /** @use HasFactory<InventorySnapshotFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'quantity_on_hand', 'reorder_point', 'captured_at'];

    protected function casts(): array
    {
        return ['captured_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
