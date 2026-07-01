import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/components/ui/toast';
import { Globe, Save, Wifi, ToggleLeft, ToggleRight } from 'lucide-react';
import type { PaymentGateway } from '@/types/super-admin';

interface Props {
    gateway: PaymentGateway;
}

const gatewayFields: Record<string, { key: string; label: string; type?: string }[]> = {
    esewa: [
        { key: 'merchant_code', label: 'Merchant Code' },
        { key: 'api_url', label: 'Payment URL' },
        { key: 'verify_url', label: 'Verify URL' },
        { key: 'success_url', label: 'Success URL' },
        { key: 'failure_url', label: 'Failure URL' },
    ],
    khalti: [
        { key: 'secret_key', label: 'Secret Key', type: 'password' },
        { key: 'api_url', label: 'API URL' },
        { key: 'verify_url', label: 'Verify URL' },
        { key: 'return_url', label: 'Return URL' },
    ],
    fonepay: [
        { key: 'merchant_id', label: 'Merchant ID' },
        { key: 'secret_key', label: 'Secret Key', type: 'password' },
        { key: 'payment_url', label: 'Payment URL' },
        { key: 'verify_url', label: 'Verify URL' },
        { key: 'return_url', label: 'Return URL' },
    ],
    connectips: [
        { key: 'merchant_id', label: 'Merchant ID' },
        { key: 'secret_key', label: 'Secret Key', type: 'password' },
        { key: 'payment_url', label: 'Payment URL' },
        { key: 'return_url', label: 'Return URL' },
    ],
    ime_pay: [
        { key: 'merchant_code', label: 'Merchant Code' },
        { key: 'secret_key', label: 'Secret Key', type: 'password' },
        { key: 'api_url', label: 'API URL' },
        { key: 'return_url', label: 'Return URL' },
    ],
};

export function PaymentGatewayForm({ gateway }: Props) {
    const queryClient = useQueryClient();
    const { addToast } = useToast();
    const [config, setConfig] = useState<Record<string, string>>(() => {
        const initial: Record<string, string> = {};
        const fields = gatewayFields[gateway.code] ?? [];
        for (const f of fields) {
            initial[f.key] = (gateway.config as Record<string, string>)?.[f.key] ?? '';
        }
        return initial;
    });

    const updateMutation = useMutation({
        mutationFn: () => superAdminService.updatePaymentGateway(gateway.id, { config }),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'payment-gateways'] });
            addToast({ type: 'success', title: 'Gateway updated successfully.' });
        },
        onError: () => addToast({ type: 'error', title: 'Failed to update gateway.' }),
    });

    const toggleMutation = useMutation({
        mutationFn: () => superAdminService.togglePaymentGateway(gateway.id),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'payment-gateways'] });
            addToast({ type: 'success', title: gateway.is_active ? 'Gateway deactivated.' : 'Gateway activated.' });
        },
    });

    const testMutation = useMutation({
        mutationFn: () => superAdminService.testPaymentGateway(gateway.id),
        onSuccess: (res) => {
            addToast({ type: res.success ? 'success' : 'error', title: res.message ?? 'Connection test completed.' });
        },
        onError: () => addToast({ type: 'error', title: 'Test connection failed.' }),
    });

    const fields = gatewayFields[gateway.code] ?? [];

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2">
                        <Globe className="h-5 w-5" />
                        {gateway.name}
                    </CardTitle>
                    <div className="flex items-center gap-2">
                        {gateway.is_sandbox && <Badge variant="warning">Sandbox</Badge>}
                        <Badge variant={gateway.is_active ? 'success' : 'secondary'}>
                            {gateway.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div className="grid gap-4 sm:grid-cols-2">
                    {fields.map((field) => (
                        <Input
                            key={field.key}
                            label={field.label}
                            type={field.type ?? 'text'}
                            value={config[field.key] ?? ''}
                            onChange={(e) => setConfig((prev) => ({ ...prev, [field.key]: e.target.value }))}
                        />
                    ))}
                </div>
                <div className="mt-4 flex flex-wrap items-center gap-2">
                    <Button
                        onClick={() => updateMutation.mutate()}
                        loading={updateMutation.isPending}
                        size="sm"
                    >
                        <Save className="mr-1 h-4 w-4" /> Save
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() => toggleMutation.mutate()}
                        loading={toggleMutation.isPending}
                        size="sm"
                    >
                        {gateway.is_active
                            ? <><ToggleRight className="mr-1 h-4 w-4" /> Deactivate</>
                            : <><ToggleLeft className="mr-1 h-4 w-4" /> Activate</>
                        }
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() => testMutation.mutate()}
                        loading={testMutation.isPending}
                        size="sm"
                        disabled={!gateway.is_active}
                    >
                        <Wifi className="mr-1 h-4 w-4" /> Test Connection
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
