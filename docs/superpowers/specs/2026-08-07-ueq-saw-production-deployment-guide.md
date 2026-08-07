# Panduan Deployment Production — Survei UEQ-SAW Wong Reang Apps

**Tujuan:** membawa aplikasi ke environment production (domain + HTTPS + hosting) agar **periode resmi** `wong-reang-2026-resmi` dapat diaktifkan untuk pengumpulan data resmi.
**Stack:** Laravel 13 · Livewire 4 · Tailwind 4 · MySQL 8 · PHP ≥ 8.3.
**Tanggal penyusunan:** 7 Agustus 2026.

> Setelah deploy selesai, rekam tiga evidence production mengikuti `2026-08-07-ueq-saw-evidence-production-guide.md`, lalu aktifkan periode di `/admin/study`.

---

## 1. Prasyarat server

- **PHP ≥ 8.3** dengan ekstensi: `mysql`/`pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `ctype`, `json`, `tokenizer`, `xml`, `curl`, `fileinfo`, `gd` (untuk ekspor XLSX).
- **Composer 2**, **Node.js ≥ 20** + npm.
- **MySQL 8** (atau MariaDB kompatibel).
- **Web server** (Nginx/Apache) dengan document root ke `application/public/`.
- **HTTPS** dengan sertifikat valid (Let's Encrypt via Certbot, atau dari provider).

## 2. Urutan deploy

### 2.1 Siapkan database

```sql
CREATE DATABASE ueq_saw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ueq_saw_app'@'localhost' IDENTIFIED BY '<password-kuat>';
GRANT ALL PRIVILEGES ON ueq_saw.* TO 'ueq_saw_app'@'localhost';
FLUSH PRIVILEGES;
```

### 2.2 Ambil kode + install dependency

```bash
git clone https://github.com/bakhrul96/Tugas-Akhir-Survei-UEQ-SAW.git
cd Tugas-Akhir-Survei-UEQ-SAW/application

composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 2.3 Konfigurasi `.env` production

Salin `.env.example` ke `.env`, lalu isi nilai production:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://survei.domainkamu.id        # domain HTTPS production

DB_HOST=127.0.0.1
DB_DATABASE=ueq_saw
DB_USERNAME=ueq_saw_app
DB_PASSWORD=<password-kuat>

# WAJIB — untuk hash token responden. Generate: php -r "echo bin2hex(random_bytes(32));"
SURVEY_TOKEN_KEY=<acak-64-karakter-hex>
SURVEY_COOKIE_NAME=ueq_survey_token

# Aman di balik HTTPS + reverse proxy
SESSION_SECURE_COOKIE=true
FORCE_PUBLIC_URL=true                        # memaksa URL absolut ke APP_URL https

# Queue + cache via database (default, tanpa Redis)
QUEUE_CONNECTION=database
CACHE_STORE=database

# Session via database
SESSION_DRIVER=database
```

Lalu generate key dan cache config:

```bash
php artisan key:generate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2.4 Migrasi + seeder studi

```bash
php artisan migrate --force
php artisan db:seed --force        # WongReangStudySeeder: periode draft + 13 modul + 26 item + 6 benchmark
```

### 2.5 Permission storage + cache

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache   # sesuaikan user web server
```

### 2.6 Buat akun admin production

```bash
php artisan app:create-admin peneliti@domainkamu.id
```

Login di `/login`, lalu **aktifkan 2FA** di Pengaturan Akun → Keamanan (wajib untuk aktivasi periode: sistem mensyaratkan tepat satu admin terverifikasi + 2FA aktif).

### 2.7 Web server (contoh Nginx)

```nginx
server {
    listen 443 ssl http2;
    server_name survei.domainkamu.id;
    root /var/www/Tugas-Akhir-Survei-UEQ-SAW/application/public;

    ssl_certificate     /etc/letsencrypt/live/survei.domainkamu.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/survei.domainkamu.id/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Redirect HTTP -> HTTPS
server {
    listen 80;
    server_name survei.domainkamu.id;
    return 301 https://$host$request_uri;
}
```

Reload Nginx: `sudo systemctl reload nginx`.

### 2.8 Queue worker (untuk proses latar belakang)

Aplikasi memakai queue database. Jalankan worker persisten via systemd:

```ini
# /etc/systemd/system/ueq-saw-worker.service
[Unit]
Description=UEQ-SAW queue worker
After=network.target

[Service]
User=www-data
WorkingDirectory=/var/www/Tugas-Akhir-Survei-UEQ-SAW/application
ExecStart=/usr/bin/php artisan queue:work database --sleep=3 --tries=1 --timeout=90
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now ueq-saw-worker
```

## 3. Verifikasi pasca-deploy

- [ ] `https://survei.domainkamu.id/login` termuat tanpa peringatan sertifikat.
- [ ] `php artisan about` tanpa error; `APP_ENV=production`, `APP_DEBUG=false`.
- [ ] Login admin berhasil; 2FA aktif.
- [ ] `php artisan test --testsuite=Browser` pass di server (uji submit end-to-end).
- [ ] Backup DB pertama diambil dan dipulihkan ke DB terpisah untuk uji restore.

## 4. Aktivasi periode resmi

1. Rekam tiga evidence production di `/admin/study` (periode `wong-reang-2026-resmi`) mengikuti `2026-08-07-ueq-saw-evidence-production-guide.md`.
2. Pastikan panel "Kesiapan aktivasi" menampilkan "✓ Semua syarat aktivasi telah terpenuhi".
3. Klik **"Aktifkan dan kunci konfigurasi"**.
4. Sebarkan URL survei: `https://survei.domainkamu.id/s/wong-reang/wong-reang-2026-resmi`.

## 5. Pemeliharaan

- **Backup rutin:** jadwalkan `mysqldump` harian (cron) ke lokasi privat.
- **Deploy update:** `git pull && composer install --no-dev -o && npm run build && php artisan migrate --force && php artisan config:cache && php artisan queue:restart`.

---

> Periode demo `wong-reang-2026` tetap `locked` dengan hasil resminya — jangan dibuka kembali.
