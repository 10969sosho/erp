# Validation

## Layers

1. Client validation for usability.
2. API schema validation for contract.
3. Domain validation for business rules.
4. Database constraint for integrity.
5. Posting validation for accounting/stock invariants.

## Error Contract

Return `code`, `message`, `field_errors[]`, `trace_id`, and `retryable`. Messages are localized; codes are stable. Never leak SQL, secrets, or authorization details.

## Mandatory Checks

Tenant/company scope, active master, date in open period, currency/rate, UOM conversion, positive quantity, tax validity, duplicate external reference, optimistic version, approval state, source quantity, credit/price/margin policy, debet-credit balance, and idempotency key.

## Validation Matrix

| Rule | Draft | Submit | Approve | Post |
|---|---:|---:|---:|---:|
| Required fields | Yes | Yes | Recheck | Recheck |
| Active master | Warning/optional | Yes | Yes | Yes |
| Permission/scope | Yes | Yes | Yes | Yes |
| Accounting period | No | Yes | Yes | Yes |
| Stock availability | Optional reserve | Yes by policy | Yes | Yes |
| Balanced journal | No | No | Preview | Mandatory |
