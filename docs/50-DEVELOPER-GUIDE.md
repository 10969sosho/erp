# Developer Guide

## Start Here

Read `00`, `07`, `11`, `12`, `17`, `18`, `19`, `37`, `43`, `44`, and the relevant domain document before implementation. Treat IDs, invariants, states, and posting rules as contracts.

## Implementing a Feature

1. Locate requirement and assign IDs for new rules/API/events/tests.
2. Define aggregate, command, query, state transitions, authorization, and transaction boundary.
3. Add schema/migration with FK, unique, check, and indexes.
4. Implement domain validation and idempotent application command.
5. Implement audit, outbox event, notification, API, UI states, report, and extension point.
6. Add unit/integration/contract/E2E/security tests.
7. Update changelog, glossary, traceability, runbook, and migration notes.

## Posting Safety

Use one atomic transaction for source state, stock movement, journal, and posting event. Lock or version relevant balances. Never trust client totals. Retry only safe idempotent commands. Rebuild balances from immutable ledger during reconciliation.

## Handoff Checklist

- [ ] Requirement and acceptance criteria linked
- [ ] Database specification and ERD updated
- [ ] API examples/errors documented
- [ ] Permission/SoD reviewed
- [ ] Audit/event/notification registered
- [ ] Reports/dashboard definitions reconciled
- [ ] Tests and operational runbook passed
- [ ] Core/custom boundary confirmed
