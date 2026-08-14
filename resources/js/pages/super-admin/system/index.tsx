import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { PageLoader } from '@/components/ui/spinner';
import {
    Server,
    Database,
    RefreshCw,
    Zap,
    CheckCircle2,
    XCircle,
} from 'lucide-react';
import { useToast } from '@/components/ui/toast';

export default function SuperAdminSystemPage() {
    const queryClient = useQueryClient();
    const { addToast } = useToast();

    const { data: health, isLoading, isError, error } = useQuery({
        queryKey: ['super-admin', 'health'],
        queryFn: () => superAdminService.getHealth(),
        select: (res) => res.data,
    });

    const clearCacheMutation = useMutation({
        mutationFn: () => superAdminService.clearCache(),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'health'] });
            addToast({ type: 'success', title: 'Cache cleared', message: 'All Laravel caches have been flushed.' });
        },
        onError: (err: any) => {
            addToast({ type: 'error', title: 'Clear cache failed', message: String(err?.message ?? err) });
        },
    });

    if (isLoading) return <PageLoader />;

    if (isError) {
        return (
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">System Health</h1>
                </div>
                <div className="rounded-lg border border-danger-200 bg-danger-50 p-6">
                    <div className="flex items-start gap-3">
                        <XCircle className="h-6 w-6 shrink-0 text-danger-600" />
                        <div className="flex-1">
                            <h3 className="text-base font-semibold text-danger-900">Unable to load system health</h3>
                            <p className="mt-1 text-sm text-danger-700">
                                {(error as any)?.message ?? 'The health endpoint returned an error.'}
                            </p>
                            <p className="mt-2 text-xs text-text-muted">
                                Try{' '}
                                <button
                                    type="button"
                                    onClick={() => clearCacheMutation.mutate()}
                                    className="font-medium text-primary-600 underline-offset-4 hover:underline"
                                >
                                    clearing the cache
                                </button>{' '}
                                or refresh the page.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    const healthChecks = [
        { label: 'Database', status: health?.checks?.database?.status, icon: Database },
        { label: 'Cache', status: health?.checks?.cache?.status, icon: Zap },
        { label: 'Queue', status: health?.checks?.queue?.status, icon: RefreshCw },
    ];

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">System Health</h1>
                <Button
                    variant="outline"
                    onClick={() => clearCacheMutation.mutate()}
                    loading={clearCacheMutation.isPending}
                >
                    <RefreshCw className="mr-2 h-4 w-4" /> Clear Cache
                </Button>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {healthChecks.map((check) => (
                    <Card key={check.label}>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className={`rounded-lg p-2 ${check.status === 'ok' ? 'bg-success-50' : 'bg-danger-50'}`}>
                                        <check.icon className={`h-5 w-5 ${check.status === 'ok' ? 'text-success-600' : 'text-danger-600'}`} />
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium">{check.label}</p>
                                        <p className="text-xs text-text-muted">Service status</p>
                                    </div>
                                </div>
                                {check.status === 'ok' ? (
                                    <CheckCircle2 className="h-6 w-6 text-success-500" />
                                ) : (
                                    <XCircle className="h-6 w-6 text-danger-500" />
                                )}
                            </div>
                            <div className="mt-3">
                                {check.status === 'ok' ? (
                                    <Badge variant="success">Healthy</Badge>
                                ) : (
                                    <Badge variant="destructive">Error</Badge>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Server className="h-5 w-5" />
                        System Information
                    </CardTitle>
                    <CardDescription>Runtime environment details</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="rounded-lg border border-border p-4">
                            <p className="text-xs font-medium uppercase text-text-muted">PHP Version</p>
                            <p className="mt-1 text-lg font-semibold">{health?.checks?.php_version?.message ?? '—'}</p>
                        </div>
                        <div className="rounded-lg border border-border p-4">
                            <p className="text-xs font-medium uppercase text-text-muted">Laravel Version</p>
                            <p className="mt-1 text-lg font-semibold">{health?.checks?.laravel_version?.message ?? '—'}</p>
                        </div>
                        <div className="rounded-lg border border-border p-4">
                            <p className="text-xs font-medium uppercase text-text-muted">System Status</p>
                            <p className="mt-1 text-lg font-semibold capitalize">{health?.status ?? '—'}</p>
                        </div>
                        <div className="rounded-lg border border-border p-4">
                            <p className="text-xs font-medium uppercase text-text-muted">Database</p>
                            <p className="mt-1 text-lg font-semibold">{health?.checks?.database?.message ?? '—'}</p>
                        </div>
                        <div className="rounded-lg border border-border p-4">
                            <p className="text-xs font-medium uppercase text-text-muted">Cache</p>
                            <p className="mt-1 text-lg font-semibold">{health?.checks?.cache?.message ?? '—'}</p>
                        </div>
                        <div className="rounded-lg border border-border p-4">
                            <p className="text-xs font-medium uppercase text-text-muted">Queue</p>
                            <p className="mt-1 text-lg font-semibold">{health?.checks?.queue?.message ?? '—'}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <RefreshCw className="h-5 w-5" />
                        Cache Management
                    </CardTitle>
                    <CardDescription>Clear application caches to resolve issues or apply changes</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap gap-3">
                        <Button
                            variant="outline"
                            onClick={() => clearCacheMutation.mutate()}
                            loading={clearCacheMutation.isPending}
                        >
                            <RefreshCw className="mr-2 h-4 w-4" /> Clear Cache
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
