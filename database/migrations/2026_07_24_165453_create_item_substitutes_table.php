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
        Schema::create('item_substitutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('substitute_item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('conversion_ratio', 18, 6)->default(1)->comment('1 item unit = ratio substitute units');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['item_id', 'substitute_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_substitutes');
    }
};
