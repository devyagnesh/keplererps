<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sales_quotations');

        Schema::create('sales_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique();
            $table->date('document_date')->index();
            $table->date('valid_until')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->unsignedBigInteger('place_of_supply_state_id')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->string('tax_type', 10)->default('cgst_sgst');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('converted_sales_order_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id', 'sq_customer_id_fk')->references('id')->on('parties')->restrictOnDelete();
            $table->foreign('warehouse_id', 'sq_warehouse_id_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('place_of_supply_state_id', 'sq_pos_state_id_fk')->references('id')->on('states')->restrictOnDelete();
            $table->foreign('created_by', 'sq_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'sq_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_quotations');
    }
};
