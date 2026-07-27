<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('qc_templates');

        Schema::create('qc_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('inspection_type', 30)->index();
            $table->unsignedBigInteger('item_id')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('sampling_plan', 30)->default('sqrt_plus_one');
            $table->decimal('sampling_value', 12, 4)->nullable()->comment('Fixed qty or percentage');
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('item_id', 'qct_item_fk')->references('id')->on('items')->nullOnDelete();
            $table->foreign('category_id', 'qct_category_fk')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('created_by', 'qct_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'qct_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_templates');
    }
};
