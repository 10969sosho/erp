# Database Design

## Standards

Relational database adalah system of record. Semua business tables menggunakan UUID/ULID `id`, `tenant_id`, `company_id` bila relevan, timestamps UTC, `created_by`, `updated_by`, `version`, dan soft archive bila diperlukan. Financial/stock postings immutable.

## Common Columns

| Column | Type | Nullable | Rule |
|---|---|---:|---|
| id | UUID/ULID | No | PK |
| tenant_id | UUID | No | FK tenant; row isolation |
| company_id | UUID | Conditional | FK company |
| status | varchar/enum | No | State machine |
| created_at/updated_at | timestamp | No | UTC |
| created_by/updated_by | UUID | No | FK user |
| version | integer | No | Optimistic lock |
| metadata | JSON | Yes | Extension only, schema validated |

## Data Rules

- Monetary values use fixed precision decimal, never floating point; store transaction, base, and reporting amounts where relevant.
- Quantity uses item UOM precision; conversion factor is snapshotted on transaction lines.
- Natural identifiers are unique within tenant/company scope, never global unless explicitly stated.
- Foreign keys use restrict for posted records; no cascade delete on business evidence.
- Index all tenant scope, status, document date, party, warehouse, and foreign key columns.
- Partition high-volume movement, audit, integration, and ledger tables by tenant/date when scale requires.

## Transaction Pattern

Header -> lines -> references -> approval events -> posting entries -> audit events. Draft can be edited; submitted/approved/posting states have controlled transitions only.
