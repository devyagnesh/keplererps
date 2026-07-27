<?php

namespace Database\Seeders;

use App\Services\UiLabelService;
use Illuminate\Database\Seeder;

/**
 * Seeds English / Hindi / Gujarati UI label packs (C7).
 */
class UiLabelSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(UiLabelService::class);

        foreach ($this->packs() as $locale => $labels) {
            foreach ($labels as $key => $value) {
                $service->upsert([
                    'locale' => $locale,
                    'label_key' => $key,
                    'label_value' => $value,
                ]);
            }
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function packs(): array
    {
        return [
            'en' => [
                'work_order' => 'Work Order',
                'sales_order' => 'Sales Order',
                'purchase_order' => 'Purchase Order',
                'delivery_challan' => 'Delivery Challan',
                'stock_transfer' => 'Stock Transfer',
                'dashboard' => 'Dashboard',
                'save' => 'Save',
                'cancel' => 'Cancel',
                'approve' => 'Approve',
                'industry_profile' => 'Industry Profile',
            ],
            'hi' => [
                'work_order' => 'कार्य आदेश',
                'sales_order' => 'विक्रय आदेश',
                'purchase_order' => 'क्रय आदेश',
                'delivery_challan' => 'डिलीवरी चालान',
                'stock_transfer' => 'स्टॉक स्थानांतरण',
                'dashboard' => 'डैशबोर्ड',
                'save' => 'सहेजें',
                'cancel' => 'रद्द करें',
                'approve' => 'स्वीकृत करें',
                'industry_profile' => 'उद्योग प्रोफ़ाइल',
            ],
            'gu' => [
                'work_order' => 'કાર્ય ઓર્ડર',
                'sales_order' => 'વેચાણ ઓર્ડર',
                'purchase_order' => 'ખરીદી ઓર્ડર',
                'delivery_challan' => 'ડિલિવરી ચલાન',
                'stock_transfer' => 'સ્ટોક ટ્રાન્સફર',
                'dashboard' => 'ડેશબોર્ડ',
                'save' => 'સાચવો',
                'cancel' => 'રદ કરો',
                'approve' => 'મંજૂર કરો',
                'industry_profile' => 'ઉદ્યોગ પ્રોફાઇલ',
            ],
        ];
    }
}
