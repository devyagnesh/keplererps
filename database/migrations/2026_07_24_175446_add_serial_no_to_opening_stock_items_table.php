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
        Schema::table('opening_stock_items', function (Blueprint $table) {
            $table->string('serial_no', 80)->nullable()->after('expiry_date')
                ->comment('Created as serial on post when tracking requires it');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opening_stock_items', function (Blueprint $table) {
            $table->dropColumn('serial_no');
        });
    }
};
