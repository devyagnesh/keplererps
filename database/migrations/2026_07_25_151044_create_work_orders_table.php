<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('work_orders');

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique();
            $table->date('document_date')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('bom_id')->index();
            $table->decimal('planned_quantity', 18, 4);
            $table->decimal('good_quantity', 18, 4)->default(0);
            $table->decimal('rejected_quantity', 18, 4)->default(0);
            $table->unsignedBigInteger('sales_order_id')->nullable()->index();
            $table->unsignedBigInteger('sales_order_item_id')->nullable()->index();
            $table->date('planned_start_date')->index();
            $table->date('planned_end_date')->index();
            $table->unsignedBigInteger('source_warehouse_id')->index();
            $table->unsignedBigInteger('target_warehouse_id')->index();
            $table->unsignedBigInteger('work_centre_id')->nullable()->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('status', 20)->default('draft')->index();
            $table->string('bom_version_reason', 255)->nullable();
            $table->decimal('standard_unit_cost', 18, 4)->default(0);
            $table->decimal('actual_material_cost', 18, 2)->default(0);
            $table->decimal('actual_machine_cost', 18, 2)->default(0);
            $table->decimal('actual_labour_cost', 18, 2)->default(0);
            $table->decimal('actual_overhead_cost', 18, 2)->default(0);
            $table->decimal('actual_total_cost', 18, 2)->default(0);
            $table->decimal('actual_unit_cost', 18, 4)->default(0);
            $table->decimal('cost_variance', 18, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'planned_end_date']);
            $table->foreign('item_id', 'wo_item_id_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('bom_id', 'wo_bom_id_fk')->references('id')->on('boms')->restrictOnDelete();
            $table->foreign('sales_order_id', 'wo_so_id_fk')->references('id')->on('sales_orders')->nullOnDelete();
            $table->foreign('sales_order_item_id', 'wo_soi_id_fk')->references('id')->on('sales_order_items')->nullOnDelete();
            $table->foreign('source_warehouse_id', 'wo_source_wh_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('target_warehouse_id', 'wo_target_wh_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('work_centre_id', 'wo_work_centre_fk')->references('id')->on('work_centres')->nullOnDelete();
            $table->foreign('released_by', 'wo_released_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('closed_by', 'wo_closed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'wo_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'wo_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
