<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_product_sales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('sale_date');
            $table->unsignedInteger('units_sold');
            $table->unsignedInteger('order_count');
            $table->decimal('gross_revenue', 12, 2);
            $table->timestamps();
            $table->unique(['product_id', 'sale_date']);
            $table->index('sale_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_product_sales');
    }
};
