# Purchasing

## Purpose and Problem

Memastikan kebutuhan dibeli dengan harga, supplier, kualitas, terms, dan approval yang benar serta dapat dicocokkan sampai pembayaran.

## Actors and Flow

Requester -> Buyer -> Approver -> Supplier -> Receiver/QC -> AP -> Finance. PR dapat berasal dari manual, reorder, project, atau service. RFQ dikirim ke supplier eligible; quotations dibandingkan by total landed cost, lead time, quality, terms, dan delivery. PO menjadi commitment snapshot.

## Requirements and Validation

Supplier active, item/UOM valid, currency/terms valid, quantity/price positive, budget optional, duplicate prevention, delivery schedule, tax snapshot, and over-receive tolerance. Three-way match compares PO, accepted receipt, and invoice; exception requires approval.

## Approval/Notification/Permission/Audit

Threshold by amount, category, branch, budget, and exception. Notify approver, buyer overdue, receiver due, AP mismatch, and supplier. Permission follows `06`; audit every price, supplier, approval, receive, QC, invoice, return, and payment.

## Reports and Dashboard

Operational: open PR/RFQ/PO, due receipt, unmatched invoice. Management: spend by supplier/category, price variance, supplier score. Executive: procurement spend, savings, working capital. Pivot by period/company/branch/item/supplier; Excel/PDF/print.

## Future and Best Practice

Supplier portal, contract purchase, landed cost allocation, vendor-managed inventory, and AI recommendation are extensions. Use three-way match, approved supplier list, competitive comparison, and supplier scorecard without copying a vendor product.
