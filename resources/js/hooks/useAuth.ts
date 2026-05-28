import { useAuthStore } from '@/stores/authStore';
import { authService } from '@/services/auth';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

export function useAuth() {
    const { user, token, isAuthenticated, setAuth, logout: storeLogout } = useAuthStore();
    const queryClient = useQueryClient();

    const meQuery = useQuery({
        queryKey: ['auth', 'me'],
        queryFn: () => authService.getMe(),
        enabled: isAuthenticated && !!token,
        select: (res) => res.data,
    });

    const loginMutation = useMutation({
        mutationFn: authService.login,
        onSuccess: (res) => {
            setAuth(res.data.user, res.data.token);
            queryClient.invalidateQueries({ queryKey: ['auth'] });
        },
    });

    const registerMutation = useMutation({
        mutationFn: authService.register,
        onSuccess: (res) => {
            setAuth(res.data.user, res.data.token);
        },
    });

    const logoutMutation = useMutation({
        mutationFn: authService.logout,
        onSettled: () => {
            storeLogout();
            queryClient.clear();
        },
    });

    const logout = () => {
        logoutMutation.mutate();
    };

    return {
        user: meQuery.data ?? user,
        isAuthenticated,
        isLoading: meQuery.isLoading,
        login: loginMutation,
        register: registerMutation,
        logout,
    };
}
