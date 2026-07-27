<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_bills', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique()->comment('System-generated purchase bill number');
            $table->date('document_date')->index();
            $table->unsignedBigInteger('supplier_id')->index();
            $table->unsignedBigInteger('purchase_order_id')->nullable()->index();
            $table->unsignedBigInteger('goods_receipt_id')->nullable()->index();
            $table->string('supplier_bill_no', 60)->comment('Invoice number printed on the supplier bill');
            $table->date('supplier_bill_date');
            $table->string('status', 20)->default('draft')->index();
            $table->string('match_status', 30)->default('matched')->index()->comment('Three-way match outcome across all lines');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('other_charges', 15, 2)->default(0)->comment('Freight and other charges billed by the supplier');
            $table->decimal('round_off', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->text('mismatch_reason')->nullable()->comment('Justification captured when approving outside tolerance');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['supplier_id', 'supplier_bill_no'], 'pb_supplier_bill_unique');
            $table->index(['status', 'document_date']);
            $table->foreign('supplier_id', 'pb_supplier_fk')->references('id')->on('parties')->restrictOnDelete();
            $table->foreign('purchase_order_id', 'pb_po_fk')->references('id')->on('purchase_orders')->nullOnDelete();
            $table->foreign('goods_receipt_id', 'pb_grn_fk')->references('id')->on('goods_receipts')->nullOnDelete();
            $table->foreign('approved_by', 'pb_approved_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'pb_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'pb_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_bills');
    }
};
