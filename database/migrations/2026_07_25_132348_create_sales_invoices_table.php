<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sales_invoices');

        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique();
            $table->date('document_date')->index();
            $table->unsignedBigInteger('sales_order_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('place_of_supply_state_id')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->string('tax_type', 10)->default('cgst_sgst');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('round_off', 8, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'document_date']);
            $table->foreign('sales_order_id', 'si_so_id_fk')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->foreign('customer_id', 'si_customer_id_fk')->references('id')->on('parties')->restrictOnDelete();
            $table->foreign('warehouse_id', 'si_warehouse_id_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('place_of_supply_state_id', 'si_pos_state_id_fk')->references('id')->on('states')->restrictOnDelete();
            $table->foreign('confirmed_by', 'si_confirmed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'si_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'si_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
