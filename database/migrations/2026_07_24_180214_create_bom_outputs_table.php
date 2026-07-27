<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bom_outputs');

        Schema::create('bom_outputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bom_id')->index();
            $table->unsignedBigInteger('item_id');
            $table->decimal('expected_quantity', 18, 4);
            $table->unsignedBigInteger('uom_id');
            $table->decimal('cost_allocation_percent', 5, 2)->default(0);
            $table->string('output_type', 20)->default('by_product');
            $table->timestamps();

            $table->unique(['bom_id', 'item_id'], 'bom_outputs_bom_item_unique');
            $table->foreign('bom_id', 'bom_outputs_bom_id_fk')->references('id')->on('boms')->cascadeOnDelete();
            $table->foreign('item_id', 'bom_outputs_item_id_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('uom_id', 'bom_outputs_uom_id_fk')->references('id')->on('uoms')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_outputs');
    }
};
