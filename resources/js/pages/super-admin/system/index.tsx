import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { superAdminService } from '@/services/super-admin';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { PageLoader } from '@/components/ui/spinner';
import {
    Server,
    Database,
    HardDrive,
    RefreshCw,
    Zap,
    CheckCircle2,
    XCircle,
} from 'lucide-react';

export default function SuperAdminSystemPage() {
    const queryClient = useQueryClient();

    const { data: health, isLoading } = useQuery({
        queryKey: ['super-admin', 'health'],
        queryFn: () => superAdminService.getHealth(),
        select: (res) => res.data,
    });

    const clearCacheMutation = useMutation({
        mutationFn: () => superAdminService.clearCache(),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['super-admin', 'health'] });
        },
    });

    if (isLoading) return <PageLoader />;

    const healthChecks = [
        { label: 'Database', status: health?.database, icon: Database },
        { label: 'Redis', status: health?.redis, icon: HardDrive },
        { label: 'Cache', status: health?.cache, icon: Zap },
        { label: 'Queue', status: health?.queue, icon: RefreshCw },
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
                                        <p className="text-xs text-gray-500">Service status</p>
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
                        <div className="rounded-lg border border-gray-200 p-4">
                            <p className="text-xs font-medium uppercase text-gray-500">PHP Version</p>
                            <p className="mt-1 text-lg font-semibold">{typeof window !== 'undefined' ? 'Server-side' : '—'}</p>
                        </div>
                        <div className="rounded-lg border border-gray-200 p-4">
                            <p className="text-xs font-medium uppercase text-gray-500">Laravel Version</p>
                            <p className="mt-1 text-lg font-semibold">11.x</p>
                        </div>
                        <div className="rounded-lg border border-gray-200 p-4">
                            <p className="text-xs font-medium uppercase text-gray-500">Node.js</p>
                            <p className="mt-1 text-lg font-semibold">Runtime</p>
                        </div>
                        <div className="rounded-lg border border-gray-200 p-4">
                            <p className="text-xs font-medium uppercase text-gray-500">Database</p>
                            <p className="mt-1 text-lg font-semibold">MySQL</p>
                        </div>
                        <div className="rounded-lg border border-gray-200 p-4">
                            <p className="text-xs font-medium uppercase text-gray-500">Cache Driver</p>
                            <p className="mt-1 text-lg font-semibold">Redis</p>
                        </div>
                        <div className="rounded-lg border border-gray-200 p-4">
                            <p className="text-xs font-medium uppercase text-gray-500">Queue Driver</p>
                            <p className="mt-1 text-lg font-semibold">Redis</p>
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
                            <RefreshCw className="mr-2 h-4 w-4" /> Clear All Cache
                        </Button>
                        <Button variant="outline" onClick={() => clearCacheMutation.mutate()} loading={clearCacheMutation.isPending}>
                            <Database className="mr-2 h-4 w-4" /> Clear Config Cache
                        </Button>
                        <Button variant="outline" onClick={() => clearCacheMutation.mutate()} loading={clearCacheMutation.isPending}>
                            <Zap className="mr-2 h-4 w-4" /> Clear Route Cache
                        </Button>
                        <Button variant="outline" onClick={() => clearCacheMutation.mutate()} loading={clearCacheMutation.isPending}>
                            <HardDrive className="mr-2 h-4 w-4" /> Clear View Cache
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
