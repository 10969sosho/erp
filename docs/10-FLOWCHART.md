# Flowchart

## Purchase

```mermaid
flowchart TD
 A[PR Draft] --> B{Approval required?}
 B -- Yes --> C[Approve PR]
 B -- No --> D[Create RFQ/PO]
 C --> D
 D --> E[Supplier Quotation & Comparison]
 E --> F[PO Approved]
 F --> G[Receive]
 G --> H{QC required?}
 H -- Yes --> I[Quality Check]
 H -- No --> J[Accepted Stock]
 I --> J
 J --> K[AP Invoice Match]
 K --> L[Payment]
```

## Sales

```mermaid
flowchart TD
 A[Lead] --> B[Opportunity]
 B --> C[Quotation]
 C --> D[Sales Order]
 D --> E{Credit & Stock valid?}
 E -- No --> F[Exception Approval]
 E -- Yes --> G[Allocate/Pick]
 F --> G
 G --> H[Delivery]
 H --> I[Invoice]
 I --> J[Payment & Reconcile]
```

## Reversal Principle

Rejected node creates a reasoned exception. Posted node goes only to cancel/reverse/return according to `12-STATES.md`; no direct database transition.
