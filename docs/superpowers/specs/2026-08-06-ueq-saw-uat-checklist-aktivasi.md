# Checklist UAT dan Kesiapan Aktivasi Periode — Survei UEQ-SAW Wong Reang Apps

**Tanggal penyusunan:** 6 Agustus 2026
**Dasar:** verifikasi kode `PeriodReadinessService.php`, `RecordReadinessEvidence.php`, `StudySettings.php`, spec desain bagian 15.1, dan hasil audit implementasi.
**Cara pakai:** kerjakan berurutan dari Fase 0. Setiap item diberi kotak centang, pemilik (Admin/PF), dan referensi bukti yang diminta sistem. Aktivasi periode **diblokir otomatis** oleh aplikasi selama satu pun item Fase 1–2 belum hijau — checklist ini memastikan kamu tahu persis apa yang harus disiapkan sebelum menekan tombol "Aktifkan".

---

## Fase 0 — Prasyarat lingkungan (PF/hosting, sebelum menyentuh aplikasi)

- [ ] **Aplikasi ter-deploy dan bisa diakses** di URL final penelitian (bukan localhost). Verifikasi: `php artisan about` tanpa error dan halaman login terbuka.
- [ ] **HTTPS aktif** dengan sertifikat valid (bukan self-signed). Verifikasi: buka URL di browser tanpa peringatan; `curl -I https://<domain>` mengembalikan 200/302.
- [ ] **Database production terisi migrasi + seeder**: `php artisan migrate --force` dan seeder studi Wong Reang sudah jalan (13 modul aktif, 26 item UEQ versi aktif, 6 benchmark versi aktif).
- [ ] **Variabel lingkungan production benar**: `APP_ENV=production`, `APP_DEBUG=false`, `SURVEY_TOKEN_KEY` terisi nilai acak kuat (≥32 byte), kredensial DB production, `survey.submit_attempts_per_minute` sesuai kebijakan rate limit.
- [ ] **Tepat satu akun admin terverifikasi + 2FA aktif** (lihat Fase 1 item 11 — sistem menolak aktivasi jika jumlahnya bukan tepat satu).

## Fase 1 — Konfigurasi periode di halaman Konfigurasi (Admin)

Semua nilai berikut **dikunci permanen saat aktivasi** (`configuration_locked_at` + `configuration_hash`). Salah isi = harus membuat periode baru. Isi di `/admin/study` lalu simpan.

- [ ] **1. Tanggal buka/tutup** valid (`closes_at` > `opens_at`).
- [ ] **2. Usia minimum** ≥ 17 (default 17).
- [ ] **3. Minimum per modul** ≥ 1 (usulan desain: 20) dan **target per modul** ≥ minimum (usulan desain: 30).
- [ ] **4. Dasar target sampel** tercatat (teks bebas — kutip Bab II revisi + persetujuan pembimbing).
- [ ] **5. Teks consent lengkap**: tujuan, data yang disimpan, penggunaan cookie, estimasi waktu (menit ≥ 1), hak berhenti, kontak penelitian — kelima field consent + kontak wajib terisi.
- [ ] **6. Ambang respons cepat** (`fast_response_seconds` > 0) dan **versi aturan kualitas** terisi; flag jawaban identik aktif.
- [ ] **7. Bobot skenario sensitivitas** S1 (0,60/0,20/0,20) dan S2 (0,20/0,40/0,40) masing-masing berjumlah tepat 1,000000 — **ditetapkan sebelum melihat hasil survei** (spec 13.7).
- [ ] **8. Verifikasi instrumen**: isi `instrument_source` (sumber instrumen UEQ Bahasa Indonesia tervalidasi) lalu klik verifikasi — `instrument_verified_at` terisi.
- [ ] **9. Verifikasi 6 benchmark**: keenam skala (Attractiveness, Perspicuity, Efficiency, Dependability, Stimulation, Novelty) versi aktif sudah `verified_at` (klik verifikasi per baris di halaman Konfigurasi).
- [ ] **10. Tepat 13 modul aktif** dan versi instrumen memuat tepat 26 item bernomor 1–26 dengan skala valid dan polaritas left/right lengkap (dicek otomatis).
- [ ] **11. Tepat satu admin** dengan email terverifikasi + 2FA terkonfirmasi. Jika lebih/kurang, rapikan dulu akun di database.
- [ ] **12. Tidak ada periode lain berstatus active** (dicek otomatis).

> Halaman Konfigurasi menampilkan daftar `issues` dari `PeriodReadinessService` secara live — selama masih ada teks issue, aktivasi pasti ditolak. Target: daftar kosong.

## Fase 2 — Tiga bukti kesiapan (readiness evidence) yang diminta pengguna

Dicatat di halaman Konfigurasi oleh satu-satunya admin 2FA (sistem menolak jika yang mencatat bukan admin tersebut). Setiap bukti disimpan ke tabel `period_readiness_evidence` dengan referensi + catatan + verifier + waktu.

### 2a. Bukti HTTPS — kind `https`

- [ ] **Lakukan:** uji URL production dari jaringan luar (ponsel 4G, bukan Wi-Fi kantor): landing page, consent, dan login admin semua termuat via HTTPS tanpa mixed content.
- [ ] **Catat di aplikasi:** reference = URL production penuh (mis. `https://survei.wongreang.example`) — sistem memvalidasi format URL https. notes = ringkasan uji (≥1 kalimat; contoh: "Landing, consent, dan login admin dimuat via HTTPS dari jaringan 4G; sertifikat Let's Encrypt valid sampai <tanggal>").

### 2b. Bukti uji pemulihan backup — kind `backup_restore`

- [ ] **Lakukan:** ambil backup database production (`mysqldump`), pulihkan ke database terpisah/lokal, dan verifikasi tabel kunci terisi (contoh query: hitung `evaluation_units` = 13, `ueq_items` = 26, `ueq_benchmarks` = 6). Simpan file backup di lokasi privat.
- [ ] **Catat di aplikasi:** reference = nama/lokasi file backup uji (mis. `ueq_saw_20260806_1200.sql`). notes ≥ 20 karakter (contoh: "Backup 2026-08-06 dipulihkan ke MySQL lokal; 13 modul, 26 item, 6 benchmark terbaca utuh; durasi restore ±3 menit; file disimpan privat di <lokasi>").

### 2c. Bukti uji submit survei — kind `submit_test`

- [ ] **Lakukan:** uji submit ujung-ke-ujung pada environment production **sebelum periode aktif**. Dua opsi:
  - Jalankan test otomatis terhadap build production: `php artisan test --testsuite=Browser` (10 test, termasuk happy path 360px) atau `--testsuite=Feature` (215 test); atau
  - Uji manual pada periode draft terpisah / environment staging dengan data uji: consent → screener → pilih modul → 26 item → konfirmasi tersimpan → cek baris di `survey_submissions` + 26 baris `survey_answers`.
- [ ] **Catat di aplikasi:** reference = nama test/hasil (mis. `SurveyHappyPathTest 1 test / 8 assertions` atau "Uji manual staging 2026-08-06, submission id 7"). notes ≥ 20 karakter (contoh: "Submit 26 item berhasil dalam satu transaksi; jawaban tersimpan lengkap; duplikasi period-token-modul ditolak; draft lokal terhapus setelah konfirmasi server").

## Fase 3 — UAT fungsional (Admin, di environment production sebelum aktivasi)

Jalankan sebagai responden uji pada periode draft (atau staging bila draft production tidak ingin dikotori). Centang setiap skenario:

- [ ] **UAT-1 Screener lolos:** consent diterima, usia ≥ 17, domisili Indramayu, pengguna Wong Reang → diarahkan ke pemilihan modul.
- [ ] **UAT-2 Screener ditolak:** salah satu kriteria gagal → halaman penolakan, form UEQ tidak dapat dibuka (URL wizard langsung = 403).
- [ ] **UAT-3 Duplikasi modul:** token yang sama menilai modul yang sama dua kali → ditolak ("Modul ini sudah pernah dinilai").
- [ ] **UAT-4 Modul berbeda + kembali sesi lain:** token yang sama dapat menilai modul lain, dan bisa kembali di sesi/browser berikutnya (cookie persisten) untuk modul yang belum dinilai.
- [ ] **UAT-5 Submit ganda/klik berulang:** tidak menghasilkan submission duplikat (idempotency key + unique index).
- [ ] **UAT-6 Ketahanan form:** matikan koneksi di tengah pengisian → jawaban tetap ada setelah reload; kirim setelah tersambung; draft lokal terhapus setelah konfirmasi server.
- [ ] **UAT-7 Anjuran istirahat:** setelah 3 modul dalam satu sesi, halaman selesai menampilkan anjuran istirahat tanpa melarang lanjut.
- [ ] **UAT-8 Dashboard:** kartu responden unik, memenuhi syarat, evaluasi terkirim, target, ber-flag, excluded, dan menunggu review menampilkan angka yang cocok dengan data uji; tabel progres per modul menampilkan status below_minimum/minimal_reached/target_reached.
- [ ] **UAT-9 Ponsel 360px:** seluruh alur UAT-1 sampai UAT-6 pada ponsel Android lebar 360px (label kutub terbaca, radio bisa disentuh, error terlihat).
- [ ] **UAT-10 Keyboard & pembaca layar:** navigasi form lengkap tanpa mouse; fokus terlihat; error terbaca (NVDA/VoiceOver) — catat temuan untuk tindak lanjut.
- [ ] **UAT-11 Ekspor data mentah:** CSV dan XLSX terunduh; memuat 26 kolom item + metadata submission; **tanpa** token mentah, NIK, nama, telepon, atau alamat.
- [ ] **UAT-12 Rate limit submit:** percobaan submit berlebihan dari satu token ditolak dengan pesan coba-lagi (bukan error 500).

> Bersihkan data UAT dari production sebelum aktivasi (submission uji ikut terkunci konfigurasinya bila dibiarkan). Cara teraman: lakukan UAT pada periode draft yang memang akan diaktifkan lalu hapus baris uji, atau UAT di staging.

## Fase 4 — Aktivasi dan pengumpulan data

- [ ] **Konfirmasi daftar issues kosong** di halaman Konfigurasi (semua Fase 1–2 hijau).
- [ ] **Klik "Aktifkan"** — sistem mengunci konfigurasi (`configuration_locked_at` + `configuration_hash`) dalam satu transaksi; konfigurasi tidak dapat diubah lagi.
- [ ] **Sebarkan URL survei** (`/s/wong-reang/{slug-periode}`) ke calon responden.
- [ ] **Pantau dashboard berkala:** kejar modul yang masih below_minimum dengan rekrutmen terarah (mitigasi risiko desain).
- [ ] **Backup rutin** selama periode aktif; simpan privat.
- [ ] **Tutup periode** (tombol di Konfigurasi, status active → closed) ketika target tercapai/pengumpulan berakhir — periode closed menolak submission baru secara otomatis.

## Ringkasan pemblokir otomatis (untuk referensi cepat)

Aktivasi ditolak `DomainException` jika salah satu ini masih ada: tanggal invalid · usia min <17 · target < minimum · dasar target kosong · field consent/kontak kosong · ambang ≤0 · versi aturan kosong · flag identik nonaktif · bobot S1/S2 ≠ 1 · admin 2FA ≠ tepat 1 · evidence https/backup_restore/submit_test belum tercatat · instrumen belum diverifikasi · modul aktif ≠ 13 · item ≠ 26 atau nomor/skala/polaritas invalid · 6 benchmark belum verified · ada periode active lain.
