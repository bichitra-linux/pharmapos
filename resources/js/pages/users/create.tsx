import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import api from '@/services/api';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast';
import { ROLES } from '@/lib/constants';
import { ArrowLeft } from 'lucide-react';
import type { User } from '@/types';

export default function CreateEditUser() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { addToast } = useToast();
    const isEdit = !!id;

    const { data: user } = useQuery({
        queryKey: ['user', id],
        queryFn: async () => {
            const res = await api.get(`/users/${id}`);
            return res.data.data as User | undefined;
        },
        enabled: isEdit,
    });

    const [form, setForm] = useState({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        role: 'cashier' as string,
        is_active: true,
    });

    useEffect(() => {
        if (user) {
            setForm({
                name: user.name,
                email: user.email,
                phone: user.phone || '',
                password: '',
                password_confirmation: '',
                role: user.role,
                is_active: user.is_active,
            });
        }
    }, [user]);

    const updateField = (field: keyof typeof form, value: string | number | boolean) => {
        setForm((prev) => ({ ...prev, [field]: value }));
    };

    const mutation = useMutation({
        mutationFn: async (data: typeof form) => {
            if (isEdit) {
                return api.put(`/users/${id}`, data);
            }
            return api.post('/users', data);
        },
        onSuccess: () => {
            addToast({ type: 'success', title: `User ${isEdit ? 'updated' : 'created'} successfully` });
            navigate('/users');
        },
        onError: () => {
            addToast({ type: 'error', title: `Failed to ${isEdit ? 'update' : 'create'} user` });
        },
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        mutation.mutate(form);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-4">
                <Button variant="ghost" onClick={() => navigate('/users')}>
                    <ArrowLeft className="mr-2 h-4 w-4" />
                    Back
                </Button>
                <h1 className="text-2xl font-bold">{isEdit ? 'Edit' : 'Add'} User</h1>
            </div>

            <form onSubmit={handleSubmit}>
                <Card>
                    <CardHeader>
                        <CardTitle>User Information</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Input label="Name" value={form.name} onChange={(e) => updateField('name', e.target.value)} required />
                            <Input label="Email" type="email" value={form.email} onChange={(e) => updateField('email', e.target.value)} required />
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Input label="Phone" value={form.phone} onChange={(e) => updateField('phone', e.target.value)} />
                            <Select
                                label="Role"
                                options={ROLES.map((r) => ({ label: r.charAt(0).toUpperCase() + r.slice(1), value: r }))}
                                value={form.role}
                                onChange={(v) => updateField('role', v)}
                            />
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Input
                                label="Password"
                                type="password"
                                value={form.password}
                                onChange={(e) => updateField('password', e.target.value)}
                                hint={isEdit ? 'Leave empty to keep current' : ''}
                                required={!isEdit}
                            />
                            <Input
                                label="Confirm Password"
                                type="password"
                                value={form.password_confirmation}
                                onChange={(e) => updateField('password_confirmation', e.target.value)}
                                required={!isEdit}
                            />
                        </div>
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.is_active}
                                onChange={(e) => updateField('is_active', e.target.checked)}
                                className="rounded border-border"
                            />
                            Active
                        </label>
                    </CardContent>
                </Card>

                <div className="mt-6 flex justify-end gap-3">
                    <Button variant="outline" type="button" onClick={() => navigate('/users')}>
                        Cancel
                    </Button>
                    <Button type="submit" loading={mutation.isPending}>
                        {isEdit ? 'Update' : 'Create'} User
                    </Button>
                </div>
            </form>
        </div>
    );
}
