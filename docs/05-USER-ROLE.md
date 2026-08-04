# User Role

## Role Model

Role adalah kumpulan permission; user dapat memiliki banyak role dengan scope company/branch/warehouse. Group role tidak boleh menjadi pengganti segregation of duties.

| Role | Tanggung jawab |
|---|---|
| Platform Owner | Tenant, subscription, global policy |
| Company Admin | Company setup, master policy, user assignment |
| Branch Manager | Operasi branch dan approval scope |
| Master Data Steward | Create/change/reference approval |
| Buyer | PR, RFQ, PO |
| Receiving Clerk | Receive dan QC |
| Warehouse Operator | Putaway, pick, pack, transfer |
| Warehouse Manager | Stock control, opname, adjustment approval |
| Sales Representative | Lead, quotation, SO |
| Sales Manager | Pricing, margin, commission approval |
| Customer Service | Ticket, return coordination |
| Finance Officer | Cash, payment, reconciliation |
| AP/AR Officer | Supplier/customer invoice, settlement |
| Accountant | Journal, ledger, close |
| Tax Officer | Tax setup dan filing report |
| Project Manager | Project/task/budget |
| Auditor | Read-only audit evidence |
| Report Analyst | Configured report/dashboard |
| Integration Operator | API, import, retry, mapping |
| Security Administrator | Role, MFA, access review |
| System Operator | Deployment, backup, monitoring; no business posting |

## User Lifecycle

Invite -> pending -> active -> suspended -> deactivated. Deactivation tidak menghapus audit history. Access review wajib periodik; privileged role menggunakan MFA.
