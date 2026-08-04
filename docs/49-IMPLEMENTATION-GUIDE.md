# Implementation Guide

## Sequence

1. Confirm tenant, legal entities, branches, warehouses, currencies, fiscal calendar, tax jurisdiction, and data ownership.
2. Implement foundation and permission/scope before transaction modules.
3. Implement master data lifecycle and import templates.
4. Implement P2P, O2C, stock/warehouse, then subledger/GL posting.
5. Add workflow, notification, reports, dashboards, integrations, CRM/project/service.
6. Migrate opening balances, opening stock, open AR/AP, and master data with reconciliation.
7. Execute UAT, security/performance, restore, cutover rehearsal, training, and go-live.

## Definition of Done

Requirement, rule, permission, state, API, UI, database migration/index, audit, notification, report, dashboard, tests, documentation, monitoring, and rollback evidence exist. No module is complete when only CRUD works.

## Cutover Controls

Freeze legacy input, extract/hash, cleanse/map, dry-run, reconcile counts/values, approve migration, load in dependency order, validate opening balances, sign off business owners, and retain migration evidence.
