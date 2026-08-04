'use client';

import Link from 'next/link';
import { useEffect, useState } from 'react';
import { api, API_URL, Row } from '@/lib/api';
import { useSession } from '@/components/session-provider';

const shortcuts = [
  { href:'/workspace/purchase-requests', label:'Purchase request', icon:'PR' }, { href:'/workspace/sales-orders', label:'Sales order', icon:'SO' },
  { href:'/workspace/stock-adjustments', label:'Stock adjustment', icon:'AD' }, { href:'/workspace/journals', label:'General journal', icon:'JV' },
];

export default function Dashboard() {
  const { user } = useSession();
  const [online, setOnline] = useState<boolean | null>(null);
  const [inventory, setInventory] = useState<Row[]>([]);
  const [notifications, setNotifications] = useState<Row[]>([]);
  const [audit, setAudit] = useState<Row[]>([]);
  const [aging, setAging] = useState({ ar:0, ap:0 });

  useEffect(() => {
    fetch(`${API_URL}/health`).then((response) => setOnline(response.ok)).catch(() => setOnline(false));
    Promise.allSettled([
      api<{data:Row[]}>('/reports/inventory-summary'), api<Record<string,unknown>>('/notifications'), api<Record<string,unknown>>('/audit-events?per_page=6'),
      api<{data:Record<string,{total:number}>}>('/reports/ar-aging'), api<{data:Record<string,{total:number}>}>('/reports/ap-aging'),
    ]).then(([stock, notes, events, ar, ap]) => {
      if (stock.status === 'fulfilled') setInventory(stock.value.data);
      if (notes.status === 'fulfilled') { const body = notes.value as {data?:Row[]}; setNotifications(Array.isArray(body.data) ? body.data : ((body as {data?:{data?:Row[]}}).data?.data ?? [])); }
      if (events.status === 'fulfilled') { const body = events.value as {data?:Row[]}; setAudit(Array.isArray(body.data) ? body.data : []); }
      const sum = (result: PromiseSettledResult<{data:Record<string,{total:number}>}>) => result.status === 'fulfilled' ? Object.values(result.value.data).reduce((total,bucket) => total + Number(bucket.total ?? 0),0) : 0;
      setAging({ ar:sum(ar), ap:sum(ap) });
    });
  }, []);
  const stockValue = inventory.reduce((total,row) => total + Number(row.total_value ?? 0),0);
  const money = (value:number) => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',notation:'compact',maximumFractionDigits:1}).format(value);

  return <div className="page-content dashboard-page">
    <div className="welcome-row"><div><p className="eyebrow">OPERATIONS CONTROL CENTER <span className={`live-dot ${online === false ? 'offline-dot' : ''}`} /> {online === null ? 'CHECKING' : online ? 'LIVE' : 'OFFLINE'}</p><h1>Selamat datang, {user?.name?.split(' ')[0]}.</h1><p className="subtitle">Satu pandangan untuk pergerakan distribusi hari ini.</p></div><div className="header-actions"><Link className="secondary-button" href="/workspace/report-jobs">Export report</Link><Link className="primary-button" href="/workspace/purchase-requests">+ Transaksi baru</Link></div></div>
    <section className="metric-grid">
      <article className="metric-card"><div><span>Nilai inventory</span><i>IV</i></div><strong>{money(stockValue)}</strong><p>{inventory.length} gudang tercatat</p></article>
      <article className="metric-card card-amber"><div><span>Piutang terbuka</span><i>AR</i></div><strong>{money(aging.ar)}</strong><p>Seluruh bucket aging</p></article>
      <article className="metric-card card-coral"><div><span>Hutang terbuka</span><i>AP</i></div><strong>{money(aging.ap)}</strong><p>Invoice belum lunas</p></article>
      <article className="metric-card card-blue"><div><span>Perlu perhatian</span><i>NT</i></div><strong>{notifications.filter((item) => item.status === 'unread').length}</strong><p>Notifikasi belum dibaca</p></article>
    </section>
    <section className="dashboard-grid">
      <article className="panel pulse-panel"><div className="panel-heading"><div><p className="eyebrow">INVENTORY NETWORK</p><h2>Nilai stok per gudang</h2></div><Link href="/workspace/inventory-summary">Buka laporan →</Link></div><div className="warehouse-bars">{inventory.length ? inventory.slice(0,6).map((row,index) => { const value=Number(row.total_value); const max=Math.max(...inventory.map((item)=>Number(item.total_value)),1); return <div key={String(row.warehouse_id)}><span>WH {String(row.warehouse_id).slice(0,8)}</span><i><em style={{width:`${Math.max(value/max*100,2)}%`,animationDelay:`${index*80}ms`}} /></i><b>{money(value)}</b></div>; }) : <div className="mini-empty">Belum ada saldo inventory.</div>}</div></article>
      <article className="panel shortcut-panel"><div className="panel-heading"><div><p className="eyebrow">QUICK ACTIONS</p><h2>Mulai pekerjaan</h2></div></div><div className="shortcut-grid">{shortcuts.map((item) => <Link href={item.href} key={item.href}><span>{item.icon}</span><b>{item.label}</b><i>→</i></Link>)}</div></article>
      <article className="panel activity-panel"><div className="panel-heading"><div><p className="eyebrow">AUDIT PULSE</p><h2>Aktivitas terbaru</h2></div><Link href="/workspace/audit-events">Semua aktivitas →</Link></div><div className="activity-list">{audit.length ? audit.map((event,index) => <div className="activity-row" key={String(event.id ?? index)}><span className="activity-dot" /><div><b>{String(event.action).replaceAll('_',' ')}</b><span>{String(event.entity_type).split('\\').pop()} · {String(event.entity_id ?? '').slice(0,8)}</span></div><time>{event.occurred_at ? new Intl.DateTimeFormat('id-ID',{dateStyle:'short',timeStyle:'short'}).format(new Date(String(event.occurred_at))) : '—'}</time></div>) : <div className="mini-empty">Belum ada aktivitas terbaru.</div>}</div></article>
      <article className="panel signal-panel"><div className="panel-heading"><div><p className="eyebrow">SYSTEM SIGNALS</p><h2>Status workspace</h2></div></div><div className="signal-list"><div className={`signal ${online ? 'signal-ok':'signal-danger'}`}><span>{online ? 'OK':'!'}</span><div><b>Backend API {online ? 'online':'offline'}</b><p>{online ? 'Semua operasi dapat diproses.':'Periksa Laravel server.'}</p></div></div><div className="signal signal-info"><span>DB</span><div><b>{inventory.length} warehouse summary</b><p>Snapshot inventory terakhir.</p></div></div><div className="signal signal-warn"><span>NT</span><div><b>{notifications.length} notifikasi</b><p>Permintaan approval dan sistem.</p></div></div></div></article>
    </section>
  </div>;
}
