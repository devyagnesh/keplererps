<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('qc_template_parameters');

        Schema::create('qc_template_parameters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('qc_template_id')->index();
            $table->string('name', 150);
            $table->string('parameter_type', 20);
            $table->string('uom', 30)->nullable();
            $table->decimal('min_value', 18, 4)->nullable();
            $table->decimal('max_value', 18, 4)->nullable();
            $table->decimal('target_value', 18, 4)->nullable();
            $table->boolean('is_critical')->default(false);
            $table->string('test_method', 150)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('qc_template_id', 'qctp_template_fk')->references('id')->on('qc_templates')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_template_parameters');
    }
};
