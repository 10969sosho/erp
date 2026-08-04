# ERD

## Core Relationship

```mermaid
erDiagram
 TENANT ||--o{ COMPANY : owns
 COMPANY ||--o{ BRANCH : has
 BRANCH ||--o{ WAREHOUSE : operates
 ITEM ||--o{ ITEM_UOM : measures
 PARTY ||--o{ SALES_ORDER : customer
 PARTY ||--o{ PURCHASE_ORDER : supplier
 PURCHASE_ORDER ||--o{ GOODS_RECEIPT : receives
 GOODS_RECEIPT ||--o{ STOCK_MOVEMENT : creates
 SALES_ORDER ||--o{ DELIVERY : fulfills
 DELIVERY ||--o{ STOCK_MOVEMENT : issues
 STOCK_MOVEMENT ||--o{ STOCK_LEDGER : posts
 SALES_ORDER ||--o{ SALES_INVOICE : bills
 PURCHASE_ORDER ||--o{ PURCHASE_INVOICE : bills
 SALES_INVOICE ||--o{ PAYMENT_ALLOCATION : settles
 PURCHASE_INVOICE ||--o{ PAYMENT_ALLOCATION : settles
 JOURNAL ||--o{ JOURNAL_LINE : contains
 JOURNAL_LINE }o--|| ACCOUNT : uses
 WORKFLOW_INSTANCE ||--o{ APPROVAL : requires
 DOCUMENT ||--o{ ATTACHMENT : contains
```

## Cardinality Rules

One header has one or more lines when submitted. One source can have many downstream documents, but downstream quantity/value cannot exceed source unless explicit over-receive/over-delivery policy. A posting may reference exactly one source event and can generate multiple journal lines.

## Detailed Model Source

Authoritative columns, indexes, FK, constraints, and seed data are in `09-TABLE-SPECIFICATION.md`; accounting and inventory subledgers are in `21-ACCOUNTING.md` and `22-INVENTORY.md`.
