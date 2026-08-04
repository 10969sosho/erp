import Link from 'next/link';

export default function NotFound() {
  return <main className="state-page full-state"><div className="state-code">404</div><p className="eyebrow">PAGE NOT FOUND</p><h1>Halaman keluar jalur.</h1><p>Alamat yang Anda buka tidak tersedia di workspace ini.</p><Link className="primary-button" href="/workspace/dashboard">Kembali ke overview</Link></main>;
}
