<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique()->comment('System-generated sales return number');
            $table->date('document_date')->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('sales_invoice_id')->nullable()->index();
            $table->unsignedBigInteger('warehouse_id')->index()->comment('Warehouse the returned goods are received into');
            $table->string('status', 20)->default('draft')->index();
            $table->string('reason', 255)->comment('Why the customer returned the goods');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'document_date']);
            $table->foreign('customer_id', 'sr_customer_fk')->references('id')->on('parties')->restrictOnDelete();
            $table->foreign('sales_invoice_id', 'sr_invoice_fk')->references('id')->on('sales_invoices')->nullOnDelete();
            $table->foreign('warehouse_id', 'sr_warehouse_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('posted_by', 'sr_posted_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'sr_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'sr_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_return_id')->index();
            $table->unsignedBigInteger('sales_invoice_item_id')->nullable()->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('uom_id')->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->decimal('quantity', 18, 4);
            $table->decimal('rate', 18, 4);
            $table->decimal('gst_rate', 8, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('sales_return_id', 'sri_return_fk')->references('id')->on('sales_returns')->cascadeOnDelete();
            $table->foreign('sales_invoice_item_id', 'sri_invoice_item_fk')->references('id')->on('sales_invoice_items')->nullOnDelete();
            $table->foreign('item_id', 'sri_item_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('uom_id', 'sri_uom_fk')->references('id')->on('uoms')->restrictOnDelete();
            $table->foreign('batch_id', 'sri_batch_fk')->references('id')->on('batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
    }
};
