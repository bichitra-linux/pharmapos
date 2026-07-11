<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CustomerReturn;
use App\Models\Sale;
use App\Models\SupplierReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessReturn
{
    public function execute(string $direction, Request $request): object
    {
        $user = $request->user();

        return DB::transaction(function () use ($direction, $request, $user) {
            $companyId = $user->company_id;
            $outletId = $user->outlet_id;

            $returnNumber = $direction === 'sale'
                ? $this->generateReturnNumber('RET-', $companyId)
                : $this->generateReturnNumber('SRET-', $companyId);

            if ($direction === 'sale') {
                return $this->processSaleReturn($request, $user, $companyId, $outletId, $returnNumber);
            }

            return $this->processSupplierReturn($request, $user, $companyId, $outletId, $returnNumber);
        });
    }

    private function processSaleReturn(Request $request, $user, int $companyId, int $outletId, string $returnNumber): CustomerReturn
    {
        $sale = Sale::where('id', $request->sale_id)
            ->where('company_id', $companyId)
            ->with('items')
            ->firstOrFail();

        $return = CustomerReturn::create([
            'company_id' => $companyId,
            'outlet_id' => $outletId,
            'sale_id' => $sale->id,
            'processed_by' => $user->id,
            'return_number' => $returnNumber,
            'return_date' => now(),
            'total_amount' => $request->refund_amount,
            'refund_amount' => $request->refund_amount,
            'refund_method' => $request->refund_method ?? 'cash',
            'reason' => $request->reason,
        ]);

        $this->insertReturnItems('customer_return_items', 'return_id', $return->id, $request->items, $sale, $companyId);

        // Restore batch stock (customer items go back to inventory)
        foreach ($request->items as $item) {
            DB::table('medicine_batches')
                ->where('id', $item['batch_id'])
                ->increment('quantity_in_stock', $item['quantity']);
        }

        $totalReturned = CustomerReturn::where('sale_id', $sale->id)->sum('refund_amount');
        if ($totalReturned >= $sale->total_amount) {
            $sale->update(['payment_status' => 'refunded']);
        }

        return $return->load(['sale', 'customer', 'items.medicine']);
    }

    private function processSupplierReturn(Request $request, $user, int $companyId, int $outletId, string $returnNumber): object
    {
        $totalAmount = 0;

        $returnId = DB::table('supplier_returns')->insertGetId([
            'company_id' => $companyId,
            'outlet_id' => $outletId,
            'supplier_id' => $request->supplier_id,
            'purchase_id' => $request->purchase_id,
            'return_number' => $returnNumber,
            'return_date' => now(),
            'total_amount' => 0,
            'refund_status' => 'received',
            'reason' => $request->notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $batchIds = collect($request->items)->pluck('batch_id')->unique();
        $batches = DB::table('medicine_batches')
            ->where('outlet_id', $outletId)
            ->whereIn('id', $batchIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($request->items as $item) {
            $batch = $batches->get($item['batch_id']);
            if (! $batch || (float) $batch->quantity_in_stock < $item['quantity']) {
                throw new \InvalidArgumentException('Insufficient stock in batch for return.');
            }

            $itemTotal = (float) $batch->purchase_price_per_unit * $item['quantity'];
            $totalAmount += $itemTotal;

            DB::table('supplier_return_items')->insert([
                'supplier_return_id' => $returnId,
                'medicine_id' => $item['medicine_id'],
                'batch_id' => $item['batch_id'],
                'quantity' => $item['quantity'],
                'amount' => $itemTotal,
                'reason' => $item['reason'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('medicine_batches')
                ->where('id', $item['batch_id'])
                ->decrement('quantity_in_stock', $item['quantity']);
        }

        DB::table('supplier_returns')
            ->where('id', $returnId)
            ->update(['total_amount' => $totalAmount]);

        return DB::table('supplier_returns')->where('id', $returnId)->first();
    }

    private function insertReturnItems(string $table, string $foreignKey, int $returnId, array $items, ?Sale $sale, int $companyId): void
    {
        $batchIds = collect($items)->pluck('batch_id')->unique();
        $alreadyReturnedByItem = DB::table('customer_return_items')
            ->join('customer_returns', 'customer_return_items.return_id', '=', 'customer_returns.id')
            ->where('customer_returns.sale_id', $sale->id)
            ->whereIn('customer_return_items.batch_id', $batchIds)
            ->groupBy('customer_return_items.medicine_id', 'customer_return_items.batch_id')
            ->selectRaw('customer_return_items.medicine_id, customer_return_items.batch_id, SUM(customer_return_items.quantity) as total_returned')
            ->get()
            ->keyBy(fn ($row) => $row->medicine_id.'-'.$row->batch_id)
            ->map(fn ($row) => (float) $row->total_returned);

        foreach ($items as $item) {
            $saleItem = $sale->items->first(function ($si) use ($item) {
                return (int) $si->medicine_id === (int) $item['medicine_id']
                    && (int) $si->batch_id === (int) $item['batch_id'];
            });

            if (! $saleItem) {
                throw new \InvalidArgumentException("Sale item not found for medicine {$item['medicine_id']} batch {$item['batch_id']}.");
            }

            $key = $item['medicine_id'].'-'.$item['batch_id'];
            $alreadyReturned = (float) ($alreadyReturnedByItem[$key] ?? 0);
            $maxReturnable = (float) $saleItem->quantity - $alreadyReturned;

            if ((float) $item['quantity'] > $maxReturnable) {
                throw new \InvalidArgumentException(
                    "Return quantity ({$item['quantity']}) exceeds returnable quantity ({$maxReturnable})."
                );
            }

            DB::table($table)->insert([
                $foreignKey => $returnId,
                'medicine_id' => $item['medicine_id'],
                'batch_id' => $item['batch_id'],
                'quantity' => $item['quantity'],
                'amount' => (float) $saleItem->selling_price * $item['quantity'],
                'reason' => $item['reason'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function generateReturnNumber(string $prefix, int $companyId): string
    {
        $dateStr = now()->format('Ymd');
        $fullPrefix = "{$prefix}{$companyId}-{$dateStr}-";
        $table = str_starts_with($prefix, 'SRET') ? 'supplier_returns' : 'customer_returns';

        $lastReturn = DB::table($table)
            ->where('company_id', $companyId)
            ->where('return_number', 'like', $fullPrefix.'%')
            ->lockForUpdate()
            ->max('return_number');

        $sequence = 1;
        if ($lastReturn) {
            $lastSequence = (int) substr($lastReturn, -4);
            $sequence = $lastSequence >= 9999 ? 1 : $lastSequence + 1;
        }

        return $fullPrefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
