<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('status');
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamp('ordered_at');
            $table->timestamps();
            $table->index(['status', 'ordered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
