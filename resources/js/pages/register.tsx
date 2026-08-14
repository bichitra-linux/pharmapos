import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { BrandLogo } from '@/components/ui/brand-logo';
import { Eye, EyeOff } from 'lucide-react';

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
    const [showPassword, setShowPassword] = useState(false);

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
        <div className="flex min-h-screen items-center justify-center bg-surface-muted px-4 py-8">
            <Card className="w-full max-w-2xl">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-100">
                        <BrandLogo variant="mark" size="md" decorative />
                    </div>
                    <CardTitle className="text-2xl">Create account</CardTitle>
                    <CardDescription>Get started with PharmaPOS — 14-day free trial</CardDescription>
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
                            autoComplete="email"
                            required
                        />
                        <Input
                            label="Phone"
                            type="tel"
                            value={form.phone}
                            onChange={(e) => updateField('phone', e.target.value)}
                            placeholder="+977-98XXXXXXXX"
                            autoComplete="tel"
                            required
                        />
                        <div className="relative">
                            <Input
                                label="Password"
                                type={showPassword ? 'text' : 'password'}
                                value={form.password}
                                onChange={(e) => updateField('password', e.target.value)}
                                placeholder="••••••••"
                                autoComplete="new-password"
                                hint="Minimum 8 characters with letters, numbers, and mixed case"
                                className="pr-10"
                                required
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword((v) => !v)}
                                aria-label={showPassword ? 'Hide password' : 'Show password'}
                                className="absolute right-2 top-[34px] min-h-[44px] min-w-[44px] flex items-center justify-center text-text-muted hover:text-text"
                            >
                                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                            </button>
                        </div>
                        <Input
                            label="Confirm Password"
                            type={showPassword ? 'text' : 'password'}
                            value={form.password_confirmation}
                            onChange={(e) => updateField('password_confirmation', e.target.value)}
                            placeholder="••••••••"
                            autoComplete="new-password"
                            required
                        />
                        {register.isError && (
                            <p role="alert" className="text-sm text-danger-600 sm:col-span-2">
                                {(register.error as Error)?.message || 'Registration failed'}
                            </p>
                        )}
                        <Button type="submit" className="w-full sm:col-span-2" loading={register.isPending}>
                            Create account
                        </Button>
                    </form>
                    <p className="mt-4 text-center text-sm text-text-muted">
                        Already have an account?{' '}
                        <Link to="/login" className="text-primary-600 hover:underline">
                            Sign in
                        </Link>
                    </p>
                    <p className="mt-2 text-center">
                        <Link to="/" className="text-sm text-text-muted hover:text-text hover:underline">
                            ← Back to home
                        </Link>
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
