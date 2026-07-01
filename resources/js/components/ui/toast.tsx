import * as React from 'react';
import { X, CheckCircle, AlertCircle, AlertTriangle, Info } from 'lucide-react';
import { cn } from '@/lib/utils';

type ToastType = 'success' | 'error' | 'warning' | 'info';

interface Toast {
    id: string;
    type: ToastType;
    title: string;
    message?: string;
}

interface ToastContextType {
    toasts: Toast[];
    addToast: (toast: Omit<Toast, 'id'>) => void;
    removeToast: (id: string) => void;
}

const ToastContext = React.createContext<ToastContextType | null>(null);

export function useToast() {
    const context = React.useContext(ToastContext);
    if (!context) {
        throw new Error('useToast must be used within a ToastProvider');
    }
    return context;
}

export function ToastProvider({ children }: { children: React.ReactNode }) {
    const [toasts, setToasts] = React.useState<Toast[]>([]);

    const timersRef = React.useRef<Map<string, ReturnType<typeof setTimeout>>>(new Map());

    React.useEffect(() => {
        return () => {
            timersRef.current.forEach((timer) => clearTimeout(timer));
        };
    }, []);

    const addToast = React.useCallback((toast: Omit<Toast, 'id'>) => {
        const id = Date.now().toString();
        setToasts((prev) => [...prev, { ...toast, id }]);
        const timer = setTimeout(() => {
            setToasts((prev) => prev.filter((t) => t.id !== id));
            timersRef.current.delete(id);
        }, 5000);
        timersRef.current.set(id, timer);
    }, []);

    const removeToast = React.useCallback((id: string) => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
    }, []);

    return (
        <ToastContext.Provider value={{ toasts, addToast, removeToast }}>
            {children}
            <ToastContainer toasts={toasts} removeToast={removeToast} />
        </ToastContext.Provider>
    );
}

const icons: Record<ToastType, React.ReactNode> = {
    success: <CheckCircle className="h-5 w-5 text-success-500" aria-hidden="true" />,
    error: <AlertCircle className="h-5 w-5 text-danger-500" aria-hidden="true" />,
    warning: <AlertTriangle className="h-5 w-5 text-warning-500" aria-hidden="true" />,
    info: <Info className="h-5 w-5 text-primary-500" aria-hidden="true" />,
};

function ToastContainer({
    toasts,
    removeToast,
}: {
    toasts: Toast[];
    removeToast: (id: string) => void;
}) {
    return (
        <div
            className="fixed bottom-4 right-4 z-[100] flex flex-col gap-2"
            role="status"
            aria-live="polite"
            aria-label="Notifications"
        >
            {toasts.map((toast) => (
                <div
                    key={toast.id}
                    role={toast.type === 'error' ? 'alert' : 'status'}
                    aria-live={toast.type === 'error' ? 'assertive' : 'polite'}
                    className={cn(
                        'flex w-80 items-start gap-3 rounded-lg border bg-surface p-4 shadow-lg transition-all duration-300 ease-in-out'
                    )}
                >
                    {icons[toast.type]}
                    <div className="flex-1">
                        <p className="text-sm font-medium">{toast.title}</p>
                        {toast.message && (
                            <p className="mt-1 text-xs text-text-muted">{toast.message}</p>
                        )}
                    </div>
                    <button
                        onClick={() => removeToast(toast.id)}
                        aria-label="Dismiss notification"
                    >
                        <X className="h-4 w-4 text-text-muted" aria-hidden="true" />
                    </button>
                </div>
            ))}
        </div>
    );
}
