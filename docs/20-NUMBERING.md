# Numbering

## Format

Document number terdiri dari prefix, company/branch token optional, fiscal year/period optional, sequence, dan checksum optional. Contoh: `PO-JKT-2026-000001`; format adalah konfigurasi, bukan hard-code.

## Guarantees

Sequence atomic, tidak reuse setelah rollback, gap boleh terjadi dan harus dijelaskan; jika regulasi memerlukan no-gap gunakan controlled legal sequence terpisah. Prefix dan sequence scope dapat tenant/company/branch/document type/fiscal period.

## Numbering Table

| Document | Default prefix | Scope |
|---|---|---|
| PR | PR | company |
| RFQ | RFQ | company |
| PO | PO | company |
| Receipt | GR | warehouse |
| SO | SO | branch |
| Delivery | DO | warehouse |
| Sales Invoice | SI | company |
| Purchase Invoice | PI | company |
| Payment | PAY | company |
| Journal | JV | company+period |
| Stock Adjustment | ADJ | warehouse |
