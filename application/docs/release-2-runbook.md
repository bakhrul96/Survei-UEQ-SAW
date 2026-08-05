# Runbook Rilis 2

Tanggal verifikasi: 2026-08-05. Ruang lingkup: review kualitas, statistik UEQ, input informan, preview UEQ/SAW.

## Gate yang dijalankan

- `php artisan migrate --force` — migrasi teknis, calculation run, dan revision input MySQL berhasil.
- `php artisan db:seed --class=WongReangStudySeeder --force` — seed Wong Reang berhasil.
- `composer test` — wajib lulus sebelum merge.
- `npm run build` — wajib lulus sebelum merge.
- `php artisan test tests/Feature/Admin/CalculationsTest.php` — halaman preview terlindungi dan tidak menampilkan kontrol Rilis 3.

## UAT browser

Masuk sebagai administrator yang telah menyelesaikan 2FA, kemudian verifikasi urutan: Dashboard → Respons → Informan → Kalkulasi → Jalankan preview. Pastikan halaman menunjukkan input hash, warning bila data informan belum lengkap, tabel UEQ, dan tabel SAW bila sedikitnya dua alternatif lengkap tersedia. Jangan catat token, password, cookie, jawaban mentah, atau identitas responden dalam bukti UAT.

## MySQL dan pemulihan

Sebelum pengumpulan data, jalankan prosedur backup/restore yang telah disetujui untuk database `ueq_saw`. Konfirmasi satu periode, 13 unit, 26 item pada versi instrumen aktif, dan enam benchmark tervalidasi. Hasil UAT harus menyebut viewport dan commit yang diuji, tanpa memuat rahasia.
