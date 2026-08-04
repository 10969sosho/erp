# Feature List

## Core Features

| Domain | Features |
|---|---|
| Platform | Tenant, company, branch, warehouse, currency, language, timezone, numbering, fiscal period |
| Master | Item, variant, category, brand, UOM, price list, tax, customer, supplier, bank, COA, employee, territory |
| Purchasing | PR, RFQ, supplier quotation, comparison, PO, receipt, QC, invoice, return, payment, scorecard |
| Sales | Lead, opportunity, customer, quotation, SO, credit check, allocation, picking, delivery, invoice, receipt, return, credit note, commission |
| Inventory | Balance, stock card, movement, transfer, adjustment, cycle count, opname, lot/batch, expiry, serial, costing, reorder |
| Warehouse | Zones, bins, putaway, picking wave, packing, dispatch, barcode/scan, carrier |
| Finance | Cash in/out, payment, receipt, bank, reconciliation, petty cash, forecast |
| Accounting | COA, journal, ledger, trial balance, P&L, balance sheet, cash flow, cost center, budget, asset, depreciation, closing |
| Control | Approval, workflow, notification, audit, security, role permission, activity log |
| Collaboration | Document, attachment, comment, task, calendar, project, service ticket |
| Platform services | API, webhook, automation, import/export, integration, report, dashboard |

## Feature Acceptance Convention

Setiap feature wajib memiliki purpose, actor, precondition, happy path, exception, permission, validation, state, audit event, notification, API contract, UI page, report, dashboard metric, dan extension point. Detail template ada di `43-CONVENTION.md`.
