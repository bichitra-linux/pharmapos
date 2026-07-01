import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { settingsService } from '@/services/settings';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PageLoader } from '@/components/ui/spinner';
import { useToast } from '@/components/ui/toast';
import { NEPAL_PROVINCES, NEPAL_LOCAL_LEVELS, COUNTRY_CODES } from '@/lib/nepal-data';

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
        phone_country_code: '+977',
        phone: '',
        address: '',
        city: '',
        state: '',
        country: 'Nepal',
        local_level: '',
        registration_number: '',
        google_maps_link: '',
        pan_number: '',
        vat_number: '',
    });

    useEffect(() => {
        if (company) {
            setForm({
                name: company.name ?? '',
                email: company.email ?? '',
                phone_country_code: company.phone_country_code ?? '+977',
                phone: company.phone ?? '',
                address: company.address ?? '',
                city: company.city ?? '',
                state: company.state ?? '',
                country: company.country ?? 'Nepal',
                local_level: company.local_level ?? '',
                registration_number: company.registration_number ?? '',
                google_maps_link: company.google_maps_link ?? '',
                pan_number: company.pan_number ?? '',
                vat_number: company.vat_number ?? '',
            });
        }
    }, [company]);

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

    const localLevelOptions = form.state ? (NEPAL_LOCAL_LEVELS[form.state] ?? []) : [];

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-bold">Settings</h1>

            <form onSubmit={(e) => { e.preventDefault(); mutation.mutate(form); }}>
                <Card>
                    <CardHeader>
                        <CardTitle>Company Profile</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Input label="Company Name" value={form.name} onChange={(e) => updateField('name', e.target.value)} required />
                            <Input label="Email" type="email" value={form.email} onChange={(e) => updateField('email', e.target.value)} required />
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div className="flex gap-2">
                                <div className="w-28">
                                    <Select
                                        label="Code"
                                        options={COUNTRY_CODES}
                                        value={form.phone_country_code}
                                        onChange={(val) => updateField('phone_country_code', String(val))}
                                    />
                                </div>
                                <div className="flex-1">
                                    <Input label="Phone" value={form.phone} onChange={(e) => updateField('phone', e.target.value)} required />
                                </div>
                            </div>
                            <Input label="City" value={form.city} onChange={(e) => updateField('city', e.target.value)} />
                        </div>
                        <Input label="Address" value={form.address} onChange={(e) => updateField('address', e.target.value)} />
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Select
                                label="Country"
                                options={[{ label: 'Nepal', value: 'Nepal' }, { label: 'India', value: 'India' }, { label: 'Other', value: 'Other' }]}
                                value={form.country}
                                onChange={(val) => {
                                    updateField('country', String(val));
                                    if (val !== 'Nepal') {
                                        updateField('state', '');
                                        updateField('local_level', '');
                                    }
                                }}
                            />
                            <Select
                                label="Province"
                                options={NEPAL_PROVINCES}
                                value={form.state}
                                onChange={(val) => {
                                    updateField('state', String(val));
                                    updateField('local_level', '');
                                }}
                                disabled={form.country !== 'Nepal'}
                            />
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Select
                                label="Local Level"
                                options={localLevelOptions}
                                value={form.local_level}
                                onChange={(val) => updateField('local_level', String(val))}
                                disabled={form.country !== 'Nepal' || !form.state}
                            />
                            <Input label="Registration Number" value={form.registration_number} onChange={(e) => updateField('registration_number', e.target.value)} />
                        </div>
                        <Input label="Google Maps Link" value={form.google_maps_link} onChange={(e) => updateField('google_maps_link', e.target.value)} placeholder="https://maps.google.com/..." />
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
