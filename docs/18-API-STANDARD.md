# API Standard

## Contract

REST JSON over HTTPS, base `/api/v1`, versioned breaking changes, ISO-8601 UTC timestamps, UUID identifiers, decimal strings for money/quantity, cursor pagination, stable enum values, and RFC 7807-like errors with `trace_id`.

## Methods and Response

`GET` list/detail, `POST` create/action, `PATCH` draft update, `DELETE` draft/archive only. List response: `data`, `pagination`, `meta`; detail response includes links and allowed actions. Use `POST /{resource}/{id}/actions/{action}` for state transitions.

## Security and Reliability

OAuth2/OIDC, scoped tokens, MFA for human privileged operations, service accounts for integration, rate limiting, idempotency key for create/payment/post/action, ETag/version for update, retryable 5xx/429, webhook signature, replay protection, and correlation ID.

## Example

```http
POST /api/v1/sales-orders/SO_ID/actions/submit
Idempotency-Key: 01J...
If-Match: "7"
```

Response includes new state, approval instance, calculated totals, links, and audit event ID. API permission and field scope follow `06-PERMISSION.md`.
