# Convention

## Naming

Use English snake_case for database/API keys, PascalCase for UI components, verb-noun service methods, stable IDs, and explicit names. Indonesian labels are presentation-only. Boolean starts `is_`, `has_`, `can_`; timestamps end `_at`; dates end `_date`.

## Document and Requirement IDs

Requirements `BR-###`, rules `RULE-###`, API `API-###`, test `TEST-###`, event `EVT-###`, permission `PERM-###`, KPI `KPI-###`. Never reuse IDs.

## Feature Definition Template

Purpose; business problem; actors; preconditions; happy/exception flow; entities; database; relationships; validation; states; workflow/approval; notifications; permissions; audit; APIs; UI/UX; reports/dashboard; extension points; best practice; future improvement; acceptance criteria.

## Dates and Money

Storage UTC, display user timezone; decimal fixed precision, currency code mandatory, tax and exchange rate snapshot; rounding policy explicit.
