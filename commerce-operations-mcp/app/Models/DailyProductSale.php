<?php

namespace App\Models;

use Database\Factories\DailyProductSaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyProductSale extends Model
{
    /** @use HasFactory<DailyProductSaleFactory> */
    use HasFactory;

    protected $fillable = ['product_id', 'sale_date', 'units_sold', 'order_count', 'gross_revenue'];

    protected function casts(): array
    {
        return ['sale_date' => 'date', 'gross_revenue' => 'decimal:2'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
