import { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { BrandLogo } from '@/components/ui/brand-logo';
import { Eye, EyeOff } from 'lucide-react';
import { useSuperAdminStore } from '@/stores/superAdminStore';
import { superAdminService } from '@/services/super-admin';

export default function SuperAdminLoginPage() {
    const navigate = useNavigate();
    const setUser = useSuperAdminStore((s) => s.setUser);
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setLoading(true);
        try {
            const res = await superAdminService.login({ email, password });
            setUser(res.data.user, res.data.token);
            navigate('/super-admin/dashboard');
        } catch (err) {
            const axiosErr = err as { response?: { data?: { message?: string } } };
            setError(axiosErr.response?.data?.message || 'Invalid credentials');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-primary-950 to-surface px-4">
            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary-100">
                        <BrandLogo variant="mark" size="md" decorative />
                    </div>
                    <CardTitle className="text-2xl">Super Admin</CardTitle>
                    <CardDescription>Sign in to the platform management panel</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <Input
                            label="Email"
                            type="email"
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="admin@pharmapos.com"
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
                        {error && (
                            <p role="alert" className="text-sm text-danger-600">{error}</p>
                        )}
                        <p className="rounded-md bg-warning-50 p-3 text-xs text-warning-700">
                            Restricted area — platform administrators only. Unauthorized access is logged.
                        </p>
                        <Button type="submit" className="w-full" loading={loading}>
                            Sign in
                        </Button>
                    </form>
                    <p className="mt-4 text-center">
                        <Link to="/" className="text-sm text-text-muted hover:text-text hover:underline">
                            ← Back to home
                        </Link>
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
