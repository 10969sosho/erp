# Audit

## Events

Capture login, permission change, master change, state transition, approval, posting, reversal, export, import, API access, integration retry, attachment access, and security event.

## Evidence

Event includes tenant/scope, actor/user/service, timestamp UTC, IP/device/request ID, action, entity/version, before/after or diff, reason, source, outcome, and hash chain where tamper evidence is required. Sensitive values are masked/tokenized.

## Retention and Access

Retention is configurable by jurisdiction and legal hold. Audit is append-only, encrypted, separately permissioned, exportable, and not editable by business users. Auditor is read-only; deletion requires documented retention process and never removes statutory evidence.
