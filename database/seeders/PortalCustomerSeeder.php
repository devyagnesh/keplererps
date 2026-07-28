<?php

namespace Database\Seeders;

use App\Enums\GstType;
use App\Enums\PartyStatus;
use App\Enums\PartyType;
use App\Models\Party;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds a demo customer party and linked portal login for /portal.
 */
class PortalCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $state = State::query()->where('code', '24')->first()
            ?? State::query()->first();

        $party = Party::query()->updateOrCreate(
            ['party_code' => 'CUST-PORTAL'],
            [
                'party_name' => 'Demo Portal Customer',
                'party_type' => PartyType::Customer,
                'gst_type' => GstType::Unregistered,
                'gstin' => null,
                'pan' => null,
                'billing_line1' => '12 Demo Industrial Estate',
                'billing_line2' => null,
                'billing_city' => 'Ahmedabad',
                'billing_state_id' => $state?->id,
                'billing_pin_code' => '380015',
                'billing_country' => 'India',
                'credit_limit' => 100000,
                'unlimited_credit' => false,
                'credit_days' => 30,
                'status' => PartyStatus::Active,
                'has_transactions' => false,
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'customer@keplererp.local'],
            [
                'name' => 'Portal Customer',
                'username' => 'customer',
                'mobile' => '9876500001',
                'password' => Hash::make('Customer@123'),
                'is_active' => true,
                'must_change_password' => false,
                'branch_id' => null,
                'party_id' => $party->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
