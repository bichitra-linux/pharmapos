<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class PurchaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Purchase::where('company_id', $request->user()->company_id)
            ->where('outlet_id', $request->user()->outlet_id)
            ->with(['supplier:id,name', 'items.medicine']);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('search')) {
            $query->where('purchase_number', 'like', "%{$request->search}%");
        }

        $purchases = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $purchases,
        ]);
    }

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $companyId = $request->user()->company_id;
            $dateStr = now()->format('Ymd');

            // Use MAX to avoid race condition
            $lastPurchase = Purchase::where('company_id', $companyId)
                ->whereDate('created_at', today())
                ->lockForUpdate()
                ->max('purchase_number');

            $sequence = 1;
            if ($lastPurchase) {
                $lastSequence = (int) substr($lastPurchase, -4);
                $sequence = $lastSequence + 1;
            }
            $purchaseNumber = "PO-{$companyId}-{$dateStr}-".str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $vatTotal = 0;

            foreach ($request->items as $item) {
                $lineTotal = $item['quantity'] * $item['purchase_price'];
                $itemVat = ($lineTotal * ($item['vat_rate'] ?? 13)) / 100;
                $subtotal += $lineTotal;
                $vatTotal += $itemVat;
            }

            $discount = (float) ($request->discount ?? 0);
            $total = $subtotal + $vatTotal - $discount;
            $paidAmount = (float) ($request->paid_amount ?? 0);
            $dueAmount = max(0, $total - $paidAmount);

            $purchase = Purchase::create([
                'company_id' => $companyId,
                'outlet_id' => $request->user()->outlet_id,
                'supplier_id' => $request->supplier_id,
                'purchase_number' => $purchaseNumber,
                'purchase_date' => $request->purchase_date ?? Carbon::today(),
                'grn_number' => $request->grn_number,
                'subtotal' => round($subtotal, 2),
                'vat' => round($vatTotal, 2),
                'discount' => $discount,
                'total' => round($total, 2),
                'paid_amount' => $paidAmount,
                'due_amount' => round($dueAmount, 2),
                'payment_status' => $dueAmount > 0 ? ($paidAmount > 0 ? 'partial' : 'due') : 'paid',
                'status' => 'draft',
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                DB::table('purchase_items')->insert([
                    'purchase_id' => $purchase->id,
                    'medicine_id' => $item['medicine_id'],
                    'batch_number' => $item['batch_number'],
                    'manufacturing_date' => $item['manufacturing_date'] ?? null,
                    'expiry_date' => $item['expiry_date'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'mrp' => $item['mrp'] ?? $item['purchase_price'],
                    'selling_price' => $item['selling_price'] ?? $item['purchase_price'],
                    'total' => $item['quantity'] * $item['purchase_price'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $purchase->load(['supplier', 'items.medicine']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully.',
                'data' => $purchase,
            ], 201);
        });
    }

    public function show(Request $request, Purchase $purchase): JsonResponse
    {
        if ($purchase->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $purchase->load(['supplier', 'items.medicine']);

        return response()->json([
            'success' => true,
            'data' => $purchase,
        ]);
    }

    public function update(StorePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        if ($purchase->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        if ($purchase->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft purchases can be updated.',
            ], 422);
        }

        $purchase->update($request->only([
            'supplier_id', 'discount', 'notes',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Purchase updated successfully.',
            'data' => $purchase->fresh(),
        ]);
    }

    public function receive(Request $request, Purchase $purchase): JsonResponse
    {
        if ($purchase->company_id !== $request->user()->company_id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.purchase_item_id' => 'required|exists:purchase_items,id',
            'items.*.received_quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            return DB::transaction(function () use ($request, $purchase) {
            $outletId = $request->user()->outlet_id;
            $companyId = $request->user()->company_id;

            // Batch-fetch purchase items to avoid N+1 queries
            $purchaseItemIds = collect($request->items)->pluck('purchase_item_id');
            $purchaseItems = DB::table('purchase_items')
                ->where('purchase_id', $purchase->id)
                ->whereIn('id', $purchaseItemIds)
                ->get()
                ->keyBy('id');

            foreach ($request->items as $receivedItem) {
                $purchaseItem = $purchaseItems->get($receivedItem['purchase_item_id']);

                if (! $purchaseItem) {
                    throw new \Exception("Purchase item {$receivedItem['purchase_item_id']} not found.");
                }

                // Validate expiry date is in the future
                if (Carbon::parse($purchaseItem->expiry_date)->isPast()) {
                    throw new \Exception("Cannot receive item with expired batch {$purchaseItem->batch_number}.");
                }

                // Track received quantity separately (keep 'quantity' as ordered amount)
                DB::table('purchase_items')
                    ->where('id', $purchaseItem->id)
                    ->update([
                        'updated_at' => now(),
                    ]);
                DB::table('purchase_items')
                    ->where('id', $purchaseItem->id)
                    ->increment('received_quantity', (float) $receivedItem['received_quantity']);

                // Create or update batch
                $existingBatch = DB::table('medicine_batches')
                    ->where('medicine_id', $purchaseItem->medicine_id)
                    ->where('batch_number', $purchaseItem->batch_number)
                    ->where('outlet_id', $outletId)
                    ->first();

                // Get units_per_pack for piece calculation
                $medicine = DB::table('medicines')->find($purchaseItem->medicine_id);
                $packSize = (int) ($medicine->units_per_pack ?? 1);
                $receivedQty = (float) $receivedItem['received_quantity'];

                if ($existingBatch) {
                    DB::table('medicine_batches')
                        ->where('id', $existingBatch->id)
                        ->update(['updated_at' => now()]);
                    DB::table('medicine_batches')
                        ->where('id', $existingBatch->id)
                        ->increment('quantity_in_stock', $receivedQty);
                    DB::table('medicine_batches')
                        ->where('id', $existingBatch->id)
                        ->increment('quantity_in_pieces', $receivedQty * $packSize);
                    DB::table('medicine_batches')
                        ->where('id', $existingBatch->id)
                        ->increment('received_pieces', $receivedQty * $packSize);
                } else {
                    DB::table('medicine_batches')->insert([
                        'company_id' => $companyId,
                        'medicine_id' => $purchaseItem->medicine_id,
                        'outlet_id' => $outletId,
                        'batch_number' => $purchaseItem->batch_number,
                        'manufacturing_date' => $purchaseItem->manufacturing_date,
                        'expiry_date' => $purchaseItem->expiry_date,
                        'quantity_in_stock' => $receivedQty,
                        'quantity_in_pieces' => $receivedQty * $packSize,
                        'received_pieces' => $receivedQty * $packSize,
                        'purchase_price_per_unit' => $purchaseItem->purchase_price,
                        'mrp_per_unit' => $purchaseItem->mrp,
                        'selling_price_per_unit' => $purchaseItem->selling_price,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Check if fully received using received_quantity vs ordered quantity
            $allItems = DB::table('purchase_items')
                ->where('purchase_id', $purchase->id)
                ->get();

            $fullyReceived = $allItems->every(fn ($item) => (float) ($item->received_quantity ?? 0) >= (float) $item->quantity);

            $purchase->update([
                'status' => $fullyReceived ? 'received' : 'partial',
            ]);

            $purchase->load(['supplier', 'items.medicine']);

            return response()->json([
                'success' => true,
                'message' => $fullyReceived ? 'Purchase fully received.' : 'Partial receipt recorded.',
                'data' => $purchase,
            ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
