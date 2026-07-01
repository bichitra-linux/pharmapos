import { useState, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation } from '@tanstack/react-query';
import { medicinesService } from '@/services/medicines';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast';
import { ArrowLeft, Upload, FileText, CheckCircle, AlertCircle } from 'lucide-react';

export default function ImportMedicine() {
    const navigate = useNavigate();
    const { addToast } = useToast();
    const fileRef = useRef<HTMLInputElement>(null);
    const [file, setFile] = useState<File | null>(null);
    const [result, setResult] = useState<{ imported: number; errors: string[] } | null>(null);

    const importMutation = useMutation({
        mutationFn: (f: File) => medicinesService.import(f),
        onSuccess: (res) => {
            setResult(res.data);
            addToast({
                type: res.data.errors.length > 0 ? 'warning' : 'success',
                title: `Imported ${res.data.imported} medicines`,
                message: res.data.errors.length > 0 ? `${res.data.errors.length} errors occurred` : undefined,
            });
        },
        onError: () => {
            addToast({ type: 'error', title: 'Import failed' });
        },
    });

    const handleImport = () => {
        if (!file) return;
        importMutation.mutate(file);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Button variant="ghost" onClick={() => navigate('/medicines')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                <h1 className="text-2xl font-bold">Import Medicines</h1>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Upload CSV File</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div
                            className="flex flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed border-border p-8 text-center hover:border-primary-400 cursor-pointer focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
                            role="button"
                            tabIndex={0}
                            onClick={() => fileRef.current?.click()}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter' || e.key === ' ') {
                                    e.preventDefault();
                                    fileRef.current?.click();
                                }
                            }}
                        >
                            <Upload className="h-10 w-10 text-text-muted" />
                            <div>
                                <p className="font-medium">Click to upload CSV file</p>
                                <p className="text-sm text-text-muted">or drag and drop</p>
                            </div>
                            {file && (
                                <div className="flex items-center gap-2 rounded-lg bg-primary-50 px-3 py-2 text-sm text-primary-700">
                                    <FileText className="h-4 w-4" />
                                    {file.name}
                                </div>
                            )}
                        </div>
                        <input
                            ref={fileRef}
                            type="file"
                            accept=".csv,.xlsx,.xls"
                            onChange={(e) => setFile(e.target.files?.[0] || null)}
                            className="hidden"
                        />
                        <Button onClick={handleImport} disabled={!file || importMutation.isPending} loading={importMutation.isPending} className="w-full">
                            Import Medicines
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>CSV Format Guide</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <p>Your CSV file should have the following columns:</p>
                        <div className="overflow-x-auto">
                            <table className="w-full text-xs">
                                <thead>
                                    <tr className="border-b">
                                        <th className="py-1.5 text-left font-medium">Column</th>
                                        <th className="py-1.5 text-left font-medium">Required</th>
                                        <th className="py-1.5 text-left font-medium">Example</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    <tr><td className="py-1.5">brand_name</td><td>Yes</td><td>Paracetamol 500mg</td></tr>
                                    <tr><td className="py-1.5">generic_name</td><td>Yes</td><td>Paracetamol</td></tr>
                                    <tr><td className="py-1.5">dosage_form</td><td>Yes</td><td>tablet, capsule, syrup</td></tr>
                                    <tr><td className="py-1.5">strength</td><td>No</td><td>500mg</td></tr>
                                    <tr><td className="py-1.5">unit_type</td><td>Yes</td><td>strip, bottle, tube</td></tr>
                                    <tr><td className="py-1.5">schedule_type</td><td>Yes</td><td>h, h1, x, g, otc</td></tr>
                                    <tr><td className="py-1.5">barcode</td><td>No</td><td>8901234567890</td></tr>
                                    <tr><td className="py-1.5">manufacturer</td><td>No</td><td>Cipla Ltd.</td></tr>
                                    <tr><td className="py-1.5">category</td><td>No</td><td>Antibiotics</td></tr>
                                    <tr><td className="py-1.5">hsn_code</td><td>No</td><td>30049099</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div className="rounded-lg bg-surface-muted p-3 text-xs text-text-muted">
                            <p className="font-medium mb-1">Schedule type values:</p>
                            <p><strong>h</strong> — Prescription required</p>
                            <p><strong>h1</strong> — Strict prescription (antibiotics etc.)</p>
                            <p><strong>x</strong> — Narcotic/psychotropic</p>
                            <p><strong>g</strong> — General (OTC)</p>
                            <p><strong>otc</strong> — Over the counter</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {result && (
                <Card>
                    <CardHeader>
                        <CardTitle>Import Results</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center gap-4 mb-4">
                            <div className="flex items-center gap-2 text-success-600">
                                <CheckCircle className="h-5 w-5" />
                                <span className="font-medium">{result.imported} medicines imported</span>
                            </div>
                            {result.errors.length > 0 && (
                                <div className="flex items-center gap-2 text-danger-600">
                                    <AlertCircle className="h-5 w-5" />
                                    <span className="font-medium">{result.errors.length} errors</span>
                                </div>
                            )}
                        </div>
                        {result.errors.length > 0 && (
                            <div className="rounded-lg border border-danger-200 bg-danger-50 p-3">
                                <p className="text-sm font-medium text-danger-700 mb-2">Errors:</p>
                                <ul className="list-disc list-inside text-sm text-danger-600 space-y-1">
                                    {result.errors.map((err, i) => (
                                        <li key={i}>{err}</li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
