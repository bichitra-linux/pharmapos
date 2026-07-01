import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import { medicinesService } from '@/services/medicines';
import { manufacturersService } from '@/services/manufacturers';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast';
import { DOSAGE_FORMS, UNIT_TYPES, SCHEDULE_TYPES } from '@/lib/constants';
import { ArrowLeft } from 'lucide-react';

export default function CreateMedicine() {
    const navigate = useNavigate();
    const { addToast } = useToast();
    const [form, setForm] = useState({
        generic_name: '',
        brand_name: '',
        manufacturer_id: null as number | null,
        medicine_category_id: null as number | null,
        salt_composition_id: null as number | null,
        dosage_form: '',
        strength: '',
        unit_type: 'strip',
        units_per_pack: 1,
        barcode: '',
        hsn_code: '',
        schedule_type: 'otc',
        is_prescription_required: false,
        is_temperature_sensitive: false,
        allow_piece_selling: true,
        piece_unit_label: 'tablet',
        description: '',
        storage_conditions: '',
    });

    const updateField = (field: string, value: string | number | boolean | null) => {
        setForm((prev) => ({ ...prev, [field]: value }));
    };

    const { data: manufacturers } = useQuery({
        queryKey: ['manufacturers', 'list'],
        queryFn: () => manufacturersService.list({ per_page: 200 }),
        select: (res) => res.data?.data ?? res.data ?? [],
        staleTime: 60_000,
    });

    const duplicateConfirm = useMutation({
        mutationFn: (data: typeof form) => medicinesService.create(data),
        onSuccess: (res) => {
            addToast({ type: 'success', title: 'Medicine created successfully' });
            navigate(`/medicines/${res.data.id}`);
        },
        onError: () => addToast({ type: 'error', title: 'Failed to create medicine' }),
    });

    const mutation = useMutation({
        mutationFn: medicinesService.create,
        onSuccess: (res) => {
            addToast({ type: 'success', title: 'Medicine created successfully' });
            navigate(`/medicines/${res.data.id}`);
        },
        onError: (err: any) => {
            const errData = err?.response?.data;
            if (errData?.warning === 'duplicate') {
                const s = errData.similar;
                if (window.confirm(`Similar medicine already exists:\n${s.brand_name}${s.strength ? ' ' + s.strength : ''} (${s.manufacturer ?? '-'})\n\nCreate anyway?`)) {
                    duplicateConfirm.mutate(form);
                }
                return;
            }
            addToast({ type: 'error', title: 'Failed to create medicine' });
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate(form);
    };

    const manufacturerList: { id: number; name: string; country?: string }[] = Array.isArray(manufacturers) ? manufacturers : [];

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Button variant="ghost" onClick={() => navigate('/medicines')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                <h1 className="text-2xl font-bold">Add Medicine</h1>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Input label="Brand Name" value={form.brand_name} onChange={(e) => updateField('brand_name', e.target.value)} required />
                            <Input label="Generic Name" value={form.generic_name} onChange={(e) => updateField('generic_name', e.target.value)} required />
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <Select label="Dosage Form" options={DOSAGE_FORMS.map((f) => ({ label: f.label, value: f.value }))} value={form.dosage_form} onChange={(v) => updateField('dosage_form', v)} />
                                <Input label="Strength" value={form.strength} onChange={(e) => updateField('strength', e.target.value)} placeholder="e.g. 500mg" />
                            </div>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <Select label="Unit Type" options={UNIT_TYPES.map((u) => ({ label: u.label, value: u.value }))} value={form.unit_type} onChange={(v) => updateField('unit_type', v)} />
                                <Select label="Schedule" options={SCHEDULE_TYPES.map((s) => ({ label: s.label, value: s.value }))} value={form.schedule_type} onChange={(v) => updateField('schedule_type', v)} />
                            </div>
                            {form.schedule_type && (
                                <div className="rounded-lg border border-primary-200 bg-primary-50 p-3 text-sm text-primary-800">
                                    <p className="font-medium mb-1">Schedule Information</p>
                                    <p>{SCHEDULE_TYPES.find(s => s.value === form.schedule_type)?.description || ''}</p>
                                    {['h', 'h1', 'x'].includes(form.schedule_type) && (
                                        <p className="mt-2 font-medium text-warning-700">
                                            ⚠ This medicine requires a valid prescription for dispensing.
                                        </p>
                                    )}
                                    {form.schedule_type === 'x' && (
                                        <p className="mt-1 font-medium text-danger-700">
                                            ⚠ Narcotic/psychotropic substance — entries will be recorded in the Narcotics Register.
                                        </p>
                                    )}
                                </div>
                            )}
                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <Input label="Units Per Pack" type="number" value={form.units_per_pack} onChange={(e) => updateField('units_per_pack', parseInt(e.target.value) || 1)} />
                                <div className="flex items-center gap-4">
                                    <label htmlFor="allow_piece_selling" className="flex items-center gap-2 text-sm">
                                        <input id="allow_piece_selling" type="checkbox" checked={form.allow_piece_selling} onChange={(e) => updateField('allow_piece_selling', e.target.checked)} className="rounded border-border" />
                                        Allow piece selling
                                    </label>
                                    {form.allow_piece_selling && (
                                        <Input label="Piece unit label" value={form.piece_unit_label} onChange={(e) => updateField('piece_unit_label', e.target.value)} className="w-40" />
                                    )}
                                </div>
                                <Input label="Barcode" value={form.barcode} onChange={(e) => updateField('barcode', e.target.value)} />
                                <Input label="HSN Code" value={form.hsn_code} onChange={(e) => updateField('hsn_code', e.target.value)} />
                            </div>
                            <div className="flex gap-6">
                                <label htmlFor="is_prescription_required" className="flex items-center gap-2 text-sm">
                                    <input id="is_prescription_required" type="checkbox" checked={form.is_prescription_required} onChange={(e) => updateField('is_prescription_required', e.target.checked)} className="rounded border-border" />
                                    Requires Prescription
                                </label>
                                <label htmlFor="is_temperature_sensitive" className="flex items-center gap-2 text-sm">
                                    <input id="is_temperature_sensitive" type="checkbox" checked={form.is_temperature_sensitive} onChange={(e) => updateField('is_temperature_sensitive', e.target.checked)} className="rounded border-border" />
                                    Temperature Sensitive
                                </label>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <label className="mb-1 block text-sm font-medium">Manufacturer</label>
                                    <select
                                        value={form.manufacturer_id ?? ''}
                                        onChange={(e) => updateField('manufacturer_id', e.target.value ? parseInt(e.target.value) : null)}
                                        className="w-full rounded border border-border px-3 py-2 text-sm"
                                    >
                                        <option value="">Select manufacturer...</option>
                                        {manufacturerList.map((m) => (
                                            <option key={m.id} value={m.id}>{m.name}{m.country ? ` (${m.country})` : ''}</option>
                                        ))}
                                    </select>
                                </div>
                                <Input label="Category ID" type="number" value={form.medicine_category_id ?? ''} onChange={(e) => updateField('medicine_category_id', e.target.value ? parseInt(e.target.value) : null)} />
                                <Input label="Salt Composition ID" type="number" value={form.salt_composition_id ?? ''} onChange={(e) => updateField('salt_composition_id', e.target.value ? parseInt(e.target.value) : null)} />
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Additional Details</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Input label="Description" value={form.description} onChange={(e) => updateField('description', e.target.value)} />
                                <Input label="Storage Conditions" value={form.storage_conditions} onChange={(e) => updateField('storage_conditions', e.target.value)} />
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <Button variant="outline" type="button" onClick={() => navigate('/medicines')}>
                        Cancel
                    </Button>
                    <Button type="submit" loading={mutation.isPending}>
                        Create Medicine
                    </Button>
                </div>
            </form>
        </div>
    );
}
