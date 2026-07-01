import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PageLoader } from '@/components/ui/spinner';
import { PaymentGatewayForm } from '@/components/super-admin/payment-gateway-form';
import { Settings, CreditCard, Save } from 'lucide-react';
import type { PlatformSetting } from '@/types/super-admin';

const settingGroups = [
    {
        key: 'general',
        label: 'General',
        description: 'Basic platform settings',
        fields: [
            { key: 'platform_name', label: 'Platform Name', placeholder: 'PharmaPOS' },
            { key: 'platform_url', label: 'Platform URL', placeholder: 'https://pharmapos.com' },
            { key: 'support_email', label: 'Support Email', placeholder: 'support@pharmapos.com' },
            { key: 'support_phone', label: 'Support Phone', placeholder: '+977-1-XXXXXXX' },
        ],
    },
    {
        key: 'billing',
        label: 'Billing',
        description: 'Billing and payment settings',
        fields: [
            { key: 'default_currency', label: 'Default Currency', placeholder: 'NPR' },
            { key: 'tax_rate', label: 'Tax Rate (%)', placeholder: '13' },
            { key: 'invoice_prefix', label: 'Invoice Prefix', placeholder: 'INV-' },
        ],
    },
    {
        key: 'email',
        label: 'Email',
        description: 'Email notification settings',
        fields: [
            { key: 'smtp_host', label: 'SMTP Host', placeholder: 'smtp.gmail.com' },
            { key: 'smtp_port', label: 'SMTP Port', placeholder: '587' },
            { key: 'smtp_username', label: 'SMTP Username', placeholder: 'noreply@pharmapos.com' },
            { key: 'from_email', label: 'From Email', placeholder: 'noreply@pharmapos.com' },
            { key: 'from_name', label: 'From Name', placeholder: 'PharmaPOS' },
        ],
    },
    {
        key: 'sms',
        label: 'SMS',
        description: 'SMS notification settings',
        fields: [
            { key: 'sms_provider', label: 'SMS Provider', placeholder: 'sparrow' },
            { key: 'sms_api_key', label: 'SMS API Key', placeholder: '' },
            { key: 'sms_sender_id', label: 'SMS Sender ID', placeholder: 'PharmaPOS' },
        ],
    },
    {
        key: 'maintenance',
        label: 'Maintenance',
        description: 'System maintenance settings',
        fields: [
            { key: 'maintenance_mode', label: 'Maintenance Mode (on/off)', placeholder: 'off' },
            { key: 'maintenance_message', label: 'Maintenance Message', placeholder: 'System under maintenance' },
        ],
    },
];

export default function SuperAdminSettingsPage() {
    const queryClient = useQueryClient();
    const [formState, setFormState] = useState<Record<string, string>>({});

    const { data: settings, isLoading } = useQuery({
        queryKey: ['super-admin', 'settings'],
        queryFn: () => superAdminService.getSettings(),
        select: (res) => res.data,
    });

    const { data: gateways, isLoading: gatewaysLoading } = useQuery({
        queryKey: ['super-admin', 'payment-gateways'],
        queryFn: () => superAdminService.getPaymentGateways(),
        select: (res) => res.data,
    });

    useEffect(() => {
        if (settings) {
            const mapped: Record<string, string> = {};
            Object.values(settings).forEach((group) => {
                if (Array.isArray(group)) {
                    group.forEach((s: { key: string; value: string | null }) => {
                        mapped[s.key] = s.value ?? '';
                    });
                }
            });
            setFormState(mapped);
        }
    }, [settings]);

    const updateMutation = useMutation({
        mutationFn: () => superAdminService.updateSettings(formState),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'settings'] });
        },
    });

    const handleFieldChange = (key: string, value: string) => {
        setFormState((prev) => ({ ...prev, [key]: value }));
    };

    const handleGroupSave = () => {
        updateMutation.mutate();
    };

    if (isLoading || gatewaysLoading) return <PageLoader />;

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Platform Settings</h1>
            </div>

            <div className="space-y-6">
                {settingGroups.map((group) => (
                    <Card key={group.key}>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Settings className="h-5 w-5" />
                                {group.label}
                            </CardTitle>
                            <CardDescription>{group.description}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 sm:grid-cols-2">
                                {group.fields.map((field) => (
                                    <Input
                                        key={field.key}
                                        label={field.label}
                                        value={formState[field.key] ?? ''}
                                        onChange={(e) => handleFieldChange(field.key, e.target.value)}
                                        placeholder={field.placeholder}
                                    />
                                ))}
                            </div>
                            <div className="mt-4 flex justify-end">
                                <Button
                                    onClick={handleGroupSave}
                                    loading={updateMutation.isPending}
                                    size="sm"
                                >
                                    <Save className="mr-2 h-4 w-4" /> Save Changes
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                ))}

                {/* Payment Gateways Section */}
                <div className="space-y-4">
                    <div className="flex items-center gap-2">
                        <CreditCard className="h-5 w-5" />
                        <h2 className="text-xl font-semibold">Payment Gateways</h2>
                    </div>
                    <p className="text-sm text-text-muted">
                        Configure payment gateway credentials for subscription payments.
                    </p>
                    <div className="grid gap-6 lg:grid-cols-2">
                        {gateways?.map((gateway) => (
                            <PaymentGatewayForm key={gateway.id} gateway={gateway} />
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
