<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('work_order_components');

        Schema::create('work_order_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('uom_id');
            $table->decimal('required_quantity', 18, 4);
            $table->decimal('issued_quantity', 18, 4)->default(0);
            $table->boolean('is_critical')->default(false);
            $table->string('issue_method', 20)->default('manual');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('work_order_id', 'woc_wo_id_fk')->references('id')->on('work_orders')->cascadeOnDelete();
            $table->foreign('item_id', 'woc_item_id_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('uom_id', 'woc_uom_id_fk')->references('id')->on('uoms')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_components');
    }
};
