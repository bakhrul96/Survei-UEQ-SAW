# Rilis 1 Runbook

Dokumen ini memisahkan bukti otomatis yang sudah diverifikasi dari tindakan
produksi yang wajib dilakukan oleh operator berwenang. Jangan mengaktifkan
periode jika panel **Kesiapan aktivasi** masih menampilkan masalah.

## Baseline terverifikasi 2026-08-06

- Commit implementasi yang diverifikasi: `eaf9c263ea216fbc727e8d8eb5561223cb9c5b92`.
- Gate operasional terakhir dijalankan pada HEAD `7f3c586` di worktree
  `codex/release-one-remediation`.
- Lingkungan lokal: MySQL 8.4.10, PHP 8.5.4, Node 22.23.2, npm 10.9.8.
- Seluruh 23 migrasi berstatus `Ran`.
- Data studi lokal: 1 periode, 13 unit, 26 item UEQ, 6 benchmark,
  0 submission, dan 0 jawaban.
- Kondisi readiness lokal: tepat 1 admin email-verified dan 2FA-confirmed,
  serta 0 bukti readiness. Enrollment TOTP diverifikasi tanpa mencatat secret,
  recovery code, maupun kode autentikasi.
- Jendela tanggal periode valid, sumber instrumen UEQ telah diverifikasi, dan
  seluruh 6 benchmark untuk versi instrumen aktif telah diverifikasi.
- Kontak penelitian telah diganti dari nilai contoh dengan kontak yang
  disediakan operator; nilai kontak tidak disalin ke dokumen verifikasi ini.

## Bukti otomatis

| Pemeriksaan | Hasil | Bukti |
| --- | --- | --- |
| Repository gate | Lulus | `composer test`: Pint lulus, PHPStan 0 temuan, Pest 183 test / 682 assertion. |
| Focused Rilis 1 | Lulus | 98 test / 282 assertion untuk auth, admin singleton, readiness, periode, survei, dashboard, serta CSV/XLSX. |
| Happy path mobile | Lulus | `SurveyHappyPathTest`: 1 test / 14 assertion pada viewport 360 × 800; mencakup 26 jawaban, fokus keyboard, submit, dan pembersihan draft lokal. |
| Offline recovery | Lulus | `OfflineDraftTest`: 1 test / 11 assertion; draft pulih setelah reload, submit disabled saat offline, lalu aktif kembali saat online. |
| Production build | Lulus | Vite 8.2.0 membangun manifest dan aset produksi. Peringatan `fontaine` hanya terkait fallback font opsional. |
| Fingerprint | Lulus | Hash kanonik SHA-256 diuji deterministik dan berubah ketika target, unit, polaritas item, atau benchmark berubah. |
| Export | Lulus | CSV dan XLSX memiliki metadata periode yang sama dan tidak memuat identifier/token privat. |

## Konfigurasi sebelum aktivasi

Operator berwenang harus menyelesaikan semua poin berikut melalui lingkungan
UAT/produksi yang disetujui:

1. Pastikan mode production, debug nonaktif, URL aplikasi tepat sama dengan
   domain HTTPS yang disetujui, cookie sesi secure, cache database aktif, dan
   kunci token survei berupa nilai acak yang disimpan di secret manager.
2. Jalankan `php artisan app:create-admin peneliti@example.test` secara
   interaktif. Command ini mempertahankan tepat satu akun admin; jangan
   menjalankannya sebelum target akun disetujui.
3. Login sebagai admin tersebut, buka `/settings/security`, lalu enroll dan
   konfirmasi TOTP menggunakan perangkat authenticator operator.
4. Ganti kontak contoh pada konfigurasi consent dengan kontak penelitian yang
   telah disetujui.
5. Isi sumber instrumen UEQ, verifikasi instrumen, dan verifikasi keenam
   benchmark melalui Study Settings.
6. Periksa tanggal buka/tutup, minimum usia, target per modul, dasar target,
   seluruh bagian consent, dan aturan kualitas sebelum merekam evidence.

Password, recovery code, token cookie, dan nilai secret tidak boleh ditulis
ke command line, log verifikasi, screenshot, atau dokumen ini.

## Backup privat dan uji restore

Buat direktori serta dump dengan permission minimum. Password dimasukkan
secara interaktif ketika diminta.

```bash
install -d -m 700 storage/app/backups
mysqldump --single-transaction --routines --triggers -u ueq_saw_app -p ueq_saw > storage/app/backups/ueq_saw_release_one_uat.sql
chmod 600 storage/app/backups/ueq_saw_release_one_uat.sql
```

DBA harus menyediakan akun `ueq_saw_restore_operator` secara out of band.
Hanya database khusus `ueq_saw_restore` yang boleh dibuat ulang. Restore dump
ke database tersebut, lalu catat jumlah baris untuk:

- `migrations`;
- `evaluation_periods`;
- `evaluation_units`;
- `ueq_items`;
- `ueq_benchmarks`;
- `survey_submissions`;
- `survey_answers`.

Dump sumber lokal `ueq_saw_release_one_uat.sql` dibuat ulang setelah verifikasi
konfigurasi pada 2026-08-06 dengan `mysqldump` 8.4.10. Direktori memiliki mode
`700`, file memiliki mode `600`, dan file berada di lokasi yang diabaikan Git.

Dump dipulihkan ke database khusus `ueq_saw_restore` menggunakan restore
operator sementara yang disediakan secara out of band dan dihapus setelah uji.
Jumlah hasil restore cocok dengan sumber: 23 migrasi, 1 periode, 13 unit,
26 item, 6 benchmark, 0 submission, dan 0 jawaban. Database aplikasi dan
database lain tidak dibuat ulang atau diubah oleh uji ini.

## Evidence readiness di Study Settings

Rekam hanya hasil yang benar-benar telah diverifikasi:

| Jenis | Referensi | Catatan minimum |
| --- | --- | --- |
| HTTPS | URL survei `https://` yang disetujui | Hasil TLS dan redirect HTTPS. |
| Backup/restore | `ueq_saw_release_one_uat.sql` | Waktu restore dan seluruh jumlah baris yang cocok. |
| Submit test | `SurveyHappyPathTest 1 test / 14 assertions` | Command, viewport 360 × 800, dan hasil sukses. |

Setiap record menyimpan admin verifier dan waktu verifikasi. Evidence hanya
dapat dikoreksi selama periode masih draft.

## UAT manual 360 piksel

Operator harus mencatat hasil tanpa data pribadi responden:

| Area | Hasil yang wajib dibuktikan |
| --- | --- |
| Consent | Tujuan, data tersimpan, cookie, estimasi waktu, hak berhenti, dan kontak tampil lengkap. |
| Eligibility | Responden eligible masuk pemilih modul; responden ineligible ditolak dari wizard. |
| UEQ | Tepat 26 jawaban dapat dikirim dan modul selesai tidak dapat dipilih ulang. |
| Multi-modul | Token yang sama dapat menilai modul lain; sesi baru terbentuk setelah jeda lebih dari 30 menit. |
| Rest recommendation | Rekomendasi istirahat tampil setelah submission ketiga dalam satu sesi. |
| Offline | Jawaban tetap ada setelah offline/reload dan draft hilang setelah konfirmasi server. |
| Keyboard/screen reader | Fokus terlihat pada checkbox, radio, navigasi, submit; error mempunyai posisi serta role yang dapat dibaca. |
| Dashboard | Unique respondent, submission, dan progress per modul terpisah dengan benar. |
| Export | CSV dan XLSX dapat dibuka, metadata periode cocok, identifier privat tidak ada. |

Baseline ini belum memuat sign-off UAT manual produksi, karena domain HTTPS
masih harus tersedia dan disahkan oleh operator berwenang.

## Aktivasi dan verifikasi lock

Aktivasi hanya dilakukan setelah panel readiness kosong. Setelah menekan
**Aktifkan dan kunci konfigurasi**, verifikasi:

- status periode adalah `active`;
- waktu lock terisi;
- fingerprint terdiri dari 64 karakter heksadesimal huruf kecil;
- survei terbuka hanya di dalam jendela tanggal;
- perubahan langsung terhadap target, consent, unit, item, atau benchmark
  membuat gate menolak akses dengan pesan konfigurasi berubah;
- Study Settings menolak perubahan field yang terkunci.

Untuk maintenance, tutup periode terlebih dahulu melalui Study Settings.
Rollback aplikasi tidak boleh menjalankan migrasi turun yang menghapus data
survei yang telah dikumpulkan.

## Keputusan readiness saat ini

Implementasi, gate otomatis, dan dump sumber privat siap untuk UAT operator.
Aktivasi produksi belum diotorisasi sampai HTTPS, tiga evidence record, dan UAT
manual memperoleh sign-off nyata.
