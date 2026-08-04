# Finance

## Purpose and Problem

Mengendalikan cash in/out, payment, receipt, bank, reconciliation, petty cash, approval, dan forecast secara aman.

## Flow and Actors

AR/AP creates payable/receivable -> Finance prepares payment/receipt -> approver approves batch -> bank/cash execution -> bank import/reconciliation -> GL posting. Petty cash uses fund, custodian, voucher, replenishment, and count.

## Requirements/Validation

Bank/cash account active, payment method valid, amount positive, beneficiary verified, duplicate reference prevented, allocation not over outstanding, period open, and maker-checker enforced. Bank statement lines cannot be silently deleted.

## Reports/Dashboard

Operational cash book, payment queue, unallocated receipt, reconciliation exceptions, AP/AR aging. Management cash forecast, collection/payable plan, bank utilization, DSO/DPO. Executive cash position, runway, working capital. Pivot and Excel/PDF/print required.
