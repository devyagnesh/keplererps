<?php

namespace Database\Seeders;

use App\Enums\BalanceSide;
use App\Enums\LedgerAccountType;
use App\Models\LedgerAccount;
use Illuminate\Database\Seeder;

/**
 * Seeds the default chart of accounts, including the control accounts used by auto-posting.
 */
class LedgerAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->accounts() as $account) {
            LedgerAccount::query()->updateOrCreate(
                ['code' => $account['code']],
                array_merge([
                    'opening_balance' => 0,
                    'opening_balance_side' => BalanceSide::Debit->value,
                    'is_active' => true,
                    'is_system' => true,
                ], $account)
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function accounts(): array
    {
        return [
            ['code' => '1000', 'name' => 'Cash in Hand', 'account_type' => LedgerAccountType::Asset->value, 'account_group' => 'Cash & Bank'],
            ['code' => '1100', 'name' => 'Bank Accounts', 'account_type' => LedgerAccountType::Asset->value, 'account_group' => 'Cash & Bank'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'account_type' => LedgerAccountType::Asset->value, 'account_group' => 'Current Assets'],
            ['code' => '1300', 'name' => 'Inventory', 'account_type' => LedgerAccountType::Asset->value, 'account_group' => 'Current Assets'],
            ['code' => '1310', 'name' => 'Input CGST', 'account_type' => LedgerAccountType::Asset->value, 'account_group' => 'Duties & Taxes'],
            ['code' => '1320', 'name' => 'Input SGST', 'account_type' => LedgerAccountType::Asset->value, 'account_group' => 'Duties & Taxes'],
            ['code' => '1330', 'name' => 'Input IGST', 'account_type' => LedgerAccountType::Asset->value, 'account_group' => 'Duties & Taxes'],
            ['code' => '2100', 'name' => 'Accounts Payable', 'account_type' => LedgerAccountType::Liability->value, 'account_group' => 'Current Liabilities', 'opening_balance_side' => BalanceSide::Credit->value],
            ['code' => '2300', 'name' => 'Salary Payable', 'account_type' => LedgerAccountType::Liability->value, 'account_group' => 'Current Liabilities', 'opening_balance_side' => BalanceSide::Credit->value],
            ['code' => '2210', 'name' => 'Output CGST', 'account_type' => LedgerAccountType::Liability->value, 'account_group' => 'Duties & Taxes', 'opening_balance_side' => BalanceSide::Credit->value],
            ['code' => '2220', 'name' => 'Output SGST', 'account_type' => LedgerAccountType::Liability->value, 'account_group' => 'Duties & Taxes', 'opening_balance_side' => BalanceSide::Credit->value],
            ['code' => '2230', 'name' => 'Output IGST', 'account_type' => LedgerAccountType::Liability->value, 'account_group' => 'Duties & Taxes', 'opening_balance_side' => BalanceSide::Credit->value],
            ['code' => '3000', 'name' => 'Owner Capital', 'account_type' => LedgerAccountType::Equity->value, 'account_group' => 'Capital', 'opening_balance_side' => BalanceSide::Credit->value],
            ['code' => '4100', 'name' => 'Sales Revenue', 'account_type' => LedgerAccountType::Income->value, 'account_group' => 'Direct Income', 'opening_balance_side' => BalanceSide::Credit->value],
            ['code' => '4200', 'name' => 'Other Income', 'account_type' => LedgerAccountType::Income->value, 'account_group' => 'Indirect Income', 'opening_balance_side' => BalanceSide::Credit->value],
            ['code' => '5100', 'name' => 'Purchases', 'account_type' => LedgerAccountType::Expense->value, 'account_group' => 'Direct Expense'],
            ['code' => '5200', 'name' => 'Freight & Handling', 'account_type' => LedgerAccountType::Expense->value, 'account_group' => 'Direct Expense'],
            ['code' => '5300', 'name' => 'Salaries & Wages', 'account_type' => LedgerAccountType::Expense->value, 'account_group' => 'Indirect Expense'],
            ['code' => '5900', 'name' => 'Round Off', 'account_type' => LedgerAccountType::Expense->value, 'account_group' => 'Indirect Expense'],
        ];
    }
}
