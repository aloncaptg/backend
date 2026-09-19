<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g., Wedding 500 Pax
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->date('delivery_date');
            $table->decimal('total_cogs', 15, 2)->default(0); // Cost of Goods Sold
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->enum('status', ['pending', 'cooking', 'ready', 'delivered'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
