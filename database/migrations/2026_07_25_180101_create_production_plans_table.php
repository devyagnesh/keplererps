<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique()->comment('System-generated production plan number');
            $table->date('document_date')->index();
            $table->date('plan_from_date')->comment('First date covered by the plan horizon');
            $table->date('plan_to_date')->comment('Last date covered by the plan horizon');
            $table->unsignedBigInteger('source_warehouse_id')->index()->comment('Component issue warehouse for generated work orders');
            $table->unsignedBigInteger('target_warehouse_id')->index()->comment('Finished goods warehouse for generated work orders');
            $table->string('status', 20)->default('draft')->index();
            $table->text('remarks')->nullable();
            $table->timestamp('posted_at')->nullable()->comment('When work orders were generated');
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'document_date']);
            $table->foreign('source_warehouse_id', 'pp_source_wh_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('target_warehouse_id', 'pp_target_wh_fk')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('posted_by', 'pp_posted_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'pp_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'pp_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('production_plan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_plan_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('bom_id')->index();
            $table->unsignedBigInteger('sales_order_id')->nullable()->index();
            $table->unsignedBigInteger('sales_order_item_id')->nullable()->index();
            $table->unsignedBigInteger('work_order_id')->nullable()->index()->comment('Draft work order created from this line');
            $table->decimal('planned_quantity', 18, 4);
            $table->date('required_date')->nullable()->comment('Customer required date driving the plan line');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('production_plan_id', 'ppi_plan_fk')->references('id')->on('production_plans')->cascadeOnDelete();
            $table->foreign('item_id', 'ppi_item_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('bom_id', 'ppi_bom_fk')->references('id')->on('boms')->restrictOnDelete();
            $table->foreign('sales_order_id', 'ppi_so_fk')->references('id')->on('sales_orders')->nullOnDelete();
            $table->foreign('sales_order_item_id', 'ppi_so_item_fk')->references('id')->on('sales_order_items')->nullOnDelete();
            $table->foreign('work_order_id', 'ppi_wo_fk')->references('id')->on('work_orders')->nullOnDelete();
        });

        Schema::create('production_plan_shortages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_plan_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->decimal('required_quantity', 18, 4);
            $table->decimal('available_quantity', 18, 4);
            $table->decimal('shortage_quantity', 18, 4)->comment('Quantity to procure or manufacture before release');
            $table->timestamps();

            $table->unique(['production_plan_id', 'item_id'], 'pps_plan_item_unique');
            $table->foreign('production_plan_id', 'pps_plan_fk')->references('id')->on('production_plans')->cascadeOnDelete();
            $table->foreign('item_id', 'pps_item_fk')->references('id')->on('items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_plan_shortages');
        Schema::dropIfExists('production_plan_items');
        Schema::dropIfExists('production_plans');
    }
};
