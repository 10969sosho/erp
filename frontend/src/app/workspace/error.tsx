'use client';

import Link from 'next/link';

export default function WorkspaceError({ reset }: { error: Error; reset: () => void }) {
  return <div className="state-page"><div className="state-code">500</div><p className="eyebrow">UNEXPECTED INTERRUPTION</p><h1>Workspace tidak dapat dimuat</h1><p>Data Anda aman. Coba muat ulang bagian ini atau kembali ke overview.</p><div><button className="primary-button" onClick={reset}>Coba lagi</button><Link className="secondary-button" href="/workspace/dashboard">Ke overview</Link></div></div>;
}
