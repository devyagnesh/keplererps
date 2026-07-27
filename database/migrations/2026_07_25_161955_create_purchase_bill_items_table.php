<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_bill_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_bill_id')->index();
            $table->unsignedBigInteger('goods_receipt_item_id')->nullable()->index();
            $table->unsignedBigInteger('purchase_order_item_id')->nullable()->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('uom_id')->index();
            $table->decimal('quantity', 18, 4)->comment('Quantity billed by the supplier');
            $table->decimal('rate', 18, 4)->comment('Rate billed by the supplier');
            $table->decimal('gst_rate', 8, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->decimal('po_rate', 18, 4)->default(0)->comment('Rate on the linked purchase order line');
            $table->decimal('grn_qty', 18, 4)->default(0)->comment('Accepted quantity on the linked goods receipt line');
            $table->decimal('rate_variance_percent', 8, 4)->default(0);
            $table->decimal('qty_variance', 18, 4)->default(0);
            $table->string('match_status', 30)->default('matched')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('purchase_bill_id', 'pbi_bill_fk')->references('id')->on('purchase_bills')->cascadeOnDelete();
            $table->foreign('goods_receipt_item_id', 'pbi_grn_item_fk')->references('id')->on('goods_receipt_items')->nullOnDelete();
            $table->foreign('purchase_order_item_id', 'pbi_po_item_fk')->references('id')->on('purchase_order_items')->nullOnDelete();
            $table->foreign('item_id', 'pbi_item_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('uom_id', 'pbi_uom_fk')->references('id')->on('uoms')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_bill_items');
    }
};
