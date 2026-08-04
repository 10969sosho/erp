# Components

## Design System Components

| Component | Contract |
|---|---|
| AppShell | Context, navigation, permission filter |
| DataTable | Server query, sort, filter, pagination, selection |
| Form | Schema validation, dirty state, draft save |
| MoneyInput/QuantityInput | Currency/UOM precision, locale display |
| Party/ItemLookup | Scoped search, active-only, duplicate-safe |
| StatusBadge/Timeline | State and audit transition evidence |
| ApprovalPanel | Steps, approver, SLA, decisions |
| DocumentLinks | Source/downstream relationship |
| AttachmentPanel | Upload scan, access, retention |
| NotificationCenter | Read/unread, deep link, channel state |
| FilterBuilder/Pivot | Whitelisted fields and aggregations |
| ScanInput | Barcode/serial/lot validation |
| ConfirmPostModal | Preview posting impact and reason |

Components must be composable, keyboard accessible, permission-aware, locale-aware, and independent of a single domain. Domain-specific behavior enters via typed props/extension registry.
