# UI/UX

## Principles

Desktop-first for operations, responsive for approvals and dashboards, keyboard/scanner friendly for warehouse, accessible WCAG 2.1 AA target, localized date/number/currency, and no destructive action without confirmation/reason.

## Application Shell

Header: tenant/company/branch context, global search, notifications, user menu. Sidebar: permission-filtered modules. Content: breadcrumb, page title, context actions, filters, work area. Right drawer: activity, attachments, audit, related documents.

## Standard Page Anatomy

List with saved filters, server pagination, bulk actions, export permission; detail with status banner, summary cards, tabs for lines/related/approval/activity/files; create/edit form with draft save and validation; posting confirmation with impact preview.

## UX Controls

Never hide required authorization silently; show why button is disabled. Show source links for derived values. Use decimal precision from UOM/currency. Warn before leaving dirty form. Use optimistic UI only for non-posting actions; posting waits for server confirmation.
