# Runbook Rilis 3 UEQ–SAW

Tanggal verifikasi: 6 Agustus 2026. Dokumen ini mencakup konfigurasi sensitivitas S0/S1/S2, final-calculation gate, backlog operasional, official lock permanen, laporan Bab IV, dan ekspor agregat.

## Persiapan produksi

1. Tetapkan `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS, `SESSION_SECURE_COOKIE=true`, dan `SURVEY_TOKEN_KEY` yang aman.
2. Jalankan `php artisan migrate --force` dan `php artisan optimize`.
3. Pastikan tersedia tepat 13 modul aktif, 26 item UEQ terverifikasi, enam benchmark terverifikasi, dan tepat satu admin dengan email serta 2FA terverifikasi.
4. Lengkapi bukti HTTPS, pemulihan backup, dan uji submit survei pada Pengaturan Studi.
5. Periksa bobot S1 dan S2. Masing-masing harus berjumlah tepat `1.000000`; nilai ini ikut configuration hash dan tidak dapat diubah setelah aktivasi.

## Alur pengumpulan dan preview

1. Aktifkan periode setelah seluruh readiness gate lulus.
2. Review semua submission menjadi `included` atau `excluded`. Submission `included` wajib memiliki item tepat `1..26`.
3. Rekam 3–5 informan teknis. Setiap informan wajib menilai seluruh 13 modul dan membagi 100 poin bobot C1/C2/C3.
4. Tutup periode melalui Pengaturan Studi.
5. Buka `/admin/calculations`, lalu jalankan preview.
6. Periksa input hash, hasil UEQ, SAW S0, sensitivitas S0/S1/S2, delta rank seluruh modul, label top-3 `STABIL`/`BERUBAH`, dan backlog lengkap `1..N`.

## Final-calculation gate

Run hanya dapat dijadikan hasil resmi bila seluruh syarat berikut terpenuhi:

- run berstatus `preview` dan periode berstatus `closed`;
- revision snapshot sama dengan revision input periode;
- semua submission memiliki keputusan kualitas final dan setiap submission included mempunyai jawaban item `1..26` lengkap;
- setiap modul aktif mencapai `minimum_per_unit`, atau alasan serta referensi persetujuan pembimbing untuk penyimpangan minimum sudah dicatat;
- konsensus teknis lengkap dari 3–5 informan;
- version/source benchmark dan versi algoritma tersedia;
- minimal dua alternatif SAW tersedia;
- setiap alternatif mempunyai hasil sensitivity S0, S1, dan S2;
- backlog operasional mempunyai tepat satu baris per alternatif SAW dengan urutan unik kontinu `1..N`.

Jika minimum sampel belum tercapai, isi alasan penyimpangan dan referensi persetujuan pembimbing pada panel kelayakan. Sistem mencatat aktor, waktu, dan audit event. Persetujuan ini hanya dapat dicatat pada run preview.

## Backlog dan official lock

- Backlog awal mengikuti rank SAW S0. Memindahkan satu modul menggeser order lain secara atomik dan wajib disertai alasan.
- Expert judgment tidak pernah mengubah nilai, kontribusi, atau rank pada `saw_results`.
- Official lock mengubah run `preview → official`, periode `closed → locked`, menetapkan pointer `official_calculation_run_id`, dan menulis audit event dalam satu transaksi.
- Official lock bersifat permanen. Hasil resmi tidak dapat diganti, diarsipkan, dibuat stale, diedit, atau dihapus. Koreksi harus dilakukan melalui periode penelitian baru.

## Membuka kembali dan mencocokkan hasil resmi

1. Buka `/admin/calculations` untuk periode locked.
2. Pastikan badge `OFFICIAL / LOCKED` tampil dan form lock, deviation, serta perubahan backlog tidak tersedia.
3. Cocokkan input hash, nilai Vi, dan rank S0/S1/S2 dengan bukti sebelum lock.
4. Buka `/admin/reports`. Laporan harus memilih pointer official meskipun terdapat preview yang lebih baru.
5. Cocokkan kembali Run ID dan input hash pada Metadata Run di ekspor XLSX/CSV.

## Visual laporan

Laporan menampilkan empat visual HTML/CSS yang masing-masing mempunyai label aksesibel dan tabel angka:

1. mean UEQ per modul/skala pada domain `-3..+3`;
2. gap terhadap benchmark Good untuk enam skala per modul;
3. kontribusi C1/C2/C3 yang jumlahnya sama dengan Vi;
4. perubahan rank S1/S2 terhadap S0 beserta status top-3 `STABIL` atau `BERUBAH`.

Tabel tambahan membandingkan rank analitis SAW dengan urutan backlog operasional.

## Struktur ekspor

XLSX mempunyai enam worksheet:

1. `Metadata Run`;
2. `Benchmark`;
3. `Hasil UEQ`;
4. `Peringkat SAW`;
5. `Analisis Sensitivitas`;
6. `Backlog Operasional`.

CSV adalah satu tabel datar dengan header stabil dan enam section: `metadata`, `benchmark`, `ueq`, `saw`, `sensitivity`, dan `operational_backlog`. Setiap baris mengulang metadata periode/run sehingga dapat dianalisis tanpa state antarbaris.

## Perintah verifikasi

Jalankan dari direktori `application`:

```bash
composer test
php artisan test tests/Browser
npm run build
```

Pemeriksaan format POSIX yang dipakai oleh gate adalah `vendor/bin/pint --test`. Untuk round-trip tiga migrasi remediasi, gunakan satu file SQLite temporer yang sama pada `migrate:fresh`, `migrate:rollback --step=3`, lalu `migrate`.

## Bukti Pengujian Rilis 3

Hasil verifikasi fresh 6 Agustus 2026:

| Pemeriksaan | Hasil | Bukti |
| --- | --- | --- |
| Composer gate | Lulus | Pint lulus; PHPStan `errors: 0`; 245 tes / 2.194 assertion lulus, 0 gagal. |
| Browser suite | Lulus | 10 tes / 106 assertion lulus, 0 gagal. |
| E2E Rilis 3 | Lulus | Reorder backlog → official lock → reload → report → tautan XLSX/CSV; 1 tes / 27 assertion. |
| Vite production build | Lulus | 22 modul ditransformasi; build selesai dengan exit code 0. |
| Migration round-trip | Lulus | Migrasi `000019`, `000020`, `000021` berhasil rollback dan diterapkan ulang pada SQLite temporer. |
