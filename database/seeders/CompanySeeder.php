<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\State;
use Illuminate\Database\Seeder;

/**
 * Seeds a starter company singleton for local development.
 */
class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Company::query()->exists()) {
            return;
        }

        $gujarat = State::query()->where('code', '24')->firstOrFail();

        Company::query()->create([
            'legal_name' => 'Kepler Manufacturing Private Limited',
            'trade_name' => 'Kepler ERP Demo',
            'is_gst_registered' => false,
            'gstin' => null,
            'pan' => 'ABCDE1234F',
            'cin' => null,
            'registered_address' => 'Plot 12, GIDC Industrial Estate, Ahmedabad',
            'state_id' => $gujarat->id,
            'pin_code' => '380015',
            'phone' => '9876543210',
            'email' => 'accounts@keplererp.local',
            'fy_start_month' => 4,
            'fy_start_day' => 1,
            'base_currency' => 'INR',
            'amount_decimals' => 2,
            'quantity_decimals' => 3,
            'has_transactions' => false,
        ]);
    }
}
