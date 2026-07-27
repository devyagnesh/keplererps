<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_challan_id')->nullable()->after('sales_order_id')->index();
            $table->foreign('delivery_challan_id', 'si_dc_id_fk')
                ->references('id')
                ->on('delivery_challans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign('si_dc_id_fk');
            $table->dropColumn('delivery_challan_id');
        });
    }
};
