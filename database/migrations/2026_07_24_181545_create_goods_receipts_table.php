<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('goods_receipts');

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique();
            $table->date('document_date')->index();
            $table->unsignedBigInteger('purchase_order_id')->index();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->string('supplier_invoice_no', 50);
            $table->date('supplier_invoice_date');
            $table->string('vehicle_number', 20)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->text('remarks')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['supplier_id', 'supplier_invoice_no'], 'grn_supplier_invoice_unique');
            $table->index(['status', 'document_date']);
            $table->foreign('purchase_order_id', 'grn_po_id_fk')->references('id')->on('purchase_orders')->restrictOnDelete();
            $table->foreign('supplier_id', 'grn_supplier_id_fk')->references('id')->on('parties')->restrictOnDelete();
            $table->foreign('warehouse_id', 'grn_warehouse_id_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('posted_by', 'grn_posted_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'grn_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'grn_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
