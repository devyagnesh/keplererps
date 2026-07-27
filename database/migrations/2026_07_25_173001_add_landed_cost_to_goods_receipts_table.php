<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->decimal('freight_charges', 15, 2)->default(0)->after('vehicle_number')
                ->comment('Inbound freight to be absorbed into item cost');
            $table->decimal('other_charges', 15, 2)->default(0)->after('freight_charges')
                ->comment('Loading, insurance and similar charges absorbed into item cost');
            $table->string('charge_allocation_basis', 20)->default('value')->after('other_charges')
                ->comment('Basis used to spread charges across receipt lines');
        });

        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->decimal('allocated_charge', 15, 2)->default(0)->after('rate')
                ->comment('Share of header charges allocated to this line on post');
            $table->decimal('landed_rate', 18, 4)->nullable()->after('allocated_charge')
                ->comment('Purchase rate plus allocated charge per accepted unit');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropColumn(['allocated_charge', 'landed_rate']);
        });

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn(['freight_charges', 'other_charges', 'charge_allocation_basis']);
        });
    }
};
