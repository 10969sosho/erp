# Nonfunctional Requirements

## Targets

| Area | Baseline target |
|---|---|
| Availability | 99.9% monthly for production core |
| API p95 | <=500 ms read, <=1.5 s non-post command under agreed load |
| Posting | Atomic; no partial accounting/stock result |
| Scalability | Horizontal application workers; partition/archive high volume |
| RPO/RTO | Configured per tier; recommended RPO <=15 min, RTO <=4 h |
| Security | OWASP baseline, encryption, MFA privileged |
| Accessibility | WCAG 2.1 AA target |
| Observability | Logs, metrics, traces, business reconciliation |

Targets are capacity-tested per tenant/profile; they are not promises without sizing and SLA approval.

## Reliability

Use transaction boundaries, outbox, idempotency, retry/backoff, circuit breaker, queue isolation, health/readiness checks, backup encryption, restore drills, and graceful degradation for notifications/reporting.
