<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('qc_inspection_readings');

        Schema::create('qc_inspection_readings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qc_inspection_id')->index();
            $table->unsignedBigInteger('qc_template_parameter_id')->nullable()->index();
            $table->string('parameter_name', 150);
            $table->string('parameter_type', 20);
            $table->boolean('is_critical')->default(false);
            $table->decimal('min_value', 18, 4)->nullable();
            $table->decimal('max_value', 18, 4)->nullable();
            $table->decimal('target_value', 18, 4)->nullable();
            $table->decimal('numeric_value', 18, 4)->nullable();
            $table->string('pass_fail_value', 10)->nullable()->comment('pass|fail');
            $table->string('text_value', 500)->nullable();
            $table->string('result', 10)->nullable()->comment('pass|fail');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('qc_inspection_id', 'qcir_inspection_fk')->references('id')->on('qc_inspections')->cascadeOnDelete();
            $table->foreign('qc_template_parameter_id', 'qcir_param_fk')->references('id')->on('qc_template_parameters')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_inspection_readings');
    }
};
