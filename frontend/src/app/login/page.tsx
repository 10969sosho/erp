'use client';

import { FormEvent, useState } from 'react';
import { api, ApiError, User } from '@/lib/api';
import { useSession } from '@/components/session-provider';

export default function LoginPage() {
  const { refresh } = useSession();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); setBusy(true); setError('');
    const data = new FormData(event.currentTarget);
    try {
      const result = await api<{ data: { token: string; user: User } }>('/auth/login', { method: 'POST', body: JSON.stringify(Object.fromEntries(data)) });
      localStorage.setItem('nexora_token', result.data.token); await refresh();
    } catch (reason) { setError(reason instanceof ApiError ? reason.message : 'Tidak dapat terhubung ke server.'); }
    finally { setBusy(false); }
  }

  return <main className="login-page"><section className="login-story"><div className="brand brand-large"><div className="brand-mark">N</div><div><strong>NEXORA</strong><span>distribution OS</span></div></div><div><p className="eyebrow light">ONE NETWORK. EVERY MOVEMENT.</p><h1>Operasi distribusi,<br />dalam satu ritme.</h1><p>Pembelian, stok, penjualan, dan keuangan yang terhubung dari permintaan hingga jurnal.</p></div><div className="login-stat"><b>79</b><span>API operations connected</span></div></section><section className="login-form-wrap"><form className="login-card" onSubmit={submit}><p className="eyebrow">SECURE WORKSPACE</p><h2>Selamat datang kembali</h2><p>Masuk untuk melanjutkan pekerjaan hari ini.</p>{error && <div className="inline-alert alert-error"><b>Login gagal</b><span>{error}</span></div>}<label>Email<input name="email" type="email" defaultValue="admin@example.com" required autoFocus /></label><label>Password<input name="password" type="password" defaultValue="ChangeMe123!" required /></label><input name="device_name" type="hidden" value="Nexora Web" /><button className="primary-button full-button" disabled={busy}>{busy ? <><i className="spinner" /> Memverifikasi...</> : 'Masuk ke workspace →'}</button><small>Gunakan akun yang disediakan administrator sistem.</small></form></section></main>;
}
