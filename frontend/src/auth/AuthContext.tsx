import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { api, setUnauthorizedHandler, tokenStore } from '../api/client';
import type { AuthResponse, Role, User } from '../api/types';

interface Auth {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<User>;
  startDemo: (role: Role) => Promise<User>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<Auth | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const queryClient = useQueryClient();
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(() => tokenStore.get() !== null);

  const clear = useCallback(() => {
    tokenStore.set(null);
    setUser(null);
    queryClient.clear();
  }, [queryClient]);

  useEffect(() => {
    setUnauthorizedHandler(clear);
    if (!tokenStore.get()) return;
    api
      .me()
      .then(setUser)
      .catch(clear)
      .finally(() => setLoading(false));
  }, [clear]);

  const accept = useCallback(
    (res: AuthResponse) => {
      queryClient.clear();
      tokenStore.set(res.token);
      setUser(res.user);
      return res.user;
    },
    [queryClient],
  );

  const value = useMemo<Auth>(
    () => ({
      user,
      loading,
      login: (email, password) => api.login(email, password).then(accept),
      startDemo: (role) => api.startDemo(role).then(accept),
      logout: async () => {
        await api.logout().catch(() => undefined);
        clear();
      },
    }),
    [user, loading, accept, clear],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

// eslint-disable-next-line react-refresh/only-export-components
export function useAuth(): Auth {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside <AuthProvider>');
  return ctx;
}
