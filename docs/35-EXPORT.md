# Export

## Modes

Interactive export for small data; asynchronous job for large data; scheduled report for recurring delivery. Formats CSV/XLSX/PDF/print. CSV/XLSX values are escaped to prevent spreadsheet formula injection.

## Controls

Export respects same filters, row/field permissions, timezone, currency, and scope as UI. It records actor, query/filter, fields, format, row count, file hash, expiry, and download event. Restricted report requires explicit permission and watermark where configured.
