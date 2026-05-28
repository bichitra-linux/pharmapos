import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Store } from 'lucide-react';

export default function RegisterPage() {
    const navigate = useNavigate();
    const { register } = useAuth();
    const [form, setForm] = useState({
        company_name: '',
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });

    const updateField = (field: keyof typeof form, value: string) => {
        setForm((prev) => ({ ...prev, [field]: value }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        register.mutate(form, {
            onSuccess: () => navigate('/dashboard'),
        });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-8">
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary-100">
                        <Store className="h-6 w-6 text-primary-600" />
                    </div>
                    <CardTitle className="text-2xl">Create account</CardTitle>
                    <CardDescription>Get started with PharmaPOS</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <Input
                            label="Company Name"
                            value={form.company_name}
                            onChange={(e) => updateField('company_name', e.target.value)}
                            placeholder="Your pharmacy name"
                            required
                        />
                        <Input
                            label="Full Name"
                            value={form.name}
                            onChange={(e) => updateField('name', e.target.value)}
                            placeholder="Your full name"
                            required
                        />
                        <Input
                            label="Email"
                            type="email"
                            value={form.email}
                            onChange={(e) => updateField('email', e.target.value)}
                            placeholder="you@example.com"
                            required
                        />
                        <Input
                            label="Phone"
                            type="tel"
                            value={form.phone}
                            onChange={(e) => updateField('phone', e.target.value)}
                            placeholder="+977-98XXXXXXXX"
                            required
                        />
                        <Input
                            label="Password"
                            type="password"
                            value={form.password}
                            onChange={(e) => updateField('password', e.target.value)}
                            placeholder="••••••••"
                            required
                        />
                        <Input
                            label="Confirm Password"
                            type="password"
                            value={form.password_confirmation}
                            onChange={(e) => updateField('password_confirmation', e.target.value)}
                            placeholder="••••••••"
                            required
                        />
                        {register.isError && (
                            <p className="text-sm text-danger-600">
                                {(register.error as Error)?.message || 'Registration failed'}
                            </p>
                        )}
                        <Button type="submit" className="w-full" loading={register.isPending}>
                            Create account
                        </Button>
                    </form>
                    <p className="mt-4 text-center text-sm text-gray-500">
                        Already have an account?{' '}
                        <Link to="/login" className="text-primary-600 hover:underline">
                            Sign in
                        </Link>
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
