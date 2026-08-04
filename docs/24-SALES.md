# Sales

## Purpose and Problem

Mengubah demand menjadi revenue dengan kontrol customer, price, credit, stock, delivery, tax, collection, return, dan commission.

## Actors and Flow

Sales rep manages lead/opportunity -> quote -> SO; sales manager approves exception; warehouse allocates/picks/delivers; AR invoices; finance receives/reconciles; service handles return/complaint.

## Requirements and Validation

Customer active and credit status valid; price list/effective date/UOM/tax valid; discount/margin and minimum price checked; delivery warehouse and availability checked; partial delivery/billing policy explicit; duplicate customer PO prevented.

## Returns and Commission

Return references delivery/invoice, receives QC, posts stock and credit note. Commission rule snapshots eligible revenue, margin, payment status, territory, and salesperson; reverses commission on credit note/return.

## Approval/Notification/Permission/Audit

Approval for discount, margin below floor, credit exceedance, free goods, backdate, and credit note. Notifications for quote expiry, SO hold, delivery due, overdue AR, and return. Audit all price/credit/fulfillment/posting changes.

## Reports and Dashboard

Operational pipeline, open SO, picking queue, delivery status, AR aging, return queue. Management sales/margin by rep/customer/item, conversion, DSO, commission. Executive revenue, gross margin, forecast. Pivot by time/company/branch/channel/territory; Excel/PDF/print.
