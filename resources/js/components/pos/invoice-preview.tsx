import { Dialog, DialogHeader, DialogTitle, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { formatCurrency, formatDate } from '@/lib/utils';
import { Printer, X } from 'lucide-react';
import type { Sale } from '@/types';

interface InvoicePreviewProps {
    sale: Sale;
    onClose: () => void;
}

export function InvoicePreview({ sale, onClose }: InvoicePreviewProps) {
    const handlePrint = () => {
        const printContent = document.getElementById('invoice-print');
        if (!printContent) return;

        const printWindow = window.open('', '_blank');
        if (!printWindow) return;

        printWindow.document.write(`
            <html>
                <head>
                    <title>Invoice #${sale.invoice_number}</title>
                    <style>
                        body { font-family: monospace; font-size: 12px; padding: 16px; margin: 0; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { padding: 4px 0; }
                        th { border-bottom: 1px solid #000; text-align: left; }
                        td { border-bottom: 1px dotted #ccc; }
                        .text-right { text-align: right; }
                        .text-center { text-align: center; }
                        .font-bold { font-weight: bold; }
                        .mt-2 { margin-top: 8px; }
                        .mt-4 { margin-top: 16px; }
                        .border-t { border-top: 1px solid #000; padding-top: 4px; }
                        .text-muted { color: #666; }
                    </style>
                </head>
                <body>
                    ${printContent.innerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
        printWindow.close();
    };

    return (
        <Dialog open={true} onClose={onClose} size="sm">
            <DialogHeader>
                <DialogTitle>Invoice #{sale.invoice_number}</DialogTitle>
            </DialogHeader>
            <DialogContent>
                <div className="rounded border p-4 font-mono text-sm" id="invoice-print">
                    <div className="mb-4 text-center">
                        <h2 className="text-lg font-bold">PharmaPOS</h2>
                        <p className="text-xs text-gray-500">Invoice #{sale.invoice_number}</p>
                        <p className="text-xs text-gray-500">{formatDate(sale.created_at, 'dd/MM/yyyy HH:mm')}</p>
                    </div>

                    {sale.customer && (
                        <div className="mb-3 border-b border-dashed pb-2">
                            <p className="text-xs">Customer: {sale.customer.name}</p>
                            <p className="text-xs">Phone: {sale.customer.phone}</p>
                        </div>
                    )}

                    <table className="w-full text-xs">
                        <thead>
                            <tr className="border-b">
                                <th className="py-1 text-left">Item</th>
                                <th className="py-1 text-right">Qty</th>
                                <th className="py-1 text-right">Rate</th>
                                <th className="py-1 text-right">Amt</th>
                            </tr>
                        </thead>
                        <tbody>
                            {sale.items?.map((item) => (
                                <tr key={item.id} className="border-b border-dotted">
                                    <td className="py-1">{item.medicine?.brand_name || `Medicine #${item.medicine_id}`}</td>
                                    <td className="py-1 text-right">{item.quantity}</td>
                                    <td className="py-1 text-right">{formatCurrency(item.unit_price)}</td>
                                    <td className="py-1 text-right">{formatCurrency(item.total_amount)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <div className="mt-3 space-y-1 text-xs">
                        <div className="flex justify-between">
                            <span>Subtotal</span>
                            <span>{formatCurrency(sale.subtotal)}</span>
                        </div>
                        {sale.discount_amount > 0 && (
                            <div className="flex justify-between">
                                <span>Discount</span>
                                <span>-{formatCurrency(sale.discount_amount)}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span>VAT</span>
                            <span>{formatCurrency(sale.tax_amount)}</span>
                        </div>
                        <div className="flex justify-between border-t pt-1 text-sm font-bold">
                            <span>Total</span>
                            <span>{formatCurrency(sale.total_amount)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Paid</span>
                            <span>{formatCurrency(sale.paid_amount)}</span>
                        </div>
                        {sale.due_amount > 0 && (
                            <div className="flex justify-between text-danger-600">
                                <span>Due</span>
                                <span>{formatCurrency(sale.due_amount)}</span>
                            </div>
                        )}
                    </div>

                    <div className="mt-4 text-center text-xs text-gray-500">
                        <p>Thank you for your purchase!</p>
                    </div>
                </div>
            </DialogContent>
            <DialogFooter>
                <Button variant="outline" onClick={onClose}>
                    <X className="mr-1 h-4 w-4" />
                    Close
                </Button>
                <Button onClick={handlePrint}>
                    <Printer className="mr-1 h-4 w-4" />
                    Print
                </Button>
            </DialogFooter>
        </Dialog>
    );
}
