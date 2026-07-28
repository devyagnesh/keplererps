<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Application database seeder.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StateSeeder::class,
            TaxRateSeeder::class,
            UomSeeder::class,
            HsnCodeSeeder::class,
            AdminUserSeeder::class,
            PortalCustomerSeeder::class,
            CompanySeeder::class,
            SystemSettingSeeder::class,
            DocumentNumberSeriesSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            ManufacturingOperationSeeder::class,
            DefectReasonSeeder::class,
            WarehouseTypeSeeder::class,
            LedgerAccountSeeder::class,
            NotificationRuleSeeder::class,
            IndustryProfileSeeder::class,
            UiLabelSeeder::class,
        ]);
    }
}
