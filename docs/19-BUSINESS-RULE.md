# Business Rule

## Universal Rules

1. PO tidak boleh dibuat jika supplier belum aktif.
2. Invoice tidak boleh diposting jika receiving/delivery yang dipersyaratkan belum selesai.
3. Stock tidak boleh minus kecuali company policy mengaktifkan controlled negative stock dan approval.
4. Approval wajib sebelum nominal, margin, credit, discount, atau adjustment melewati threshold konfigurasi.
5. Harga jual tidak boleh di bawah minimum price tanpa approval exception.
6. Posted document tidak boleh diedit/dihapus; gunakan reversal, return, atau credit/debit note.
7. Semua dokumen harus berada pada open period dan scope user.
8. Payment tidak boleh dialokasikan melebihi outstanding invoice.
9. Journal harus debet=kredit dalam transaction currency dan base currency.
10. Lot/serial/expiry wajib mengikuti tracking policy item.
11. Duplicate supplier invoice, external reference, payment, dan webhook harus ditolak/deduplicate.
12. Tax code, rate, exemption, dan rounding diambil dari effective-dated policy dan disnapshot.
13. Dokumen turunan tidak boleh melampaui open quantity/value sumber tanpa over policy.
14. Segregation-of-duties conflict memblokir approval/posting atau meminta override beralasan.
15. Closing period menolak posting dan adjustment biasa; reopening memerlukan controller approval dan audit event.

## Rule Evaluation

Rule ID, severity, condition, action, exception approver, effective date, dan evidence wajib tersimpan. Rules configurable tidak boleh menghapus invariant database.
