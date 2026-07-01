import { create } from 'zustand';

interface Notification {
    id: string;
    type: 'info' | 'success' | 'warning' | 'error';
    title: string;
    message?: string;
}

interface UIState {
    sidebarOpen: boolean;
    superAdminSidebarOpen: boolean;
    notifications: Notification[];
    toggleSidebar: () => void;
    setSidebarOpen: (open: boolean) => void;
    toggleSuperAdminSidebar: () => void;
    setSuperAdminSidebarOpen: (open: boolean) => void;
    addNotification: (notification: Omit<Notification, 'id'>) => void;
    removeNotification: (id: string) => void;
    clearNotifications: () => void;
}

export const useUIStore = create<UIState>()((set) => ({
    sidebarOpen: true,
    superAdminSidebarOpen: true,
    notifications: [],

    toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
    setSidebarOpen: (open) => set({ sidebarOpen: open }),
    toggleSuperAdminSidebar: () => set((state) => ({ superAdminSidebarOpen: !state.superAdminSidebarOpen })),
    setSuperAdminSidebarOpen: (open) => set({ superAdminSidebarOpen: open }),

    addNotification: (notification) =>
        set((state) => ({
            notifications: [
                ...state.notifications,
                { ...notification, id: crypto.randomUUID() },
            ],
        })),

    removeNotification: (id) =>
        set((state) => ({
            notifications: state.notifications.filter((n) => n.id !== id),
        })),

    clearNotifications: () => set({ notifications: [] }),
}));
