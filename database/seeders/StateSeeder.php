<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;

/**
 * Seeds Indian GST state codes.
 */
class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            ['code' => '01', 'name' => 'Jammu and Kashmir'],
            ['code' => '02', 'name' => 'Himachal Pradesh'],
            ['code' => '03', 'name' => 'Punjab'],
            ['code' => '04', 'name' => 'Chandigarh', 'is_union_territory' => true],
            ['code' => '05', 'name' => 'Uttarakhand'],
            ['code' => '06', 'name' => 'Haryana'],
            ['code' => '07', 'name' => 'Delhi', 'is_union_territory' => true],
            ['code' => '08', 'name' => 'Rajasthan'],
            ['code' => '09', 'name' => 'Uttar Pradesh'],
            ['code' => '10', 'name' => 'Bihar'],
            ['code' => '11', 'name' => 'Sikkim'],
            ['code' => '12', 'name' => 'Arunachal Pradesh'],
            ['code' => '13', 'name' => 'Nagaland'],
            ['code' => '14', 'name' => 'Manipur'],
            ['code' => '15', 'name' => 'Mizoram'],
            ['code' => '16', 'name' => 'Tripura'],
            ['code' => '17', 'name' => 'Meghalaya'],
            ['code' => '18', 'name' => 'Assam'],
            ['code' => '19', 'name' => 'West Bengal'],
            ['code' => '20', 'name' => 'Jharkhand'],
            ['code' => '21', 'name' => 'Odisha'],
            ['code' => '22', 'name' => 'Chhattisgarh'],
            ['code' => '23', 'name' => 'Madhya Pradesh'],
            ['code' => '24', 'name' => 'Gujarat'],
            ['code' => '26', 'name' => 'Dadra and Nagar Haveli and Daman and Diu', 'is_union_territory' => true],
            ['code' => '27', 'name' => 'Maharashtra'],
            ['code' => '29', 'name' => 'Karnataka'],
            ['code' => '30', 'name' => 'Goa'],
            ['code' => '31', 'name' => 'Lakshadweep', 'is_union_territory' => true],
            ['code' => '32', 'name' => 'Kerala'],
            ['code' => '33', 'name' => 'Tamil Nadu'],
            ['code' => '34', 'name' => 'Puducherry', 'is_union_territory' => true],
            ['code' => '35', 'name' => 'Andaman and Nicobar Islands', 'is_union_territory' => true],
            ['code' => '36', 'name' => 'Telangana'],
            ['code' => '37', 'name' => 'Andhra Pradesh'],
            ['code' => '38', 'name' => 'Ladakh', 'is_union_territory' => true],
        ];

        foreach ($states as $state) {
            State::query()->updateOrCreate(
                ['code' => $state['code']],
                [
                    'name' => $state['name'],
                    'is_union_territory' => $state['is_union_territory'] ?? false,
                    'is_active' => true,
                ]
            );
        }
    }
}
