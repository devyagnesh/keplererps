<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('work_order_operations');

        Schema::create('work_order_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id')->index();
            $table->unsignedInteger('sequence')->default(10);
            $table->unsignedBigInteger('manufacturing_operation_id')->nullable();
            $table->unsignedBigInteger('work_centre_id')->nullable();
            $table->decimal('setup_time_minutes', 12, 2)->default(0);
            $table->decimal('run_time_per_unit_minutes', 12, 4)->default(0);
            $table->decimal('machine_rate_per_hour', 15, 4)->default(0);
            $table->decimal('labour_rate_per_hour', 15, 4)->default(0);
            $table->timestamps();

            $table->foreign('work_order_id', 'woo_wo_id_fk')->references('id')->on('work_orders')->cascadeOnDelete();
            $table->foreign('manufacturing_operation_id', 'woo_mfg_op_fk')->references('id')->on('manufacturing_operations')->nullOnDelete();
            $table->foreign('work_centre_id', 'woo_wc_fk')->references('id')->on('work_centres')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_operations');
    }
};
