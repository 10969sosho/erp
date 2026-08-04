'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useState } from 'react';
import { groups, resources } from '@/lib/resources';
import { useSession } from '@/components/session-provider';

const groupIcons: Record<string, string> = { 'Master Data':'MD', Purchasing:'PO', Sales:'SO', Inventory:'IV', Finance:'FN', 'CRM & Service':'CR', Platform:'PF' };

export function AppShell({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const { user, ready, logout } = useSession();
  const [open, setOpen] = useState(false);
  const [expanded, setExpanded] = useState<string[]>(groups);
  const [search, setSearch] = useState('');
  const activeSlug = pathname.split('/').pop();
  const active = resources.find((item) => item.slug === activeSlug);

  function toggle(group: string) { setExpanded((current) => current.includes(group) ? current.filter((item) => item !== group) : [...current, group]); }
  const filtered = resources.filter((item) => `${item.title} ${item.group}`.toLowerCase().includes(search.toLowerCase()));

  if (!ready || !user) return <div className="session-loading"><div className="brand-mark">N</div><i className="spinner dark-spinner" /><span>Menyiapkan workspace...</span></div>;

  return <main className="app-shell">
    {open && <button className="nav-scrim" aria-label="Tutup navigasi" onClick={() => setOpen(false)} />}
    <aside className={`sidebar ${open ? 'sidebar-open' : ''}`}>
      <Link href="/workspace/dashboard" className="brand"><div className="brand-mark">N</div><div><strong>NEXORA</strong><span>distribution OS</span></div></Link>
      <div className="workspace-switcher"><span className="avatar avatar-teal">{user?.name?.slice(0,2).toUpperCase() ?? 'NS'}</span><div><small>Workspace</small><b>ERP Distributor</b></div><span className="connection-dot" title="Connected" /></div>
      <label className="nav-search"><span>/</span><input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Cari menu..." /></label>
      <nav className="sidebar-nav">
        <Link href="/workspace/dashboard" onClick={() => setOpen(false)} className={`nav-item ${activeSlug === 'dashboard' ? 'nav-active' : ''}`}><span className="nav-icon">OV</span>Overview</Link>
        {groups.map((group) => {
          const items = filtered.filter((item) => item.group === group);
          if (!items.length) return null;
          return <section className="nav-group" key={group}><button className="nav-group-button" onClick={() => toggle(group)}><span>{groupIcons[group]}</span>{group}<i>{expanded.includes(group) ? '-' : '+'}</i></button>{expanded.includes(group) && <div className="nav-children">{items.map((item) => <Link href={`/workspace/${item.slug}`} onClick={() => setOpen(false)} className={`nav-item ${item.slug === activeSlug ? 'nav-active' : ''}`} key={item.slug}><span className="nav-icon">{item.icon}</span>{item.title}</Link>)}</div>}</section>;
        })}
      </nav>
      <div className="sidebar-foot"><div className="avatar avatar-amber">{user?.name?.slice(0,2).toUpperCase() ?? 'US'}</div><div><b>{user?.name ?? 'User'}</b><span>{user?.email}</span></div><button onClick={logout} title="Keluar">OUT</button></div>
    </aside>
    <section className="content-area">
      <header className="topbar"><button className="mobile-menu" onClick={() => setOpen(true)} aria-label="Buka navigasi">MENU</button><div className="breadcrumbs"><span>Workspace</span><b>/</b><strong>{active?.title ?? (activeSlug === 'dashboard' ? 'Overview' : 'Page')}</strong></div><div className="top-actions"><Link href="/workspace/notifications" className="icon-button" title="Notifikasi">NT</Link><div className="profile"><span className="avatar avatar-amber">{user?.name?.slice(0,2).toUpperCase() ?? 'US'}</span><div><b>{user?.name}</b><small>Active workspace</small></div></div></div></header>
      {children}
    </section>
  </main>;
}
