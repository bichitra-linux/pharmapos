<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $sale->invoice_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; }
        .company-info h1 { margin: 0; font-size: 18px; color: #2c3e50; }
        .company-info p { margin: 2px 0; color: #666; font-size: 10px; }
        .invoice-info { text-align: right; }
        .invoice-info h2 { margin: 0; color: #3498db; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 6px 10px; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: 600; }
        .text-right { text-align: right; }
        .totals { margin-top: 10px; }
        .totals table { width: 250px; float: right; }
        .total-row td { font-weight: bold; font-size: 12px; border-top: 2px solid #333; }
        .footer { margin-top: 40px; text-align: center; color: #999; font-size: 9px; }
        .bill-to { margin-bottom: 15px; }
        .bill-to strong { font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h1>{{ $company->name }}</h1>
            <p>{{ $company->address }}</p>
            <p>Ph: {{ $company->phone }}</p>
            <p>PAN: {{ $company->pan_number }}</p>
        </div>
        <div class="invoice-info">
            <h2>INVOICE</h2>
            <p><strong>#:</strong> {{ $sale->invoice_number }}</p>
            <p><strong>Date:</strong> {{ $sale->created_at->format('Y-m-d H:i') }}</p>
            <p><strong>Cashier:</strong> {{ $sale->dispensedBy?->name }}</p>
        </div>
    </div>

    <div class="bill-to">
        <strong>Bill To:</strong><br>
        {{ $sale->customer?->name }}<br>
        {{ $sale->customer?->phone }}
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
            @foreach($sale->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item?->medicine?->generic_name ?? $item?->medicine?->brand_name ?? 'N/A' }}
                    @if($item?->batch?->batch_number)
                        <br><small>Batch: {{ $item->batch->batch_number }}</small>
                    @endif
                </td>
                <td class="text-right">{{ number_format((float) $item->quantity, 0) }}</td>
                <td class="text-right">{{ number_format((float) $item->selling_price, 2) }}</td>
                <td class="text-right">{{ number_format((float) $item->discount, 2) }}</td>
                <td class="text-right">{{ number_format((float) $item->vat, 2) }}</td>
                <td class="text-right">{{ number_format((float) $item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td>Subtotal:</td><td class="text-right">{{ number_format((float) $sale->subtotal, 2) }}</td></tr>
            @if((float) $sale->discount_amount > 0)
            <tr><td>Discount:</td><td class="text-right">-{{ number_format((float) $sale->discount_amount, 2) }}</td></tr>
            @endif
            <tr><td>VAT ({{ $company->settings['vat_rate'] ?? 13 }}%):</td><td class="text-right">{{ number_format((float) $sale->vat_amount, 2) }}</td></tr>
            <tr class="total-row"><td>TOTAL:</td><td class="text-right">{{ number_format((float) $sale->total_amount, 2) }}</td></tr>
        </table>
    </div>

    <div style="clear: both; margin-top: 15px;">
        <strong>Payments</strong>
        <table style="width: 250px;">
            @foreach($sale->payments as $payment)
            <tr><td>{{ $payment->paymentMethod?->name ?? 'Cash' }}:</td><td class="text-right">{{ number_format((float) $payment->amount, 2) }}</td></tr>
            @endforeach
        </table>
    </div>

    <div class="footer">
        <p>Thank you for your visit!</p>
        @if($company->pharmacy_license_number)
        <p>License: {{ $company->pharmacy_license_number }}</p>
        @endif
        @if($company->pharmacist_name)
        <p>Pharmacist: {{ $company->pharmacist_name }}</p>
        @endif
    </div>
</body>
</html>
