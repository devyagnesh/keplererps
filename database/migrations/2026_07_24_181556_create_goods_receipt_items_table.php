<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('goods_receipt_items');

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receipt_id')->index();
            $table->unsignedBigInteger('purchase_order_item_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->decimal('received_qty', 18, 4);
            $table->decimal('accepted_qty', 18, 4);
            $table->decimal('rejected_qty', 18, 4)->default(0);
            $table->string('rejection_reason', 255)->nullable();
            $table->decimal('rate', 18, 4)->default(0);
            $table->string('batch_no', 50)->nullable();
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('serial_no', 80)->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('goods_receipt_id', 'gri_grn_id_fk')->references('id')->on('goods_receipts')->cascadeOnDelete();
            $table->foreign('purchase_order_item_id', 'gri_poi_id_fk')->references('id')->on('purchase_order_items')->restrictOnDelete();
            $table->foreign('item_id', 'gri_item_id_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('batch_id', 'gri_batch_id_fk')->references('id')->on('batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
