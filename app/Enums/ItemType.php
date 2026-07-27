<?php

namespace App\Enums;

/**
 * Item classification controlling form sections and stock behaviour.
 */
enum ItemType: string
{
    case RawMaterial = 'raw_material';
    case Consumable = 'consumable';
    case SemiFinished = 'semi_finished';
    case FinishedGoods = 'finished_goods';
    case PackingMaterial = 'packing_material';
    case Scrap = 'scrap';
    case SparePart = 'spare_part';
    case Service = 'service';

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
            self::RawMaterial => 'Raw Material',
            self::Consumable => 'Consumable',
            self::SemiFinished => 'Semi-Finished',
            self::FinishedGoods => 'Finished Goods',
            self::PackingMaterial => 'Packing Material',
            self::Scrap => 'Scrap',
            self::SparePart => 'Spare Part',
            self::Service => 'Service',
        };
    }

    public function isStocked(): bool
    {
        return $this !== self::Service;
    }
}
