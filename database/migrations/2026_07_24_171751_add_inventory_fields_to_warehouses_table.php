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
        Schema::table('warehouses', function (Blueprint $table) {
            $table->string('warehouse_type', 20)->default('store')->after('level')
                ->comment('store|quarantine|wip|rejection|with_vendor|in_transit');
            $table->boolean('is_leaf')->default(true)->after('depth')->index()
                ->comment('Only leaf locations may hold stock');
            $table->boolean('allow_negative_stock')->default(false)->after('is_leaf');
            $table->boolean('is_system')->default(false)->after('allow_negative_stock')
                ->comment('System locations such as quarantine cannot be deleted');
            $table->index('warehouse_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex(['warehouse_type']);
            $table->dropColumn(['warehouse_type', 'is_leaf', 'allow_negative_stock', 'is_system']);
        });
    }
};
