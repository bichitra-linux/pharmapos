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
        <div className="flex min-h-screen items-center justify-center bg-[oklch(0.96_0.007_50)] px-4 py-8 dark:bg-[oklch(0.15_0.015_50)]">
            <Card className="w-full max-w-2xl border-[oklch(0.88_0.008_50)]">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[oklch(0.93_0.04_50)]">
                        <Store className="h-6 w-6 text-[oklch(0.42_0.13_50)]" />
                    </div>
                    <CardTitle className="text-2xl">Create account</CardTitle>
                    <CardDescription>Get started with PharmaPOS</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="grid gap-4 sm:grid-cols-2">
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
                        <div className="sm:col-span-2 grid gap-4 sm:grid-cols-2">
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
                        </div>
                        <Input
                            label="Confirm Password"
                            type="password"
                            value={form.password_confirmation}
                            onChange={(e) => updateField('password_confirmation', e.target.value)}
                            placeholder="••••••••"
                            required
                        />
                        {register.isError && (
                            <p className="text-sm text-danger-600 sm:col-span-2">
                                {(register.error as Error)?.message || 'Registration failed'}
                            </p>
                        )}
                        <Button type="submit" className="w-full bg-[oklch(0.42_0.13_50)] hover:bg-[oklch(0.55_0.13_50)] sm:col-span-2" loading={register.isPending}>
                            Create account
                        </Button>
                    </form>
                    <p className="mt-4 text-center text-sm text-text-muted">
                        Already have an account?{' '}
                        <Link to="/login" className="text-[oklch(0.42_0.13_50)] hover:underline">
                            Sign in
                        </Link>
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
