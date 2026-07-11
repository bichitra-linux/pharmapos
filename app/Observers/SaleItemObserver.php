<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SaleItem;

class SaleItemObserver
{
    public function creating(SaleItem $item): void
    {
        if ($item->medicine_id && ! $item->medicine_name) {
            $medicine = $item->medicine()->withoutGlobalScopes()->first();

            if ($medicine) {
                $item->medicine_name = $medicine->brand_name ?? $medicine->generic_name;
                $item->medicine_generic_name = $medicine->generic_name;
                $item->medicine_strength = $medicine->strength;
                $item->medicine_manufacturer = optional($medicine->manufacturer)->name;
                $item->medicine_dosage_form = $medicine->dosage_form?->value;
            }
        }
    }
}
