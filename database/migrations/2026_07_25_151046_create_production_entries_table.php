<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('production_entries');

        Schema::create('production_entries', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique();
            $table->date('document_date')->index();
            $table->unsignedBigInteger('work_order_id')->index();
            $table->decimal('good_quantity', 18, 4)->default(0);
            $table->decimal('rejected_quantity', 18, 4)->default(0);
            $table->unsignedBigInteger('defect_reason_id')->nullable()->index();
            $table->string('rejection_disposition', 20)->nullable();
            $table->unsignedBigInteger('downgrade_item_id')->nullable()->index();
            $table->string('batch_no', 50)->nullable();
            $table->unsignedBigInteger('batch_id')->nullable()->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('downtime_minutes')->default(0);
            $table->string('downtime_reason', 100)->nullable();
            $table->decimal('machine_hours', 12, 4)->default(0);
            $table->decimal('labour_hours', 12, 4)->default(0);
            $table->decimal('material_cost', 18, 2)->default(0);
            $table->decimal('machine_cost', 18, 2)->default(0);
            $table->decimal('labour_cost', 18, 2)->default(0);
            $table->decimal('overhead_cost', 18, 2)->default(0);
            $table->decimal('total_cost', 18, 2)->default(0);
            $table->unsignedBigInteger('operator_user_id')->nullable()->index();
            $table->text('remarks')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('work_order_id', 'pe_wo_id_fk')->references('id')->on('work_orders')->restrictOnDelete();
            $table->foreign('defect_reason_id', 'pe_defect_fk')->references('id')->on('defect_reasons')->nullOnDelete();
            $table->foreign('downgrade_item_id', 'pe_downgrade_item_fk')->references('id')->on('items')->nullOnDelete();
            $table->foreign('batch_id', 'pe_batch_id_fk')->references('id')->on('batches')->nullOnDelete();
            $table->foreign('operator_user_id', 'pe_operator_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('posted_by', 'pe_posted_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'pe_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'pe_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_entries');
    }
};
