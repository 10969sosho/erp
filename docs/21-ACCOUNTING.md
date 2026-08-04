# Accounting

## Purpose and Problem

Menyediakan double-entry, subledger, tax, budget, asset, dan closing yang dapat direkonsiliasi; mengatasi spreadsheet, posting tidak seimbang, dan hilangnya audit evidence.

## Actors and Use Cases

Accountant mengelola COA/journal/ledger; Finance mengelola AR/AP/cash; Tax Officer mengelola tax; Controller approve/close; Auditor membaca evidence. Use case: create journal, post source, reconcile subledger, run TB/P&L/BS/CF, budget variance, asset depreciation, open/close period.

## Core Rules

COA memiliki account type (asset, liability, equity, revenue, expense), parent, normal balance, dimensions, dan active date. Journal wajib balanced, source-linked, open period, currency-valid, dan immutable setelah posted. Reversal membuat journal lawan dengan reason.

## Posting Matrix

| Event | Debit | Credit |
|---|---|---|
| Purchase receipt | Inventory/GRNI | GRNI/AP clearing |
| Sales delivery | COGS | Inventory |
| Sales invoice | AR | Revenue + tax payable |
| Supplier invoice | Inventory/expense + input tax | AP |
| Customer payment | Bank/cash | AR |
| Supplier payment | AP | Bank/cash |

Account mapping, tax, costing, rounding, and accrual policy are company configuration. Reports: operational posting queue/subledger aging; management trial balance, margin, budget variance; executive P&L/BS/CF; pivot by account/company/branch/cost center. Export Excel, PDF, and print required.
