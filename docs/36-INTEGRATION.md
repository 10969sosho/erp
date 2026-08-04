# Integration

## Integration Pattern

API synchronous for commands/queries needing immediate response; event/webhook asynchronous for downstream notification; batch import for large legacy data. Outbox guarantees committed events are published; consumer uses idempotency.

## Integration Registry

Provider, version, direction, endpoint, auth, mapping, frequency, timeout, retry, dead-letter, owner, SLA, data classification, and reconciliation procedure are mandatory. Examples: bank statement, tax provider, courier, email, e-commerce, BI, identity, and storage.

## Failure Handling

Persist request/response metadata without secrets, correlation ID, retry count, next retry, status, and human resolution. Replayed command must be safe. Reconciliation report compares sent/accepted/failed/settled totals.
