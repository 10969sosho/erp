# FAQ

## Apakah dokumen posted boleh diedit?

Tidak. Gunakan reversal, return, credit note, atau debit note dengan alasan dan approval.

## Bagaimana customization dilakukan?

Extension table/field registry, plugin, hook, event, observer, dan report registry; lihat `37-CUSTOMIZATION.md`.

## Apakah negative stock boleh?

Default tidak boleh. Jika bisnis memilih controlled negative stock, policy harus effective-dated dan exception approval wajib.

## Apakah satu item dapat memiliki banyak UOM?

Ya, melalui conversion factor per item; factor disnapshot di transaksi.

## Bagaimana perbedaan pajak antar negara ditangani?

Tax engine dan filing adapter dikonfigurasi/di-extension per jurisdiction; core menyimpan tax code, rate, basis, evidence, dan snapshot.

## Apakah report boleh langsung query semua tabel?

Tidak. Gunakan governed read model/query registry dengan scope dan audit.

## Bagaimana integrasi gagal?

Retry idempotent, dead-letter, alert, dan reconciliation; jangan mengulang command tanpa idempotency.
