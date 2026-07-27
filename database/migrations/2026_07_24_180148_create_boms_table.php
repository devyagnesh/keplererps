<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BOM headers — versioned recipes for manufacturable items (M04).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->string('bom_number', 40)->unique()->comment('System-generated BOM number');
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->unsignedInteger('version')->default(1)->comment('Auto-incremented per item');
            $table->decimal('output_quantity', 18, 4)->default(1);
            $table->foreignId('output_uom_id')->constrained('uoms')->restrictOnDelete();
            $table->date('valid_from')->index();
            $table->date('valid_to')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->decimal('overhead_percent', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->decimal('rolled_material_cost', 18, 2)->default(0);
            $table->decimal('rolled_operation_cost', 18, 2)->default(0);
            $table->decimal('rolled_total_cost', 18, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['item_id', 'version']);
            $table->index(['item_id', 'is_active', 'valid_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boms');
    }
};
