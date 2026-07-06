import * as React from 'react';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

interface DialogProps {
    open: boolean;
    onClose: () => void;
    children: React.ReactNode;
    size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
}

export function Dialog({ open, onClose, children, size = 'md' }: DialogProps) {
    const dialogRef = React.useRef<HTMLDivElement>(null);

    React.useEffect(() => {
        if (open) {
            document.body.style.overflow = 'hidden';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [open]);

    React.useEffect(() => {
        if (!open) return;

        const handleKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                onClose();
                return;
            }

            if (e.key === 'Tab' && dialogRef.current) {
                const focusable = dialogRef.current.querySelectorAll<HTMLElement>(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                if (focusable.length === 0) return;

                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (e.shiftKey) {
                    if (document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    }
                } else {
                    if (document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        requestAnimationFrame(() => {
            const focusable = dialogRef.current?.querySelectorAll<HTMLElement>(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            focusable?.[0]?.focus();
        });

        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [open, onClose]);

    if (!open) return null;

    const sizeClasses = {
        sm: 'max-w-sm max-h-[90vh]',
        md: 'max-w-lg max-h-[90vh]',
        lg: 'max-w-2xl max-h-[90vh]',
        xl: 'max-w-4xl max-h-[90vh]',
        full: 'max-w-[95vw] max-h-[95vh]',
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center" role="dialog" aria-modal="true">
            <div className="fixed inset-0 bg-black/50" onClick={onClose} aria-hidden="true" />
            <div
                ref={dialogRef}
                className={cn(
                    'relative z-50 w-full rounded-lg bg-surface p-6 shadow-xl flex flex-col',
                    sizeClasses[size]
                )}
            >
                {children}
            </div>
        </div>
    );
}

export function DialogHeader({ children, className }: { children: React.ReactNode; className?: string }) {
    return <div className={cn('mb-4', className)}>{children}</div>;
}

export function DialogTitle({ children, className }: { children: React.ReactNode; className?: string }) {
    return <h2 className={cn('text-lg font-semibold', className)}>{children}</h2>;
}

export function DialogDescription({ children, className }: { children: React.ReactNode; className?: string }) {
    return <p className={cn('text-sm text-text-muted', className)}>{children}</p>;
}

export function DialogContent({ children, className }: { children: React.ReactNode; className?: string }) {
    return <div className={cn('py-4 overflow-y-auto flex-1 min-h-0 thin-scrollbar', className)}>{children}</div>;
}

export function DialogFooter({ children, className }: { children: React.ReactNode; className?: string }) {
    return <div className={cn('mt-4 flex justify-end gap-2', className)}>{children}</div>;
}

export function DialogClose({ onClose }: { onClose: () => void }) {
    return (
        <button
            onClick={onClose}
            aria-label="Close dialog"
            className="absolute right-4 top-4 rounded-sm opacity-70 hover:opacity-100"
        >
            <X className="h-4 w-4" aria-hidden="true" />
        </button>
    );
}
