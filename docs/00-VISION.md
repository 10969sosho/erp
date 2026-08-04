# ERP Distributor Enterprise

## Status Dokumen

| Item | Nilai |
|---|---|
| Status | Baseline normatif / single source of truth |
| Bahasa | Indonesia; istilah teknis mengikuti bahasa industri |
| Sasaran | UMKM sampai enterprise distributor |
| Prinsip | Core reusable 80%, extension/customization 20% |
| Sumber kebenaran | Dokumen bernomor `00`-`50`; dokumen lebih bernomor tidak boleh bertentangan dengan dokumen lebih awal |

## Visi

Membangun platform ERP distributor modular, auditable, multi-company, dan scalable yang mengendalikan siklus procure-to-pay, order-to-cash, inventory-to-finance, service, CRM, serta integrasi eksternal dalam satu model data dan kontrol internal yang konsisten.

## Sasaran Bisnis

1. Menyediakan satu sumber data untuk master, transaksi, stok, piutang, hutang, kas, pajak, dan laporan.
2. Mengurangi pekerjaan manual melalui workflow, automation, import, API, dan notification.
3. Menjamin setiap angka finansial dan stok dapat ditelusuri ke dokumen sumber, pengguna, waktu, dan approval.
4. Mendukung pertumbuhan volume transaksi tanpa mengubah kontrak core.
5. Memisahkan konfigurasi per perusahaan/branch dari kode produk.

## Batasan dan Keputusan Wajib

- Sistem tidak membuat keputusan legal/pajak negara tertentu secara otomatis tanpa konfigurasi dan validasi konsultan pajak lokal.
- Transaksi yang sudah diposting tidak diedit atau dihapus; koreksi dilakukan dengan reversal/return/credit note.
- Stok dan ledger akuntansi menggunakan append-only journal; saldo adalah hasil kalkulasi atau materialized balance yang dapat direbuild.
- Semua amount memiliki currency dan exchange rate snapshot.
- Semua record bisnis memiliki `tenant_id`, `company_id`, dan audit metadata.
- Customization hanya melalui extension, plugin, hook, event, observer, field registry, dan report registry.

## Success Metrics

| Area | Target baseline |
|---|---|
| Traceability | 100% posting memiliki source document dan audit trail |
| Stock accuracy | ≥99% setelah stock opname |
| Approval compliance | 100% transaksi melewati policy yang berlaku |
| API reliability | ≥99.9% untuk endpoint production yang disepakati |
| Month-end | Closing dapat direkonsiliasi tanpa spreadsheet sebagai sumber utama |

## Prinsip Desain

`Configuration over customization`, least privilege, explicit state transition, idempotent integration, eventual consistency hanya untuk proses non-finansial, dan backward-compatible API versioning. Detail mengikat ada di `44-ARCHITECTURE.md`, `37-CUSTOMIZATION.md`, `39-NONFUNCTIONAL.md`, dan `43-CONVENTION.md`.
