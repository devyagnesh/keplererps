<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bom_components');

        Schema::create('bom_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bom_id')->index();
            $table->unsignedBigInteger('component_item_id')->index();
            $table->decimal('quantity', 18, 4);
            $table->unsignedBigInteger('uom_id');
            $table->decimal('wastage_percent', 5, 2)->default(0);
            $table->boolean('is_critical')->default(false);
            $table->string('issue_method', 20)->default('manual');
            $table->unsignedInteger('operation_sequence')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['bom_id', 'component_item_id'], 'bom_components_bom_item_unique');
            $table->foreign('bom_id', 'bom_components_bom_id_fk')->references('id')->on('boms')->cascadeOnDelete();
            $table->foreign('component_item_id', 'bom_components_item_id_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('uom_id', 'bom_components_uom_id_fk')->references('id')->on('uoms')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_components');
    }
};
