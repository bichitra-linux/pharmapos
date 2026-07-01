import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { SuperAdmin } from '@/types/super-admin';

interface SuperAdminState {
    user: SuperAdmin | null;
    token: string | null;
    isAuthenticated: boolean;
    setUser: (user: SuperAdmin, token: string) => void;
    setToken: (token: string) => void;
    logout: () => void;
}

export const useSuperAdminStore = create<SuperAdminState>()(
    persist(
        (set) => ({
            user: null,
            token: null,
            isAuthenticated: false,
            setUser: (user, token) => {
                set({ user, token, isAuthenticated: true });
            },
            setToken: (token) => {
                set({ token, isAuthenticated: true });
            },
            logout: () => {
                set({ user: null, token: null, isAuthenticated: false });
            },
        }),
        {
            name: 'super-admin-storage',
            partialize: (state) => ({
                user: state.user,
                token: state.token,
                isAuthenticated: state.isAuthenticated,
            }),
        }
    )
);
