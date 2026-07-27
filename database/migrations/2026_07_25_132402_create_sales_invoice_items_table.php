<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sales_invoice_items');

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_invoice_id')->index();
            $table->unsignedBigInteger('sales_order_item_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('uom_id');
            $table->string('description', 500)->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('rate', 18, 4)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('taxable_amount', 18, 2)->default(0);
            $table->decimal('gst_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 18, 2)->default(0);
            $table->decimal('sgst_amount', 18, 2)->default(0);
            $table->decimal('igst_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('sales_invoice_id', 'sii_si_id_fk')->references('id')->on('sales_invoices')->cascadeOnDelete();
            $table->foreign('sales_order_item_id', 'sii_soi_id_fk')->references('id')->on('sales_order_items')->restrictOnDelete();
            $table->foreign('item_id', 'sii_item_id_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('uom_id', 'sii_uom_id_fk')->references('id')->on('uoms')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
    }
};
