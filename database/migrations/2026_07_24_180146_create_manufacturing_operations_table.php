<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manufacturing operation master used on BOM routing lines (M04).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_operations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Short operation code');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_operations');
    }
};
