import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Store } from 'lucide-react';

export default function LoginPage() {
    const navigate = useNavigate();
    const { login } = useAuth();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        login.mutate(
            { email, password, remember },
            { onSuccess: () => navigate('/dashboard') }
        );
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-[oklch(0.96_0.007_50)] px-4 dark:bg-[oklch(0.15_0.015_50)]">
            <Card className="w-full max-w-md border-[oklch(0.88_0.008_50)]">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-[oklch(0.93_0.04_50)]">
                        <Store className="h-6 w-6 text-[oklch(0.42_0.13_50)]" />
                    </div>
                    <CardTitle className="text-2xl">Welcome back</CardTitle>
                    <CardDescription>Sign in to your PharmaPOS account</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <Input
                            label="Email"
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="you@example.com"
                            required
                        />
                        <Input
                            label="Password"
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder="••••••••"
                            required
                        />
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
                            <span className="text-sm text-text-muted">Contact admin for password reset</span>
                        </div>
                        {login.isError && (
                            <p className="text-sm text-danger-600">
                                {(login.error as Error)?.message || 'Invalid credentials'}
                            </p>
                        )}
                        <Button type="submit" className="w-full bg-[oklch(0.42_0.13_50)] hover:bg-[oklch(0.55_0.13_50)]" loading={login.isPending}>
                            Sign in
                        </Button>
                    </form>
                    <p className="mt-4 text-center text-sm text-text-muted">
                        Don't have an account?{' '}
                        <Link to="/register" className="text-[oklch(0.42_0.13_50)] hover:underline">
                            Register
                        </Link>
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
