<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\SaleType;
use App\Models\Customer;
use App\Models\MedicineBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $company = Auth::user()->company;
            $vatRate = (float) ($company->settings['vat_rate'] ?? 13.0);

            $subtotal = 0.0;
            $totalVat = 0.0;
            $totalDiscount = 0.0;
            $items = [];

            foreach ($data['items'] as $item) {
                $batch = MedicineBatch::where('id', $item['batch_id'])
                    ->where('outlet_id', $data['outlet_id'])
                    ->where('is_active', true)
                    ->firstOrFail();

                if ((float) $batch->quantity_in_stock < (float) $item['quantity']) {
                    throw new InvalidArgumentException(
                        "Insufficient stock for batch {$batch->batch_number}. Available: {$batch->quantity_in_stock}"
                    );
                }

                $unitPrice = (float) $batch->selling_price_per_unit;
                $quantity = (float) $item['quantity'];
                $lineTotal = $unitPrice * $quantity;

                $discountAmount = (float) ($item['discount'] ?? 0);
                $afterDiscount = $lineTotal - $discountAmount;
                $vatAmount = ($afterDiscount * $vatRate) / 100;
                $lineTotalWithVat = $afterDiscount + $vatAmount;

                $subtotal += $afterDiscount;
                $totalDiscount += $discountAmount;
                $totalVat += $vatAmount;

                $items[] = [
                    'batch' => $batch,
                    'medicine_id' => $batch->medicine_id,
                    'quantity' => $quantity,
                    'unit_type' => $batch->medicine->unit_type ?? 'strip',
                    'mrp' => (float) $batch->mrp_per_unit,
                    'selling_price' => $unitPrice,
                    'discount' => round($discountAmount, 2),
                    'vat' => round($vatAmount, 2),
                    'total' => round($lineTotalWithVat, 2),
                    'prescription_required' => false,
                ];
            }

            $totalAmount = round($subtotal + $totalVat, 2);
            $paidAmount = (float) ($data['paid_amount'] ?? $totalAmount);
            $dueAmount = max(0, $totalAmount - $paidAmount);

            $paymentStatus = match (true) {
                $paidAmount >= $totalAmount => PaymentStatus::Paid,
                $paidAmount > 0 => PaymentStatus::Partial,
                default => PaymentStatus::Due,
            };

            $sale = Sale::create([
                'company_id' => Auth::user()->company_id,
                'outlet_id' => $data['outlet_id'],
                'register_id' => $data['register_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'prescription_id' => $data['prescription_id'] ?? null,
                'dispensed_by' => Auth::id(),
                'invoice_number' => $this->generateInvoiceNumber(Auth::user()->company_id),
                'sale_type' => SaleType::from($data['sale_type'] ?? 'walk_in'),
                'subtotal' => round($subtotal, 2),
                'discount_amount' => round($totalDiscount, 2),
                'discount_type' => 'fixed',
                'vat_amount' => round($totalVat, 2),
                'vat_percentage' => $vatRate,
                'total_amount' => $totalAmount,
                'paid_amount' => round($paidAmount, 2),
                'due_amount' => round($dueAmount, 2),
                'payment_status' => $paymentStatus,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $itemData) {
                $batch = $itemData['batch'];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'medicine_id' => $itemData['medicine_id'],
                    'batch_id' => $batch->id,
                    'quantity' => $itemData['quantity'],
                    'unit_type' => $itemData['unit_type'],
                    'mrp' => $itemData['mrp'],
                    'selling_price' => $itemData['selling_price'],
                    'discount' => $itemData['discount'],
                    'vat' => $itemData['vat'],
                    'total' => $itemData['total'],
                    'prescription_required' => $itemData['prescription_required'],
                ]);

                $this->inventoryService->reduceStock($batch, $itemData['quantity']);
            }

            if (! empty($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    SalePayment::create([
                        'sale_id' => $sale->id,
                        'payment_method_id' => $payment['payment_method_id'],
                        'amount' => $payment['amount'],
                        'reference_number' => $payment['reference_number'] ?? null,
                        'gateway_response' => $payment['gateway_response'] ?? null,
                    ]);
                }
            }

            if ($sale->customer_id && $company->settings['loyalty_enabled'] ?? false) {
                $pointsPerRupee = (int) ($company->settings['loyalty_points_per_rupee'] ?? 1);
                $earnedPoints = (int) floor($totalAmount / $pointsPerRupee);

                $customer = Customer::find($sale->customer_id);
                if ($customer) {
                    $customer->addLoyaltyPoints($earnedPoints);

                    if (($data['loyalty_points_redeemed'] ?? 0) > 0) {
                        $customer->redeemLoyaltyPoints((int) $data['loyalty_points_redeemed']);
                    }
                }
            }

            return $sale->load(['items', 'payments', 'customer', 'dispensedBy']);
        });
    }

    public function generateInvoiceNumber(int $companyId): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $lastSale = Sale::where('company_id', $companyId)
            ->where('invoice_number', 'like', "{$prefix}-{$date}-%")
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->first();

        $sequence = 1;
        if ($lastSale) {
            $lastSequence = (int) substr($lastSale->invoice_number, -5);
            $sequence = $lastSequence + 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $date, $sequence);
    }

    public function getDailySummary(int $companyId, int $outletId, ?string $date = null): array
    {
        $query = Sale::where('company_id', $companyId)
            ->where('outlet_id', $outletId);

        if ($date) {
            $query->whereDate('created_at', $date);
        } else {
            $query->whereDate('created_at', today());
        }

        $sales = $query->get();

        return [
            'total_sales' => $sales->sum('total_amount'),
            'total_discount' => $sales->sum('discount_amount'),
            'total_vat' => $sales->sum('vat_amount'),
            'total_transactions' => $sales->count(),
            'total_cash' => $sales->where('payment_status', PaymentStatus::Paid)->sum('total_amount'),
            'total_returns' => 0,
        ];
    }
}
