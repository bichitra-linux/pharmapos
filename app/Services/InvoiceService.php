<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate a unique invoice number.
     */
    public function generateInvoiceNumber(int $companyId): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $lastSale = Sale::where('company_id', $companyId)
            ->where('invoice_number', 'like', "{$prefix}-{$date}-%")
            ->orderByDesc('invoice_number')
            ->lockForUpdate()
            ->first();

        $sequence = 1;
        if ($lastSale) {
            $lastSequence = (int) substr($lastSale->invoice_number, -5);
            $sequence = $lastSequence + 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $date, $sequence);
    }

    /**
     * Generate thermal receipt data for POS printer.
     */
    public function generateThermalReceiptData(Sale $sale): array
    {
        $sale->load(['items.medicine', 'items.batch', 'customer', 'dispensedBy', 'payments.paymentMethod', 'company', 'outlet']);

        $company = $sale->company;
        $outlet = $sale->outlet;
        $settings = $company->settings ?? [];

        $lines = [];
        $charWidth = 48; // Standard thermal printer width

        // Header
        $lines[] = $this->center(strtoupper($company->name), $charWidth);
        if ($outlet && $outlet->name !== $company->name) {
            $lines[] = $this->center($outlet->name, $charWidth);
        }
        if ($company->address) {
            $lines[] = $this->center($company->address, $charWidth);
        }
        if ($outlet->phone ?? $company->phone) {
            $lines[] = $this->center('Ph: '.($outlet->phone ?? $company->phone), $charWidth);
        }
        if ($company->pan_number) {
            $lines[] = $this->center('PAN: '.$company->pan_number, $charWidth);
        }
        $lines[] = str_repeat('-', $charWidth);

        // Invoice info
        $lines[] = 'Invoice: '.$sale->invoice_number;
        $lines[] = 'Date: '.$sale->created_at->format('Y-m-d H:i');
        if ($sale->dispensedBy) {
            $lines[] = 'Cashier: '.$sale->dispensedBy->name;
        }
        if ($sale->customer) {
            $lines[] = 'Customer: '.$sale->customer->name;
            if ($sale->customer->phone) {
                $lines[] = 'Phone: '.$sale->customer->phone;
            }
        }
        $lines[] = str_repeat('-', $charWidth);

        // Items header
        $lines[] = sprintf('%-20s %4s %8s %10s', 'Item', 'Qty', 'Rate', 'Amount');
        $lines[] = str_repeat('-', $charWidth);

        // Items
        foreach ($sale->items as $item) {
            $name = substr($item->medicine->brand_name ?? 'Unknown', 0, 20);
            $lines[] = sprintf('%-20s %4s %8s %10s', $name, number_format((float) $item->quantity, 0), number_format((float) $item->selling_price, 2), number_format((float) $item->total, 2));

            if ($item->batch?->batch_number) {
                $lines[] = sprintf('  Batch: %s', $item->batch->batch_number);
            }
            if ((float) $item->discount > 0) {
                $lines[] = sprintf('  Discount: -%s', number_format((float) $item->discount, 2));
            }
        }

        $lines[] = str_repeat('-', $charWidth);

        // Totals
        $lines[] = sprintf('%-34s %10s', 'Subtotal:', number_format((float) $sale->subtotal, 2));

        if ((float) $sale->discount_amount > 0) {
            $lines[] = sprintf('%-34s %10s', 'Discount:', '-'.number_format((float) $sale->discount_amount, 2));
        }

        if ((float) $sale->vat_amount > 0) {
            $lines[] = sprintf('%-34s %10s', 'VAT ('.($settings['vat_rate'] ?? 13).'%):', number_format((float) $sale->vat_amount, 2));
        }

        $lines[] = str_repeat('=', $charWidth);
        $lines[] = sprintf('%-34s %10s', 'TOTAL:', number_format((float) $sale->total_amount, 2));
        $lines[] = str_repeat('=', $charWidth);

        // Payments
        foreach ($sale->payments as $payment) {
            $methodName = $payment->paymentMethod?->name ?? 'Cash';
            $lines[] = sprintf('%-34s %10s', $methodName.':', number_format((float) $payment->amount, 2));
        }

        // Footer
        $lines[] = str_repeat('-', $charWidth);
        $lines[] = $this->center('Thank you for your visit!', $charWidth);

        if ($company->pharmacy_license_number) {
            $lines[] = $this->center('License: '.$company->pharmacy_license_number, $charWidth);
        }

        if ($company->pharmacist_name) {
            $lines[] = $this->center('Pharmacist: '.$company->pharmacist_name, $charWidth);
        }

        return [
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'lines' => $lines,
            'total_lines' => count($lines),
            'char_width' => $charWidth,
        ];
    }

    /**
     * Generate HTML invoice for PDF generation.
     */
    public function generateInvoiceHtml(Sale $sale): string
    {
        $sale->load(['items.medicine', 'items.batch', 'customer', 'dispensedBy', 'payments.paymentMethod', 'company', 'outlet']);

        $company = $sale->company;
        $outlet = $sale->outlet;
        $settings = $company->settings ?? [];

        $itemsHtml = '';
        foreach ($sale->items as $index => $item) {
            $name = $item->medicine->brand_name ?? 'Unknown';
            $itemsHtml .= '<tr>';
            $itemsHtml .= '<td>'.($index + 1).'</td>';
            $itemsHtml .= '<td>'.e($name);
            if ($item->batch?->batch_number) {
                $itemsHtml .= '<br><small>Batch: '.e($item->batch->batch_number).'</small>';
            }
            $itemsHtml .= '</td>';
            $itemsHtml .= '<td class="text-right">'.number_format((float) $item->quantity, 0).'</td>';
            $itemsHtml .= '<td class="text-right">'.number_format((float) $item->selling_price, 2).'</td>';
            $itemsHtml .= '<td class="text-right">'.number_format((float) $item->discount, 2).'</td>';
            $itemsHtml .= '<td class="text-right">'.number_format((float) $item->vat, 2).'</td>';
            $itemsHtml .= '<td class="text-right">'.number_format((float) $item->total, 2).'</td>';
            $itemsHtml .= '</tr>';
        }

        $paymentsHtml = '';
        foreach ($sale->payments as $payment) {
            $methodName = $payment->paymentMethod?->name ?? 'Cash';
            $paymentsHtml .= '<tr>';
            $paymentsHtml .= '<td>'.e($methodName).'</td>';
            $paymentsHtml .= '<td class="text-right">'.number_format((float) $payment->amount, 2).'</td>';
            $paymentsHtml .= '</tr>';
        }

        $vatRate = $settings['vat_rate'] ?? 13;
        $companyName = e($company->name);
        $companyAddress = e($company->address ?? '');
        $companyPhone = e($company->phone ?? '');
        $companyPan = e($company->pan_number ?? '');
        $invoiceNumber = e($sale->invoice_number);
        $saleDate = e($sale->created_at->format('Y-m-d H:i'));
        $cashierName = e($sale->dispensedBy?->name ?? '');
        $customerName = e($sale->customer?->name ?? '');
        $customerPhone = e($sale->customer?->phone ?? '');
        $licenseNumber = e($company->pharmacy_license_number ?? '');
        $pharmacistName = e($company->pharmacist_name ?? '');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {$invoiceNumber}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0,0,0,.15); }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .company-info h1 { margin: 0; color: #2c3e50; font-size: 24px; }
        .company-info p { margin: 2px 0; color: #666; }
        .invoice-info { text-align: right; }
        .invoice-info h2 { margin: 0; color: #3498db; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: 600; text-align: left; }
        .text-right { text-align: right; }
        .totals { margin-top: 20px; }
        .totals table { width: 300px; float: right; }
        .total-row { font-weight: bold; font-size: 14px; border-top: 2px solid #333; }
        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 11px; clear: both; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div class="company-info">
                <h1>{$companyName}</h1>
                <p>{$companyAddress}</p>
                <p>Ph: {$companyPhone}</p>
                <p>PAN: {$companyPan}</p>
            </div>
            <div class="invoice-info">
                <h2>INVOICE</h2>
                <p><strong>Invoice #:</strong> {$invoiceNumber}</p>
                <p><strong>Date:</strong> {$saleDate}</p>
                <p><strong>Cashier:</strong> {$cashierName}</p>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <strong>Bill To:</strong><br>
            {$customerName}<br>
            {$customerPhone}
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Discount</th>
                    <th class="text-right">VAT</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                {$itemsHtml}
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">NPR {$this->formatNumber($sale->subtotal)}</td>
                </tr>
                <tr>
                    <td>Discount:</td>
                    <td class="text-right">-NPR {$this->formatNumber($sale->discount_amount)}</td>
                </tr>
                <tr>
                    <td>VAT ({$vatRate}%):</td>
                    <td class="text-right">NPR {$this->formatNumber($sale->vat_amount)}</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td class="text-right">NPR {$this->formatNumber($sale->total_amount)}</td>
                </tr>
            </table>
        </div>

        <div style="clear: both; margin-top: 20px;">
            <h4>Payments</h4>
            <table style="width: 300px;">
                {$paymentsHtml}
            </table>
        </div>

        <div class="footer">
            <p>Thank you for your visit!</p>
            <p>License: {$licenseNumber} | Pharmacist: {$pharmacistName}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Save invoice as PDF.
     */
    public function saveInvoicePdf(Sale $sale): string
    {
        $html = $this->generateInvoiceHtml($sale);
        $filename = "invoices/{$sale->company_id}/{$sale->invoice_number}.pdf";

        // Note: Requires a PDF library like dompdf or snappy
        // For now, save as HTML that can be converted
        Storage::put(str_replace('.pdf', '.html', $filename), $html);

        return $filename;
    }

    /**
     * Center text within a given width.
     */
    private function center(string $text, int $width): string
    {
        $textLength = strlen($text);
        if ($textLength >= $width) {
            return $text;
        }

        $padding = intdiv($width - $textLength, 2);

        return str_repeat(' ', $padding).$text;
    }

    /**
     * Format number for display.
     */
    private function formatNumber(mixed $value): string
    {
        return number_format((float) $value, 2);
    }
}
