<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AdjustmentItem;
use App\Models\InventoryAdjustment;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Get stock summary for an outlet.
     */
    public function getStockSummary(int $outletId): array
    {
        $batches = MedicineBatch::where('outlet_id', $outletId)
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->with('medicine')
            ->get();

        $totalItems = $batches->count();
        $totalValue = $batches->sum(fn (MedicineBatch $b) => (float) $b->quantity_in_stock * (float) $b->purchase_price_per_unit);
        $totalRetailValue = $batches->sum(fn (MedicineBatch $b) => (float) $b->quantity_in_stock * (float) $b->selling_price_per_unit);
        $expiredItems = $batches->filter(fn (MedicineBatch $b) => $b->isExpired())->count();
        $expiringSoon = $batches->filter(fn (MedicineBatch $b) => ! $b->isExpired() && $b->daysUntilExpiry() <= 90)->count();

        return [
            'total_items' => $totalItems,
            'total_cost_value' => round($totalValue, 2),
            'total_retail_value' => round($totalRetailValue, 2),
            'expired_items' => $expiredItems,
            'expiring_soon' => $expiringSoon,
        ];
    }

    /**
     * Get low stock medicines for an outlet.
     */
    public function getLowStockMedicines(int $outletId, ?int $threshold = null): Collection
    {
        return Medicine::where('company_id', Auth::user()->company_id)
            ->where('is_active', true)
            ->whereHas('batches', function ($query) use ($outletId) {
                $query->where('outlet_id', $outletId)
                    ->where('is_active', true);
            })
            ->with(['batches' => function ($query) use ($outletId) {
                $query->where('outlet_id', $outletId)
                    ->where('is_active', true);
            }])
            ->get()
            ->filter(function (Medicine $medicine) use ($threshold) {
                $totalStock = $medicine->batches->sum('quantity_in_stock');
                $reorderLevel = $threshold ?? 10;

                return $totalStock <= $reorderLevel;
            })
            ->values();
    }

    /**
     * Get expiring medicines for an outlet.
     */
    public function getExpiringMedicines(int $outletId, int $days = 90): Collection
    {
        return MedicineBatch::where('outlet_id', $outletId)
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now())
            ->with(['medicine', 'medicine.manufacturer'])
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get expired medicines for an outlet.
     */
    public function getExpiredMedicines(int $outletId): Collection
    {
        return MedicineBatch::where('outlet_id', $outletId)
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->where('expiry_date', '<=', now())
            ->with(['medicine', 'medicine.manufacturer'])
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Select batches using FIFO (First Expiry, First Out).
     */
    public function selectFifoBatches(int $medicineId, int $outletId, float $quantity): Collection
    {
        $batches = MedicineBatch::where('medicine_id', $medicineId)
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->where('quantity_in_stock', '>', 0)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date', 'asc')
            ->get();

        $selected = collect();
        $remaining = $quantity;

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $available = (float) $batch->quantity_in_stock;
            $take = min($available, $remaining);

            $selected->push([
                'batch' => $batch,
                'quantity' => $take,
            ]);

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \InvalidArgumentException(
                "Insufficient stock for medicine #{$medicineId}. Requested: {$quantity}, Short: {$remaining}"
            );
        }

        return $selected;
    }

    /**
     * Reduce stock for a batch.
     */
    public function reduceStock(MedicineBatch $batch, float $quantity): void
    {
        if ((float) $batch->quantity_in_stock < $quantity) {
            throw new \InvalidArgumentException(
                "Insufficient stock in batch {$batch->batch_number}. Available: {$batch->quantity_in_stock}"
            );
        }

        $batch->decrement('quantity_in_stock', $quantity);

        if ((float) $batch->quantity_in_stock <= 0) {
            $batch->update(['is_active' => false]);
        }
    }

    /**
     * Increase stock for a batch.
     */
    public function increaseStock(MedicineBatch $batch, float $quantity): void
    {
        $batch->increment('quantity_in_stock', $quantity);

        if (! $batch->is_active) {
            $batch->update(['is_active' => true]);
        }
    }

    /**
     * Create a stock adjustment.
     */
    public function createAdjustment(array $data): InventoryAdjustment
    {
        return DB::transaction(function () use ($data) {
            $adjustment = InventoryAdjustment::create([
                'company_id' => Auth::user()->company_id,
                'outlet_id' => $data['outlet_id'],
                'type' => $data['adjustment_type'],
                'reason' => $data['reason'] ?? null,
                'adjusted_by' => Auth::id(),
            ]);

            foreach ($data['items'] as $item) {
                $batch = MedicineBatch::findOrFail($item['batch_id']);
                $quantityBefore = (float) $batch->quantity_in_stock;
                $quantityAdjusted = (float) $item['quantity_adjusted'];
                $quantityAfter = $quantityBefore + $quantityAdjusted;

                if ($quantityAfter < 0) {
                    throw new \InvalidArgumentException(
                        "Adjustment would result in negative stock for batch {$batch->batch_number}"
                    );
                }

                AdjustmentItem::create([
                    'adjustment_id' => $adjustment->id,
                    'medicine_id' => $batch->medicine_id,
                    'batch_id' => $batch->id,
                    'quantity' => $quantityAdjusted,
                    'reason' => $item['reason'] ?? null,
                ]);

                $batch->update(['quantity_in_stock' => $quantityAfter]);

                if ($quantityAfter <= 0) {
                    $batch->update(['is_active' => false]);
                }
            }

            return $adjustment->load('items');
        });
    }
}
