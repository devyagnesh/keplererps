<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Work centres / machines used by BOM operations and M11 maintenance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_centres', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->decimal('machine_rate_per_hour', 12, 2)->default(0);
            $table->decimal('labour_rate_per_hour', 12, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_centres');
    }
};
