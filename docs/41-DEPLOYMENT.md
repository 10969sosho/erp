# Deployment

## Environments

Local -> development -> test -> staging -> production. Environment configuration and secrets are externalized; production data never copied unmasked to lower environments.

## Release Flow

Build immutable artifact -> scan/dependency/license check -> database migration dry-run -> automated tests -> staging approval -> backup -> canary/blue-green -> health/reconciliation check -> progressive rollout. Rollback application and forward-fix migration plan are mandatory.

## Operations

Monitor API, queue, DB, storage, posting failures, stock/accounting reconciliation, backup, and security. Runbook covers incident, restore, dead letter, failed migration, key rotation, and tenant isolation alert.
