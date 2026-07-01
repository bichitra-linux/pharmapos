import * as React from 'react';
import { useClickOutside } from '@/hooks/useClickOutside';
import { cn } from '@/lib/utils';

interface DropdownMenuProps {
    trigger: React.ReactNode;
    children: React.ReactNode;
    align?: 'left' | 'right';
}

export function DropdownMenu({ trigger, children, align = 'right' }: DropdownMenuProps) {
    const [open, setOpen] = React.useState(false);
    const ref = useClickOutside(() => setOpen(false));

    return (
        <div ref={ref} className="relative inline-block">
            <button type="button" onClick={() => setOpen(!open)} aria-haspopup="menu" aria-expanded={open}>
                {trigger}
            </button>
            {open && (
                <div
                    role="menu"
                    className={cn(
                        'absolute z-50 mt-1 min-w-[180px] rounded-md border border-border bg-surface py-1 shadow-lg',
                        align === 'right' ? 'right-0' : 'left-0'
                    )}
                    onClick={() => setOpen(false)}
                >
                    {children}
                </div>
            )}
        </div>
    );
}

export function DropdownMenuItem({
    children,
    onClick,
    className,
    destructive,
}: {
    children: React.ReactNode;
    onClick?: () => void;
    className?: string;
    destructive?: boolean;
}) {
    return (
        <button
            role="menuitem"
            onClick={onClick}
            className={cn(
                'flex w-full items-center px-3 py-2 text-sm hover:bg-surface-muted',
                destructive && 'text-danger-600 hover:bg-danger-50',
                className
            )}
        >
            {children}
        </button>
    );
}

export function DropdownMenuSeparator() {
    return <div role="separator" className="my-1 h-px bg-border" />;
}
