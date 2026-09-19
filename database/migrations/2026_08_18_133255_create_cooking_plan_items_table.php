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
        Schema::create('cooking_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooking_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->integer('portions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cooking_plan_items');
    }
};
