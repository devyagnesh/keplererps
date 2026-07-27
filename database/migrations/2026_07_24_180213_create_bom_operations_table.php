<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bom_operations');

        Schema::create('bom_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bom_id')->index();
            $table->unsignedInteger('sequence');
            $table->unsignedBigInteger('manufacturing_operation_id');
            $table->unsignedBigInteger('work_centre_id')->nullable();
            $table->decimal('setup_time_minutes', 12, 2)->default(0);
            $table->decimal('run_time_per_unit_minutes', 12, 4);
            $table->decimal('machine_rate_per_hour', 12, 2)->default(0);
            $table->decimal('labour_rate_per_hour', 12, 2)->default(0);
            $table->unsignedTinyInteger('operators_required')->default(1);
            $table->boolean('is_outsourced')->default(false);
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->decimal('outsourced_rate', 12, 4)->nullable();
            $table->boolean('quality_check_required')->default(false);
            $table->timestamps();

            $table->unique(['bom_id', 'sequence'], 'bom_operations_bom_seq_unique');
            $table->foreign('bom_id', 'bom_operations_bom_id_fk')->references('id')->on('boms')->cascadeOnDelete();
            $table->foreign('manufacturing_operation_id', 'bom_operations_mfg_op_id_fk')->references('id')->on('manufacturing_operations')->restrictOnDelete();
            $table->foreign('work_centre_id', 'bom_operations_work_centre_id_fk')->references('id')->on('work_centres')->nullOnDelete();
            $table->foreign('vendor_id', 'bom_operations_vendor_id_fk')->references('id')->on('parties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_operations');
    }
};
