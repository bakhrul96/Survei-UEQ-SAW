# Desain Sub-proyek F: Pengalaman Pengguna Survey

Tanggal: 2026-08-08
Status: Disetujui
Cakupan: Sub-proyek F dari pemetaan perbaikan Tugas Akhir (responsif mobile + feedback pasca-survei).

## Latar Belakang

Analisis frontend survei publik menemukan beberapa gap pengalaman pengguna:

1. **Skala Likert 7 poin** di `ueq-wizard.blade.php:33` (`grid grid-cols-7 gap-2`) tetap 7 kolom di semua ukuran layar. Pada viewport 360px, setiap sel hanya ~44px — sempit untuk tap target.
2. **Kartu statistik persetujuan** di `consent-screener.blade.php:9` (`grid grid-cols-3 gap-2.5`) tetap 3 kolom di semua ukuran, sempit di 360px.
3. **Tidak ada tes regresi** untuk overflow horizontal 360px maupun scan aksesibilitas (Axe) pada tampilan survei publik. Sisi admin sudah punya pola ini (`ReleaseTwoUiAuditTest.php:93`).
4. **Halaman selesai** (`complete.blade.php`) hanya menampilkan konfirmasi simpan — tidak ada ringkasan ringan berupa nama modul yang dinilai.

Sub-proyek ini mengatasi keempat gap tersebut. Tidak menyentuh logika UEQ/SAW, database, maupun API.

## Keputusan Desain (hasil brainstorming)

| Area | Keputusan |
|------|-----------|
| Skala Likert | 7 kolom di `sm:` ke atas, vertikal (1 kolom) di bawahnya |
| Kartu statistik consent | Stack vertikal di mobile, 3 kolom di `sm:`+ |
| Feedback pasca-survei | Ringkasan ringan: nama modul + konfirmasi + terima kasih |
| Cakupan tes | Overflow 360px + Axe scan |

## Perubahan

### 1. Skala Likert Responsif — `application/resources/views/livewire/survey/ueq-wizard.blade.php:33`

Ganti `grid grid-cols-7 gap-2` menjadi `grid grid-cols-1 gap-2 sm:grid-cols-7`.

- Desktop (`sm:`+): tetap 7 kolom horizontal.
- Mobile (<640px): satu kolom vertikal; tiap opsi tetap menampilkan angka (1–7) dengan tap target `min-h-11` yang sudah ada.
- Murni perubahan class CSS. Tidak ada perubahan pada logika, nama field, nilai, maupun `aria-label`.

### 2. Kartu Statistik Responsif — `application/resources/views/livewire/survey/consent-screener.blade.php:9`

Ganti `grid grid-cols-3 gap-2.5` menjadi `grid gap-2.5 sm:grid-cols-3`.

- Mobile: tiga kartu (Anonim / ±N mnt / 26 pertanyaan) bertumpuk satu kolom.
- `sm:`+: tiga kolom seperti sekarang.
- Murni perubahan class CSS.

### 3. Halaman Selesai — Ringkasan Ringan

**`application/app/Livewire/Survey/Complete.php`**:
- Ubah query `SurveySubmission` (baris 22-26) untuk *eager-load* relasi `unit()`, sehingga `$submission->unit` tersedia di view.
- Tidak ada perubahan lain pada logika.

**`application/resources/views/livewire/survey/complete.blade.php`**:
- Tambahkan baris kecil di dalam kartu emerald, di bawah kalimat terima kasih (baris 7), menampilkan nama modul: misalnya teks **"Modul yang Anda nilai: {{ $submission->unit->name }}"**.
- Tidak menampilkan jawaban per item maupun skor dimensi (menghindari bocornya data agregasi).

### 4. Tes Regresi — `application/tests/Browser/SurveyResponsiveTest.php`

File baru yang meniru pola `ReleaseTwoUiAuditTest.php`:

- **Overflow 360px**: viewport 360×800, asersi `document.documentElement.scrollWidth <= window.innerWidth` pada halaman consent, wizard (setiap langkah 1-4), dan complete.
- **Axe scan**: `assertNoAccessibilityIssues()` pada consent, wizard, dan complete.
- **Helper**: gunakan `surveyFixture()`, `lockStudyConfiguration()` yang sudah dipakai `SurveyHappyPathTest.php`. Set periode ke status `Active` dengan `opens_at`/`closes_at` di sekitar `now()` dan `configuration_locked_at = now()`.

## Batasan

- Tidak ada migrasi database.
- Tidak ada perubahan API atau kontrak data.
- Tidak ada perubahan logika SAW/UEQ.
- Tidak mengubah perilaku validator atau penyimpanan jawaban.

## Tidak Masuk Cakupan

- Konfirmasi visual "draft tersimpan" (catatan UX minor dari eksplorasi) — sengaja dikecualikan untuk menjaga fokus.
- Recap jawaban lengkap / skor dimensi — ditolak pada brainstorming (risiko bocornya data agregasi).

## Verifikasi

- Menjalankan test suite browser untuk survei: `php artisan test --filter=Survey` (bagian browser).
- Memastikan tes happy path dan offline draft tetap hijau.
- Memastikan `SurveyResponsiveTest` baru hijau (overflow + Axe).
- Review visual manual di viewport 360px dan desktop untuk wizard, consent, dan complete.