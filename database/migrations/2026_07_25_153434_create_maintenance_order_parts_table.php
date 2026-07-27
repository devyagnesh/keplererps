<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('maintenance_order_parts');

        Schema::create('maintenance_order_parts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_order_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            $table->decimal('quantity', 18, 4);
            $table->decimal('rate', 18, 4)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('issued')->default(false)->index();
            $table->timestamp('issued_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('maintenance_order_id', 'mop_order_fk')->references('id')->on('maintenance_orders')->cascadeOnDelete();
            $table->foreign('item_id', 'mop_item_fk')->references('id')->on('items')->restrictOnDelete();
            $table->foreign('warehouse_id', 'mop_warehouse_fk')->references('id')->on('warehouses')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_order_parts');
    }
};
