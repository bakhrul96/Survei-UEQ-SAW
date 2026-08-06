# Rilis 3 Runbook

Tanggal verifikasi: 2026-08-06. Ruang lingkup: Analisis Sensitivitas (S0, S1, S2), Expert Judgment & Backlog Operasional, Penguncian Hasil Resmi (Official Lock), Laporan Visual Bab IV, dan Ekspor Agregat CSV/XLSX.

## Pre-activation & Production Setup

- Set `APP_ENV=production` dan `APP_DEBUG=false`.
- Confirm `APP_URL` pada domain HTTPS resmi.
- Set `SESSION_SECURE_COOKIE=true` dan `SURVEY_TOKEN_KEY`.
- Jalankan `php artisan migrate --force` dan `php artisan optimize`.
- Verifikasi 13 unit, 26 verified items, dan 6 verified benchmark rows.

## Gate & Pengujian Otomatis

- `php artisan test` — 132 test (544 assertions) lulus 100%.
- `php artisan test tests/Browser/ReleaseThreeFlowTest.php` — browser test alur admin Rilis 3 lulus (3.8 detik).
- `vendor\bin\pint --test` — Pint memverifikasi 0 format error.
- `npm run build` — asset Vite diproduksi tanpa error.

## Prosedur Analisis & Operasional Rilis 3

1. **Review Kualitas & Informan Teknis**: Pastikan seluruh respons minimal 2 per modul telah ditinjau dan informan teknis memasukkan estimasi hari serta urgensi arsitektur.
2. **Kalkulasi & Preview**: Buka `/admin/calculations` dan jalankan preview. Verifikasi tabel UEQ, peringkat SAW (S0), serta tabel Analisis Sensitivitas (S0 vs S1 vs S2).
3. **Expert Judgment**: Jika terdapat modul dengan prioritas operasional mendesak di luar urutan matematis SAW, catat alasan pada form Expert Judgment.
4. **Penguncian Hasil Resmi (Official Lock)**: Tekan tombol **Kunci Hasil Resmi (Official)** untuk menandai run tersebut sebagai `OFFICIAL / LOCKED`.
5. **Laporan & Ekspor Bab IV**: Buka `/admin/reports` untuk melihat visualisasi grafik batang UEQ, perbandingan peringkat, dan unduh file XLSX/CSV agregat untuk lampiran Bab IV.

## Bukti Pengujian Rilis 3

| Check | Result | Evidence |
| --- | --- | --- |
| Full Test Suite | Pass | 132 tests / 544 assertions lulus 100%. |
| Browser E2E Rilis 3 | Pass | `ReleaseThreeFlowTest`: Dashboard → Reports → Calculations → Run Preview → Official Lock. |
| Pint Code Standard | Pass | 0 formatting violations. |
| Production Build | Pass | Assets ter-compile sukses via Vite. |
