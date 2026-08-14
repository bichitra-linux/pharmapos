import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useToast } from '@/components/ui/toast';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { PageLoader } from '@/components/ui/spinner';
import { Building2, ChevronDown, ChevronRight, ArrowLeft } from 'lucide-react';
import type { SelectOption } from '@/components/ui/select';

export default function SuperAdminTenantCreate() {
    const navigate = useNavigate();
    const { addToast } = useToast();
    const [showMore, setShowMore] = useState(false);

    const { data: plans, isLoading: plansLoading } = useQuery({
        queryKey: ['super-admin', 'plans'],
        queryFn: () => superAdminService.getPlans(),
        select: (res) => res.data,
    });

    const planOptions: SelectOption[] = (plans ?? []).map((p) => ({
        label: `${p.name} (रू ${p.price_monthly}/mo)`,
        value: p.id,
    }));

    const [form, setForm] = useState({
        name: '',
        email: '',
        phone: '',
        address: '',
        pan_number: '',
        vat_number: '',
        drug_license_number: '',
        pharmacy_license_number: '',
        pharmacist_name: '',
        pharmacist_registration_number: '',
        subscription_plan_id: '' as string | number,
        admin_name: '',
        admin_email: '',
        admin_password: '',
    });

    const update = (field: string, value: string | number) =>
        setForm((prev) => ({ ...prev, [field]: value }));

    const createMutation = useMutation({
        mutationFn: () =>
            superAdminService.createTenant({
                name: form.name,
                email: form.email,
                phone: form.phone || undefined,
                address: form.address || undefined,
                pan_number: form.pan_number || undefined,
                vat_number: form.vat_number || undefined,
                drug_license_number: form.drug_license_number || undefined,
                pharmacy_license_number: form.pharmacy_license_number || undefined,
                pharmacist_name: form.pharmacist_name || undefined,
                pharmacist_registration_number: form.pharmacist_registration_number || undefined,
                subscription_plan_id: form.subscription_plan_id ? Number(form.subscription_plan_id) : undefined,
                admin_name: form.admin_name,
                admin_email: form.admin_email,
                admin_password: form.admin_password,
            }),
        onSuccess: () => {
            addToast({ type: 'success', title: 'Tenant created', message: 'The pharmacy has been created.' });
            navigate('/super-admin/tenants');
        },
        onError: (err: any) => {
            const msg = err?.message?.includes('Validation failed') ? err.message : (err?.response?.data?.message ?? String(err?.message ?? err));
            addToast({ type: 'error', title: 'Create failed', message: msg });
        },
    });

    if (plansLoading) return <PageLoader />;

    return (
        <div className="space-y-6">
            <div className="flex items-center gap-4">
                <Button variant="ghost" size="icon" onClick={() => navigate('/super-admin/tenants')}>
                    <ArrowLeft className="h-5 w-5" />
                </Button>
                <h1 className="text-2xl font-bold">Create Tenant</h1>
            </div>

            <form onSubmit={(e) => { e.preventDefault(); createMutation.mutate(); }}>
                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building2 className="h-5 w-5" />
                                Pharmacy Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Input
                                    label="Pharmacy Name *"
                                    value={form.name}
                                    onChange={(e) => update('name', e.target.value)}
                                    placeholder="e.g. City Pharmacy"
                                    required
                                />
                                <Input
                                    label="Email *"
                                    type="email"
                                    value={form.email}
                                    onChange={(e) => update('email', e.target.value)}
                                    placeholder="pharmacy@example.com"
                                    required
                                />
                                <Input
                                    label="Phone"
                                    value={form.phone}
                                    onChange={(e) => update('phone', e.target.value)}
                                    placeholder="+977-98XXXXXXXX"
                                />
                                <Input
                                    label="Address"
                                    value={form.address}
                                    onChange={(e) => update('address', e.target.value)}
                                    placeholder="Street, city, district"
                                />
                                <Input
                                    label="PAN Number"
                                    value={form.pan_number}
                                    onChange={(e) => update('pan_number', e.target.value)}
                                    placeholder="PAN"
                                />
                                <Input
                                    label="VAT Number"
                                    value={form.vat_number}
                                    onChange={(e) => update('vat_number', e.target.value)}
                                    placeholder="VAT"
                                />
                                <Input
                                    label="Drug License Number"
                                    value={form.drug_license_number}
                                    onChange={(e) => update('drug_license_number', e.target.value)}
                                    placeholder="Drug license"
                                />
                                <Input
                                    label="Pharmacy License Number"
                                    value={form.pharmacy_license_number}
                                    onChange={(e) => update('pharmacy_license_number', e.target.value)}
                                    placeholder="Pharmacy license"
                                />
                                <Input
                                    label="Pharmacist Name"
                                    value={form.pharmacist_name}
                                    onChange={(e) => update('pharmacist_name', e.target.value)}
                                    placeholder="Pharmacist in charge"
                                />
                                <Input
                                    label="Pharmacist Registration Number"
                                    value={form.pharmacist_registration_number}
                                    onChange={(e) => update('pharmacist_registration_number', e.target.value)}
                                    placeholder="Registration number"
                                />
                            </div>

                            <div className="mt-4">
                                <Select
                                    label="Subscription Plan"
                                    options={planOptions}
                                    value={form.subscription_plan_id}
                                    onChange={(v) => update('subscription_plan_id', v)}
                                    placeholder="Select a plan (optional)"
                                />
                            </div>

                            <button
                                type="button"
                                onClick={() => setShowMore(!showMore)}
                                className="mt-4 flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700"
                            >
                                {showMore ? <ChevronDown className="h-4 w-4" /> : <ChevronRight className="h-4 w-4" />}
                                {showMore ? 'Less fields' : 'More fields'}
                            </button>

                            {showMore && (
                                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                    <Input
                                        label="Country"
                                        value="Nepal"
                                        onChange={() => {}}
                                        disabled
                                    />
                                    <Input
                                        label="State"
                                        value=""
                                        onChange={() => {}}
                                        placeholder="State / Province"
                                    />
                                    <Input
                                        label="Local Level"
                                        value=""
                                        onChange={() => {}}
                                        placeholder="Municipality / Rural municipality"
                                    />
                                    <Input
                                        label="Registration Number"
                                        value=""
                                        onChange={() => {}}
                                        placeholder="Company registration"
                                    />
                                    <div className="sm:col-span-2">
                                        <Input
                                            label="Google Maps Link"
                                            value=""
                                            onChange={() => {}}
                                            placeholder="https://maps.google.com/..."
                                        />
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building2 className="h-5 w-5" />
                                Admin Account
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Input
                                    label="Admin Name *"
                                    value={form.admin_name}
                                    onChange={(e) => update('admin_name', e.target.value)}
                                    placeholder="Full name"
                                    required
                                />
                                <Input
                                    label="Admin Email *"
                                    type="email"
                                    value={form.admin_email}
                                    onChange={(e) => update('admin_email', e.target.value)}
                                    placeholder="admin@pharmacy.com"
                                    required
                                />
                                <Input
                                    label="Admin Password *"
                                    type="password"
                                    value={form.admin_password}
                                    onChange={(e) => update('admin_password', e.target.value)}
                                    placeholder="Min 8 characters"
                                    required
                                    minLength={8}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Button variant="outline" onClick={() => navigate('/super-admin/tenants')}>
                            Cancel
                        </Button>
                        <Button type="submit" loading={createMutation.isPending}>
                            Create Tenant
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    );
}
