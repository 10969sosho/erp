'use client';

import { FormEvent, useEffect, useEffectEvent, useState } from 'react';
import { api, ApiError, Row } from '@/lib/api';
import { Field, Resource } from '@/lib/resources';

type LookupMap = Record<string, { label: string; value: string }[]>;
type Toast = { tone: 'success' | 'error'; title: string; message: string } | null;

function valueAt(row: Row, key: string): unknown {
  return key.split('.').reduce<unknown>((value, part) => value && typeof value === 'object' ? (value as Row)[part] : undefined, row);
}
function display(value: unknown, key = '') {
  if (value === null || value === undefined || value === '') return '—';
  if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';
  if (typeof value === 'object') return Array.isArray(value) ? `${value.length} item` : JSON.stringify(value);
  if (/(_total|amount|price|cost|value|debit|credit|on_hand|reserved|quantity|rate)$/.test(key) && !Number.isNaN(Number(value))) return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(Number(value));
  if (key.includes('date') || key.endsWith('_at')) { const parsed = new Date(String(value)); if (!Number.isNaN(parsed.valueOf())) return new Intl.DateTimeFormat('id-ID', { dateStyle:'medium', ...(key.endsWith('_at') ? { timeStyle:'short' as const } : {}) }).format(parsed); }
  return String(value).replaceAll('_', ' ');
}
function label(key: string) { return key.replaceAll('_',' ').replace(/\b\w/g, (letter) => letter.toUpperCase()).replace(' Id',' ID'); }

function normalize(payload: unknown): { rows: Row[]; page: number; lastPage: number; total: number } {
  const body = payload as Record<string, unknown>;
  if (Array.isArray(body?.data) && 'current_page' in body) return { rows:body.data as Row[], page:Number(body.current_page ?? 1), lastPage:Number(body.last_page ?? 1), total:Number(body.total ?? 0) };
  let data = body?.data;
  if (data && typeof data === 'object' && !Array.isArray(data) && Array.isArray((data as Row).data)) data = (data as Row);
  if (Array.isArray(data)) return { rows:data as Row[], page:1, lastPage:1, total:data.length };
  if (data && typeof data === 'object' && Array.isArray((data as Row).data)) { const paged = data as Row; return { rows:paged.data as Row[], page:Number(paged.current_page ?? 1), lastPage:Number(paged.last_page ?? 1), total:Number(paged.total ?? 0) }; }
  if (data && typeof data === 'object') {
    const buckets = Object.entries(data as Row).flatMap(([bucket, value]) => value && typeof value === 'object' && Array.isArray((value as Row).items) ? ((value as Row).items as Row[]).map((item) => ({ ...item, bucket })) : []);
    if (buckets.length || Object.keys(data as Row).some((key) => ['current','1-30','31-60','61-90','90+'].includes(key))) return { rows:buckets, page:1, lastPage:1, total:buckets.length };
    return { rows:[data as Row], page:1, lastPage:1, total:1 };
  }
  if (Array.isArray(body)) return { rows:body as Row[], page:1, lastPage:1, total:body.length };
  if (Array.isArray(body?.data)) return { rows:body.data as Row[], page:1, lastPage:1, total:body.data.length };
  return { rows:[], page:1, lastPage:1, total:0 };
}

export function ResourceWorkspace({ resource }: { resource: Resource }) {
  const [rows, setRows] = useState<Row[]>([]);
  const [loading, setLoading] = useState(Boolean(resource.listPath));
  const [loadError, setLoadError] = useState('');
  const [query, setQuery] = useState<Record<string,string>>({});
  const [page, setPage] = useState(1); const [lastPage, setLastPage] = useState(1); const [total, setTotal] = useState(0);
  const [showForm, setShowForm] = useState(!resource.listPath && Boolean(resource.createPath));
  const [busy, setBusy] = useState(false); const [toast, setToast] = useState<Toast>(null);
  const [lookups, setLookups] = useState<LookupMap>({});
  const [lineCounts, setLineCounts] = useState<Record<string,number>>(() => Object.fromEntries((resource.lines ?? []).map((group) => [group.name, group.min ?? 1])));
  const [selected, setSelected] = useState<Row | null>(null);
  const [editing, setEditing] = useState<Row | null>(null);

  async function load(targetPage = page) {
    if (!resource.listPath) return; setLoading(true); setLoadError('');
    const params = new URLSearchParams({ page:String(targetPage), per_page:'20', ...Object.fromEntries(Object.entries(query).filter(([,v]) => v)) });
    try { const result = normalize(await api(`${resource.listPath}?${params}`)); setRows(result.rows); setPage(result.page); setLastPage(result.lastPage); setTotal(result.total); }
    catch (error) { setLoadError(error instanceof ApiError ? error.message : 'Backend tidak dapat dijangkau.'); }
    finally { setLoading(false); }
  }

  const loadInitial = useEffectEvent(() => { void load(1); });

  useEffect(() => {
    const timer = window.setTimeout(() => { loadInitial(); }, 0);
    return () => window.clearTimeout(timer);
  }, [resource.slug]);
  useEffect(() => {
    const types = [...new Set([...(resource.fields ?? []), ...(resource.lines ?? []).flatMap((group) => group.fields)].map((field) => field.lookup).filter(Boolean))] as string[];
    Promise.all(types.map(async (type) => {
      try {
        const master = ['units','items','parties','tax-codes'].includes(type);
        const suffix = type === 'parties' ? `&type=${resource.fields?.find((field) => field.lookup === type)?.lookupFilter ?? ''}` : '';
        const result = normalize(await api(master ? `/master-data/${type}?per_page=100&status=active${suffix}` : `/lookups/${type}`));
        return [type, result.rows.map((row) => ({ value:String(row.id), label:[row.sku ?? row.code ?? row.key ?? row.name, row.legal_name ?? row.name ?? row.email].filter(Boolean).join(' · ') || String(row.id) }))] as const;
      }
      catch { return [type, []] as const; }
    })).then((entries) => setLookups(Object.fromEntries(entries)));
  }, [resource]);

  function buildPayload(form: HTMLFormElement) {
    const data = new FormData(form); const payload: Record<string,unknown> = {};
    for (const field of resource.fields ?? []) {
      if (resource.createPath?.includes(`{${field.name}}`)) continue;
      const raw = data.getAll(field.name).at(-1) ?? null; if ((raw === '' || raw === null) && !field.required) continue;
      payload[field.name] = parseField(field, raw);
    }
    for (const group of editing ? [] : resource.lines ?? []) {
      payload[group.name] = Array.from({ length:lineCounts[group.name] ?? 1 }, (_, index) => {
        if (group.fields.length === 1 && group.fields[0].name === 'value') return parseField(group.fields[0], data.get(`${group.name}.${index}.value`));
        return Object.fromEntries(group.fields.flatMap((field) => { const raw = data.get(`${group.name}.${index}.${field.name}`); return (raw === '' || raw === null) && !field.required ? [] : [[field.name, parseField(field, raw)]]; }));
      });
    }
    return payload;
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); if (!resource.createPath && !editing) return; setBusy(true); setToast(null);
    const form = event.currentTarget; const payload = buildPayload(form);
    let path = editing ? resource.updatePath! : resource.createPath!;
    for (const [key,value] of new FormData(form)) path = path.replace(`{${key}}`, encodeURIComponent(String(value)));
    try {
      await api(path, { method:editing ? 'PATCH' : resource.method ?? 'POST', body:JSON.stringify(payload) });
      setToast({ tone:'success', title:'Berhasil disimpan', message:editing ? `${resource.singular} berhasil diperbarui.` : resource.success ?? `${resource.singular} berhasil diproses backend.` }); form.reset();
      if (resource.listPath) { setShowForm(false); setEditing(null); await load(1); }
    } catch (error) { const reason = error instanceof ApiError ? error : new ApiError('Tidak dapat terhubung ke server.', 0); setToast({ tone:'error', title:`Gagal (${reason.status || 'network'})`, message:[reason.message, ...Object.values(reason.errors).flat()].join(' ') }); }
    finally { setBusy(false); }
  }

  async function rowAction(path: string, message: string) {
    if (!confirm(message)) return;
    try { await api(path, { method:'POST' }); setToast({ tone:'success', title:'Aksi selesai', message:'Status dokumen berhasil diperbarui.' }); setSelected(null); await load(); }
    catch (error) { setToast({ tone:'error', title:'Aksi gagal', message:error instanceof ApiError ? error.message : 'Server tidak dapat dijangkau.' }); }
  }

  async function openDetail(row: Row) {
    setSelected(row);
    if (!resource.showPath || !row.id) return;
    try { const result = await api<{data:Row}>(resource.showPath.replace('{id}', String(row.id))); setSelected(result.data); }
    catch (error) { setToast({ tone:'error', title:'Detail gagal dimuat', message:error instanceof ApiError ? error.message : 'Server tidak dapat dijangkau.' }); }
  }

  function openEdit(row: Row) {
    if (resource.updateStatuses && !resource.updateStatuses.includes(String(row.status))) {
      setToast({ tone:'error', title:'Record terkunci', message:'Hanya dokumen draft yang dapat diedit. Gunakan action koreksi untuk dokumen yang sudah diproses.' });
      return;
    }
    setEditing(row); setSelected(null); setShowForm(true);
  }

  const columns = resource.columns ?? [];
  return <div className="page-content resource-page">
    {toast && <div className={`toast toast-${toast.tone}`}><span>{toast.tone === 'success' ? 'OK' : '!'}</span><div><b>{toast.title}</b><p>{toast.message}</p></div><button onClick={() => setToast(null)}>x</button></div>}
    <div className="page-heading"><div><p className="eyebrow">{resource.group.toUpperCase()}</p><h1>{resource.title}</h1><p>{resource.description}</p></div><div className="header-actions">{resource.listPath && <button className="secondary-button" onClick={() => load()}>Refresh</button>}{resource.createPath && <button className="primary-button" onClick={() => setShowForm(true)}>+ {resource.actionLabel ?? `Buat ${resource.singular}`}</button>}</div></div>
    {resource.responseNote && <div className="inline-alert alert-info"><b>Catatan kontrak</b><span>{resource.responseNote}</span></div>}
    {resource.listPath && <section className="data-panel">
      <form className="table-toolbar" onSubmit={(event) => { event.preventDefault(); load(1); }}><div><b>{total}</b><span>record ditemukan</span></div><div className="toolbar-filters">{(resource.filters ?? []).map((field) => <FieldInput key={field.name} field={field} value={query[field.name] ?? ''} onChange={(value) => setQuery((current) => ({...current,[field.name]:value}))} lookups={lookups} compact />)}<button className="filter-button">Terapkan filter</button>{Object.values(query).some(Boolean) && <button type="button" className="clear-button" onClick={() => { setQuery({}); setTimeout(() => load(1)); }}>Reset</button>}</div></form>
      <div className="table-wrap"><table><thead><tr>{columns.map((column) => <th key={column}>{label(column)}</th>)}<th>Aksi</th></tr></thead><tbody>{loading ? Array.from({length:6},(_,i) => <tr key={i}>{[...columns,'action'].map((column) => <td key={column}><i className="cell-skeleton" /></td>)}</tr>) : rows.map((row,index) => <tr key={String(row.id ?? index)} onClick={() => openDetail(row)}>{columns.map((column) => <td key={column}>{column === 'status' || column === 'bucket' ? <span className={`status status-${String(valueAt(row,column)).replace('+','plus')}`}>{display(valueAt(row,column),column)}</span> : display(valueAt(row,column),column)}</td>)}<td><button className="row-menu" aria-label="Lihat">...</button></td></tr>)}</tbody></table></div>
      {!loading && loadError && <EmptyState icon="!" title="Data gagal dimuat" message={loadError} action="Coba lagi" onAction={() => load()} />}
      {!loading && !loadError && !rows.length && <EmptyState icon="0" title="Belum ada data" message="Ubah filter atau buat record pertama untuk modul ini." action={resource.createPath ? `Buat ${resource.singular}` : undefined} onAction={() => setShowForm(true)} />}
      {lastPage > 1 && <div className="pagination"><button disabled={page <= 1} onClick={() => load(page-1)}>Sebelumnya</button><span>Halaman <b>{page}</b> dari {lastPage}</span><button disabled={page >= lastPage} onClick={() => load(page+1)}>Berikutnya</button></div>}
    </section>}
    {!resource.listPath && !resource.createPath && <EmptyState icon="i" title="Read only view" message="Data ditampilkan langsung dari laporan backend." />}
    {showForm && (resource.createPath || editing) && <div className="modal-layer" onMouseDown={(event) => { if (event.target === event.currentTarget) { setShowForm(false); setEditing(null); } }}><section className="form-drawer"><header><div><p className="eyebrow">{editing ? 'EDIT RECORD' : 'NEW OPERATION'}</p><h2>{editing ? `Edit ${resource.singular}` : resource.actionLabel ?? `Buat ${resource.singular}`}</h2><p>Field bertanda * wajib diisi sesuai kontrak backend.</p></div>{resource.listPath && <button onClick={() => { setShowForm(false); setEditing(null); }}>x</button>}</header><form key={editing ? String(editing.id) : 'create'} onSubmit={submit}><div className="form-grid">{(resource.fields ?? []).map((field) => <FieldInput key={field.name} field={field} initialValue={editing?.[field.name]} lookups={lookups} />)}</div>{!editing && (resource.lines ?? []).map((group) => <fieldset className="line-group" key={group.name}><legend><div><b>{group.label}</b><span>{lineCounts[group.name]} baris</span></div><button type="button" onClick={() => setLineCounts((current) => ({...current,[group.name]:(current[group.name] ?? 1)+1}))}>+ Tambah baris</button></legend>{Array.from({length:lineCounts[group.name] ?? 1},(_,index) => <div className="line-row" key={index}><span className="line-number">{String(index+1).padStart(2,'0')}</span>{group.fields.map((field) => <FieldInput key={field.name} field={{...field,name:`${group.name}.${index}.${field.name}`}} lookupName={field.lookup} lookups={lookups} />)}{(lineCounts[group.name] ?? 1) > (group.min ?? 1) && <button type="button" className="remove-line" onClick={() => setLineCounts((current) => ({...current,[group.name]:current[group.name]-1}))}>x</button>}</div>)}</fieldset>)}<footer><span>Data dikirim langsung ke API dan divalidasi server.</span>{resource.listPath && <button type="button" className="secondary-button" onClick={() => { setShowForm(false); setEditing(null); }}>Batal</button>}<button className="primary-button" disabled={busy}>{busy ? <><i className="spinner" /> Memproses</> : editing ? 'Simpan perubahan' : resource.actionLabel ?? 'Simpan & proses'}</button></footer></form></section></div>}
    {selected && <div className="modal-layer" onMouseDown={(event) => { if (event.target === event.currentTarget) setSelected(null); }}><aside className="detail-drawer"><header><div><p className="eyebrow">RECORD DETAIL</p><h2>{String(selected.number ?? selected.name ?? selected.code ?? resource.singular)}</h2></div><button onClick={() => setSelected(null)}>x</button></header><div className="detail-grid">{Object.entries(selected).filter(([,value]) => typeof value !== 'object').map(([key,value]) => <div key={key}><span>{label(key)}</span><b>{display(value,key)}</b></div>)}</div>{Object.entries(selected).filter(([,value]) => Array.isArray(value)).map(([key,value]) => <section className="nested-detail" key={key}><h3>{label(key)}</h3>{(value as Row[]).map((item,index) => <article key={String(item.id ?? index)}><b>#{index+1}</b>{Object.entries(item).filter(([,nested]) => typeof nested !== 'object').slice(0,6).map(([field,nested]) => <span key={field}>{label(field)}: <strong>{display(nested,field)}</strong></span>)}</article>)}</section>)}<div className="detail-actions">{resource.updatePath && <button className="secondary-button" onClick={() => openEdit(selected)}>Edit</button>}{resource.slug === 'purchase-requests' && selected.status === 'draft' && <button className="primary-button" onClick={() => rowAction(`/purchase-requests/${selected.id}/submit`,'Submit purchase request ini?')}>Submit</button>}{resource.slug === 'purchase-requests' && ['draft','submitted'].includes(String(selected.status)) && <button className="danger-button" onClick={() => rowAction(`/purchase-requests/${selected.id}/cancel`,'Batalkan purchase request ini?')}>Batalkan</button>}{resource.slug === 'rfqs' && selected.status === 'draft' && <button className="primary-button" onClick={() => rowAction(`/rfqs/${selected.id}/submit`,'Kirim RFQ ke supplier?')}>Kirim RFQ</button>}{resource.slug === 'goods-receipts' && selected.status === 'qc_completed' && <button className="primary-button" onClick={() => rowAction(`/goods-receipts/${selected.id}/post`,'Posting penerimaan ke stok dan jurnal?')}>Posting</button>}{['units','items','parties','tax-codes'].includes(resource.slug) && selected.status === 'active' && <button className="danger-button" onClick={() => rowAction(`/master-data/${resource.slug}/${selected.id}/archive`,'Arsipkan master data ini?')}>Arsipkan</button>}</div></aside></div>}
  </div>;
}

function parseField(field: Field, raw: FormDataEntryValue | null): unknown {
  if (field.type === 'number') return Number(raw || 0);
  if (field.type === 'boolean') return raw === 'on' || raw === 'true';
  if (field.type === 'json') { try { return JSON.parse(String(raw || '{}')); } catch { return String(raw); } }
  return String(raw ?? '');
}

function FieldInput({ field, lookups, lookupName, compact, value, initialValue, onChange }: { field: Field; lookups: LookupMap; lookupName?: Field['lookup']; compact?: boolean; value?: string; initialValue?: unknown; onChange?: (value:string) => void }) {
  const type = lookupName ?? field.lookup; let options = type ? lookups[type] ?? [] : field.options;
  if (type === 'parties' && field.lookupFilter) options = options;
  const initial = initialValue !== undefined && initialValue !== null ? (field.type === 'json' ? JSON.stringify(initialValue, null, 2) : field.type === 'date' ? String(initialValue).slice(0,10) : String(initialValue)) : String(field.default ?? '');
  const common = { name:field.name, required:field.required, defaultValue:initial, placeholder:field.placeholder ?? (compact ? field.label : ''), onChange:onChange ? (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => onChange(event.target.value) : undefined };
  const controlled = { name:field.name, required:field.required, value:value ?? '', placeholder:field.placeholder ?? field.label, onChange:(event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => onChange?.(event.target.value) };
  if (compact) return <label className="compact-field"><span>{field.label}</span>{field.type === 'select' || options ? <select {...controlled}><option value="">Semua {field.label.toLowerCase()}</option>{options?.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select> : <input {...controlled} />}</label>;
  return <label className={`form-field ${field.type === 'textarea' || field.type === 'json' ? 'wide-field' : ''}`}><span>{field.label}{field.required && <i>*</i>}</span>{field.type === 'textarea' || field.type === 'json' ? <textarea {...common} rows={field.type === 'json' ? 4 : 3} /> : field.type === 'select' || options ? <select {...common}><option value="">Pilih {field.label.toLowerCase()}</option>{options?.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select> : field.type === 'boolean' ? <><input name={field.name} type="hidden" value="false" /><input name={field.name} type="checkbox" value="true" defaultChecked={initialValue === undefined ? Boolean(field.default) : Boolean(initialValue)} className="toggle-input" /></> : <input {...common} type={field.type ?? 'text'} step={field.type === 'number' ? 'any' : undefined} />}{field.help && <small>{field.help}</small>}</label>;
}

function EmptyState({ icon, title, message, action, onAction }: { icon:string; title:string; message:string; action?:string; onAction?:()=>void }) {
  return <div className="empty-state"><span>{icon}</span><h3>{title}</h3><p>{message}</p>{action && <button className="secondary-button" onClick={onAction}>{action}</button>}</div>;
}
