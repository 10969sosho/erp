'use client';

import { createContext, useContext, useEffect, useState } from 'react';
import { usePathname, useRouter } from 'next/navigation';
import { api, User } from '@/lib/api';

type Session = { user: User | null; ready: boolean; logout: () => Promise<void>; refresh: () => Promise<void> };
const SessionContext = createContext<Session>({ user: null, ready: false, logout: async () => {}, refresh: async () => {} });

export function SessionProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [ready, setReady] = useState(false);
  const pathname = usePathname();
  const router = useRouter();

  async function refresh() {
    if (!localStorage.getItem('nexora_token')) { setReady(true); return; }
    try { const result = await api<{ data: User }>('/auth/me'); setUser(result.data); }
    catch { localStorage.removeItem('nexora_token'); setUser(null); }
    finally { setReady(true); }
  }

  useEffect(() => {
    const timer = window.setTimeout(() => { void refresh(); }, 0);
    return () => window.clearTimeout(timer);
  }, []);
  useEffect(() => {
    if (!ready) return;
    if (!user && pathname !== '/login') router.replace('/login');
    if (user && pathname === '/login') router.replace('/workspace/dashboard');
  }, [pathname, ready, router, user]);

  async function logout() {
    try { await api('/auth/logout', { method: 'POST' }); } catch {}
    localStorage.removeItem('nexora_token'); setUser(null); router.replace('/login');
  }

  return <SessionContext.Provider value={{ user, ready, logout, refresh }}>{children}</SessionContext.Provider>;
}

export const useSession = () => useContext(SessionContext);
