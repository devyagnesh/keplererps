<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Purchase order headers (M07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('purchase_orders');

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique();
            $table->date('document_date')->index();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->date('expected_delivery_date')->index();
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'document_date']);
            $table->foreign('supplier_id', 'po_supplier_id_fk')->references('id')->on('parties')->restrictOnDelete();
            $table->foreign('warehouse_id', 'po_warehouse_id_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('approved_by', 'po_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'po_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'po_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
