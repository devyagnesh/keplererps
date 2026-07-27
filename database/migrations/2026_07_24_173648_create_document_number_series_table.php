<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_number_series', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 40)->index();
            $table->foreignId('financial_year_id')->nullable()->constrained('financial_years')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('prefix', 20);
            $table->string('suffix', 20)->nullable();
            $table->string('separator', 5)->default('-');
            $table->unsignedTinyInteger('padding')->default(5);
            $table->unsignedInteger('start_number')->default(1);
            $table->unsignedInteger('next_number')->default(1);
            $table->boolean('include_fy_code')->default(false);
            $table->boolean('reset_yearly')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['document_type', 'financial_year_id', 'branch_id'], 'doc_series_unique');
            $table->index(['document_type', 'branch_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_number_series');
    }
};
