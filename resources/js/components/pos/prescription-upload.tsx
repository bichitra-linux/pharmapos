import { useState } from 'react';
import { Upload, X } from 'lucide-react';
import { useCartStore } from '@/stores/cartStore';
import { Dialog, DialogHeader, DialogTitle, DialogContent, DialogFooter } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

export function PrescriptionUpload() {
    const setPrescription = useCartStore((s) => s.setPrescription);
    const prescriptionId = useCartStore((s) => s.prescription_id);
    const [showInput, setShowInput] = useState(false);
    const [presNumber, setPresNumber] = useState('');

    if (prescriptionId) {
        return (
            <div className="flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-1.5 text-sm text-primary-700">
                <span>Prescription #{prescriptionId}</span>
                <button onClick={() => setPrescription(null)} aria-label="Remove prescription">
                    <X className="h-4 w-4" />
                </button>
            </div>
        );
    }

    return (
        <>
            <button
                onClick={() => setShowInput(true)}
                aria-label="Link prescription"
                className="flex items-center gap-2 rounded-lg border border-border bg-surface px-3 py-2 text-sm hover:bg-surface-muted"
            >
                <Upload className="h-4 w-4" />
                Prescription
            </button>
            <Dialog open={showInput} onClose={() => setShowInput(false)} size="sm">
                <DialogHeader>
                    <DialogTitle>Link Prescription</DialogTitle>
                </DialogHeader>
                <DialogContent>
                    <p className="mb-2 text-sm text-text-muted">Enter prescription ID</p>
                    <input
                        type="text"
                        value={presNumber}
                        onChange={(e) => setPresNumber(e.target.value)}
                        placeholder="Prescription #"
                        aria-label="Prescription number"
                        className="w-full rounded border border-border px-3 py-2 text-sm"
                    />
                </DialogContent>
                <DialogFooter>
                    <Button variant="outline" onClick={() => setShowInput(false)}>
                        Cancel
                    </Button>
                    <Button
                        onClick={() => {
                            const parsed = parseInt(presNumber, 10);
                            if (presNumber && !isNaN(parsed) && parsed > 0) {
                                setPrescription(parsed);
                                setShowInput(false);
                                setPresNumber('');
                            }
                        }}
                    >
                        Link
                    </Button>
                </DialogFooter>
            </Dialog>
        </>
    );
}
