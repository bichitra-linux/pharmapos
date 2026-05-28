import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import { customersService } from '@/services/customers';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast';
import { BLOOD_GROUPS } from '@/lib/constants';
import { ArrowLeft } from 'lucide-react';
import type { Customer } from '@/types';

export default function CreateEditCustomer() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { addToast } = useToast();
    const isEdit = !!id;

    const { data: customer } = useQuery({
        queryKey: ['customer', id],
        queryFn: () => customersService.get(Number(id)),
        enabled: isEdit,
        select: (res) => res.data,
    });

    const [form, setForm] = useState({
        name: '',
        phone: '',
        email: '',
        address: '',
        date_of_birth: '',
        gender: '',
        blood_group: '',
        allergies: '',
        credit_limit: 0,
    });

    useEffect(() => {
        if (customer) {
            setForm({
                name: customer.name,
                phone: customer.phone,
                email: customer.email || '',
                address: customer.address || '',
                date_of_birth: customer.date_of_birth || '',
                gender: customer.gender || '',
                blood_group: customer.blood_group || '',
                allergies: customer.allergies || '',
                credit_limit: customer.credit_limit,
            });
        }
    }, [customer]);

    const updateField = (field: string, value: string | number) => {
        setForm((prev) => ({ ...prev, [field]: value }));
    };

    const mutation = useMutation({
        mutationFn: (data: typeof form) =>
            isEdit
                ? customersService.update(Number(id), data as Partial<Customer>)
                : customersService.create(data as Partial<Customer>),
        onSuccess: () => {
            addToast({ type: 'success', title: `Customer ${isEdit ? 'updated' : 'created'} successfully` });
            navigate('/customers');
        },
        onError: () => {
            addToast({ type: 'error', title: `Failed to ${isEdit ? 'update' : 'create'} customer` });
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate(form);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Button variant="ghost" onClick={() => navigate('/customers')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                <h1 className="text-2xl font-bold">{isEdit ? 'Edit' : 'Add'} Customer</h1>
            </div>

            <form onSubmit={handleSubmit}>
                <Card>
                    <CardHeader>
                        <CardTitle>Customer Information</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <Input label="Name" value={form.name} onChange={(e) => updateField('name', e.target.value)} required />
                            <Input label="Phone" value={form.phone} onChange={(e) => updateField('phone', e.target.value)} required />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <Input label="Email" type="email" value={form.email} onChange={(e) => updateField('email', e.target.value)} />
                            <Input label="Date of Birth" type="date" value={form.date_of_birth} onChange={(e) => updateField('date_of_birth', e.target.value)} />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <Select
                                label="Gender"
                                options={[
                                    { label: 'Male', value: 'male' },
                                    { label: 'Female', value: 'female' },
                                    { label: 'Other', value: 'other' },
                                ]}
                                value={form.gender}
                                onChange={(v) => updateField('gender', v)}
                            />
                            <Select
                                label="Blood Group"
                                options={BLOOD_GROUPS.map((g) => ({ label: g, value: g }))}
                                value={form.blood_group}
                                onChange={(v) => updateField('blood_group', v)}
                            />
                        </div>
                        <Input label="Address" value={form.address} onChange={(e) => updateField('address', e.target.value)} />
                        <Input label="Allergies" value={form.allergies} onChange={(e) => updateField('allergies', e.target.value)} hint="Known drug allergies" />
                        <Input label="Credit Limit" type="number" value={form.credit_limit} onChange={(e) => updateField('credit_limit', parseFloat(e.target.value) || 0)} />
                    </CardContent>
                </Card>

                <div className="mt-6 flex justify-end gap-3">
                    <Button variant="outline" type="button" onClick={() => navigate('/customers')}>
                        Cancel
                    </Button>
                    <Button type="submit" loading={mutation.isPending}>
                        {isEdit ? 'Update' : 'Create'} Customer
                    </Button>
                </div>
            </form>
        </div>
    );
}
