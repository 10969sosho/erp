# Reports

## Report Contract

Every module provides four classes: operational (actionable current work), management (trend/variance), executive (small KPI set), and pivot (user-selected dimensions/measures). Every report has owner, definition, source, filters, timezone, currency, row-level permission, refresh mode, and reconciliation note.

## Mandatory Output

Excel preserves typed columns and metadata; PDF has title, filter snapshot, generated time, page number, and sign-off area; print uses print CSS. Large exports are asynchronous jobs with notification and expiry.

## Controls

Whitelist fields/aggregations, prevent formula injection, mask sensitive columns, record export audit, and use snapshot/as-of date for financial reports. Report totals must tie to source ledger or disclose aggregation lag.
