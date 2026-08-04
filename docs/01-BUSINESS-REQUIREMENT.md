# Business Requirement

## Stakeholder dan Kebutuhan

| Stakeholder | Kebutuhan utama | Bukti keberhasilan |
|---|---|---|
| Owner/Board | Visibilitas profit, cash, stock, risiko | Dashboard executive dan audit-ready report |
| Finance | Jurnal, AR/AP, payment, reconciliation, closing | Trial balance balance dan subledger reconcile |
| Purchasing | Pengadaan terkendali dan supplier performance | PR sampai payment terlacak |
| Sales | Penjualan cepat dengan kontrol harga/kredit | Order-to-cash tanpa dokumen ganda |
| Warehouse | Stok akurat, picking cepat, traceability lot/serial | Stock ledger dan scan operation |
| Tax | Tax code, tax transaction, reporting | Tax amount dapat direkonsiliasi |
| Manager | Approval, KPI, exception | Work queue dan notification |
| Auditor | Immutability dan evidence | Audit log lengkap |
| Integrator | API dan webhook stabil | Contract, idempotency, retry |

## Functional Requirements

| ID | Requirement | Prioritas | Acceptance criteria |
|---|---|---|---|
| BR-001 | Multi-tenant, company, branch, warehouse | Must | Data scope dan permission terisolasi |
| BR-002 | Master customer, supplier, item, COA, tax, currency | Must | Lifecycle, duplicate control, audit |
| BR-003 | PR-RFQ-quotation-comparison-PO-receive-invoice-payment | Must | Dokumen linked dan state valid |
| BR-004 | Lead-customer-quotation-SO-picking-delivery-invoice-payment | Must | Allocation dan posting valid |
| BR-005 | Stock ledger, lot, batch, expiry, serial, transfer, count | Must | Saldo tidak negatif kecuali policy eksplisit |
| BR-006 | GL, AR, AP, cash, bank, tax, budget, asset, closing | Must | Debet=kredit dan reconciliation |
| BR-007 | Configurable workflow, approval, notification | Must | Policy berdasarkan amount, role, dimension |
| BR-008 | Report operational/management/executive/pivot, PDF/Excel/print | Must | Filter dan export permission-aware |
| BR-009 | Import/export, REST API, webhook, integration log | Must | Idempotent, retry, observability |
| BR-010 | Document, attachment, activity, audit, security | Must | Retention dan access control |
| BR-011 | CRM, project, service, commission | Should | Terhubung ke customer dan transaksi |
| BR-012 | Extension/plugin tanpa core modification | Must | Upgrade core tetap aman |

## Non-Functional Requirements

Availability, performance, security, backup, disaster recovery, observability, accessibility, localization, dan maintainability dijabarkan di `39-NONFUNCTIONAL.md`.

## Configurable Decisions

Country tax regime, fiscal calendar, approval threshold, costing method, negative stock policy, rounding, payment terms, credit limit, numbering, retention, SLA, dan integration provider harus dikonfigurasi pada setup; nilai default bukan keputusan bisnis.

## Out of Scope Baseline

Manufacturing/MRP, payroll, point-of-sale offline, banking license, automatic legal tax filing, dan marketplace-specific behavior tidak termasuk core; implementasikan sebagai extension bila disetujui.
