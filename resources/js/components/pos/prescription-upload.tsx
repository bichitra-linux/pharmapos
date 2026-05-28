import { useState } from 'react';
import { Upload, X } from 'lucide-react';
import { useCartStore } from '@/stores/cartStore';

export function PrescriptionUpload() {
    const setPrescription = useCartStore((s) => s.setPrescription);
    const prescriptionId = useCartStore((s) => s.prescription_id);
    const [showInput, setShowInput] = useState(false);
    const [presNumber, setPresNumber] = useState('');

    if (prescriptionId) {
        return (
            <div className="flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-1.5 text-sm text-primary-700">
                <span>Prescription #{prescriptionId}</span>
                <button onClick={() => setPrescription(null)}>
                    <X className="h-4 w-4" />
                </button>
            </div>
        );
    }

    return (
        <div className="relative">
            <button
                onClick={() => setShowInput(!showInput)}
                className="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm hover:bg-gray-50"
            >
                <Upload className="h-4 w-4" />
                Prescription
            </button>
            {showInput && (
                <div className="absolute right-0 top-full z-10 mt-1 w-64 rounded-lg border border-gray-200 bg-white p-3 shadow-lg">
                    <p className="mb-2 text-xs text-gray-500">Enter prescription ID</p>
                    <div className="flex gap-2">
                        <input
                            type="text"
                            value={presNumber}
                            onChange={(e) => setPresNumber(e.target.value)}
                            placeholder="Prescription #"
                            className="flex-1 rounded border border-gray-300 px-2 py-1 text-sm"
                        />
                        <button
                            onClick={() => {
                                if (presNumber) {
                                    setPrescription(parseInt(presNumber));
                                    setShowInput(false);
                                    setPresNumber('');
                                }
                            }}
                            className="rounded bg-primary-600 px-3 py-1 text-sm text-white"
                        >
                            Link
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
