import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import { suppliersService } from '@/services/suppliers';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast';
import { ArrowLeft } from 'lucide-react';

export default function CreateEditSupplier() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { addToast } = useToast();
    const isEdit = !!id;

    const { data: supplier } = useQuery({
        queryKey: ['supplier', id],
        queryFn: () => suppliersService.get(Number(id)),
        enabled: isEdit,
        select: (res) => res.data,
    });

    const [form, setForm] = useState({
        name: '',
        contact_person: '',
        phone: '',
        email: '',
        address: '',
        city: '',
        state: '',
        pan_number: '',
        bank_details: '',
        credit_limit: 0,
    });

    useEffect(() => {
        if (supplier) {
            setForm({
                name: supplier.name,
                contact_person: supplier.contact_person || '',
                phone: supplier.phone,
                email: supplier.email || '',
                address: supplier.address,
                city: supplier.city,
                state: supplier.state,
                pan_number: supplier.pan_number || '',
                bank_details: supplier.bank_details || '',
                credit_limit: supplier.credit_limit,
            });
        }
    }, [supplier]);

    const updateField = (field: keyof typeof form, value: string | number) => {
        setForm((prev) => ({ ...prev, [field]: value }));
    };

    const mutation = useMutation({
        mutationFn: (data: typeof form) =>
            isEdit ? suppliersService.update(Number(id), data) : suppliersService.create(data),
        onSuccess: () => {
            addToast({ type: 'success', title: `Supplier ${isEdit ? 'updated' : 'created'} successfully` });
            navigate('/suppliers');
        },
        onError: () => {
            addToast({ type: 'error', title: `Failed to ${isEdit ? 'update' : 'create'} supplier` });
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate(form);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Button variant="ghost" onClick={() => navigate('/suppliers')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                <h1 className="text-2xl font-bold">{isEdit ? 'Edit' : 'Add'} Supplier</h1>
            </div>

            <form onSubmit={handleSubmit}>
                <Card>
                    <CardHeader>
                        <CardTitle>Supplier Information</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <Input label="Company Name" value={form.name} onChange={(e) => updateField('name', e.target.value)} required />
                            <Input label="Contact Person" value={form.contact_person} onChange={(e) => updateField('contact_person', e.target.value)} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <Input label="Phone" value={form.phone} onChange={(e) => updateField('phone', e.target.value)} required />
                            <Input label="Email" type="email" value={form.email} onChange={(e) => updateField('email', e.target.value)} />
                        </div>
                        <Input label="Address" value={form.address} onChange={(e) => updateField('address', e.target.value)} required />
                        <div className="grid grid-cols-2 gap-4">
                            <Input label="City" value={form.city} onChange={(e) => updateField('city', e.target.value)} required />
                            <Input label="State" value={form.state} onChange={(e) => updateField('state', e.target.value)} required />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <Input label="PAN Number" value={form.pan_number} onChange={(e) => updateField('pan_number', e.target.value)} />
                            <Input label="Credit Limit" type="number" value={form.credit_limit} onChange={(e) => updateField('credit_limit', parseFloat(e.target.value) || 0)} />
                        </div>
                        <Input label="Bank Details" value={form.bank_details} onChange={(e) => updateField('bank_details', e.target.value)} />
                    </CardContent>
                </Card>

                <div className="mt-6 flex justify-end gap-3">
                    <Button variant="outline" type="button" onClick={() => navigate('/suppliers')}>
                        Cancel
                    </Button>
                    <Button type="submit" loading={mutation.isPending}>
                        {isEdit ? 'Update' : 'Create'} Supplier
                    </Button>
                </div>
            </form>
        </div>
    );
}
