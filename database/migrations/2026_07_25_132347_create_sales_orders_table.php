<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sales_orders');

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique();
            $table->date('document_date')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('place_of_supply_state_id')->index();
            $table->unsignedBigInteger('quotation_id')->nullable()->index();
            $table->string('customer_po_no', 50)->nullable();
            $table->date('customer_po_date')->nullable();
            $table->date('expected_delivery_date')->index();
            $table->string('status', 30)->default('draft')->index();
            $table->string('tax_type', 10)->default('cgst_sgst');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->boolean('credit_hold')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'document_date']);
            $table->foreign('customer_id', 'so_customer_id_fk')->references('id')->on('parties')->restrictOnDelete();
            $table->foreign('warehouse_id', 'so_warehouse_id_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('place_of_supply_state_id', 'so_pos_state_id_fk')->references('id')->on('states')->restrictOnDelete();
            $table->foreign('quotation_id', 'so_quotation_id_fk')->references('id')->on('sales_quotations')->nullOnDelete();
            $table->foreign('confirmed_by', 'so_confirmed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'so_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'so_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
