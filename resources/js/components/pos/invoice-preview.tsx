import { useRef, useState } from 'react';
import { Dialog, DialogHeader, DialogTitle, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDate, formatNepaliDate } from '@/lib/utils';
import { Printer, X, FileText, Receipt } from 'lucide-react';
import type { Sale } from '@/types';

interface InvoicePreviewProps {
    sale: Sale;
    onClose: () => void;
}

function InvoiceContent({ sale, isReceipt }: { sale: Sale; isReceipt: boolean }) {
    const company = (sale as any).company;
    const cashier = (sale as any).dispensed_by_user;
    const payments = (sale as any).payments ?? [];

    return (
        <div className={isReceipt ? 'font-mono text-xs leading-tight' : 'font-sans text-sm'}>
            {/* Header */}
            {isReceipt ? (
                <div className="text-center mb-2">
                    <p className="font-bold text-sm">{company?.name || 'PharmaPOS'}</p>
                    {company && <p className="text-xs">{company.address?.split('\n')[0]}</p>}
                    {company && <p className="text-xs">Tel: {company.phone}</p>}
                    {company?.pan_number && <p className="text-xs">PAN: {company.pan_number}</p>}
                    {company?.drug_license_number && <p className="text-xs">DL: {company.drug_license_number}</p>}
                    <p className="font-bold mt-1">TAX INVOICE / कर विवरण</p>
                    <p className="text-xs">#{sale.invoice_number}</p>
                    <p className="text-xs">{formatDate(sale.created_at, 'dd/MM/yyyy HH:mm')}</p>
                    {formatNepaliDate(sale.created_at) && <p className="text-xs">{formatNepaliDate(sale.created_at)}</p>}
                </div>
            ) : (
                <div className="flex items-start justify-between border-b border-border pb-4 mb-4">
                    <div className="flex items-start gap-4">
                        {company?.logo && (
                            <img src={`/storage/${company.logo}`} alt="Logo" className="h-16 w-16 object-contain rounded" />
                        )}
                        <div>
                            <h1 className="text-2xl font-bold">{company?.name || 'PharmaPOS'}</h1>
                            {company && (
                                <div className="text-xs text-text-muted mt-1 space-y-0.5">
                                    {company.address && <p>{company.address}</p>}
                                    <p>Tel: {company.phone} | Email: {company?.email || '-'}</p>
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="text-right">
                        <h2 className="text-xl font-bold text-primary-600">TAX INVOICE</h2>
                        <p className="text-xs text-text-muted">कर विवरण</p>
                        <p className="mt-1 font-mono text-lg font-bold">#{sale.invoice_number}</p>
                        <Badge variant={sale.payment_status === 'paid' ? 'success' : sale.payment_status === 'partial' ? 'warning' : 'destructive'} className="mt-1">
                            {sale.payment_status?.toUpperCase()}
                        </Badge>
                    </div>
                </div>
            )}

            {/* Regulatory info row (A4 only) */}
            {!isReceipt && company && (
                <div className="mb-4 grid grid-cols-2 gap-2 rounded-lg bg-surface-muted p-3 text-xs text-text-muted">
                    <div><span className="font-medium">PAN:</span> {company.pan_number || '-'}</div>
                    <div><span className="font-medium">VAT:</span> {company.vat_number || '-'}</div>
                    <div><span className="font-medium">Drug License:</span> {company.drug_license_number || '-'}</div>
                    <div><span className="font-medium">Pharmacy Reg:</span> {company.pharmacy_license_number || '-'}</div>
                </div>
            )}

            {/* Bill To / Sold By */}
            {isReceipt ? (
                <div className="mb-2">
                    {sale.customer && (
                        <p className="text-xs">{sale.customer.name}{sale.customer.phone ? ` | ${sale.customer.phone}` : ''}</p>
                    )}
                    {cashier && <p className="text-xs">Cashier: {cashier.name}</p>}
                </div>
            ) : (
                <div className="mb-4 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p className="font-medium text-text-muted text-xs">Bill To</p>
                        {sale.customer ? (
                            <div className="mt-1">
                                <p className="font-medium">{sale.customer.name}</p>
                                {sale.customer.phone && <p className="text-xs">Tel: {sale.customer.phone}</p>}
                                {sale.customer.address && <p className="text-xs">{sale.customer.address}</p>}
                            </div>
                        ) : (
                            <p className="text-text-muted italic mt-1">Walk-in Customer</p>
                        )}
                    </div>
                    <div className="text-right">
                        <p className="font-medium text-text-muted text-xs">Sold By</p>
                        <div className="mt-1">
                            <p className="font-medium">{cashier?.name || '-'}</p>
                            {company?.pharmacist_name && <p className="text-xs">Pharmacist: {company.pharmacist_name}</p>}
                            <p className="text-xs">{formatDate(sale.created_at, 'dd/MM/yyyy HH:mm')}</p>
                        </div>
                    </div>
                </div>
            )}

            {/* Items table */}
            {isReceipt ? (
                <div className="border-t border-b border-dashed py-1 mb-2">
                    <div className="flex justify-between font-bold text-xs mb-1">
                        <span className="flex-1">Item</span>
                        <span className="w-16 text-right">Qty</span>
                        <span className="w-16 text-right">Rate</span>
                        <span className="w-16 text-right">Amt</span>
                    </div>
                    {sale.items?.map((item, idx) => (
                        <div key={item.id} className="flex justify-between text-xs py-0.5">
                            <span className="flex-1 truncate">{item.medicine_name || `#${item.medicine_id}`}</span>
                            <span className="w-16 text-right">{item.quantity}</span>
                            <span className="w-16 text-right">{formatCurrency(item.selling_price)}</span>
                            <span className="w-16 text-right">{formatCurrency(item.total)}</span>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="overflow-x-auto mb-4">
                    <table className="w-full text-xs">
                        <thead>
                            <tr className="border-b border-border">
                                <th className="py-2 text-left w-8">#</th>
                                <th className="py-2 text-left">Item</th>
                                <th className="py-2 text-left">Batch</th>
                                <th className="py-2 text-left">Exp</th>
                                <th className="py-2 text-right">Qty</th>
                                <th className="py-2 text-right">MRP</th>
                                <th className="py-2 text-right">Rate</th>
                                <th className="py-2 text-right">Disc</th>
                                <th className="py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            {sale.items?.map((item, idx) => (
                                <tr key={item.id} className="border-b border-dotted border-border hover:bg-surface-muted/50">
                                    <td className="py-1.5 text-text-muted">{idx + 1}</td>
                                    <td className="py-1.5">
                                        <p className="font-medium text-sm">{item.medicine_name || `#${item.medicine_id}`}</p>
                                        {item.medicine_generic_name && (
                                            <p className="text-xs text-text-muted">{item.medicine_generic_name}{item.medicine_manufacturer ? ` • ${item.medicine_manufacturer}` : ''}</p>
                                        )}
                                    </td>
                                    <td className="py-1.5 font-mono text-xs">{item.batch?.batch_number || '-'}</td>
                                    <td className="py-1.5 text-xs">{item.batch?.expiry_date ? formatDate(item.batch.expiry_date, 'MM/yy') : '-'}</td>
                                    <td className="py-1.5 text-right">{item.quantity} {item.sell_mode === 'piece' ? 'pcs' : item.unit_type}</td>
                                    <td className="py-1.5 text-right">{formatCurrency(item.mrp)}</td>
                                    <td className="py-1.5 text-right">{formatCurrency(item.selling_price)}</td>
                                    <td className="py-1.5 text-right text-danger-600">{item.discount > 0 ? formatCurrency(item.discount) : '-'}</td>
                                    <td className="py-1.5 text-right font-medium">{formatCurrency(item.total)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {/* Totals */}
            <div className={isReceipt ? 'space-y-0.5 mb-2' : 'ml-auto w-72 space-y-1 mb-4'}>
                <div className="flex justify-between text-xs">
                    <span>Subtotal</span>
                    <span>{formatCurrency(sale.subtotal)}</span>
                </div>
                {sale.discount_amount > 0 && (
                    <div className="flex justify-between text-xs text-success-600">
                        <span>Discount</span>
                        <span>-{formatCurrency(sale.discount_amount)}</span>
                    </div>
                )}
                <div className="flex justify-between text-xs">
                    <span>VAT ({sale.vat_percentage}%)</span>
                    <span>{formatCurrency(sale.vat_amount)}</span>
                </div>
                <div className={`flex justify-between font-bold ${isReceipt ? 'text-sm border-t' : 'text-lg border-t border-border pt-1'}`}>
                    <span>Total</span>
                    <span className="text-primary-600">{formatCurrency(sale.total_amount)}</span>
                </div>
                <div className="flex justify-between text-xs">
                    <span>Paid</span>
                    <span className="text-success-600">{formatCurrency(sale.paid_amount)}</span>
                </div>
                {sale.due_amount > 0 && (
                    <div className="flex justify-between text-xs text-danger-600 font-medium">
                        <span>Due</span>
                        <span>{formatCurrency(sale.due_amount)}</span>
                    </div>
                )}
                {sale.paid_amount > sale.total_amount && (
                    <div className="flex justify-between text-xs text-success-600">
                        <span>Change</span>
                        <span>{formatCurrency(sale.paid_amount - sale.total_amount)}</span>
                    </div>
                )}
            </div>

            {/* Payment breakdown */}
            {payments.length > 0 && (
                <div className={isReceipt ? 'text-xs mb-2' : 'text-xs mb-4'}>
                    <p className="font-medium mb-1">Payment</p>
                    {payments.map((p: any, idx: number) => (
                        <div key={idx} className="flex justify-between">
                            <span>{p.payment_method?.name || p.payment_method_id}</span>
                            <span>{formatCurrency(p.amount)}</span>
                        </div>
                    ))}
                </div>
            )}

            {/* Footer */}
            {isReceipt ? (
                <div className="text-center text-xs pt-2 border-t border-dashed">
                    {company?.pharmacist_registration_number && <p>Pharm Reg: {company.pharmacist_registration_number}</p>}
                    <p className="mt-1">Thank you! / धन्यवाद!</p>
                    <p className="text-[10px] text-text-muted">Returns within 7 days with original receipt</p>
                </div>
            ) : (
                <div className="border-t border-border pt-4 text-xs text-text-muted space-y-1">
                    <div className="flex justify-between items-start">
                        <div>
                            {company?.pharmacist_registration_number && <p>Pharmacist Registration: {company.pharmacist_registration_number}</p>}
                            <p>Returns accepted within 7 days with original receipt.</p>
                            <p>All medicines dispensed under supervision of registered pharmacist.</p>
                        </div>
                        <div className="text-right">
                            <p className="text-lg">🙏</p>
                            <p>Thank you for your purchase!</p>
                            <p className="italic">खरिदको लागि धन्यवाद!</p>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

export function InvoicePreview({ sale, onClose }: InvoicePreviewProps) {
    const [mode, setMode] = useState<'invoice' | 'receipt'>('invoice');
    const printRef = useRef<HTMLDivElement>(null);

    const handlePrint = () => {
        const content = printRef.current;
        if (!content) return;
        const win = window.open('', '_blank');
        if (!win) return;
        const doc = win.document;
        const isReceipt = mode === 'receipt';

        // Build comprehensive print CSS
        const width = isReceipt ? '58mm' : '210mm';
        const css = isReceipt ? `
            @page { width: 58mm; margin: 0; padding: 4mm; }
            body { font-family: 'Courier New', monospace; font-size: 10px; width: 58mm; margin: 0; padding: 4mm; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 2px 0; }
        ` : `
            @page { size: A4 landscape; margin: 10mm; }
            body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; font-size: 12px; width: 100%; margin: 0; padding: 0; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 4px 0; }
        `;

        doc.write(`<html><head><style>${css}</style></head><body>`);
        const clone = content.cloneNode(true) as HTMLElement;
        doc.body.appendChild(clone);
        doc.close();
        win.print();
        win.close();
    };

    return (
        <Dialog open={true} onClose={onClose} size={mode === 'receipt' ? 'sm' : 'lg'}>
            <DialogHeader>
                <div className="flex items-center justify-between">
                    <DialogTitle>Invoice</DialogTitle>
                    <div className="flex gap-1 rounded border border-border overflow-hidden text-xs">
                        <button
                            onClick={() => setMode('invoice')}
                            className={`flex items-center gap-1 px-2 py-1 ${mode === 'invoice' ? 'bg-primary-600 text-white' : 'bg-surface text-text'}`}
                        >
                            <FileText className="h-3 w-3" /> Invoice
                        </button>
                        <button
                            onClick={() => setMode('receipt')}
                            className={`flex items-center gap-1 px-2 py-1 ${mode === 'receipt' ? 'bg-primary-600 text-white' : 'bg-surface text-text'}`}
                        >
                            <Receipt className="h-3 w-3" /> Receipt
                        </button>
                    </div>
                </div>
            </DialogHeader>
            <DialogContent>
                <div ref={printRef}>
                    <InvoiceContent sale={sale} isReceipt={mode === 'receipt'} />
                </div>
            </DialogContent>
            <DialogFooter>
                <Button variant="outline" onClick={onClose}>
                    <X className="mr-1 h-4 w-4" /> Close
                </Button>
                <Button onClick={handlePrint}>
                    <Printer className="mr-1 h-4 w-4" /> Print {mode === 'receipt' ? 'Receipt' : 'Invoice'}
                </Button>
            </DialogFooter>
        </Dialog>
    );
}
