# Permission

## Permission Format

Permission key: `module.resource.action`, contoh `sales.sales_order.approve`. Actions minimum: `view`, `create`, `edit`, `submit`, `approve`, `reject`, `post`, `cancel`, `reverse`, `export`, `delete` (draft only), `configure`.

## Scope

Scope hierarchy: tenant > company > branch > warehouse > own record. Effective permission adalah role union dikurangi deny policy; deny untuk data sensitif dapat override union. Field-level restriction digunakan untuk cost, margin, salary-like data, dan bank details.

## Matrix Baseline

| Resource | Create/Edit | Approve | Post/Reverse | View |
|---|---|---|---|---|
| PR/RFQ/PO | Buyer | Manager | Purchasing Manager | Purchasing |
| Receipt/QC | Receiving | Warehouse Manager (exception) | Warehouse Manager | Warehouse |
| SO/Delivery | Sales | Sales Manager (exception) | Warehouse/Finance sesuai dokumen | Sales |
| Invoice/Payment | AR/AP/Finance | Finance Manager | Accountant/Finance Manager | Finance |
| Journal/Close | Accountant | Controller | Controller | Finance/Auditor |
| Stock Adjustment | Warehouse | Warehouse Manager | Warehouse Manager | Warehouse/Auditor |
| Role/Policy | Security Admin | Platform Owner | Security Admin | Auditor |

## SoD Rules

Requester tidak boleh menjadi sole approver; creator payment tidak boleh menjadi reconciler; creator journal tidak boleh approve journal; warehouse receiver tidak boleh approve own stock adjustment; integration service account tidak boleh memperoleh human approval permission.
