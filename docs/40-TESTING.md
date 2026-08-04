# Testing

## Test Pyramid

Unit tests for rules/calculation; integration tests for database/queue/storage; contract tests for API/webhook; workflow tests for state/approval; end-to-end tests for P2P/O2C; performance/security/accessibility tests before release.

## Mandatory Scenarios

Balanced journal, no negative stock, lot/serial/expiry, partial receipt/delivery, three-way mismatch, credit limit, minimum price, tax rounding, currency conversion, duplicate idempotency, SoD, rejection/rework, reversal, period close, tenant isolation, import rollback, export masking, notification retry, and integration replay.

## Test Evidence

Each requirement maps to test ID, data fixture, expected state/posting, actor/permission, and evidence. Production-like data must be anonymized. Release gate requires zero critical defects and approved migration/rollback.
