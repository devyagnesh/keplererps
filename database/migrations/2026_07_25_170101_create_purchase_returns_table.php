<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique()->comment('System-generated purchase return number');
            $table->date('document_date')->index();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->unsignedBigInteger('goods_receipt_id')->index()->comment('Posted GRN the goods are returned against');
            $table->unsignedBigInteger('warehouse_id')->index()->comment('Warehouse the stock is issued from');
            $table->string('status', 20)->default('draft')->index();
            $table->string('reason', 255)->comment('Why the material is being returned to the supplier');
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
            $table->foreign('supplier_id', 'pr_supplier_fk')->references('id')->on('parties')->restrictOnDelete();
            $table->foreign('goods_receipt_id', 'pr_grn_fk')->references('id')->on('goods_receipts')->restrictOnDelete();
            $table->foreign('warehouse_id', 'pr_warehouse_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('posted_by', 'pr_posted_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'pr_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'pr_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_return_id')->index();
            $table->unsignedBigInteger('goods_receipt_item_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->decimal('quantity', 18, 4);
            $table->decimal('rate', 18, 4);
            $table->decimal('gst_rate', 8, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('purchase_return_id', 'pri_return_fk')->references('id')->on('purchase_returns')->cascadeOnDelete();
            $table->foreign('goods_receipt_item_id', 'pri_grn_item_fk')->references('id')->on('goods_receipt_items')->restrictOnDelete();
            $table->foreign('item_id', 'pri_item_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('batch_id', 'pri_batch_fk')->references('id')->on('batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
