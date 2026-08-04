# Architecture

## Logical Architecture

```mermaid
flowchart TB
 UI[Web/Mobile UI] --> API[API Gateway]
 API --> APP[Application Services]
 APP --> DOMAIN[Domain Modules]
 DOMAIN --> DB[(Relational Database)]
 DOMAIN --> OUTBOX[(Outbox/Event Store)]
 OUTBOX --> BUS[Message Broker]
 BUS --> WORKER[Workers/Integrations]
 DOMAIN --> FILE[Object Storage]
 APP --> OBS[Logs Metrics Traces]
```

## Boundaries

Modules own their domain rules and repositories; cross-module orchestration uses application service/events, not direct table mutation. Accounting and stock posting are transactional with source document. Reports use read models/materialized views, never bypass permission.

## Deployment and Scale

Stateless app workers, queue workers, relational primary/replica where safe, cache for reference/read only, object storage, observability, and tenant-aware partitioning. Strong consistency for posting; eventual for notification, search, and analytics.
