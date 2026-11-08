<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity_on_hand');
            $table->unsignedInteger('reorder_point');
            $table->timestamp('captured_at');
            $table->timestamps();
            $table->unique(['product_id', 'captured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_snapshots');
    }
};
