# ERP Distributor Backend

Laravel 13 API foundation for the ERP Distributor platform. The frontend will be implemented separately with Next.js.

## Requirements

- PHP 8.3+
- Composer
- MySQL 8+

## Setup

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` only in `.env`. Never commit `.env` or expose credentials in documentation, logs, or source control.

## Initial API

- `GET /api/health`
- `POST /api/auth/login`
- `GET /api/auth/me` with `Authorization: Bearer TOKEN`
- `POST /api/auth/logout` with `Authorization: Bearer TOKEN`

## Master Data API

All master-data endpoints require a Sanctum bearer token and are scoped to the authenticated user's tenant.

- `GET /api/master-data/{resource}` with optional `search`, `status`, and `per_page`
- `POST /api/master-data/{resource}`
- `PATCH /api/master-data/{resource}/{id}`
- `POST /api/master-data/{resource}/{id}/archive`

Supported resources: `units`, `items`, `parties`, and `tax-codes`.

## Purchasing API

- `GET /api/purchase-requests`
- `GET /api/purchase-requests/{id}`
- `POST /api/purchase-requests`
- `POST /api/purchase-requests/{id}/submit`
- `POST /api/purchase-requests/{id}/cancel`

Purchase Request starts in `draft`, requires at least one valid tenant-scoped line, and can be submitted or cancelled. Each create and state transition writes an audit event.

## Purchasing Flow API

- `GET /api/rfqs`
- `POST /api/rfqs`
- `POST /api/rfqs/{id}/submit`
- `GET /api/supplier-quotations`
- `POST /api/supplier-quotations`
- `POST /api/quotation-comparisons`
- `GET /api/purchase-orders`
- `POST /api/purchase-orders`

The implemented source chain is `submitted PR -> draft RFQ -> sent RFQ -> submitted supplier quotation -> approved quotation comparison -> approved purchase order`. A quotation must belong to the RFQ and invited supplier; a PO must use the quotation selected by an approved comparison.

Purchasing records are tenant-scoped and every create or state transition writes an audit event.

## Receiving and Inventory API

- `GET /api/goods-receipts`
- `POST /api/goods-receipts`
- `POST /api/goods-receipts/{id}/quality-check`
- `POST /api/goods-receipts/{id}/post`
- `GET /api/stock`

Accepted QC quantity is posted to an immutable stock movement and materialized stock balance. Receipt quantity cannot exceed open PO quantity and stock posting requires completed QC.

## Payables API

- `GET /api/purchase-invoices`
- `POST /api/purchase-invoices`
- `GET /api/payments`
- `POST /api/payments`

Purchase invoice creation requires a posted goods receipt and a total matching the PO. Payment allocations cannot exceed invoice value.

## Sales API

- `POST /api/sales-orders`
- `POST /api/deliveries`
- `POST /api/sales-invoices`

Sales delivery validates available stock and writes an outbound stock movement. Sales invoice requires an actual delivery.

## Accounting API

- `GET /api/accounts`
- `POST /api/accounts`
- `GET /api/journals`
- `POST /api/journals`
- `GET /api/reports/trial-balance`

Journal posting requires at least two lines, exactly one debit or credit per line, and total debit equal to total credit.

Fiscal period control:

- `POST /api/fiscal-periods/{id}/close`
- `POST /api/fiscal-periods/{id}/reopen`

The accounting posting service creates or reuses the monthly open fiscal period and rejects posting into a closed period. Core source events now generate idempotent journals for goods receipt, purchase invoice, supplier payment, sales delivery, sales invoice, and customer receipt.

## Receivables and Returns API

- `POST /api/sales-invoices/{id}/tax-snapshot`
- `POST /api/customer-receipts`
- `POST /api/sales-returns`
- `POST /api/credit-notes`

Tax snapshots store the effective rate on the invoice. Customer receipt allocation is restricted to invoices belonging to the same customer and company. Sales return restores inventory through an immutable inbound movement; credit note must reference the related return and cannot exceed the invoice subtotal.

## Warehouse Control API

- `POST /api/stock-transfers`
- `POST /api/stock-adjustments`

Transfers create paired outbound/inbound stock movements atomically. Adjustments require a reason, update the materialized balance, and reject negative stock.

## Platform API

Workflow and approval:

- `POST /api/workflow-definitions`
- `POST /api/workflow-instances`
- `POST /api/approvals/{id}/decide`
- `GET /api/notifications`
- `POST /api/notifications/{id}/read`

CRM and service:

- `POST /api/leads`
- `POST /api/opportunities`
- `POST /api/activities`
- `POST /api/service-tickets`

Project:

- `POST /api/projects`
- `POST /api/projects/{projectId}/tasks`

Report/import job registry:

- `POST /api/report-jobs`
- `POST /api/import-jobs`

Approval decisions, notifications, CRM, projects, tickets, and jobs are tenant-scoped and audited where they mutate business records. Report/import endpoints currently create queued job contracts; actual worker execution, object storage, PDF/XLSX generation, file parsing, and external queue infrastructure remain deployment extensions.

## Security and Reporting API

Role and permission administration:

- `GET /api/roles` requires `security.role.view`
- `POST /api/roles` requires `security.role.manage`
- `POST /api/users/{userId}/roles/{roleId}` requires `security.role.manage`

Audit and management reports:

- `GET /api/audit-events`
- `GET /api/reports/ar-aging`
- `GET /api/reports/ap-aging`
- `GET /api/reports/inventory-summary`

AP and AR settlement now calculate historical allocations. An invoice is `partially_paid` until allocated amount reaches its total, then becomes `paid`; cross-payment over-allocation is rejected.

## Current Scope Boundary

Implemented backend increments currently cover platform foundation, authentication, tenant-scoped master data, PR/RFQ/quotation/comparison/PO, receiving/QC/stock ledger, purchase invoice/payment, sales order/delivery/invoice, AR customer receipts, sales returns, credit notes, tax snapshots, stock transfer/adjustment, accounting COA/journal/trial balance, fiscal periods, automatic source posting, workflow/approval/notification, CRM lead/opportunity/activity, project/task, service ticket, report/import job registry, RBAC enforcement, audit listing, AR/AP aging, and inventory summary. Remaining documented domains include full tax engine/filing, commission, warehouse bin/scan/wave operations, cycle count/stock opname, scheduler/worker execution, real XLSX/PDF/import processing, bank reconciliation, petty cash, budgets, fixed assets, depreciation, advanced closing/opening balance, integrations, document storage, and production security/operations hardening.

The latest operations increment also includes warehouse bins, cycle count/opname sessions, bank accounts/statements/matching contracts, document and attachment metadata, and idempotent integration log intake. The Next.js frontend lives in `../frontend` and currently provides a responsive operations dashboard shell with mobile navigation, KPI cards, alerts, revenue chart, activity feed, and network health panel.

Seeded development user: `admin@example.com` / `ChangeMe123!`. Change or remove this credential before any shared environment.
