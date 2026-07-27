<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('maintenance_orders');

        Schema::create('maintenance_orders', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique();
            $table->date('document_date')->index();
            $table->string('order_type', 20)->index()->comment('preventive|breakdown');
            $table->string('status', 20)->default('open')->index();
            $table->unsignedBigInteger('work_centre_id')->index();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('reason', 255)->nullable();
            $table->text('action_taken')->nullable();
            $table->unsignedInteger('downtime_minutes')->default(0);
            $table->decimal('downtime_cost', 15, 2)->default(0);
            $table->unsignedBigInteger('reported_by')->nullable()->index();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'document_date']);
            $table->index(['work_centre_id', 'status']);
            $table->foreign('work_centre_id', 'mo_work_centre_fk')->references('id')->on('work_centres')->restrictOnDelete();
            $table->foreign('reported_by', 'mo_reported_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_to', 'mo_assigned_to_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'mo_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'mo_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_orders');
    }
};
