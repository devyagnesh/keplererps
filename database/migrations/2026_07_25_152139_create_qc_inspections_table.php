<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('qc_inspections');

        Schema::create('qc_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique();
            $table->date('document_date')->index();
            $table->string('inspection_type', 30)->index();
            $table->string('status', 20)->default('pending')->index();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->unsignedBigInteger('qc_template_id')->nullable()->index();
            $table->unsignedBigInteger('quarantine_warehouse_id')->nullable()->index();
            $table->unsignedBigInteger('target_warehouse_id')->nullable()->index();
            $table->decimal('lot_quantity', 18, 4);
            $table->decimal('sample_size', 18, 4);
            $table->string('sampling_plan', 30)->nullable();
            $table->string('sample_override_reason', 255)->nullable();
            $table->string('overall_result', 20)->nullable()->comment('pass|fail');
            $table->string('disposition', 30)->nullable();
            $table->decimal('accepted_qty', 18, 4)->default(0);
            $table->decimal('rejected_qty', 18, 4)->default(0);
            $table->decimal('rework_qty', 18, 4)->default(0);
            $table->text('deviation_note')->nullable();
            $table->unsignedBigInteger('deviation_approved_by')->nullable();
            $table->timestamp('deviation_approved_at')->nullable();
            $table->unsignedBigInteger('inspector_id')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_type', 'source_id']);
            $table->index(['status', 'document_date']);
            $table->foreign('item_id', 'qci_item_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('batch_id', 'qci_batch_fk')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('qc_template_id', 'qci_template_fk')->references('id')->on('qc_templates')->nullOnDelete();
            $table->foreign('quarantine_warehouse_id', 'qci_quarantine_wh_fk')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreign('target_warehouse_id', 'qci_target_wh_fk')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreign('deviation_approved_by', 'qci_deviation_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('inspector_id', 'qci_inspector_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'qci_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'qci_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_inspections');
    }
};
