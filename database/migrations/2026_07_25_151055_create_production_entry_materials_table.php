<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('production_entry_materials');

        Schema::create('production_entry_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_entry_id')->index();
            $table->unsignedBigInteger('work_order_component_id')->nullable()->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('uom_id');
            $table->decimal('quantity', 18, 4);
            $table->decimal('rate', 18, 4)->default(0);
            $table->decimal('value', 18, 2)->default(0);
            $table->string('issue_method', 20)->default('backflush');
            $table->timestamps();

            $table->foreign('production_entry_id', 'pem_pe_id_fk')->references('id')->on('production_entries')->cascadeOnDelete();
            $table->foreign('work_order_component_id', 'pem_woc_fk')->references('id')->on('work_order_components')->nullOnDelete();
            $table->foreign('item_id', 'pem_item_id_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('uom_id', 'pem_uom_id_fk')->references('id')->on('uoms')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_entry_materials');
    }
};
