import { useState } from 'react';
import { useNavigate, Link, useSearchParams } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { BrandLogo } from '@/components/ui/brand-logo';
import { Eye, EyeOff } from 'lucide-react';

export default function LoginPage() {
    const navigate = useNavigate();
    const { login } = useAuth();
    const [searchParams] = useSearchParams();
    const suspendedReason = searchParams.get('suspended');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [remember, setRemember] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        login.mutate(
            { email, password, remember },
            { onSuccess: () => navigate('/dashboard') }
        );
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-surface-muted px-4">
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-100">
                        <BrandLogo variant="mark" size="md" decorative />
                    </div>
                    <CardTitle className="text-2xl">Welcome back</CardTitle>
                    <CardDescription>Sign in to your PharmaPOS account</CardDescription>
                </CardHeader>
                <CardContent>
                    {suspendedReason && (
                        <div role="alert" className="mb-4 rounded-md border border-danger-200 bg-danger-50 p-3 text-sm text-danger-700">
                            Your account is suspended: {suspendedReason}
                        </div>
                    )}
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <Input
                            label="Email"
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="you@example.com"
                            autoComplete="email"
                            required
                        />
                        <div className="relative">
                            <Input
                                label="Password"
                                type={showPassword ? 'text' : 'password'}
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                placeholder="••••••••"
                                autoComplete="current-password"
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
                        <div className="flex items-center justify-between">
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={remember}
                                    onChange={(e) => setRemember(e.target.checked)}
                                    className="rounded border-border"
                                />
                                Remember me
                            </label>
                            <span className="text-sm text-text-muted">Forgot password? Contact your admin</span>
                        </div>
                        {login.isError && (
                            <p role="alert" className="text-sm text-danger-600">
                                {((login.error as Error)?.message || 'Invalid credentials')}
                            </p>
                        )}
                        <Button type="submit" className="w-full" loading={login.isPending}>
                            Sign in
                        </Button>
                    </form>
                    <p className="mt-4 text-center text-sm text-text-muted">
                        Don't have an account?{' '}
                        <Link to="/register" className="text-primary-600 hover:underline">
                            Register
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
