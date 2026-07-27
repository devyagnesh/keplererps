<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('delivery_challans');

        Schema::create('delivery_challans', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique();
            $table->date('document_date')->index();
            $table->unsignedBigInteger('sales_order_id')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->string('transport_mode', 10);
            $table->string('vehicle_number', 20)->nullable();
            $table->unsignedBigInteger('transporter_id')->nullable()->index();
            $table->string('transporter_gstin', 15)->nullable();
            $table->string('lr_number', 30)->nullable();
            $table->date('lr_date')->nullable();
            $table->unsignedInteger('distance_km')->nullable()->comment('Required when e-way bill applies');
            $table->string('driver_name', 100)->nullable();
            $table->string('driver_mobile', 10)->nullable();
            $table->unsignedInteger('number_of_packages')->default(1);
            $table->decimal('gross_weight', 18, 3)->nullable();
            $table->decimal('net_weight', 18, 3)->nullable();
            $table->string('eway_bill_number', 12)->nullable();
            $table->boolean('eway_required')->default(false);
            $table->decimal('dispatch_value', 18, 2)->default(0)->comment('Estimated taxable value for e-way threshold');
            $table->date('expected_delivery_date')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->unsignedBigInteger('dispatched_by')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('pod_path', 255)->nullable()->comment('Proof of delivery file path');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'document_date']);
            $table->foreign('sales_order_id', 'dc_so_id_fk')->references('id')->on('sales_orders')->restrictOnDelete();
            $table->foreign('customer_id', 'dc_customer_id_fk')->references('id')->on('parties')->restrictOnDelete();
            $table->foreign('warehouse_id', 'dc_warehouse_id_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('transporter_id', 'dc_transporter_id_fk')->references('id')->on('transporters')->nullOnDelete();
            $table->foreign('dispatched_by', 'dc_dispatched_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'dc_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'dc_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_challans');
    }
};
