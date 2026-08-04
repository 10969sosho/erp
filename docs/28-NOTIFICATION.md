# Notification

## Channels and Policy

In-app is mandatory for actionable work; email/SMS/push/WhatsApp/provider channels are configurable extensions. User preference, quiet hours, consent, template localization, and sensitive-data masking apply.

## Events

Approval requested/overdue, document rejected/posted, stock low/expired, payment due/failed, invoice overdue, integration failed, import completed, SLA breached, and security alert.

## Reliability

Outbox pattern, dedupe key, retry with exponential backoff, dead-letter queue, provider response, delivery status, unsubscribe, and audit. Notification failure must not roll back a committed financial post; it creates an operational exception.
