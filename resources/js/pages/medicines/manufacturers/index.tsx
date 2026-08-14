import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { manufacturersService } from '@/services/manufacturers';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { PageLoader } from '@/components/ui/spinner';
import { useToast } from '@/components/ui/toast';
import { useNavigate } from 'react-router-dom';
import { ArrowLeft, Plus, Save, X } from 'lucide-react';
import type { Manufacturer } from '@/types';

export default function ManufacturersIndex() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const { addToast } = useToast();
    const [newName, setNewName] = useState('');
    const [newCountry, setNewCountry] = useState('');
    const [editing, setEditing] = useState<{ id: number; name: string; country: string } | null>(null);

    const { data, isLoading } = useQuery({
        queryKey: ['manufacturers'],
        queryFn: () => manufacturersService.list({ per_page: 100 }),
        select: (res) => res.data,
    });

    const createMutation = useMutation({
        mutationFn: () => manufacturersService.create({ name: newName, country: newCountry || undefined }),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['manufacturers'] });
            setNewName('');
            setNewCountry('');
            addToast({ type: 'success', title: 'Manufacturer created' });
        },
        onError: () => addToast({ type: 'error', title: 'Failed to create manufacturer' }),
    });

    const updateMutation = useMutation({
        mutationFn: () => manufacturersService.update(editing!.id, { name: editing!.name, country: editing!.country || undefined }),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['manufacturers'] });
            setEditing(null);
            addToast({ type: 'success', title: 'Manufacturer updated' });
        },
        onError: () => addToast({ type: 'error', title: 'Failed to update' }),
    });

    if (isLoading) return <PageLoader />;

    const manufacturers: Manufacturer[] = Array.isArray(data) ? data : [];

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" onClick={() => navigate('/medicines')}>
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Back
                    </Button>
                    <h1 className="text-2xl font-bold">Manufacturers</h1>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Add Manufacturer</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex gap-3">
                        <Input
                            placeholder="Manufacturer name"
                            value={newName}
                            onChange={(e) => setNewName(e.target.value)}
                            className="max-w-xs"
                        />
                        <Input
                            placeholder="Country (optional)"
                            value={newCountry}
                            onChange={(e) => setNewCountry(e.target.value)}
                            className="max-w-[160px]"
                        />
                        <Button onClick={() => createMutation.mutate()} loading={createMutation.isPending} disabled={!newName.trim()}>
                            <Plus className="mr-1 h-4 w-4" /> Add
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {manufacturers.map((m) => (
                    <Card key={m.id}>
                        <CardContent className="p-4">
                            {editing?.id === m.id ? (
                                <div className="space-y-2">
                                    <Input value={editing.name} onChange={(e) => setEditing({ ...editing, name: e.target.value })} />
                                    <Input value={editing.country} onChange={(e) => setEditing({ ...editing, country: e.target.value })} />
                                    <div className="flex gap-2">
                                        <Button size="sm" onClick={() => updateMutation.mutate()} loading={updateMutation.isPending}>
                                            <Save className="mr-1 h-3 w-3" /> Save
                                        </Button>
                                        <Button size="sm" variant="outline" onClick={() => setEditing(null)}>
                                            <X className="mr-1 h-3 w-3" /> Cancel
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <div>
                                    <p className="font-medium">{m.name}</p>
                                    {m.country && <p className="text-xs text-text-muted">{m.country}</p>}
                                    <Button size="sm" variant="ghost" onClick={() => setEditing({ id: m.id, name: m.name, country: m.country ?? '' })} className="mt-2">
                                        Edit
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}
