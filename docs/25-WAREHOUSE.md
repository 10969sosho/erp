# Warehouse

## Purpose and Problem

Menjadikan aktivitas fisik terukur dan traceable dari receiving sampai dispatch dengan lokasi, barcode, dan exception control.

## Flow and Actors

Receiver scans ASN/PO -> receives -> QC -> putaway. Planner creates wave -> operator pick (FIFO/FEFO) -> pack -> dispatch/carrier -> proof of delivery. Manager handles transfer, count, damage, and adjustment.

## Entities and Validation

Warehouse, zone, bin, operation type, wave, pick list, pack, dispatch, carrier, proof. Bin must belong to warehouse; item tracking rules mandatory; scan must match expected item/UOM/lot/serial; picking cannot use blocked/expired stock.

## Approval, Notification, Audit

Manager approves variance, damage, manual pick, negative stock, and backdate. Notify queue aging, QC failure, low stock, missed SLA, and dispatch exception. Audit scan actor/device/time, movement, and handoff evidence.

## Reports/Dashboard/Best Practice

Operational receiving/picking/packing/dispatch, location stock, count variance; management productivity, fill rate, accuracy, utilization; executive service level and inventory risk; pivot by warehouse/operator/item. Barcode-first, scan confirmation, FEFO, and separation of physical versus accounting posting are recommended.
