# Survei UEQ-SAW Wong Reang Apps

Aplikasi penelitian Tugas Akhir untuk mengevaluasi pengalaman pengguna (UX) terhadap **13 modul Wong Reang Apps** menggunakan **User Experience Questionnaire (UEQ)** 26 item, lalu menentukan **prioritas perbaikan** dengan **Simple Additive Weighting (SAW)** — menggabungkan gap UX terhadap benchmark, estimasi waktu pengerjaan, dan urgensi arsitektur dari informan teknis — disertai **analisis sensitivitas** dan **expert judgment**.

Dibangun dengan **Laravel 13 · Livewire 4 · Tailwind CSS 4 · MySQL 8**.

---

## Daftar Isi

1. [Konsep Singkat](#1-konsep-singkat)
2. [Prasyarat](#2-prasyarat)
3. [Instalasi & Setup Awal](#3-instalasi--setup-awal)
4. [Menjalankan Aplikasi](#4-menjalankan-aplikasi)
5. [Alur Penggunaan dari Awal hingga Akhir](#5-alur-penggunaan-dari-awal-hingga-akhir)
   - [Fase A — Konfigurasi & Aktivasi Periode](#fase-a--konfigurasi--aktivasi-periode-admin)
   - [Fase B — Pengumpulan Data Responden](#fase-b--pengumpulan-data-responden-publik)
   - [Fase C — Review Kualitas Respons](#fase-c--review-kualitas-respons-admin)
   - [Fase D — Input Penilaian Informan Teknis](#fase-d--input-penilaian-informan-teknis-admin)
   - [Fase E — Tutup Periode & Kalkulasi](#fase-e--tutup-periode--kalkulasi-admin)
   - [Fase F — Laporan & Ekspor untuk Bab IV](#fase-f--laporan--ekspor-untuk-bab-iv-admin)
6. [Peran Halaman Admin](#6-peran-halaman-admin)
7. [Menjalankan Test](#7-menjalankan-test)
8. [Struktur Proyek](#8-struktur-proyek)
9. [Troubleshooting](#9-troubleshooting)
10. [Dokumen Terkait](#10-dokumen-terkait)

---

## 1. Konsep Singkat

| Istilah | Arti |
|---|---|
| **UEQ** | 26 item kuesioner UX (skala 1–7) dalam 6 skala: Attractiveness, Perspicuity, Efficiency, Dependability, Stimulation, Novelty. |
| **Modul** | 13 unit layanan Wong Reang Apps yang dinilai responden. |
| **Gap** | Selisih mean skala modul terhadap threshold "Good" benchmark, `max(0, threshold − mean)` — makin besar makin perlu diperbaiki. |
| **SAW** | Pembobotan 3 kriteria: **C1** Gap UEQ (benefit), **C2** mean estimasi hari (cost), **C3** mean urgensi arsitektur (benefit). Menghasilkan nilai preferensi `Vi` dan peringkat. |
| **Sensitivitas** | Uji ketahanan peringkat pada 3 skenario bobot: **S0** (bobot aktual informan), **S1** (0,60/0,20/0,20 — dominasi UX), **S2** (0,20/0,40/0,40 — dominasi teknis). |
| **Calculation run** | Setiap kalkulasi disimpan sebagai run dengan versi algoritma + hash input; bisa preview (tanpa kunci) lalu satu run dikunci sebagai **hasil resmi**. |
| **Expert judgment** | Backlog operasional yang boleh berbeda dari peringkat SAW, dengan alasan wajib — tanpa mengubah angka SAW. |

**Prinsip data:** responden anonim (hanya hash token + cookie), satu token tidak bisa menilai modul yang sama dua kali, jawaban mentah tidak diimputasi, dan setiap angka hasil dapat ditelusuri ke run + input-nya.

---

## 2. Prasyarat

- **PHP ≥ 8.3** dengan ekstensi `mysql`, `mbstring`, `openssl`, `bcmath`.
- **Composer 2**.
- **Node.js ≥ 20** + npm.
- **MySQL 8** (atau MariaDB kompatibel).
- Untuk uji browser: **Chrome/Chromium** headless.

---

## 3. Instalasi & Setup Awal

Semua perintah dijalankan dari direktori `application/`.

```bash
cd application
```

### 3.1 Setup otomatis (disarankan)

```bash
composer setup
```

Perintah ini menjalankan: `composer install` → salin `.env` → `key:generate` → `migrate --force` → `npm install` → `npm run build`.

### 3.2 Konfigurasi environment (`.env`)

Nilai yang **wajib** diisi/disesuaikan:

```dotenv
APP_URL=http://localhost:8000            # ganti ke domain production saat deploy

DB_DATABASE=ueq_saw
DB_USERNAME=ueq_saw_app
DB_PASSWORD=<password-database>

SURVEY_TOKEN_KEY=<acak-kuat-minimal-32-byte>   # WAJIB — untuk hash token responden
```

> **`SURVEY_TOKEN_KEY` wajib diisi.** Tanpa ini, penerbitan token responden gagal. Generate misalnya dengan `php -r "echo bin2hex(random_bytes(32));"`.

### 3.3 Migrasi + seeder studi

```bash
php artisan migrate --force
php artisan db:seed --force        # menjalankan WongReangStudySeeder
```

Seeder mengisi: 1 periode draft (`wong-reang-2026`), 13 modul aktif, 26 item UEQ versi `UEQ-ID-26-v1`, dan 6 benchmark versi aktif.

### 3.4 Buat akun admin

```bash
php artisan app:create-admin peneliti@example.com
```

Ikuti prompt nama + password (min. 12 karakter). Login di `/login`, lalu **aktifkan 2FA** di menu Pengaturan → Keamanan. **Aktivasi periode mensyaratkan tepat satu admin terverifikasi dengan 2FA aktif.**

---

## 4. Menjalankan Aplikasi

### Mode pengembangan (semua sekaligus)

```bash
composer dev
```

Menjalankan server (`:8000`), queue worker, dan Vite dev server bersamaan.

### Mode produksi / manual

```bash
npm run build                 # bundle asset
php artisan serve             # atau konfigurasi virtual host ke public/
```

Akses:

- **Survei responden (publik):** `http://localhost:8000/s/wong-reang/{slug-periode}` — contoh `/s/wong-reang/wong-reang-2026`
- **Admin:** `http://localhost:8000/login` → dashboard di `/admin/dashboard`

---

## 5. Alur Penggunaan dari Awal hingga Akhir

> **Gambaran besar:** Admin menyiapkan & mengaktifkan periode → responden mengisi survei → admin mereview kualitas → admin memasukkan penilaian informan → admin menutup periode & menjalankan kalkulasi → admin mengunci hasil resmi → ekspor laporan.

### Fase A — Konfigurasi & Aktivasi Periode (Admin)

Menu **Konfigurasi** (`/admin/study`).

1. **Isi konfigurasi periode** lalu simpan — tanggal buka/tutup, usia minimum, **minimum & target per modul** + **dasar target** (kutip Bab II + persetujuan pembimbing), seluruh teks **consent** (tujuan, data, cookie, estimasi menit, hak berhenti, kontak), **ambang respons cepat** + versi aturan kualitas, dan **bobot skenario sensitivitas S1/S2** (masing-masing harus berjumlah tepat 1,000000 — **ditetapkan sebelum melihat hasil**).
2. **Verifikasi instrumen** — isi sumber instrumen UEQ lalu klik verifikasi.
3. **Verifikasi 6 benchmark** — klik verifikasi per skala.
4. **Catat tiga bukti kesiapan (readiness evidence)** oleh admin 2FA:
   - **HTTPS** — URL production (divalidasi format https).
   - **Uji pemulihan backup** — dump DB, pulihkan ke DB terpisah, verifikasi 13 modul/26 item/6 benchmark; catat nama file + catatan ≥20 karakter.
   - **Uji submit survei** — jalankan `php artisan test tests/Browser` atau uji manual end-to-end; catat hasilnya.
5. **Klik "Aktifkan".** Sistem memvalidasi semua prasyarat; jika ada yang kurang, daftar issue ditampilkan. Saat berhasil, konfigurasi **dikunci permanen** (`configuration_locked_at` + `configuration_hash`).

> Checklist lengkap: `docs/superpowers/specs/2026-08-06-ueq-saw-uat-checklist-aktivasi.md`.

### Fase B — Pengumpulan Data Responden (Publik)

Sebarkan URL survei `/s/wong-reang/{slug}`. Alur responden (semua tanpa akun):

1. **Informasi penelitian & consent** — setujui untuk lanjut.
2. **Screener** — usia ≥ minimum, domisili Kabupaten Indramayu, pernah memakai Wong Reang. Yang tidak lolos diarahkan ke halaman penolakan (form UEQ tidak bisa dibuka).
3. **Pilih modul** — hanya modul yang **belum** dinilai token ini yang bisa dipilih.
4. **Konfirmasi pengalaman** — menyatakan pernah menyelesaikan layanan pada modul itu.
5. **Isi 26 item UEQ** dalam 4 langkah (7-7-6-6). Jawaban tersimpan sementara di perangkat (tahan jika koneksi putus) dan baru dianggap selesai setelah server mengonfirmasi.
6. **Selesai** — boleh berhenti atau menilai modul lain. Setelah 3 modul dalam satu sesi, sistem menganjurkan istirahat (tanpa melarang).

**Jaminan sistem:** satu token tidak bisa menilai modul sama dua kali; submit ganda tidak membuat duplikat (idempotency key); submission + 26 jawaban tersimpan dalam satu transaksi.

### Fase C — Review Kualitas Respons (Admin)

Menu **Respons** (`/admin/responses`).

- Sistem otomatis memberi **flag** pada respons yang terlalu cepat (di bawah ambang) atau yang 26 jawabannya identik — flag **tidak** otomatis mengecualikan.
- Untuk setiap submission, tetapkan **included** atau **excluded**. **Excluded wajib diisi alasannya** (tersimpan dengan reviewer + waktu).
- Setiap perubahan keputusan membuat calculation run sebelumnya menjadi **stale**.

> Pantau progres di **Dashboard** (`/admin/dashboard`): responden unik, evaluasi terkirim, evaluasi ber-flag, excluded, menunggu review, dan progres minimum/target per modul. Kejar modul yang masih di bawah minimum dengan rekrutmen terarah.

### Fase D — Input Penilaian Informan Teknis (Admin)

Menu **Informan** (`/admin/technical-assessments`).

- Masukkan **3–5 informan** dengan kode anonim (mis. AH-01).
- Setiap informan menilai **seluruh 13 modul**: estimasi **hari** (> 0) dan **urgensi arsitektur** (integer 1–5).
- Setiap informan membagi **tepat 100 poin** ke C1, C2, C3 — jumlah ≠ 100 ditolak.
- Sistem menghitung mean, simpangan baku, dan **bobot konsensus ternormalisasi** (jumlah = 1) sebagai bobot aktual S0.

### Fase E — Tutup Periode & Kalkulasi (Admin)

Menu **Kalkulasi** (`/admin/calculations`).

1. **Tutup periode** di menu Konfigurasi (active → closed) — periode closed menolak submission baru.
2. **Jalankan Preview.** Sistem menghitung UEQ → gap → SAW → sensitivitas, menyimpan run baru dengan versi algoritma + hash input (tanpa mengunci).
3. Tinjau tabel hasil: statistik UEQ per modul, gap, peringkat SAW, dan perbandingan sensitivitas S0/S1/S2 dengan delta peringkat + label stabil.
4. **(Opsional) Expert judgment** — ubah urutan **backlog operasional**; perbedaan urutan dari SAW wajib beralasan dan tercatat.
5. **(Bila minimum tidak tercapai)** — catat **keputusan penyimpangan minimum** dengan referensi persetujuan pembimbing (aturan 10.7); jika tidak, penguncian diblokir.
6. **Kunci Hasil Resmi.** Sistem memvalidasi kelayakan (semua submission ter-review, data teknis lengkap, bobot sah, ≥2 alternatif, sensitivitas lengkap, backlog urut). Setelah dikunci, hasil **immutable** — koreksi dilakukan dengan membuat run baru, bukan menimpa.

### Fase F — Laporan & Ekspor untuk Bab IV (Admin)

Menu **Laporan** (`/admin/reports`).

- **Peringkat analitis** (SAW) dan **backlog operasional** (expert judgment) ditampilkan sebagai dua keluaran berbeda, beserta grafik dan **tabel angka yang selalu menyertai**.
- **Ekspor agregat (XLSX/CSV):** 6 sheet — Metadata Run, Benchmark, Hasil UEQ, Peringkat SAW, Analisis Sensitivitas, Backlog Operasional — lengkap dengan periode, versi instrumen, versi algoritma, run ID, status run, dan waktu pembuatan.
- **Ekspor data mentah (CSV/XLSX)** dari Dashboard: 26 kolom item + metadata submission, **tanpa** token mentah/identitas pribadi.

---

## 6. Peran Halaman Admin

| Halaman | Rute | Fungsi |
|---|---|---|
| Dashboard | `/admin/dashboard` | Pantau responden, evaluasi, flag/excluded, progres per modul, ekspor data mentah. |
| Respons | `/admin/responses` | Review kualitas submission (included/excluded beralasan). |
| Informan | `/admin/technical-assessments` | Input penilaian 13 modul + bobot 100 poin per informan, konsensus. |
| Kalkulasi | `/admin/calculations` | Preview, sensitivitas, expert judgment, penyimpangan minimum, kunci resmi, riwayat run. |
| Laporan | `/admin/reports` | Peringkat analitis + backlog operasional, grafik + tabel, ekspor agregat. |
| Konfigurasi | `/admin/study` | Setup periode, consent, target, benchmark, evidence, aktivasi, tutup periode. |

---

## 7. Menjalankan Test

```bash
php artisan test                          # Unit + Feature (default)
php artisan test --testsuite=Unit         # 30 test kalkulasi (UEQ/SAW/sensitivitas/konsensus)
php artisan test --testsuite=Feature      # 215 test alur aplikasi
php artisan test --testsuite=Browser      # 10 test UI (headless Chrome), termasuk viewport 360px
```

**Fixture emas:** perhitungan UEQ & SAW diverifikasi terhadap dataset acuan independen (`tests/Fixtures/ueq-saw-golden.json`) dalam toleransi numerik — memastikan hasil matematis deterministik dan benar.

Alat kualitas lain:

```bash
composer lint        # Laravel Pint (format)
vendor/bin/phpstan   # analisis statis (bila dikonfigurasi)
```

---

## 8. Struktur Proyek

```
Tugas-Akhir-Survei-UEQ-SAW/
├── application/               # Aplikasi Laravel
│   ├── app/
│   │   ├── Domain/            # Logic deterministik (Ueq, Saw, Sensitivity, Technical, Quality, Study, Survey)
│   │   ├── Application/       # Use-case services (Survey, Quality, Calculation, Reporting, Study)
│   │   ├── Livewire/          # Komponen UI (Survey/* untuk responden, Admin/* untuk admin)
│   │   ├── Models/            # Eloquent models
│   │   └── Console/Commands/  # app:create-admin
│   ├── database/migrations/   # Skema (periode, submission, jawaban, kualitas, informan, hasil)
│   ├── database/seeders/      # WongReangStudySeeder (periode + 13 modul + 26 item + 6 benchmark)
│   ├── resources/views/       # Blade (layouts + komponen Livewire)
│   ├── routes/web.php         # Rute survei publik + admin
│   └── tests/                 # Unit / Feature / Browser + fixture emas
└── docs/
    ├── superpowers/specs/     # Spec desain MVP, klarifikasi, checklist UAT
    └── release-*-runbook.md   # Runbook operasional per rilis
```

---

## 9. Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| Token responden gagal / error `SURVEY_TOKEN_KEY wajib diatur` | `SURVEY_TOKEN_KEY` kosong | Isi nilai acak ≥32 byte di `.env`, lalu `config:clear`. |
| Tombol "Aktifkan" selalu menolak | Masih ada issue kesiapan | Lihat daftar issue di halaman Konfigurasi — lengkapi (benchmark, evidence, instrumen, bobot S1/S2, admin 2FA). |
| Halaman tanpa styling saat diakses lewat tunnel/domain lain | Asset di-generate dari `APP_URL` lama | Set `APP_URL` (dan `ASSET_URL`) ke domain aktif, `npm run build`, `config:clear`. |
| Penguncian hasil resmi ditolak | Prasyarat belum lengkap | Baca daftar issue di Kalkulasi: review semua submission, lengkapi informan, catat penyimpangan minimum bila perlu. |
| Kolom sensitivity tidak ditemukan | Migrasi tertinggal | `php artisan migrate --force` (ada 3 migrasi release-3). |
| Test browser "no tests found" | Suite belum dipanggil eksplisit | Jalankan `php artisan test --testsuite=Browser`. |

---

## 10. Dokumen Terkait

- **Spec desain MVP:** `docs/superpowers/specs/2026-08-04-ueq-saw-ta-mvp-design.md`
- **Checklist UAT & aktivasi:** `docs/superpowers/specs/2026-08-06-ueq-saw-uat-checklist-aktivasi.md`
- **Klarifikasi status `calculated` & kriteria 18.3.5:** `docs/superpowers/specs/2026-08-06-ueq-saw-status-calculated-dan-1835-klarifikasi.md`
- **Runbook operasional:** `docs/release-1-runbook.md`, `docs/release-2-runbook.md`, `docs/release-3-runbook.md`
