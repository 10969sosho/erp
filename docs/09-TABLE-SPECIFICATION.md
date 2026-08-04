# Table Specification

## Convention

Every table below includes purpose, key columns, indexes/unique, FK, constraints, and seeder guidance. `created_at`, `updated_at`, `created_by`, `updated_by`, `version` apply to all mutable tables. Audit and posting tables append `event_id`, `occurred_at`, and immutable hash where required.

## Foundation and Master

| Table | Purpose | Key columns | Unique/index | FK/constraints | Seeder |
|---|---|---|---|---|---|
| tenants | Tenant boundary | name, code, status | unique code | status enum | one demo tenant only |
| companies | Legal entity | tenant_id, name, tax_id, base_currency | tenant+code | tenant, currency | configurable |
| branches | Operational unit | company_id, code, address | company+code | company | configurable |
| warehouses | Stock location | branch_id, code, costing_method | branch+code | branch | configurable |
| bins | Bin location | warehouse_id, code, zone_id | warehouse+code | warehouse | optional |
| parties | Customer/supplier/person | type, legal_name, tax_id, credit_limit | tenant+external_ref | tenant | none |
| items | Product/service | sku, name, type, category_id, tracking | tenant+sku | UOM, category | sample only |
| item_uoms | UOM conversions | item_id, uom_id, factor | item+uom unique | positive factor | configurable |
| units | UOM catalog | code, precision | tenant+code | positive precision | standard catalog |
| price_lists | Pricing policy | name, currency, validity | company+code | currency | configurable |
| price_list_lines | Item price | price_list_id, item_id, min_qty, price | list+item+min_qty | price >= 0 | none |
| tax_codes | Tax policy | code, rate, recoverability | company+code | rate range | local setup |
| accounts | COA | code, name, type, parent_id | company+code | no cyclic parent | chart template |
| cost_centers | Dimension | code, name | company+code | active only | configurable |

## Procurement, Sales, Stock, Finance

| Table | Purpose | Key columns | Unique/index | FK/constraints | Seeder |
|---|---|---|---|---|---|
| purchase_requests | Internal demand | number, requester, date, status | company+number | total lines >0 submitted | none |
| rfqs | Supplier request | number, request_id, deadline | company+number | deadline >= date | none |
| supplier_quotations | Offer | rfq_id, supplier_id, valid_until | rfq+supplier | currency required | none |
| purchase_orders | Commitment | number, supplier_id, total, status | company+number | active supplier | none |
| goods_receipts | Physical receive | number, PO, warehouse, received_at | company+number | qty <= open PO policy | none |
| quality_checks | Inspection | receipt_line, result, reason | receipt_line+sequence | result enum | none |
| purchase_invoices | AP evidence | number, supplier, total, status | company+supplier+number | match policy | none |
| sales_orders | Customer commitment | number, customer, total, status | company+number | customer active | none |
| deliveries | Dispatch evidence | number, SO, warehouse, status | company+number | allocation valid | none |
| sales_invoices | AR evidence | number, customer, total, status | company+number | billable source | none |
| stock_movements | Immutable quantity event | item, warehouse, qty, direction, source | warehouse+item+date | qty > 0; no delete | none |
| stock_ledger | Running/value evidence | movement_id, qty_balance, value | item+warehouse+occurred_at | immutable | none |
| lots | Batch/expiry | item, lot_no, expiry_date | item+lot_no | expiry optional by policy | none |
| serials | Serial identity | item, serial_no, status | tenant+serial_no | one current owner | none |
| stock_counts | Count session | warehouse, scope, status | company+number | freeze policy | none |
| journals | GL header | number, date, period, source | company+number | balanced lines | none |
| journal_lines | GL lines | journal_id, account, debit, credit | journal+line_no | exactly one side >0 | none |
| payment_transactions | Cash movement | number, type, account, amount | company+number | amount >0 | none |
| payment_allocations | Settlement link | payment_id, invoice_id, amount | payment+invoice | allocated <= open balance | none |
| bank_reconciliations | Bank match | bank_account, statement_date, status | account+statement_no | one period scope | none |

## Control, Platform, Collaboration

| Table | Purpose | Key columns | Unique/index | FK/constraints | Seeder |
|---|---|---|---|---|---|
| users | Identity | email, status | tenant+email | verified policy | none |
| roles | Role | code, name | tenant+code | protected system roles | baseline roles |
| permissions | Capability | key | unique key | valid action | baseline catalog |
| role_permissions | Role grant | role_id, permission_id, scope | role+permission+scope | no duplicate | baseline mapping |
| workflow_definitions | Workflow template | entity, version, active | company+entity+version | one active version | approval baseline |
| workflow_instances | Runtime | entity_id, definition, state | entity+entity_id+version | state machine | none |
| approvals | Decision | instance, approver, decision | instance+step+approver | SoD | none |
| notifications | User message | recipient, channel, event, status | recipient+dedupe_key | retry count | none |
| documents | Business document | entity_type, entity_id, type | entity+type+version | retention | none |
| attachments | File metadata | document_id, storage_key, hash | hash+tenant | virus scan | none |
| audit_events | Evidence | actor, action, entity, before/after, hash | tenant+occurred_at | append-only | none |
| integration_logs | External exchange | provider, direction, idempotency, payload | provider+idempotency | retry policy | none |
| import_jobs | Bulk load | type, mapping, status | tenant+job_no | row error detail | none |

## Seeder Rules

Seeder must be idempotent, environment-safe, no production sample transactions, and must never insert secrets. Seed only reference catalogs, permission definitions, system roles, workflow templates, and optional demo tenant explicitly enabled.
