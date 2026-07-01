import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast';
import { ArrowLeft } from 'lucide-react';
import api from '@/services/api';

export default function CreateReturn() {
    const navigate = useNavigate();
    const { addToast } = useToast();
    const [type, setType] = useState<'customer' | 'supplier'>('customer');
    const [referenceId, setReferenceId] = useState('');
    const [reason, setReason] = useState('');
    const [notes, setNotes] = useState('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            const endpoint = type === 'customer' ? '/sale-returns' : '/supplier-returns';
            await api.post(endpoint, {
                [type === 'customer' ? 'sale_id' : 'purchase_id']: Number(referenceId),
                reason,
                notes,
                items: [],
            });
            addToast({ type: 'success', title: 'Return created successfully' });
            navigate('/returns');
        } catch {
            addToast({ type: 'error', title: 'Failed to create return' });
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Button variant="ghost" onClick={() => navigate('/returns')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                <h1 className="text-2xl font-bold">Process Return</h1>
            </div>

            <form onSubmit={handleSubmit}>
                <Card>
                    <CardHeader>
                        <CardTitle>Return Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Select
                            label="Return Type"
                            options={[
                                { label: 'Customer Return', value: 'customer' },
                                { label: 'Supplier Return', value: 'supplier' },
                            ]}
                            value={type}
                            onChange={(v) => setType(v as 'customer' | 'supplier')}
                        />
                        <Input
                            label={type === 'customer' ? 'Sale ID' : 'Purchase ID'}
                            type="number"
                            value={referenceId}
                            onChange={(e) => setReferenceId(e.target.value)}
                            required
                        />
                        <Input label="Reason" value={reason} onChange={(e) => setReason(e.target.value)} required />
                        <Input label="Notes" value={notes} onChange={(e) => setNotes(e.target.value)} />
                    </CardContent>
                </Card>

                <div className="mt-6 flex justify-end gap-3">
                    <Button variant="outline" type="button" onClick={() => navigate('/returns')}>
                        Cancel
                    </Button>
                    <Button type="submit">Create Return</Button>
                </div>
            </form>
        </div>
    );
}
