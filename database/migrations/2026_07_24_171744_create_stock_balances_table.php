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
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->restrictOnDelete();
            $table->unsignedBigInteger('batch_key')->default(0)->comment('Equals batch_id, or 0 when unbatched');
            $table->decimal('qty', 18, 4)->default(0)->comment('Physical quantity');
            $table->decimal('committed_qty', 18, 4)->default(0);
            $table->decimal('on_order_qty', 18, 4)->default(0);
            $table->decimal('value', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['item_id', 'warehouse_id', 'batch_key']);
            $table->index(['warehouse_id', 'item_id']);
            $table->index(['item_id', 'qty']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
