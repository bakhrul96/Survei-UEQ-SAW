# Runbook Verifikasi Rilis 2

Tanggal verifikasi: 2026-08-06 13:15 WIB
Commit implementasi yang diverifikasi: `6256e3511d39aabd304a3222f72766d0a1e74dd0`

Ruang lingkup verifikasi meliputi quality flag/review, statistik dan reliabilitas UEQ, input serta konsensus informan teknis, calculation run, SAW, keterlacakan, immutability, UI admin, browser UAT, dan paritas schema MySQL.

## Lingkungan

| Komponen | Versi / konfigurasi |
|---|---|
| PHP | 8.5.4 CLI |
| Laravel | 13.23.0 |
| Composer | 2.9.5 |
| Node.js | 22.23.2 |
| npm | 10.9.8 |
| MySQL server | 8.4.10 |
| Database penelitian | MySQL `ueq_saw` |
| Browser viewport utama | 1280 × 800 |
| Pemeriksaan responsif tambahan | 360 × 800, sidebar read-only |

## Gate otomatis

| Perintah | Hasil |
|---|---|
| `composer test` | PASS — Pint PASS, PHPStan 0 error, Pest 210 tes / 2.001 assertion |
| `php artisan test tests/Browser/AdminAnalysisFlowTest.php tests/Browser/ReleaseThreeFlowTest.php tests/Browser/AdminSidebarTest.php` | PASS — 4 tes / 30 assertion |
| `npm run build` | PASS — 22 module ditransformasi; warning opsional `fontaine` tidak menggagalkan build |
| `git diff --check` | PASS — tidak ada whitespace error |
| Golden workbook/JSON/persistence tests | PASS — fixture `ueq-saw-v1`, toleransi `0.000001`, 0 mismatch |

Tidak ada screenshot kegagalan tersisa di `tests/Browser/Screenshots`.

## Bukti MySQL

Pemeriksaan dilakukan melalui konfigurasi lokal yang tidak dicetak ke output. Target dikonfirmasi sebagai database penelitian `ueq_saw`. Sebelum migrasi, backup/restore Rilis 1 tanggal 2026-08-06 dikonfirmasi tersedia dan berhasil dipulihkan dengan jumlah baris sumber dan restore identik. Referensi evidence yang disetujui: `ueq_saw_release_one_uat.sql`; credential dan lokasi privat tidak disalin ke dokumen ini.

Dua migrasi pending Rilis 2 diterapkan setelah konfirmasi tersebut:

- `2026_08_06_000017_allow_pending_quality_reviews` — Ran, batch 5;
- `2026_08_06_000018_create_ueq_pooled_results_and_reliability_metadata` — Ran, batch 5.

Status akhir dan jumlah non-sensitif:

| Bukti | Nilai |
|---|---:|
| Migrasi berstatus Ran | 25 |
| Periode evaluasi | 1 |
| Unit Wong Reang | 13 |
| Item UEQ | 26 |
| Benchmark | 6 |
| Benchmark terverifikasi | 6 |
| Pooled result produksi | 0 — wajar sampai calculation run baru dibuat |

## Matriks acceptance criteria Rilis 2

| Kriteria desain §18.2 | Status | Bukti utama |
|---|---|---|
| Transformasi seluruh item sesuai polaritas tervalidasi | PASS | `UeqTransformerTest`, snapshot polarity, dan workbook formula |
| Statistik UEQ dan gap cocok fixture emas | PASS | `UeqStatisticsCalculatorTest`, `GoldenWorkbookConsistencyTest`, `GoldenCalculationRunTest`; 0 mismatch |
| Penilaian setiap informan tersimpan terpisah | PASS | `TechnicalInputLifecycleTest`, tiga informan × 13 assessment individual, konsensus/SD di snapshot |
| Bobot yang tidak berjumlah 100 ditolak | PASS | domain-boundary dan Livewire validation tests |
| Matriks, normalisasi, Vi, dan peringkat cocok fixture emas | PASS | `SawGoldenFixtureTest` memeriksa X, R, kontribusi C1–C3, Vi, rank, dan tie |
| Setiap angka hasil dapat ditelusuri ke run dan inputnya | PASS | immutable run/snapshot/result rows, input hash, audit event, dan tabel bukti admin |

## Alur UAT browser

Administrator fixture telah berstatus email-verified dan 2FA-confirmed. Alur 1280 × 800 yang lulus:

1. Dashboard → review kualitas respons;
2. halaman informan menampilkan tiga informan dan status konsensus lengkap;
3. halaman kalkulasi membuat preview baru;
4. metadata input hash, pooled reliability, serta kontribusi C1–C3 terlihat;
5. regresi Rilis 3 membuka laporan, membuat preview, dan mengunci run official;
6. sidebar desktop dan drawer 360 × 800 tetap dapat digunakan.

## Privasi evidence

Evidence ini dan test assertion tidak memuat password, recovery code, token/token hash, cookie, IP address, user agent, jawaban mentah, anonymous respondent ID, atau identitas personal responden. Calculation audit hanya menyimpan metadata run dan input hash; raw answers tetap berada di snapshot run yang memiliki boundary akses admin, bukan di audit event atau UI evidence.
