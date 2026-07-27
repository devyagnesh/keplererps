<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('delivery_challan_items');

        Schema::create('delivery_challan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_challan_id')->index();
            $table->unsignedBigInteger('sales_order_item_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('uom_id');
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('rate', 18, 4)->default(0)->comment('Copied from SO for value/e-way calc');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('delivery_challan_id', 'dci_dc_id_fk')->references('id')->on('delivery_challans')->cascadeOnDelete();
            $table->foreign('sales_order_item_id', 'dci_soi_id_fk')->references('id')->on('sales_order_items')->restrictOnDelete();
            $table->foreign('item_id', 'dci_item_id_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('uom_id', 'dci_uom_id_fk')->references('id')->on('uoms')->restrictOnDelete();
            $table->foreign('batch_id', 'dci_batch_id_fk')->references('id')->on('batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_challan_items');
    }
};
