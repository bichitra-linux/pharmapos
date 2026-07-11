<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
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
     * Generate invoice PDF using DomPDF.
     */
    public function generateInvoicePdf(Sale $sale): \Barryvdh\DomPDF\PDF
    {
        $sale->loadMissing(['items.medicine', 'items.batch', 'customer', 'dispensedBy', 'payments.paymentMethod', 'company', 'outlet']);

        $settings = $sale->company->settings ?? [];

        return Pdf::loadView('invoice', [
            'sale' => $sale,
            'company' => $sale->company,
            'outlet' => $sale->outlet,
            'settings' => $settings,
        ]);
    }

    /**
     * Save invoice as PDF on disk.
     */
    public function saveInvoicePdf(Sale $sale): string
    {
        $pdf = $this->generateInvoicePdf($sale);
        $filename = "invoices/{$sale->company_id}/{$sale->invoice_number}.pdf";
        Storage::put($filename, $pdf->output());

        return $filename;
    }

    /**
     * Stream invoice as a download response.
     */
    public function downloadInvoice(Sale $sale, string $filename = null): Response
    {
        $pdf = $this->generateInvoicePdf($sale);
        $filename ??= "invoice-{$sale->invoice_number}.pdf";

        return $pdf->download($filename);
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
