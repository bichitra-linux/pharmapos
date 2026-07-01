import { useEffect } from 'react';

interface ShortcutMap {
    [key: string]: () => void;
}

export function useKeyboardShortcuts(shortcuts: ShortcutMap) {
    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement || e.target instanceof HTMLSelectElement) {
                if (e.key === 'Escape') {
                    (e.target as HTMLElement).blur();
                }
                return;
            }

            const key = e.key.toLowerCase();
            const action = shortcuts[key] || shortcuts[`F${key}`];

            if (action) {
                e.preventDefault();
                action();
            }
        };

        window.addEventListener('keydown', handler);
        return () => window.removeEventListener('keydown', handler);
    }, [shortcuts]);
}
