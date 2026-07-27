<?php

namespace App\Enums;

/**
 * Channel a lead arrived through (M05).
 */
enum LeadSource: string
{
    case Referral = 'referral';
    case Website = 'website';
    case Exhibition = 'exhibition';
    case ColdCall = 'cold_call';
    case Existing = 'existing_customer';
    case IndiaMart = 'indiamart';
    case TradeIndia = 'tradeindia';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Referral => 'Referral',
            self::Website => 'Website / Enquiry',
            self::Exhibition => 'Exhibition',
            self::ColdCall => 'Cold Call',
            self::Existing => 'Existing Customer',
            self::IndiaMart => 'IndiaMART',
            self::TradeIndia => 'TradeIndia',
            self::Other => 'Other',
        };
    }
}
