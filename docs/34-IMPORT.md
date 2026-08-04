# Import

## Process

Download versioned template -> upload -> detect encoding/columns -> map -> dry-run -> show row errors -> approve import -> enqueue -> commit transaction batches -> reconcile -> archive file/log. Never partially mutate silently.

## Rules

Idempotency key/external ID required for transactional imports. Master imports support upsert only within tenant scope and never deactivate unseen records. Posted transactions cannot be imported as edits. Validate references, dates, precision, tax, duplicate, and permission.

## Governance

Import template, mapping, user, file hash, row counts, errors, start/end, and result are audited. Large jobs are resumable, rate-limited, and downloadable with error report.
