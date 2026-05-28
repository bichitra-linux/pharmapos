import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import { medicinesService } from '@/services/medicines';
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
        manufacturer_id: 0,
        medicine_category_id: 0,
        salt_composition_id: 0,
        dosage_form: '',
        strength: '',
        unit_type: 'strip',
        units_per_pack: 1,
        barcode: '',
        hsn_code: '',
        schedule_type: 'OTC',
        is_prescription_required: false,
        is_temperature_sensitive: false,
        description: '',
        storage_conditions: '',
    });

    const updateField = (field: string, value: string | number | boolean) => {
        setForm((prev) => ({ ...prev, [field]: value }));
    };

    const mutation = useMutation({
        mutationFn: medicinesService.create,
        onSuccess: (res) => {
            addToast({ type: 'success', title: 'Medicine created successfully' });
            navigate(`/medicines/${res.data.id}`);
        },
        onError: () => {
            addToast({ type: 'error', title: 'Failed to create medicine' });
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate(form);
    };

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
                            <div className="grid grid-cols-2 gap-4">
                                <Select label="Dosage Form" options={DOSAGE_FORMS.map((f) => ({ label: f, value: f }))} value={form.dosage_form} onChange={(v) => updateField('dosage_form', v)} />
                                <Input label="Strength" value={form.strength} onChange={(e) => updateField('strength', e.target.value)} placeholder="e.g. 500mg" />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <Select label="Unit Type" options={UNIT_TYPES.map((u) => ({ label: u, value: u }))} value={form.unit_type} onChange={(v) => updateField('unit_type', v)} />
                                <Select label="Schedule" options={SCHEDULE_TYPES.map((s) => ({ label: s, value: s }))} value={form.schedule_type} onChange={(v) => updateField('schedule_type', v)} />
                            </div>
                            <div className="grid grid-cols-3 gap-4">
                                <Input label="Units Per Pack" type="number" value={form.units_per_pack} onChange={(e) => updateField('units_per_pack', parseInt(e.target.value) || 1)} />
                                <Input label="Barcode" value={form.barcode} onChange={(e) => updateField('barcode', e.target.value)} />
                                <Input label="HSN Code" value={form.hsn_code} onChange={(e) => updateField('hsn_code', e.target.value)} />
                            </div>
                            <div className="flex gap-6">
                                <label className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" checked={form.is_prescription_required} onChange={(e) => updateField('is_prescription_required', e.target.checked)} className="rounded border-gray-300" />
                                    Requires Prescription
                                </label>
                                <label className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" checked={form.is_temperature_sensitive} onChange={(e) => updateField('is_temperature_sensitive', e.target.checked)} className="rounded border-gray-300" />
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
                                <Input label="Manufacturer ID" type="number" value={form.manufacturer_id} onChange={(e) => updateField('manufacturer_id', parseInt(e.target.value) || 0)} />
                                <Input label="Category ID" type="number" value={form.medicine_category_id} onChange={(e) => updateField('medicine_category_id', parseInt(e.target.value) || 0)} />
                                <Input label="Salt Composition ID" type="number" value={form.salt_composition_id} onChange={(e) => updateField('salt_composition_id', parseInt(e.target.value) || 0)} />
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
