# Inventory

## Purpose and Problem

Mengontrol quantity, value, location, tracking, availability, dan expiry dari receipt sampai delivery; mencegah saldo tidak akurat dan stock loss.

## Actors/Flow

Buyer/receiver menerima -> QC -> putaway; sales/warehouse reserve -> pick -> issue; manager transfer/adjust/count; accountant values movement. Stock card bersumber dari immutable movement, bukan edit balance.

## Costing and Availability

Company memilih FIFO atau moving average per warehouse/item policy. Cost layer menyimpan receipt cost; average cost menghitung weighted average. Available = on hand - reserved - blocked. Safety stock, min/max, reorder point, lead time, dan supplier preference menentukan replenishment suggestion.

## Tracking

Lot/batch menyimpan manufacture/expiry; FEFO direkomendasikan untuk expiry item. Serial wajib unik dan statusnya available/reserved/issued/returned/quarantine. Expired/blocked tidak boleh dialokasikan tanpa override.

## Operations and Controls

Mutation: receipt, issue, transfer out/in, adjustment, return, count variance. Transfer atomic atau status in-transit. Cycle count tidak mengubah saldo sampai variance approved. Stock opname membekukan scope sesuai policy. Negative stock blocked by default.

Reports/dashboard: stock card, valuation, aging/expiry, movement, negative exception, fill rate, turnover, days-on-hand; management valuation/slow-moving; executive inventory working capital; pivot item/location/lot. Excel/PDF/print required.
