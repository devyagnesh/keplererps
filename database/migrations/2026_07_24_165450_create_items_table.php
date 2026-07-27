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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 30)->unique();
            $table->string('item_name', 150)->index();
            $table->string('item_type', 30)->index();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('stock_uom_id')->constrained('uoms')->restrictOnDelete();
            $table->foreignId('purchase_uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->foreignId('sales_uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->foreignId('hsn_code_id')->constrained('hsn_codes')->restrictOnDelete();
            $table->decimal('gst_rate', 5, 2);
            $table->decimal('cess_rate', 5, 2)->default(0);
            $table->string('tracking_type', 20)->default('none')->index();
            $table->boolean('expiry_tracking')->default(false);
            $table->unsignedSmallInteger('shelf_life_days')->nullable();
            $table->decimal('standard_cost', 15, 4)->default(0);
            $table->decimal('selling_price', 15, 4)->nullable();
            $table->decimal('minimum_selling_price', 15, 4)->nullable();
            $table->decimal('min_stock', 15, 4)->nullable();
            $table->decimal('max_stock', 15, 4)->nullable();
            $table->unsignedSmallInteger('lead_time_days')->nullable();
            $table->foreignId('default_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('weight_per_unit', 15, 4)->nullable();
            $table->string('barcode', 64)->nullable()->unique();
            $table->boolean('is_purchasable')->default(false);
            $table->boolean('is_sellable')->default(false);
            $table->boolean('is_manufacturable')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('has_transactions')->default(false);
            $table->boolean('has_stock')->default(false)->comment('Locks type, UOM and tracking once true');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['item_type', 'is_active']);
            $table->index(['category_id', 'item_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
