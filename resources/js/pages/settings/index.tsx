import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { settingsService } from '@/services/settings';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PageLoader } from '@/components/ui/spinner';
import { useToast } from '@/components/ui/toast';

export default function SettingsPage() {
    const { addToast } = useToast();
    const queryClient = useQueryClient();

    const { data: company, isLoading } = useQuery({
        queryKey: ['settings', 'company'],
        queryFn: () => settingsService.getCompany(),
        select: (res) => res.data,
    });

    const [form, setForm] = useState({
        name: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        state: '',
        pan_number: '',
        vat_number: '',
    });

    useState(() => {
        if (company) {
            setForm({
                name: company.name,
                email: company.email,
                phone: company.phone,
                address: company.address,
                city: company.city,
                state: company.state,
                pan_number: company.pan_number,
                vat_number: company.vat_number,
            });
        }
    });

    const updateField = (field: string, value: string) => {
        setForm((prev) => ({ ...prev, [field]: value }));
    };

    const mutation = useMutation({
        mutationFn: (data: typeof form) => settingsService.updateCompany(data),
        onSuccess: () => {
            addToast({ type: 'success', title: 'Settings updated' });
            queryClient.invalidateQueries({ queryKey: ['settings'] });
        },
        onError: () => {
            addToast({ type: 'error', title: 'Failed to update settings' });
        },
    });

    if (isLoading) return <PageLoader />;

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold">Settings</h1>

            <form onSubmit={(e) => { e.preventDefault(); mutation.mutate(form); }}>
                <Card>
                    <CardHeader>
                        <CardTitle>Company Profile</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <Input label="Company Name" value={form.name} onChange={(e) => updateField('name', e.target.value)} required />
                            <Input label="Email" type="email" value={form.email} onChange={(e) => updateField('email', e.target.value)} required />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <Input label="Phone" value={form.phone} onChange={(e) => updateField('phone', e.target.value)} required />
                            <Input label="City" value={form.city} onChange={(e) => updateField('city', e.target.value)} />
                        </div>
                        <Input label="Address" value={form.address} onChange={(e) => updateField('address', e.target.value)} />
                        <Input label="State" value={form.state} onChange={(e) => updateField('state', e.target.value)} />
                        <div className="grid grid-cols-2 gap-4">
                            <Input label="PAN Number" value={form.pan_number} onChange={(e) => updateField('pan_number', e.target.value)} />
                            <Input label="VAT Number" value={form.vat_number} onChange={(e) => updateField('vat_number', e.target.value)} />
                        </div>
                    </CardContent>
                </Card>

                <div className="mt-6 flex justify-end">
                    <Button type="submit" loading={mutation.isPending}>
                        Save Settings
                    </Button>
                </div>
            </form>
        </div>
    );
}
