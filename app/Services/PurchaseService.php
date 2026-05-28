<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\MedicineBatch;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseService
{
    /**
     * Create a purchase order.
     */
    public function createPurchase(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $company = Auth::user()->company;
            $vatRate = (float) ($company->settings['vat_rate'] ?? 13.0);

            $subtotal = 0.0;
            $totalTaxable = 0.0;
            $totalVat = 0.0;
            $totalDiscount = 0.0;

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity_ordered'];
                $unitPrice = (float) $item['purchase_price_per_unit'];
                $lineTotal = $unitPrice * $quantity;

                $discountPercentage = (float) ($item['discount_percentage'] ?? 0);
                $discountAmount = ($lineTotal * $discountPercentage) / 100;
                $taxableAmount = $lineTotal - $discountAmount;
                $vatAmount = ($taxableAmount * $vatRate) / 100;

                $subtotal += $lineTotal;
                $totalDiscount += $discountAmount;
                $totalTaxable += $taxableAmount;
                $totalVat += $vatAmount;
            }

            $totalAmount = round($totalTaxable + $totalVat, 2);
            $paidAmount = (float) ($data['paid_amount'] ?? 0);

            $paymentStatus = match (true) {
                $paidAmount >= $totalAmount => PaymentStatus::Paid,
                $paidAmount > 0 => PaymentStatus::Partial,
                default => PaymentStatus::Due,
            };

            $purchase = Purchase::create([
                'company_id' => Auth::user()->company_id,
                'outlet_id' => $data['outlet_id'],
                'supplier_id' => $data['supplier_id'],
                'purchase_number' => $this->generatePurchaseNumber(Auth::user()->company_id),
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'supplier_invoice_date' => $data['supplier_invoice_date'] ?? null,
                'subtotal' => round($subtotal, 2),
                'discount_amount' => round($totalDiscount, 2),
                'taxable_amount' => round($totalTaxable, 2),
                'vat_amount' => round($totalVat, 2),
                'total_amount' => $totalAmount,
                'paid_amount' => round($paidAmount, 2),
                'payment_status' => $paymentStatus,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'purchase_date' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $quantity = (float) $item['quantity_ordered'];
                $unitPrice = (float) $item['purchase_price_per_unit'];
                $lineTotal = $unitPrice * $quantity;
                $discountPercentage = (float) ($item['discount_percentage'] ?? 0);
                $discountAmount = ($lineTotal * $discountPercentage) / 100;
                $taxableAmount = $lineTotal - $discountAmount;
                $vatAmount = ($taxableAmount * $vatRate) / 100;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'medicine_id' => $item['medicine_id'],
                    'medicine_name' => $item['medicine_name'] ?? null,
                    'batch_number' => $item['batch_number'],
                    'expiry_date' => $item['expiry_date'],
                    'quantity_ordered' => $quantity,
                    'quantity_received' => 0,
                    'purchase_price_per_unit' => $unitPrice,
                    'mrp_per_unit' => $item['mrp_per_unit'],
                    'selling_price_per_unit' => $item['selling_price_per_unit'],
                    'discount_percentage' => $discountPercentage,
                    'discount_amount' => round($discountAmount, 2),
                    'taxable_amount' => round($taxableAmount, 2),
                    'vat_rate' => $vatRate,
                    'vat_amount' => round($vatAmount, 2),
                    'total_amount' => round($taxableAmount + $vatAmount, 2),
                ]);
            }

            return $purchase->load('items');
        });
    }

    /**
     * Process GRN (Goods Received Note) - receive items and create/update batches.
     */
    public function processGrn(int $purchaseId, array $receivedItems): Purchase
    {
        return DB::transaction(function () use ($purchaseId, $receivedItems) {
            $purchase = Purchase::findOrFail($purchaseId);

            if ($purchase->status === 'received') {
                throw new InvalidArgumentException('Purchase already fully received.');
            }

            foreach ($receivedItems as $received) {
                $purchaseItem = PurchaseItem::where('purchase_id', $purchaseId)
                    ->where('id', $received['purchase_item_id'])
                    ->firstOrFail();

                $quantityReceived = (float) $received['quantity_received'];
                $totalOrdered = (float) $purchaseItem->quantity_ordered;
                $alreadyReceived = (float) $purchaseItem->quantity_received;

                if (($alreadyReceived + $quantityReceived) > $totalOrdered) {
                    throw new InvalidArgumentException(
                        "Received quantity exceeds ordered for item #{$purchaseItem->id}. Ordered: {$totalOrdered}, Already received: {$alreadyReceived}"
                    );
                }

                $purchaseItem->increment('quantity_received', $quantityReceived);

                // Create or update batch
                $batch = MedicineBatch::where('medicine_id', $purchaseItem->medicine_id)
                    ->where('outlet_id', $purchase->outlet_id)
                    ->where('batch_number', $purchaseItem->batch_number)
                    ->first();

                if ($batch) {
                    $batch->increment('quantity_in_stock', $quantityReceived);
                    $batch->update([
                        'purchase_price_per_unit' => $purchaseItem->purchase_price_per_unit,
                        'mrp_per_unit' => $purchaseItem->mrp_per_unit,
                        'selling_price_per_unit' => $purchaseItem->selling_price_per_unit,
                    ]);
                } else {
                    MedicineBatch::create([
                        'company_id' => $purchase->company_id,
                        'medicine_id' => $purchaseItem->medicine_id,
                        'outlet_id' => $purchase->outlet_id,
                        'batch_number' => $purchaseItem->batch_number,
                        'expiry_date' => $purchaseItem->expiry_date,
                        'quantity_in_stock' => $quantityReceived,
                        'purchase_price_per_unit' => $purchaseItem->purchase_price_per_unit,
                        'mrp_per_unit' => $purchaseItem->mrp_per_unit,
                        'selling_price_per_unit' => $purchaseItem->selling_price_per_unit,
                        'supplier_id' => $purchase->supplier_id,
                        'purchase_id' => $purchase->id,
                        'is_active' => true,
                    ]);
                }
            }

            // Check if all items are fully received
            $allReceived = $purchase->items->every(
                fn (PurchaseItem $item) => (float) $item->quantity_received >= (float) $item->quantity_ordered
            );

            $purchase->update([
                'status' => $allReceived ? 'received' : 'partial',
                'received_at' => $allReceived ? now() : null,
            ]);

            return $purchase->load('items');
        });
    }

    /**
     * Record a supplier payment.
     */
    public function recordSupplierPayment(int $purchaseId, array $data): SupplierPayment
    {
        return DB::transaction(function () use ($purchaseId, $data) {
            $purchase = Purchase::findOrFail($purchaseId);

            $payment = SupplierPayment::create([
                'company_id' => $purchase->company_id,
                'supplier_id' => $purchase->supplier_id,
                'purchase_id' => $purchase->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'paid_at' => now(),
            ]);

            $totalPaid = SupplierPayment::where('purchase_id', $purchaseId)->sum('amount');
            $purchase->update([
                'paid_amount' => $totalPaid,
                'payment_status' => $totalPaid >= (float) $purchase->total_amount
                    ? PaymentStatus::Paid
                    : PaymentStatus::Partial,
            ]);

            return $payment;
        });
    }

    /**
     * Generate purchase number.
     */
    public function generatePurchaseNumber(int $companyId): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $last = Purchase::where('company_id', $companyId)
            ->where('purchase_number', 'like', "{$prefix}-{$date}-%")
            ->orderByDesc('purchase_number')
            ->first();

        $sequence = 1;
        if ($last) {
            $lastSequence = (int) substr($last->purchase_number, -5);
            $sequence = $lastSequence + 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $date, $sequence);
    }
}
