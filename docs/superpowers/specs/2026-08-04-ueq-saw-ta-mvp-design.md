# SPESIFIKASI DESAIN MVP PENELITIAN UEQ-SAW

**Instrumen Pengumpulan Data dan Sistem Pendukung Keputusan Prioritas Perbaikan Modul Wong Reang Apps**  
**Platform:** Laravel 13, Livewire 4, Tailwind CSS 4, MySQL 8  
**Disusun untuk:** Bakhrul Ullum (250101020011)  
**Sumber kebutuhan:** Bab I sampai Bab III dan hasil brainstorming desain  
**Status:** Desain tervalidasi untuk ditinjau sebelum rencana implementasi  
**Tanggal:** 4 Agustus 2026

## 1. Ringkasan Keputusan

MVP ditujukan untuk menyelesaikan Tugas Akhir dengan Wong Reang Apps sebagai satu-satunya objek penelitian. Sistem mengumpulkan evaluasi User Experience Questionnaire (UEQ) per modul, menjaga keterlacakan data, menghitung gap UX, menggabungkan gap dengan estimasi waktu dan urgensi arsitektur melalui Simple Additive Weighting (SAW), menjalankan analisis sensitivitas, dan menyimpan expert judgment.

Pengumpulan data harus dapat dimulai dalam satu sampai dua minggu. Karena itu, implementasi dibagi menjadi tiga rilis vertikal. Rilis pertama hanya memuat fungsi yang diperlukan agar survei dapat dibuka secara aman. Mesin analisis dan pelaporan dikembangkan ketika pengumpulan data sudah berjalan.

Platform multi-aplikasi, penugasan banyak admin, dan klaim production-ready dikeluarkan dari MVP. Reusabilitas dipertahankan melalui pemisahan modul internal Survey, UEQ, SAW, Validation, dan Reporting, bukan melalui fitur organisasi atau multi-aplikasi.

## 2. Masalah yang Diselesaikan

1. Evaluasi UX terhadap 13 modul Wong Reang Apps belum dikumpulkan melalui instrumen yang terstruktur per modul.
2. Data UEQ belum langsung menghasilkan ukuran gap terhadap benchmark.
3. Prioritas perbaikan tidak cukup ditentukan dari persepsi pengguna karena waktu pengerjaan dan urgensi arsitektur juga memengaruhi kelayakan operasional.
4. Pemindahan dan perhitungan data secara manual berisiko menimbulkan kesalahan yang sulit ditelusuri.
5. Peneliti membutuhkan keluaran yang dapat digunakan untuk menyusun analisis Bab IV tanpa mengubah hasil matematis secara tersembunyi.

## 3. Tujuan dan Batas Keberhasilan

### 3.1 Tujuan

- Mengumpulkan 26 jawaban UEQ untuk setiap evaluasi modul yang valid.
- Membedakan jumlah responden unik dari jumlah evaluasi modul.
- Mencegah satu token anonim menilai modul yang sama dua kali dalam satu periode.
- Menghitung UEQ, gap, SAW, dan sensitivitas secara deterministik serta dapat diuji.
- Menjaga data mentah, hasil antara, bobot, dan keputusan ahli agar dapat diaudit.
- Menghasilkan peringkat analitis dan backlog operasional sebagai dua keluaran berbeda.

### 3.2 Batas Klaim

- Hasil empiris hanya berlaku untuk Wong Reang Apps pada periode penelitian.
- Satu responden boleh menilai lebih dari satu modul sehingga data mengandung repeated measures.
- Sistem tidak menganggap evaluasi dari responden yang sama sebagai observasi independen untuk pengujian inferensial antar-modul.
- Cookie adalah kontrol duplikasi perangkat, bukan bukti identitas manusia.
- Reusabilitas merupakan karakteristik desain internal, bukan hasil validasi empiris pada aplikasi lain.

## 4. Ruang Lingkup

### 4.1 Termasuk dalam MVP

- Satu studi Wong Reang Apps.
- Satu periode penelitian aktif.
- Satu peran internal Peneliti/Admin.
- Consent, screener, dan token anonim.
- Tiga belas unit evaluasi berupa modul Wong Reang Apps.
- Dua puluh enam item UEQ Bahasa Indonesia dengan polaritas terkunci.
- Submission terpisah untuk setiap modul.
- Pemantauan jumlah responden unik dan evaluasi valid per modul.
- Review kualitas respons dengan keputusan included atau excluded.
- Input penilaian tiga sampai lima informan teknis.
- Perhitungan UEQ, benchmark, gap, SAW, dan sensitivitas.
- Pencatatan expert judgment.
- Dashboard penelitian dan ekspor CSV/XLSX.
- Riwayat calculation run dan penguncian hasil resmi.

### 4.2 Ditunda Setelah Tugas Akhir

- Master banyak aplikasi dan isolasi data antar-aplikasi.
- Super Administrator, undangan Admin Aplikasi, dan penugasan lintas aplikasi.
- Registrasi organisasi, langganan, atau kemampuan SaaS.
- Banyak periode aktif secara bersamaan.
- Konfigurasi instrumen UEQ melalui antarmuka umum.
- Audit lintas organisasi dan manajemen akses kompleks.
- PDF dengan tata letak kompleks.
- SLA 99 persen dan klaim kapasitas produksi yang belum diuji.

## 5. Tahapan Rilis

### 5.1 Rilis 1: Survei Siap Digunakan

Rilis pertama harus tersedia sebelum pengumpulan data resmi dimulai.

- Login Peneliti/Admin.
- Konfigurasi satu periode penelitian.
- Consent dan screener.
- Daftar tetap 13 modul.
- Form UEQ 26 item per modul.
- Token anonim dan pencegahan duplikasi.
- Penyimpanan jawaban dalam transaksi database.
- Pemulihan jawaban ketika koneksi gagal sebelum submit berhasil.
- Progres evaluasi per modul.
- Ekspor data mentah CSV/XLSX.

### 5.2 Rilis 2: Pengolahan Penelitian

Rilis kedua dikerjakan ketika survei sudah berjalan.

- Flag kualitas respons dan review included/excluded.
- Transformasi polaritas UEQ.
- Statistik deskriptif dan reliabilitas.
- Benchmark dan gap.
- Input informan teknis serta bobot.
- Matriks, normalisasi, nilai preferensi, dan peringkat SAW.
- Fixture emas sebagai pembanding perhitungan.

### 5.3 Rilis 3: Analisis dan Pelaporan

- Analisis sensitivitas.
- Expert judgment.
- Grafik UEQ, gap, kontribusi SAW, dan perubahan peringkat.
- Peringkat analitis dan backlog operasional.
- Ekspor hasil agregat untuk penyusunan Bab IV.

## 6. Aktor

### 6.1 Responden Anonim

Responden menyetujui informasi penelitian, lolos screener, memilih modul yang benar-benar pernah digunakan, mengisi UEQ, dan dapat kembali untuk menilai modul lain. Responden tidak membuat akun dan tidak memberikan NIK, nama, nomor telepon, atau alamat lengkap.

### 6.2 Peneliti/Admin

Peneliti mengelola periode, memantau progres sampel, meninjau kualitas respons, memasukkan hasil wawancara informan, menjalankan kalkulasi, mencatat expert judgment, mengunci hasil, dan mengekspor data.

## 7. Satuan Data dan Rancangan Sampel

### 7.1 Satuan Data

- **Responden unik:** satu token anonim dalam satu periode.
- **Evaluasi modul:** satu responden menilai satu modul.
- **Jawaban UEQ:** 26 nilai mentah dalam satu evaluasi modul.

Kecukupan data dinilai terutama pada jumlah evaluasi valid per modul. Jumlah responden unik dilaporkan secara terpisah untuk menunjukkan berapa orang yang berpartisipasi.

### 7.2 Target Operasional

Angka 96 responden unik tidak menjadi satu-satunya indikator kecukupan. Sistem memakai target yang dikunci pada saat periode diaktifkan dengan aturan berikut:

- target selalu ditetapkan untuk setiap modul;
- nilai awal yang diajukan adalah minimum 20 dan target 30 evaluasi valid per modul;
- target resmi harus mengikuti Bab II yang telah direvisi dan persetujuan pembimbing;
- periode tidak dapat diaktifkan sebelum Peneliti mencatat dasar target yang digunakan;
- dashboard tidak menyatakan penelitian cukup jika terdapat modul yang belum mencapai minimum.

### 7.3 Konsekuensi Repeated Measures

Responden boleh menilai seluruh modul yang pernah digunakan. Setiap modul tetap menjadi submission terpisah. Sistem menyimpan urutan modul dan jumlah submission dalam satu sesi. Laporan wajib menyatakan bahwa beberapa evaluasi berasal dari responden yang sama dan tidak boleh mengklaim seluruh observasi antar-modul independen.

## 8. Alur Responden

1. Responden membuka landing page penelitian.
2. Sistem membuat token acak jika token periode belum tersedia.
3. Responden membaca informasi penelitian dan memberikan consent eksplisit.
4. Responden mengisi screener usia, domisili Kabupaten Indramayu, dan penggunaan Wong Reang Apps.
5. Responden yang tidak memenuhi syarat diarahkan ke halaman penolakan tanpa membuat submission.
6. Responden memilih satu modul yang pernah digunakan.
7. Responden mengonfirmasi pernah menyelesaikan minimal satu proses layanan pada modul tersebut. Tidak ada batas recency tambahan pada MVP.
8. Responden mengisi 26 item UEQ dalam empat langkah berurutan 7-7-6-6.
9. Sistem memvalidasi kelengkapan dan menyimpan submission beserta seluruh jawaban dalam satu transaksi.
10. Sistem menampilkan konfirmasi berhasil serta pilihan berhenti atau menilai modul lain.
11. Setelah tiga modul dalam satu sesi, sistem menampilkan anjuran istirahat, tetapi tidak melarang responden melanjutkan.
12. Responden dapat kembali pada sesi lain untuk menilai modul yang belum pernah dinilai oleh token tersebut.

## 9. Kebutuhan Fungsional

### 9.1 Periode Penelitian

- Status periode: `draft`, `active`, `closed`, `calculated`, dan `locked`.
- Hanya satu periode yang dapat aktif.
- Instrumen, polaritas, benchmark, screener, modul, dan target dikunci ketika periode diaktifkan.
- Periode closed tidak menerima submission baru.
- Periode calculated dapat mempunyai beberapa calculation run.
- Periode locked menunjuk satu run sebagai hasil resmi.

### 9.2 Consent dan Screener

- Consent menjelaskan tujuan, data yang disimpan, penggunaan cookie, estimasi waktu, hak berhenti, dan kontak penelitian.
- Screener memeriksa usia minimum, domisili Kabupaten Indramayu, dan pengalaman menggunakan Wong Reang Apps.
- Kelayakan disimpan sekali per token dan periode.
- Responden yang tidak memenuhi syarat tidak dapat membuka form UEQ.

### 9.3 Survei UEQ

- Responden memilih satu dari 13 modul yang belum pernah dinilai token tersebut.
- Dua puluh enam item ditampilkan sesuai urutan instrumen resmi.
- Setiap item memakai rentang 1 sampai 7 dan menampilkan kedua label kutub.
- Item pada langkah aktif harus lengkap sebelum responden melanjutkan.
- Jawaban dapat diperbaiki sebelum submit final.
- Skor terkonversi dan hasil penelitian tidak ditampilkan kepada responden.
- Sistem menyimpan skor mentah, waktu mulai, waktu selesai, urutan modul, versi instrumen, dan nomor sesi.

### 9.4 Kualitas Respons

- Sistem memberi flag pada durasi di bawah ambang yang ditetapkan sebelum periode aktif.
- Sistem memberi flag pada 26 jawaban mentah yang identik dan pola kualitas lain yang didefinisikan sebelum aktivasi.
- Flag tidak otomatis mengecualikan respons.
- Peneliti menetapkan included atau excluded dengan alasan wajib.
- Perubahan keputusan kualitas menghasilkan audit minimal dan membuat run sebelumnya stale.
- Nilai ambang dan aturan flag disimpan sebagai bagian konfigurasi periode agar tidak dipilih setelah melihat hasil.

### 9.5 Penilaian Informan Teknis

- Tiga sampai lima informan direkam menggunakan kode anonim.
- Setiap informan memberi estimasi hari dan urgensi arsitektur 1 sampai 5 untuk seluruh modul.
- Setiap informan membagi tepat 100 poin ke C1, C2, dan C3.
- Sistem menyimpan nilai individual, mean, simpangan baku, dan hasil konsensus.
- Kalkulasi final diblokir jika terdapat nilai teknis atau bobot yang belum lengkap.

### 9.6 Kalkulasi dan Validasi

- Preview dapat dijalankan tanpa mengunci hasil.
- Setiap kalkulasi membuat calculation run baru.
- Run menyimpan versi algoritma, snapshot/hash input, benchmark, bobot, jumlah included/excluded, dan hasil antara.
- Perubahan input menandai run lama stale tanpa menghapusnya.
- Satu run dapat ditetapkan sebagai hasil resmi setelah validasi selesai.

### 9.7 Pelaporan

- Dashboard membedakan responden unik, total evaluasi, valid, flagged, dan excluded.
- Progres minimum dan target ditampilkan per modul.
- Tabel angka selalu tersedia bersama grafik.
- Ekspor mencantumkan periode, versi instrumen, benchmark, run ID, status run, dan waktu pembuatan.

## 10. Aturan Bisnis dan Integritas

1. Satu hash token hanya boleh mempunyai satu submission untuk kombinasi periode dan modul.
2. Satu submission valid harus mempunyai tepat 26 jawaban dengan nomor item unik.
3. Nilai mentah hanya 1 sampai 7.
4. Submission dan 26 jawaban disimpan dalam satu transaksi.
5. Idempotency key mencegah request submit yang sama membuat submission kedua.
6. Responden unik dan evaluasi modul tidak boleh ditampilkan sebagai angka yang sama.
7. Modul yang belum mencapai minimum tetap dapat dihitung sebagai preview. Run final diblokir kecuali minimum tercapai atau Peneliti mencatat keputusan penyimpangan yang telah disetujui pembimbing beserta konsekuensi interpretasinya.
8. Estimasi waktu harus lebih besar dari nol.
9. Urgensi hanya 1 sampai 5.
10. Alokasi bobot setiap informan harus tepat 100 poin.
11. Gap tidak pernah bernilai negatif.
12. Data hilang tidak diimputasi otomatis.
13. Run locked tidak dapat ditimpa; koreksi menghasilkan run baru.
14. Peringkat SAW dan backlog hasil expert judgment disimpan sebagai keluaran berbeda.
15. Eksklusi respons selalu mempunyai alasan, pengguna, dan waktu perubahan.

## 11. Arsitektur Sistem

### 11.1 Pilihan Arsitektur

Sistem menggunakan modular monolith Laravel dengan satu database MySQL. Livewire menangani interaksi form dan presentasi, sedangkan perhitungan berada pada service domain deterministik.

```text
Antarmuka Survei dan Admin
            |
Application Actions dan Queries
            |
Study | Survey | Quality | UEQ | Technical Assessment | SAW | Validation | Reporting
            |
          MySQL
```

### 11.2 Batas Modul

- **Study Configuration:** periode, consent, screener, target, dan modul.
- **Survey:** token anonim, profil kelayakan, submission, jawaban, dan sesi.
- **Quality Review:** flag dan keputusan included/excluded.
- **UEQ:** polaritas, agregasi, reliabilitas, benchmark, dan gap.
- **Technical Assessment:** informan, estimasi waktu, urgensi, bobot, dan konsensus.
- **SAW:** matriks, normalisasi, kontribusi, nilai preferensi, dan peringkat.
- **Validation:** sensitivitas dan expert judgment.
- **Reporting:** progres, tabel, grafik, dan ekspor.

Komponen UI tidak boleh mengandung rumus UEQ atau SAW. Service perhitungan menerima input eksplisit dan menghasilkan output tanpa bergantung pada session atau state antarmuka.

## 12. Model Data Konseptual

| Entitas | Data utama | Integritas |
|---|---|---|
| `users` | akun Peneliti/Admin | email unik |
| `evaluation_periods` | status, tanggal, consent, screener, target, aturan flag | satu active |
| `evaluation_units` | kode, nama, urutan | kode unik |
| `anonymous_respondents` | token_hash, first_seen, last_seen | token_hash unik |
| `respondent_profiles` | period, respondent, consent, eligibility | unik period-respondent |
| `survey_sessions` | respondent, period, started, last_activity | menyimpan urutan sesi |
| `survey_submissions` | period, respondent, session, unit, timestamps, status | unik period-respondent-unit |
| `survey_answers` | submission, item, raw_score | unik submission-item |
| `quality_reviews` | submission, flags, decision, reason, reviewer | satu keputusan aktif |
| `ueq_items` | order, labels, scale, positive_pole, version | tepat 26 pada versi aktif |
| `ueq_benchmarks` | scale, threshold, source, version | unik version-scale |
| `technical_informants` | anonymous_code | kode unik |
| `technical_assessments` | informant, unit, days, urgency | unik informant-unit |
| `criteria_weights` | informant, C1, C2, C3 | jumlah 100 |
| `calculation_runs` | version, input_hash, status, snapshot, official | immutable setelah selesai |
| `ueq_results` | run, unit, scale, n, mean, sd, se, ci, alpha, gap | unik run-unit-scale |
| `saw_results` | run, unit, X, R, contributions, Vi, rank | unik run-unit |
| `sensitivity_results` | run, scenario, unit, Vi, rank, delta | unik run-scenario-unit |
| `expert_judgments` | run, decision, reason, operational_order | tidak menimpa saw_results |
| `audit_events` | user, action, object, old_value, new_value, timestamp | append-only untuk perubahan kritis |

## 13. Spesifikasi Perhitungan

### 13.1 Transformasi UEQ

Untuk jawaban mentah `x` pada rentang 1 sampai 7:

```text
jika kutub kanan positif: y = x - 4
jika kutub kiri positif : y = 4 - x
```

Skor terkonversi `y` berada pada rentang -3 sampai +3. Polaritas berasal dari master item tervalidasi dan tidak ditentukan dari teks label saat runtime.

### 13.2 Statistik per Modul

Untuk setiap modul dan skala, sistem menghitung:

- jumlah evaluasi valid `n`;
- mean item terkonversi pada skala;
- sample standard deviation;
- standard error `SE = SD / sqrt(n)`;
- confidence interval 95 persen menggunakan distribusi t ketika dapat dihitung.

Jika statistik tidak dapat dihitung karena jumlah atau variasi data tidak memadai, nilai disimpan sebagai tidak tersedia beserta alasan; sistem tidak mengubahnya menjadi nol.

### 13.3 Reliabilitas

- Cronbach's Alpha per modul dan skala menjadi keluaran utama.
- Alpha gabungan seluruh modul ditampilkan sebagai diagnostik tambahan dengan label `pooled`.
- Alpha per modul dengan `n < 20` diberi status belum memadai untuk interpretasi.
- Alpha di bawah 0,70 menghasilkan peringatan dan tidak otomatis menghapus data.
- Skala tanpa variasi yang cukup dilaporkan sebagai tidak dapat dihitung.

### 13.4 Benchmark dan Gap

```text
Gap(unit, scale) = max(0, GoodThreshold(scale) - Mean(unit, scale))
Gap(unit)        = jumlah enam Gap(unit, scale) / 6
```

Benchmark wajib mempunyai sumber dan versi. Jika threshold kategori lengkap belum diverifikasi, sistem hanya menampilkan posisi terhadap batas Good dan gap; kategori Excellent sampai Bad tidak dibuat.

### 13.5 Matriks SAW

| Kriteria | Nilai | Sifat |
|---|---|---|
| C1 | Gap UEQ modul | Benefit |
| C2 | Mean estimasi hari | Cost |
| C3 | Mean urgensi arsitektur | Benefit |

Normalisasi:

```text
Benefit: r(i,j) = x(i,j) / max_i x(i,j)
Cost   : r(i,j) = min_i x(i,j) / x(i,j)
```

Kasus tepi:

- jika seluruh C1 nol, seluruh R1 ditetapkan nol dan run diberi peringatan;
- C2 nol atau negatif ditolak sebelum kalkulasi;
- nilai yang hilang memblokir kalkulasi final;
- minimal dua modul dengan data lengkap diperlukan untuk menjalankan SAW.

### 13.6 Bobot dan Nilai Preferensi

Bobot final adalah rata-rata alokasi informan yang dinormalisasi hingga jumlahnya 1.

```text
Vi = (w1 * r(i,1)) + (w2 * r(i,2)) + (w3 * r(i,3))
```

Nilai disimpan dengan presisi tinggi dan diurutkan menggunakan nilai penuh. Nilai yang sama dalam toleransi presisi ditampilkan sebagai seri. MVP tidak memberi kategori percentile buatan; hasil utama adalah Vi, peringkat, seri, dan tiga posisi teratas.

### 13.7 Sensitivitas

| Skenario | C1 | C2 | C3 | Makna |
|---|---:|---:|---:|---|
| S0 | bobot aktual | bobot aktual | bobot aktual | baseline informan |
| S1 | 0,60 | 0,20 | 0,20 | dominasi kebutuhan UX |
| S2 | 0,20 | 0,40 | 0,40 | dominasi pertimbangan teknis |

Sistem membandingkan seluruh perubahan peringkat terhadap S0 dan menyoroti perubahan pada tiga posisi teratas. Label stabil diberikan ketika tiga posisi teratas tidak berubah, tetapi besar perubahan peringkat seluruh modul tetap ditampilkan.

S1 dan S2 merupakan skenario penelitian yang ditetapkan sebelum hasil survei diperiksa. Nilai skenario disimpan pada konfigurasi periode dan tidak boleh diubah untuk mengejar peringkat tertentu setelah hasil diketahui.

### 13.8 Expert Judgment

Expert judgment dapat menghasilkan backlog operasional yang berbeda dari peringkat SAW. Sistem tidak mengubah `saw_results`. Perbedaan urutan wajib mempunyai alasan, waktu, dan pengguna yang mencatat keputusan.

## 14. Rancangan Antarmuka

### 14.1 Halaman Responden

| Halaman | Isi |
|---|---|
| Informasi penelitian | tujuan, privasi, cookie, waktu, hak berhenti, consent |
| Screener | usia, domisili, penggunaan Wong Reang |
| Pemilihan modul | 13 modul, status sudah/belum dinilai |
| Konfirmasi pengalaman | konfirmasi penggunaan modul terpilih |
| UEQ langkah 1-4 | 7-7-6-6 item dalam urutan resmi |
| Selesai | konfirmasi, berhenti, atau menilai modul lain |

### 14.2 Halaman Admin

| Halaman | Isi |
|---|---|
| Dashboard | responden unik, evaluasi, progres per modul, flagged, excluded |
| Respons | submission, durasi, flag, keputusan kualitas |
| Informan | penilaian teknis, bobot, dan konsensus |
| Kalkulasi | preview UEQ, gap, SAW, sensitivitas, riwayat run |
| Laporan | tabel angka, grafik, peringkat analitis, backlog operasional, ekspor |
| Konfigurasi | periode, consent, screener, target, benchmark, status |

### 14.3 Ketahanan Form

- Jawaban sementara disimpan pada perangkat sampai server mengonfirmasi submit berhasil.
- Jawaban lokal dihapus setelah konfirmasi server.
- Kegagalan jaringan menampilkan tindakan untuk mencoba kembali tanpa mengisi ulang.
- Modul tidak ditandai selesai sebelum transaksi server berhasil.
- Sistem tidak mengalihkan responden otomatis ke modul berikutnya.

### 14.4 Aksesibilitas

- Survei berfungsi mulai lebar 360 piksel.
- Label kutub tidak hanya dibedakan oleh posisi atau warna.
- Fokus keyboard terlihat.
- Pesan kesalahan berada dekat input dan dapat dibaca screen reader.
- Area sentuh serta kontras memadai.

## 15. Validasi dan Penanganan Kesalahan

### 15.1 Sebelum Aktivasi

Aktivasi periode diblokir jika:

- consent atau screener belum lengkap;
- 13 modul belum tersedia;
- versi instrumen tidak mempunyai tepat 26 item;
- polaritas atau pemetaan skala belum lengkap;
- benchmark tidak mempunyai sumber dan versi;
- target per modul atau dasar target belum dicatat;
- ambang dan aturan quality flag belum ditetapkan;
- akun Peneliti/Admin tidak aktif;
- HTTPS, backup, dan uji submit belum diverifikasi pada lingkungan penelitian.

### 15.2 Sebelum Kalkulasi Final

- periode sudah closed;
- seluruh respons included mempunyai tepat 26 jawaban;
- setiap modul mencapai minimum yang dikunci, kecuali terdapat keputusan penyimpangan yang disetujui pembimbing dan direkam dalam run;
- seluruh modul yang dipakai dalam SAW mempunyai data teknis lengkap;
- seluruh informan mempunyai bobot berjumlah 100;
- minimal dua alternatif lengkap tersedia;
- benchmark dan versi algoritma tercatat;
- keputusan kualitas sudah dibekukan untuk run tersebut.

### 15.3 Pola Kesalahan

Pesan menjelaskan masalah dan pemulihan. Contoh: `Bobot informan AH-02 berjumlah 95 poin. Tambahkan 5 poin sebelum menyimpan.` Pesan generik seperti `Data tidak valid` tidak digunakan jika sistem mengetahui penyebabnya.

## 16. Keamanan dan Privasi

- Seluruh trafik deployment penelitian memakai HTTPS.
- Cookie anonim memakai Secure, HttpOnly, dan SameSite=Lax.
- Database hanya menyimpan hash token menggunakan secret server; token mentah tidak dicatat pada log.
- Login dan submit memakai rate limit.
- Semua form memakai CSRF protection dan validasi server-side.
- Output di-escape untuk mencegah XSS.
- Data responden tidak memuat NIK, nama, telepon, atau alamat lengkap.
- IP hanya boleh digunakan sementara untuk log keamanan atau rate limiter dan tidak masuk dataset penelitian.
- Backup disimpan privat dan diuji pemulihannya sebelum periode aktif.
- Audit minimal mencatat eksklusi respons, perubahan data informan, kalkulasi, dan penguncian hasil.

## 17. Strategi Pengujian

### 17.1 Unit Test UEQ

- transformasi kutub kanan positif;
- transformasi kutub kiri positif;
- nilai batas 1, 4, dan 7;
- pemetaan 26 item ke enam skala;
- mean, SD, SE, confidence interval, dan alpha;
- kondisi statistik tidak dapat dihitung;
- gap tidak negatif.

### 17.2 Unit Test SAW

- normalisasi benefit dan cost;
- seluruh gap nol;
- penolakan waktu nol atau negatif;
- rata-rata dan normalisasi bobot;
- kontribusi kriteria dan Vi;
- seri dan peringkat;
- skenario S0, S1, S2 serta delta peringkat.

### 17.3 Feature Test Survei

- screener lolos dan ditolak;
- token dibuat dan hanya hash yang disimpan;
- submission mempunyai tepat 26 jawaban;
- duplikasi period-token-unit ditolak;
- token yang sama dapat menilai unit berbeda;
- request submit berulang idempotent;
- kegagalan submit tidak membuat data parsial;
- periode closed menolak submission;
- admin yang belum login ditolak;
- perubahan inclusion membuat run lama stale.

### 17.4 Uji Penggunaan dan Operasional

- pengisian lengkap pada ponsel Android lebar 360 piksel;
- pemulihan form setelah koneksi terputus;
- navigasi keyboard dan pembacaan label;
- ekspor data mentah dan agregat;
- pemulihan database dari backup uji.

### 17.5 Fixture Emas

Satu dataset kecil dihitung secara independen menggunakan spreadsheet. Dataset memuat skor mentah, transformasi, mean, gap, matriks X dan R, bobot, Vi, peringkat, serta skenario sensitivitas. Test otomatis harus menghasilkan nilai yang sama dalam toleransi numerik yang ditentukan pada fixture.

## 18. Kriteria Penerimaan

### 18.1 Rilis 1

1. Responden yang memenuhi screener dapat menyelesaikan 26 item untuk satu modul.
2. Responden yang tidak memenuhi screener tidak dapat membuka form.
3. Token yang sama tidak dapat menilai modul yang sama dua kali.
4. Token yang sama dapat menilai modul lain dan kembali pada sesi berikutnya.
5. Submit ganda tidak membuat data duplikat.
6. Kegagalan jaringan sebelum submit berhasil tidak menghilangkan jawaban sementara.
7. Admin melihat responden unik, evaluasi, dan progres setiap modul secara terpisah.
8. Data mentah dapat diekspor tanpa identitas yang tidak diperlukan.

### 18.2 Rilis 2

1. Transformasi seluruh item sesuai polaritas tervalidasi.
2. Statistik UEQ dan gap cocok dengan fixture emas.
3. Penilaian setiap informan tersimpan terpisah.
4. Bobot yang tidak berjumlah 100 ditolak.
5. Matriks, normalisasi, Vi, dan peringkat cocok dengan fixture emas.
6. Setiap angka hasil dapat ditelusuri ke run dan inputnya.

### 18.3 Rilis 3

1. S0 memakai bobot aktual informan.
2. S1 dan S2 menghasilkan perbandingan serta delta peringkat.
3. Peringkat SAW tidak berubah ketika expert judgment dicatat.
4. Backlog operasional yang berbeda mempunyai alasan.
5. Satu run resmi dapat dikunci dan dibuka kembali tanpa perubahan angka.
6. Laporan dan ekspor mencantumkan metadata periode serta run.

## 19. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Rilis 1 terlambat | Pengumpulan data dan Bab IV tertunda | Buka survei setelah fungsi pengumpulan lulus; jangan menunggu analitik lengkap |
| Modul kurang populer tidak mencapai minimum | Perbandingan antar-modul lemah | Rekrutmen terarah berdasarkan progres per modul |
| Responden menilai banyak modul berturut-turut | Kelelahan dan carryover | Submission terpisah, pilihan berhenti, anjuran istirahat setelah tiga modul, simpan urutan |
| Cookie dihapus atau perangkat berganti | Duplikasi manusia tidak terdeteksi | Nyatakan keterbatasan, rate limit, flag pola, dan review manual |
| Polaritas salah | Skor UEQ terbalik | Verifikasi sumber, kunci versi, dan unit test seluruh item |
| Benchmark belum lengkap | Kategori menyesatkan | Hanya tampilkan gap terhadap batas Good yang terverifikasi |
| Respons cepat atau identik | Kualitas data turun | Flag prospektif dan review beralasan, tanpa eksklusi otomatis |
| Bobot mengubah hasil secara besar | Prioritas tidak stabil | S0/S1/S2 dan tampilkan delta seluruh peringkat |
| Expert judgment menutupi hasil | Integritas keputusan turun | Pisahkan peringkat analitis dari backlog operasional |
| Scope kembali membesar | Survei tidak selesai tepat waktu | Pertahankan daftar penundaan dan Definition of Done per rilis |

## 20. Traceability ke Penelitian

| Kebutuhan penelitian | Implementasi | Bukti keluaran |
|---|---|---|
| Evaluasi UX 13 modul | Survei UEQ per modul | n, mean, SD, SE, CI, alpha |
| Perbandingan dengan benchmark | UEQ Engine | gap per skala dan modul |
| Estimasi waktu perbaikan | Input informan | mean C2 dan nilai individual |
| Urgensi arsitektur | Input informan | mean C3 dan nilai individual |
| Bobot preferensi | Alokasi 100 poin | bobot aktual S0 |
| Prioritas perbaikan | SAW Engine | X, R, kontribusi, Vi, peringkat |
| Ketahanan prioritas | Sensitivity Engine | S0/S1/S2 dan delta peringkat |
| Kelayakan operasional | Expert judgment | backlog serta alasan perbedaan |
| Keterlacakan | Calculation run | versi, input hash, hasil antara, status |

## 21. Definition of Done

Desain dianggap selesai diimplementasikan untuk Tugas Akhir ketika:

- seluruh kriteria penerimaan Rilis 1 sampai Rilis 3 lulus;
- fixture emas UEQ-SAW cocok dengan hasil aplikasi;
- setiap submission valid mempunyai 26 jawaban dan tidak ada duplikasi kombinasi periode-token-modul;
- data dapat dipulihkan dari backup uji;
- satu run resmi dapat dilacak dari data mentah sampai backlog operasional;
- Bab II dan Bab III sudah selaras mengenai unit sampel, target per modul, repeated measures, aturan kualitas, benchmark, dan skenario sensitivitas;
- tidak ada fitur multi-aplikasi yang menghambat penyelesaian alur penelitian utama.

## 22. Urutan Implementasi Tingkat Tinggi

1. Fondasi Laravel, autentikasi admin, periode, dan seed 13 modul serta 26 item.
2. Token anonim, consent, screener, sesi, form UEQ, transaksi, dan idempotensi.
3. Dashboard progres serta ekspor data mentah; selesaikan Rilis 1 dan mulai pengumpulan data.
4. Review kualitas respons dan penguncian aturan periode.
5. UEQ Engine, benchmark, statistik, gap, dan fixture emas.
6. Input informan, bobot, konsensus, dan SAW Engine.
7. Sensitivitas, expert judgment, grafik, ekspor hasil, dan penguncian run.
8. UAT, backup-restore, pemeriksaan privasi, dan bukti pengujian untuk Bab IV.

Urutan ini merupakan peta rilis. Rencana implementasi rinci per file dan test dibuat setelah dokumen ini ditinjau dan disetujui pengguna.
