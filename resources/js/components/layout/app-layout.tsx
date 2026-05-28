import { Outlet } from 'react-router-dom';
import { Sidebar } from './sidebar';
import { Header } from './header';
import { useUIStore } from '@/stores/uiStore';
import { cn } from '@/lib/utils';

export function AppLayout() {
    const sidebarOpen = useUIStore((s) => s.sidebarOpen);
    const toggleSidebar = useUIStore((s) => s.toggleSidebar);

    return (
        <div className="min-h-screen bg-gray-50">
            <Sidebar />

            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-30 bg-black/40 lg:hidden"
                    onClick={toggleSidebar}
                    aria-hidden="true"
                />
            )}

            <div
                className={cn(
                    'transition-all duration-300',
                    'ml-0 lg:ml-16',
                    sidebarOpen && 'lg:ml-64'
                )}
            >
                <Header />
                <main className="p-4 lg:p-6">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
