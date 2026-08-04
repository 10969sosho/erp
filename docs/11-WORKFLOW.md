# Workflow

## Workflow Engine

Definition terdiri dari entity, version, trigger, conditions, ordered steps, approver resolver, SLA, escalation, rejection behavior, and effective dates. Instance menyimpan snapshot definition agar perubahan policy tidak mengubah histori.

## Approval Resolver

Resolver dapat berdasarkan fixed role, reporting line, amount tier, branch, company, cost center, margin exception, credit exception, atau named user. Jika resolver menghasilkan zero/multiple invalid approver, instance masuk `blocked` dan mengirim notification ke administrator.

## Standard Steps

Draft -> Submit -> Validate -> Approval 1..N -> Approved/Rejected -> Execute/Post -> Complete. Rework kembali ke Draft/Needs Changes; approval lama invalidated bila nilai material berubah.

## SLA and Escalation

SLA configurable per entity/priority. Reminder dikirim sebelum due; escalation ke manager lalu administrator. Escalation tidak otomatis approve. Semua delivery/retry tercatat.

## Workflow Events

`document.submitted`, `approval.requested`, `approval.approved`, `approval.rejected`, `document.posted`, `document.cancelled`, `document.reversed`, `sla.breached` adalah canonical events. Payload, idempotency, dan security mengikuti `18-API-STANDARD.md`.
