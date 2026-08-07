# Panduan Evidence Production — Aktivasi Periode Resmi

**Untuk periode:** `Evaluasi Wong Reang Apps 2026 (Resmi)` — slug `wong-reang-2026-resmi` (status: draft)
**Tanggal penyusunan:** 7 Agustus 2026
**Sifat:** Panduan + template. Tiga evidence ini **hanya bisa direkam setelah environment production live** (domain + hosting). Aktivasi periode **diblokir otomatis** sampai ketiganya tercatat di halaman Konfigurasi oleh satu-satunya admin 2FA.

Cara mencatat: buka `/admin/study` (pilih periode resmi) → bagian "Bukti kesiapan operasional" → isi Referensi + Catatan → Simpan bukti. Sistem memvalidasi format.

---

## 1. Evidence HTTPS — kind `https`

**Lakukan setelah deploy production:**
- Buka URL production (mis. `https://survei.domainkamu.id`) dari jaringan luar (ponsel 4G, bukan Wi-Fi kantor).
- Pastikan landing, consent, dan login admin termuat via HTTPS tanpa peringatan sertifikat atau mixed content.

**Catat di aplikasi:**
- **Referensi:** URL production penuh, harus berformat https valid (dicek `filter_var` + skema). Contoh: `https://survei.domainkamu.id`
- **Catatan (≥1 kalimat):** contoh → `Landing, consent, dan login admin dimuat via HTTPS dari jaringan 4G; sertifikat valid sampai <tanggal>.`

## 2. Evidence uji pemulihan backup — kind `backup_restore`

**Lakukan setelah deploy production:**
- Ambil backup DB production: `mysqldump -h <host> -u <user> -p <db> --single-transaction --routines --triggers > ueq_saw_prod_YYYYMMDD_HHMMSS.sql`
- Pulihkan ke database terpisah/lokal.
- Verifikasi tabel kunci: 13 modul aktif, 26 item UEQ, 6 benchmark. Contoh query:
  ```sql
  SELECT (SELECT COUNT(*) FROM evaluation_units WHERE is_active=1) AS modul,
         (SELECT COUNT(*) FROM ueq_items) AS items,
         (SELECT COUNT(*) FROM ueq_benchmarks) AS benchmarks;
  ```
- Simpan file backup di lokasi privat.

**Catat di aplikasi:**
- **Referensi:** nama/lokasi file backup. Contoh: `ueq_saw_prod_20260807_090000.sql`
- **Catatan (≥20 karakter):** contoh → `Backup production dipulihkan ke DB terpisah; 13 modul, 26 item, 6 benchmark terbaca utuh; durasi restore ±X menit; file disimpan privat di <lokasi>.`

## 3. Evidence uji submit survei — kind `submit_test`

**Lakukan setelah deploy production (sebelum periode aktif):**
- Jalankan test otomatis terhadap build production:
  ```bash
  php artisan test --testsuite=Browser   # 10 test, termasuk happy path 360px
  ```
  atau uji manual end-to-end di environment production: consent → screener → pilih modul → 26 item → konfirmasi tersimpan → cek baris di `survey_submissions` + 26 baris `survey_answers`.

**Catat di aplikasi:**
- **Referensi:** nama/hasil test. Contoh: `tests/Browser 10 tests / 106 assertions` atau `Uji manual production 2026-08-07, submission id N`.
- **Catatan (≥20 karakter):** contoh → `Submit 26 item berhasil dalam satu transaksi; jawaban tersimpan lengkap; duplikasi period-token-modul ditolak; draft lokal terhapus setelah konfirmasi server.`

---

## Setelah ketiga evidence tercatat

1. Buka `/admin/study` (periode resmi) — panel "Kesiapan aktivasi" harus menampilkan **"✓ Semua syarat aktivasi telah terpenuhi"** (panel berubah hijau).
2. Klik **"Aktifkan dan kunci konfigurasi"** — konfigurasi terkunci permanen, periode jadi `active`.
3. Sebarkan URL survei: `/s/wong-reang/wong-reang-2026-resmi`.

> Periode lama `wong-reang-2026` tetap `locked` dengan hasil resmi dari data uji — jangan dibuka kembali agar jejak resmi terjaga.
