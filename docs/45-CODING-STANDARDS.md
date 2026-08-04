# Coding Standards

## Required Practices

Typed interfaces, explicit domain services, parameterized queries/ORM constraints, input validation, structured logs, no secrets in source/logs, deterministic migrations, small cohesive modules, code review, and tests for every rule/posting path.

## Forbidden Practices

Direct balance edits, deleting posted evidence, business logic in UI, unscoped queries, floating-point financial arithmetic, hard-coded tenant/company IDs, hidden approval bypass, swallowed errors, and custom code in core files.

## Review Checklist

- [ ] Scope and authorization checked server-side
- [ ] State transition and idempotency defined
- [ ] Accounting/stock invariant tested
- [ ] Audit and notification event defined
- [ ] Migration/index/rollback reviewed
- [ ] API contract and error codes documented
- [ ] Extension boundary preserved
