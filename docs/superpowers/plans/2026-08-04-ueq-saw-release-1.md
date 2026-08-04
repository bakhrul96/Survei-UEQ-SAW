# UEQ-SAW Release 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun Rilis 1 yang memungkinkan pengumpulan UEQ 26 item per modul Wong Reang Apps secara anonim, utuh, tahan submit ganda, dapat dipantau per modul, dan dapat diekspor untuk penelitian.

**Architecture:** Aplikasi Laravel ditempatkan di `application/` karena root workspace sudah berisi dokumen penelitian. Laravel modular monolith memakai class-based Livewire pages; interaksi UI berada di Livewire, sedangkan identitas anonim, readiness periode, sesi survei, submission transaksional, query dashboard, dan ekspor berada pada service/action terpisah. Rilis ini tidak memuat kalkulasi UEQ, SAW, sensitivitas, atau multi-aplikasi.

**Tech Stack:** Laravel 13, PHP 8.3+, Livewire 4, Tailwind CSS 4, Flux UI dari starter kit Livewire, Pest 5, MySQL 8 untuk runtime/UAT, SQLite in-memory untuk test cepat, PhpSpreadsheet untuk CSV/XLSX, Vite dan Node.js.

**Dependency decision (2026-08-04):** Pest 5 menggantikan Pest 4 pada implementasi. Laravel 13 dan starter kit Livewire resmi saat ini mengunci `pestphp/pest-plugin-laravel` pada lini 5; lini 4 hanya mendukung Laravel 11/12. Menurunkan Laravel akan melanggar keputusan platform TA, sehingga perubahan ini diratifikasi sebagai deviasi dependency tanpa perubahan metodologi atau perilaku aplikasi.

## Global Constraints

- Semua source aplikasi berada di `application/`; semua perintah PHP, Composer, NPM, dan Artisan dijalankan dari folder tersebut.
- Gunakan Laravel 13, Livewire 4, Tailwind CSS 4, MySQL 8, dan Pest 5; jangan menurunkan versi untuk menghindari penyesuaian kode.
- Hanya satu studi Wong Reang Apps dan satu periode aktif; jangan membuat master aplikasi, organisasi, tenant, atau penugasan banyak admin.
- Registrasi publik wajib dinonaktifkan; akun Peneliti/Admin dibuat melalui command interaktif.
- Responden tidak mempunyai akun dan tidak menyimpan NIK, nama, nomor telepon, atau alamat lengkap.
- Satu submission selalu mewakili satu responden anonim, satu periode, satu modul, dan tepat 26 jawaban.
- Token mentah hanya berada dalam cookie terenkripsi Laravel; database menyimpan HMAC-SHA256 menggunakan `SURVEY_TOKEN_KEY`.
- Satu token boleh menilai banyak modul, tetapi kombinasi periode-token-modul harus unik.
- Form UEQ memakai empat langkah 7-7-6-6 tanpa mengubah urutan item resmi.
- Jangan mengimplementasikan perhitungan UEQ, gap, SAW, sensitivitas, expert judgment, atau PDF dalam plan ini.
- Tulis test terlebih dahulu untuk setiap perilaku; jalankan test yang ditargetkan sebelum full suite.
- Gunakan commit kecil sesuai akhir setiap task. Karena workspace saat ini belum merupakan repositori Git, Task 1 menginisialisasi Git tanpa menambahkan dua file DOCX milik pengguna.
- Dokumentasi resmi yang menjadi acuan implementasi: `https://laravel.com/docs/13.x`, `https://livewire.laravel.com/docs/4.x`, `https://tailwindcss.com/docs/installation/framework-guides/laravel/vite`, dan `https://phpspreadsheet.readthedocs.io/en/stable/`.

---

## Peta File Rilis 1

### Fondasi dan konfigurasi

- `application/composer.json`: Laravel 13, Pest 5, Livewire 4, dan PhpSpreadsheet.
- `application/config/fortify.php`: menonaktifkan registrasi publik.
- `application/config/survey.php`: nama cookie, token key, rate limit, dan masa cookie.
- `application/.env.example`: variabel MySQL dan `SURVEY_TOKEN_KEY` tanpa secret nyata.
- `application/routes/web.php`: route publik survei dan route admin berautentikasi.
- `application/app/Console/Commands/CreateAdmin.php`: membuat satu akun admin tanpa registrasi publik.

### Study Configuration

- `application/app/Domain/Study/PeriodStatus.php`: enum status periode.
- `application/app/Domain/Study/PeriodReadinessService.php`: memeriksa syarat aktivasi.
- `application/app/Models/EvaluationPeriod.php`: konfigurasi dan siklus hidup periode.
- `application/app/Models/EvaluationUnit.php`: 13 modul.
- `application/app/Models/UeqItem.php`: 26 item, skala, polaritas, dan versi.
- `application/app/Models/UeqBenchmark.php`: enam batas Good beserta sumber dan verifikasi.
- `application/database/factories/EvaluationPeriodFactory.php`: periode draft/active untuk test.
- `application/database/factories/EvaluationUnitFactory.php`: unit evaluasi untuk test.
- `application/database/factories/UeqItemFactory.php`: item berurutan untuk fixture test.
- `application/app/Livewire/Admin/StudySettings.php`: konfigurasi dan aktivasi periode.
- `application/resources/views/livewire/admin/study-settings.blade.php`: UI konfigurasi.

### Survey

- `application/app/Models/AnonymousRespondent.php`: identitas berbasis hash token.
- `application/app/Models/RespondentProfile.php`: consent dan hasil screener.
- `application/database/factories/AnonymousRespondentFactory.php`: responden anonim untuk test.
- `application/database/factories/RespondentProfileFactory.php`: profil eligible/ineligible untuk test.
- `application/app/Models/SurveySession.php`: satu rangkaian kunjungan survei.
- `application/app/Models/SurveySubmission.php`: satu evaluasi modul.
- `application/app/Models/SurveyAnswer.php`: satu jawaban UEQ mentah.
- `application/database/factories/SurveySessionFactory.php`: sesi aktif untuk test.
- `application/database/factories/SurveySubmissionFactory.php`: submission lengkap untuk test.
- `application/app/Domain/Survey/IssuedRespondent.php`: DTO hasil penerbitan token.
- `application/app/Domain/Survey/SurveyTokenService.php`: issue, hash, dan resolve token.
- `application/app/Domain/Survey/SurveyContext.php`: memperoleh period dan respondent aktif dari request.
- `application/app/Domain/Survey/SurveyDraftKey.php`: nama key localStorage yang stabil.
- `application/app/Application/Survey/StartSurveySession.php`: membuat atau memakai sesi aktif.
- `application/app/Application/Survey/SubmitSurveyData.php`: DTO submission tervalidasi.
- `application/app/Application/Survey/SubmitSurvey.php`: transaksi idempotent submission dan 26 jawaban.
- `application/app/Http/Controllers/SurveyEntryController.php`: menerbitkan cookie lalu mengarahkan ke consent.
- `application/app/Livewire/Survey/ConsentScreener.php`: consent dan screener.
- `application/app/Livewire/Survey/UnitChooser.php`: daftar modul tersedia/selesai.
- `application/app/Livewire/Survey/UeqWizard.php`: form 7-7-6-6 dan submit.
- `application/app/Livewire/Survey/Complete.php`: konfirmasi dan pilihan berikutnya.
- `application/resources/views/livewire/survey/*.blade.php`: UI mobile-first.

### Admin dan ekspor

- `application/app/Application/Reporting/ReleaseOneDashboardQuery.php`: agregasi progres per modul.
- `application/app/Application/Reporting/ReleaseOneDashboardData.php`: DTO ringkasan dashboard.
- `application/app/Application/Reporting/UnitProgressData.php`: DTO progres satu modul.
- `application/app/Application/Reporting/RawSurveyExport.php`: workbook CSV/XLSX tanpa token hash.
- `application/app/Http/Controllers/Admin/RawSurveyExportController.php`: download terautentikasi.
- `application/app/Livewire/Admin/Dashboard.php`: kartu dan tabel progres.
- `application/resources/views/livewire/admin/dashboard.blade.php`: UI dashboard.

### Pengujian

- `application/tests/Feature/Auth/PublicRegistrationDisabledTest.php`
- `application/tests/Feature/Console/CreateAdminTest.php`
- `application/tests/Feature/Study/StudySeedTest.php`
- `application/tests/Feature/Study/PeriodActivationTest.php`
- `application/tests/Feature/Survey/AnonymousTokenTest.php`
- `application/tests/Feature/Survey/ConsentScreenerTest.php`
- `application/tests/Feature/Survey/UnitChooserTest.php`
- `application/tests/Feature/Survey/UeqWizardTest.php`
- `application/tests/Feature/Survey/SubmitSurveyTest.php`
- `application/tests/Feature/Admin/DashboardTest.php`
- `application/tests/Feature/Admin/RawSurveyExportTest.php`
- `application/tests/Browser/SurveyHappyPathTest.php`
- `application/docs/release-1-runbook.md`

---

### Task 1: Scaffold Laravel, toolchain, authentication, and test baseline

**Files:**
- Create: `application/` via official Laravel Livewire starter kit
- Create: `application/app/Console/Commands/CreateAdmin.php`
- Create: `application/tests/Feature/Auth/PublicRegistrationDisabledTest.php`
- Create: `application/tests/Feature/Console/CreateAdminTest.php`
- Modify: `application/config/fortify.php`
- Modify: `application/.env.example`
- Modify: `application/composer.json`
- Create: `.gitignore`

**Interfaces:**
- Consumes: none.
- Produces: Laravel application bootable with `php artisan`, authenticated `/admin/dashboard`, Pest test runner, and command `app:create-admin {email}`.

- [ ] **Step 1: Install and verify the missing local toolchain**

The current machine has no `php`, `composer`, `node`, `npm`, or `mysql` command on PATH. Use the official Windows PowerShell installer from Laravel documentation in an elevated PowerShell, restart the terminal, then verify:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
php -v
composer --version
laravel --version
node --version
npm --version
```

Expected: PHP, Composer, Laravel installer, Node, and NPM all print versions. Download the official `mysql-installer-web-community-8.0.46.0.msi` from `https://dev.mysql.com/downloads/windows/installer/8.0.html`, verify MD5 `210420aef5b5006ab54bb1dab4183768`, choose `Server Only`, keep TCP port 3306, enable the Windows service, and store the root password only in the password manager. Feature tests use SQLite in-memory until the MySQL verification in Task 8.

- [ ] **Step 2: Scaffold the application and install export/browser dependencies**

From the workspace root:

```powershell
laravel new application
Set-Location application
composer require phpoffice/phpspreadsheet
composer require pestphp/pest-plugin-browser --dev
npm install
npm install playwright@latest --save-dev
npx playwright install chromium
npm run build
```

Choose these installer answers: Livewire starter kit, built-in Laravel authentication, no teams, Pest, and MySQL. Confirm `composer.json` contains `laravel/framework:^13.0`, `livewire/livewire:^4.0`, `pestphp/pest:^5.0`, and `phpoffice/phpspreadsheet`.

Configure `.env.example` for runtime without a password value:

```dotenv
APP_NAME="UEQ-SAW Wong Reang"
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ueq_saw
DB_USERNAME=ueq_saw_app
DB_PASSWORD=
SESSION_SECURE_COOKIE=false
```

Configure the `phpunit.xml` `<php>` section for isolated fast tests:

```xml
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_STORE" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SURVEY_TOKEN_KEY" value="test-only-survey-token-key-32-bytes"/>
```

- [ ] **Step 3: Initialize Git without staging the user's DOCX files**

From the workspace root:

```powershell
git init
```

Create root `.gitignore` with:

```gitignore
/.codex-work/
/application/.env
/application/vendor/
/application/node_modules/
/application/public/build/
/application/storage/*.key
```

Do not add either DOCX file in the workspace root.

- [ ] **Step 4: Write failing authentication and admin-command tests**

`tests/Feature/Auth/PublicRegistrationDisabledTest.php`:

```php
<?php

it('does not expose public registration', function () {
    $this->get('/register')->assertNotFound();
});
```

`tests/Feature/Console/CreateAdminTest.php`:

```php
<?php

use App\Models\User;

it('creates an admin with a hashed password', function () {
    $this->artisan('app:create-admin', ['email' => 'peneliti@example.test'])
        ->expectsQuestion('Nama', 'Peneliti')
        ->expectsQuestion('Password', 'Rahasia-12345')
        ->assertSuccessful();

    $admin = User::query()->where('email', 'peneliti@example.test')->firstOrFail();

    expect($admin->name)->toBe('Peneliti')
        ->and($admin->password)->not->toBe('Rahasia-12345');
});
```

- [ ] **Step 5: Run the tests and verify the intended failures**

```powershell
php artisan test tests/Feature/Auth/PublicRegistrationDisabledTest.php tests/Feature/Console/CreateAdminTest.php
```

Expected: registration test fails because the starter kit exposes `/register`; command test fails because `app:create-admin` does not exist.

- [ ] **Step 6: Disable registration and implement the admin command**

In `config/fortify.php`, keep password reset and email verification if generated, but remove `Features::registration()` from the `features` array.

`app/Console/Commands/CreateAdmin.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {email}';
    protected $description = 'Create or replace the single researcher admin account';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $name = trim((string) $this->ask('Nama'));
        $password = (string) $this->secret('Password');

        if ($name === '' || mb_strlen($password) < 12) {
            $this->error('Nama wajib dan password minimal 12 karakter.');
            return self::FAILURE;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password), 'email_verified_at' => now()],
        );

        $this->info('Akun Peneliti/Admin siap digunakan.');
        return self::SUCCESS;
    }
}
```

- [ ] **Step 7: Run baseline verification**

```powershell
php artisan test tests/Feature/Auth/PublicRegistrationDisabledTest.php tests/Feature/Console/CreateAdminTest.php
php artisan test
npm run build
```

Expected: all tests pass and Vite build exits successfully.

- [ ] **Step 8: Commit the scaffold**

From the workspace root:

```powershell
git add .gitignore application docs/superpowers/specs/2026-08-04-ueq-saw-ta-mvp-design.md docs/superpowers/plans/2026-08-04-ueq-saw-release-1.md
git commit -m "chore: scaffold Laravel survey application"
```

---

### Task 2: Create study schema, exact seeds, and model invariants

**Files:**
- Create: `application/app/Domain/Study/PeriodStatus.php`
- Create: `application/app/Models/EvaluationPeriod.php`
- Create: `application/app/Models/EvaluationUnit.php`
- Create: `application/app/Models/UeqItem.php`
- Create: `application/app/Models/UeqBenchmark.php`
- Create: `application/database/factories/EvaluationPeriodFactory.php`
- Create: `application/database/factories/EvaluationUnitFactory.php`
- Create: `application/database/factories/UeqItemFactory.php`
- Create: `application/database/migrations/2026_08_04_000001_create_evaluation_periods_table.php`
- Create: `application/database/migrations/2026_08_04_000002_create_evaluation_units_table.php`
- Create: `application/database/migrations/2026_08_04_000003_create_ueq_items_table.php`
- Create: `application/database/migrations/2026_08_04_000004_create_ueq_benchmarks_table.php`
- Create: `application/database/seeders/WongReangStudySeeder.php`
- Modify: `application/database/seeders/DatabaseSeeder.php`
- Create: `application/tests/Feature/Study/StudySeedTest.php`

**Interfaces:**
- Consumes: Laravel application and Pest baseline from Task 1.
- Produces: `PeriodStatus`, `EvaluationPeriod`, `EvaluationUnit`, `UeqItem`, `UeqBenchmark`, and an idempotent `WongReangStudySeeder` containing one draft period, 13 units, 26 candidate instrument items, and six candidate Good thresholds.

- [ ] **Step 1: Write the failing study-seed test**

```php
<?php

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\UeqBenchmark;
use App\Models\UeqItem;
use Database\Seeders\WongReangStudySeeder;

it('seeds the fixed Wong Reang study exactly once', function () {
    $this->seed(WongReangStudySeeder::class);
    $this->seed(WongReangStudySeeder::class);

    expect(EvaluationPeriod::count())->toBe(1)
        ->and(EvaluationPeriod::first()->status)->toBe(PeriodStatus::Draft)
        ->and(EvaluationUnit::count())->toBe(13)
        ->and(UeqItem::count())->toBe(26)
        ->and(UeqBenchmark::count())->toBe(6);

    expect(UeqItem::query()->where('order', 1)->firstOrFail()->positive_pole)->toBe('right')
        ->and(UeqItem::query()->where('order', 3)->firstOrFail()->positive_pole)->toBe('left');
});
```

- [ ] **Step 2: Run the test and verify missing classes/tables**

```powershell
php artisan test tests/Feature/Study/StudySeedTest.php
```

Expected: FAIL because study classes and tables do not exist.

- [ ] **Step 3: Implement enum, migrations, and models**

`app/Domain/Study/PeriodStatus.php`:

```php
<?php

namespace App\Domain\Study;

enum PeriodStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
    case Calculated = 'calculated';
    case Locked = 'locked';
}
```

Create migration columns exactly as follows:

```php
// evaluation_periods
$table->id();
$table->string('name');
$table->string('slug')->unique();
$table->string('status')->default('draft');
$table->timestamp('opens_at')->nullable();
$table->timestamp('closes_at')->nullable();
$table->unsignedTinyInteger('minimum_age')->default(17);
$table->unsignedInteger('minimum_per_unit')->default(20);
$table->unsignedInteger('target_per_unit')->default(30);
$table->text('target_basis');
$table->longText('consent_text');
$table->unsignedInteger('fast_response_seconds')->default(120);
$table->string('instrument_version')->default('UEQ-ID-26-v1');
$table->string('instrument_source')->nullable();
$table->timestamp('instrument_verified_at')->nullable();
$table->timestamp('configuration_locked_at')->nullable();
$table->timestamps();

// evaluation_units
$table->id();
$table->string('code')->unique();
$table->string('name');
$table->unsignedTinyInteger('display_order')->unique();
$table->boolean('is_active')->default(true);
$table->timestamps();

// ueq_items
$table->id();
$table->string('version');
$table->unsignedTinyInteger('order');
$table->string('left_label');
$table->string('right_label');
$table->string('scale');
$table->string('positive_pole');
$table->timestamps();
$table->unique(['version', 'order']);

// ueq_benchmarks
$table->id();
$table->string('version');
$table->string('scale');
$table->decimal('good_threshold', 8, 4);
$table->string('source');
$table->timestamp('verified_at')->nullable();
$table->timestamps();
$table->unique(['version', 'scale']);
```

In `EvaluationPeriod`, cast `status` to `PeriodStatus`, all timestamp columns to `datetime`, and numeric target fields to `integer`. Do not introduce an application/tenant foreign key.

Every model referenced with `Model::factory()` must use Laravel's `HasFactory` trait and declare the conventional factory class listed in this task.

Create exact factory defaults:

```php
// EvaluationPeriodFactory::definition()
return [
    'name' => 'Periode Uji',
    'slug' => fake()->unique()->slug(2),
    'status' => PeriodStatus::Draft,
    'opens_at' => now(),
    'closes_at' => now()->addMonth(),
    'minimum_age' => 17,
    'minimum_per_unit' => 20,
    'target_per_unit' => 30,
    'target_basis' => 'Fixture pengujian',
    'consent_text' => 'Saya menyetujui partisipasi pada survei pengujian.',
    'fast_response_seconds' => 120,
    'instrument_version' => 'UEQ-ID-26-v1',
];

// EvaluationUnitFactory::definition()
return [
    'code' => fake()->unique()->slug(2),
    'name' => fake()->words(2, true),
    'display_order' => fake()->unique()->numberBetween(1, 200),
    'is_active' => true,
];

// UeqItemFactory::definition()
return [
    'version' => 'UEQ-TEST-'.fake()->uuid(),
    'order' => 1,
    'left_label' => 'kiri',
    'right_label' => 'kanan',
    'scale' => 'Attractiveness',
    'positive_pole' => 'right',
];
```

- [ ] **Step 4: Implement the exact fixed seed data**

Seed 13 units with order 1-13:

```php
$period = EvaluationPeriod::query()->updateOrCreate(
    ['slug' => 'wong-reang-2026'],
    [
        'name' => 'Evaluasi Wong Reang Apps 2026',
        'status' => PeriodStatus::Draft,
        'minimum_age' => 17,
        'minimum_per_unit' => 20,
        'target_per_unit' => 30,
        'target_basis' => 'Usulan awal minimum 20 dan target 30 evaluasi valid per modul; nilai final mengikuti Bab II dan persetujuan pembimbing.',
        'consent_text' => 'Saya telah membaca informasi penelitian, memahami bahwa partisipasi bersifat sukarela, menyetujui penyimpanan jawaban UEQ dan cookie anonim untuk mencegah pengisian ulang modul yang sama, serta dapat berhenti kapan saja sebelum mengirim jawaban.',
        'fast_response_seconds' => 120,
        'instrument_version' => 'UEQ-ID-26-v1',
    ],
);

$units = [
    ['ibadah-yu', 'Ibadah-Yu'], ['info-yu', 'Info-Yu'],
    ['dumas-yu', 'Dumas-Yu'], ['sekolah-yu', 'Sekolah-Yu'],
    ['sehat-yu', 'Sehat-Yu'], ['pasar-yu', 'Pasar-Yu'],
    ['adminduk-yu', 'Adminduk-Yu'], ['kerja-yu', 'Kerja-Yu'],
    ['renbang-yu', 'Renbang-Yu'], ['izin-yu', 'Izin-Yu'],
    ['pajak-yu', 'Pajak-Yu'], ['plesir-yu', 'Plesir-Yu'],
    ['wifi-yu', 'WiFi-Yu'],
];
```

Seed 26 candidate items. The fifth value is `positive_pole`:

```php
$items = [
    [1, 'menyusahkan', 'menyenangkan', 'Attractiveness', 'right'],
    [2, 'tak dapat dipahami', 'dapat dipahami', 'Perspicuity', 'right'],
    [3, 'kreatif', 'monoton', 'Novelty', 'left'],
    [4, 'mudah dipelajari', 'sulit dipelajari', 'Perspicuity', 'left'],
    [5, 'bermanfaat', 'kurang bermanfaat', 'Stimulation', 'left'],
    [6, 'membosankan', 'mengasyikkan', 'Stimulation', 'right'],
    [7, 'tidak menarik', 'menarik', 'Stimulation', 'right'],
    [8, 'tak dapat diprediksi', 'dapat diprediksi', 'Dependability', 'right'],
    [9, 'cepat', 'lambat', 'Efficiency', 'left'],
    [10, 'berdaya cipta', 'konvensional', 'Novelty', 'left'],
    [11, 'menghalangi', 'mendukung', 'Dependability', 'right'],
    [12, 'baik', 'buruk', 'Attractiveness', 'left'],
    [13, 'rumit', 'sederhana', 'Perspicuity', 'right'],
    [14, 'tidak disukai', 'menggembirakan', 'Attractiveness', 'right'],
    [15, 'lazim', 'terdepan', 'Novelty', 'right'],
    [16, 'tidak nyaman', 'nyaman', 'Attractiveness', 'right'],
    [17, 'aman', 'tidak aman', 'Dependability', 'left'],
    [18, 'memotivasi', 'tidak memotivasi', 'Stimulation', 'left'],
    [19, 'memenuhi ekspektasi', 'tidak memenuhi ekspektasi', 'Dependability', 'left'],
    [20, 'tidak efisien', 'efisien', 'Efficiency', 'right'],
    [21, 'jelas', 'membingungkan', 'Perspicuity', 'left'],
    [22, 'tidak praktis', 'praktis', 'Efficiency', 'right'],
    [23, 'terorganisasi', 'berantakan', 'Efficiency', 'left'],
    [24, 'atraktif', 'tidak atraktif', 'Attractiveness', 'left'],
    [25, 'ramah pengguna', 'tidak ramah pengguna', 'Attractiveness', 'left'],
    [26, 'konservatif', 'inovatif', 'Novelty', 'right'],
];
```

Seed candidate Good thresholds with source `Bab III TA Bakhrul Ullum 2026; verifikasi UEQ source wajib sebelum aktivasi` and `verified_at = null`:

```php
$benchmarks = [
    'Attractiveness' => 1.58,
    'Perspicuity' => 1.73,
    'Efficiency' => 1.50,
    'Dependability' => 1.48,
    'Stimulation' => 1.35,
    'Novelty' => 1.12,
];
```

Use `updateOrCreate` keyed by slug, code, version+order, and version+scale so re-running the seeder is idempotent. Do not set `instrument_verified_at` or benchmark `verified_at` in the seeder.

- [ ] **Step 5: Run migration and seed tests**

```powershell
php artisan migrate:fresh --seed
php artisan test tests/Feature/Study/StudySeedTest.php
```

Expected: one period, 13 units, 26 items, and six benchmarks; test passes.

- [ ] **Step 6: Commit the study foundation**

```powershell
git add application/app/Domain/Study application/app/Models application/database application/tests/Feature/Study/StudySeedTest.php
git commit -m "feat: add fixed Wong Reang study data"
```

---

### Task 3: Implement period readiness, configuration locking, and admin settings

**Files:**
- Create: `application/app/Domain/Study/PeriodReadinessService.php`
- Create: `application/app/Livewire/Admin/StudySettings.php`
- Create: `application/resources/views/livewire/admin/study-settings.blade.php`
- Create: `application/tests/Feature/Study/PeriodActivationTest.php`
- Modify: `application/routes/web.php`

**Interfaces:**
- Consumes: `EvaluationPeriod`, `EvaluationUnit`, `UeqItem`, `UeqBenchmark`, and `PeriodStatus` from Task 2.
- Produces: `PeriodReadinessService::issues(EvaluationPeriod): array<string>` and `PeriodReadinessService::activate(EvaluationPeriod): EvaluationPeriod`; authenticated route `admin.study-settings`.

- [ ] **Step 1: Write failing activation tests**

```php
<?php

use App\Domain\Study\PeriodReadinessService;
use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use App\Models\UeqBenchmark;
use Database\Seeders\WongReangStudySeeder;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
});

it('rejects activation while instrument and benchmarks are unverified', function () {
    $period = EvaluationPeriod::firstOrFail();
    $issues = app(PeriodReadinessService::class)->issues($period);

    expect($issues)->toContain('Instrumen UEQ belum diverifikasi.')
        ->and($issues)->toContain('Enam benchmark belum diverifikasi.');
});

it('locks configuration when every readiness rule passes', function () {
    $period = EvaluationPeriod::firstOrFail();
    $period->update([
        'instrument_source' => 'UEQ Bahasa Indonesia terverifikasi',
        'instrument_verified_at' => now(),
        'opens_at' => now(),
        'closes_at' => now()->addMonth(),
    ]);
    UeqBenchmark::query()->update(['verified_at' => now()]);

    $activated = app(PeriodReadinessService::class)->activate($period->fresh());

    expect($activated->status)->toBe(PeriodStatus::Active)
        ->and($activated->configuration_locked_at)->not->toBeNull();
});
```

- [ ] **Step 2: Run tests and verify failure**

```powershell
php artisan test tests/Feature/Study/PeriodActivationTest.php
```

Expected: FAIL because `PeriodReadinessService` does not exist.

- [ ] **Step 3: Implement exact readiness rules**

`PeriodReadinessService::issues()` returns these messages when applicable:

```php
$issues = [];

if ($period->status !== PeriodStatus::Draft) $issues[] = 'Periode bukan draft.';
if (! $period->opens_at || ! $period->closes_at || $period->closes_at <= $period->opens_at) $issues[] = 'Tanggal periode tidak valid.';
if ($period->minimum_age < 17) $issues[] = 'Usia minimum harus sedikitnya 17 tahun.';
if ($period->minimum_per_unit < 1 || $period->target_per_unit < $period->minimum_per_unit) $issues[] = 'Target per modul tidak valid.';
if (trim($period->target_basis) === '') $issues[] = 'Dasar target sampel wajib dicatat.';
if (trim($period->consent_text) === '') $issues[] = 'Teks consent wajib diisi.';
if (! $period->instrument_verified_at || ! $period->instrument_source) $issues[] = 'Instrumen UEQ belum diverifikasi.';
if (EvaluationUnit::query()->where('is_active', true)->count() !== 13) $issues[] = 'Harus tersedia tepat 13 modul aktif.';
if (UeqItem::query()->where('version', $period->instrument_version)->count() !== 26) $issues[] = 'Versi instrumen harus memiliki tepat 26 item.';
if (UeqBenchmark::query()->whereNotNull('verified_at')->count() !== 6) $issues[] = 'Enam benchmark belum diverifikasi.';
if (EvaluationPeriod::query()->where('status', PeriodStatus::Active->value)->where('id', '!=', $period->id)->exists()) $issues[] = 'Periode aktif lain sudah tersedia.';
```

`activate()` throws `DomainException(implode(' ', $issues))` when non-empty. Otherwise update status, `configuration_locked_at`, and save in a database transaction.

- [ ] **Step 4: Add the authenticated settings page**

Route:

```php
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/study', StudySettings::class)->name('study-settings');
});
```

The Livewire class exposes editable fields only while status is draft. Its `activate()` method calls `PeriodReadinessService`, catches `DomainException`, and maps the message to a visible error summary. The Blade view must show readiness issues individually, verification timestamps, target basis, minimum/target per unit, dates, and an activation confirmation.

- [ ] **Step 5: Add authorization and lock-behavior tests**

Extend `PeriodActivationTest.php`:

```php
use App\Models\User;
use App\Livewire\Admin\StudySettings;
use Livewire\Livewire;

it('requires login for settings', function () {
    $this->get(route('admin.study-settings'))->assertRedirect('/login');
});

it('does not let an active period change locked fields', function () {
    $admin = User::factory()->create();
    $period = EvaluationPeriod::firstOrFail();
    $period->update(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);

    Livewire::actingAs($admin)->test(StudySettings::class)
        ->set('minimumPerUnit', 99)
        ->call('save')
        ->assertForbidden();
});
```

- [ ] **Step 6: Run and commit**

```powershell
php artisan test tests/Feature/Study/PeriodActivationTest.php
php artisan test
git add application/app/Domain/Study/PeriodReadinessService.php application/app/Livewire/Admin application/resources/views/livewire/admin application/routes/web.php application/tests/Feature/Study/PeriodActivationTest.php
git commit -m "feat: validate and activate research period"
```

---

### Task 4: Issue anonymous tokens and capture consent/screener data

**Files:**
- Create: `application/config/survey.php`
- Modify: `application/.env.example`
- Create: `application/app/Models/AnonymousRespondent.php`
- Create: `application/app/Models/RespondentProfile.php`
- Create: `application/database/factories/AnonymousRespondentFactory.php`
- Create: `application/database/factories/RespondentProfileFactory.php`
- Create: `application/app/Domain/Survey/IssuedRespondent.php`
- Create: `application/app/Domain/Survey/SurveyTokenService.php`
- Create: `application/app/Domain/Survey/SurveyContext.php`
- Create: `application/app/Http/Controllers/SurveyEntryController.php`
- Create: `application/app/Livewire/Survey/ConsentScreener.php`
- Create: `application/resources/views/livewire/survey/consent-screener.blade.php`
- Create: `application/database/migrations/2026_08_04_000005_create_anonymous_respondents_table.php`
- Create: `application/database/migrations/2026_08_04_000006_create_respondent_profiles_table.php`
- Create: `application/tests/Feature/Survey/AnonymousTokenTest.php`
- Create: `application/tests/Feature/Survey/ConsentScreenerTest.php`
- Modify: `application/routes/web.php`

**Interfaces:**
- Consumes: active `EvaluationPeriod` from Task 3.
- Produces: `SurveyTokenService::issue(): IssuedRespondent`, `SurveyTokenService::resolve(string): ?AnonymousRespondent`, `SurveyContext::period(): EvaluationPeriod`, `SurveyContext::respondent(): AnonymousRespondent`, and eligible `RespondentProfile`.

- [ ] **Step 1: Write failing anonymous-token tests**

```php
<?php

use App\Domain\Study\PeriodStatus;
use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;

it('issues an encrypted cookie while storing only a keyed hash', function () {
    EvaluationPeriod::factory()->create(['slug' => 'riset-2026', 'status' => PeriodStatus::Active]);

    $response = $this->get('/s/wong-reang/riset-2026');

    $response->assertRedirect('/s/wong-reang/riset-2026/consent')
        ->assertCookie('ueq_survey_token');

    $respondent = AnonymousRespondent::firstOrFail();
    expect($respondent->token_hash)->toMatch('/^[a-f0-9]{64}$/')
        ->and($response->getCookie('ueq_survey_token')->getValue())->not->toBe($respondent->token_hash);
});

it('reuses a valid survey token instead of creating a second respondent', function () {
    $period = EvaluationPeriod::factory()->create(['slug' => 'riset-2026', 'status' => PeriodStatus::Active]);
    $first = $this->get('/s/wong-reang/riset-2026');
    $plainToken = $first->getCookie('ueq_survey_token')->getValue();

    $this->withCookie('ueq_survey_token', $plainToken)
        ->get('/s/wong-reang/riset-2026')
        ->assertRedirect();

    expect(AnonymousRespondent::count())->toBe(1);
});
```

- [ ] **Step 2: Write the failing screener test**

```php
<?php

use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyTokenService;
use App\Livewire\Survey\ConsentScreener;
use App\Models\EvaluationPeriod;
use App\Models\RespondentProfile;
use Livewire\Livewire;

it('stores consent and allows only eligible respondents', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active, 'minimum_age' => 17]);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(ConsentScreener::class, ['period' => $period])
        ->set('consent', true)
        ->set('age', 20)
        ->set('isIndramayuResident', true)
        ->set('hasUsedWongReang', true)
        ->call('submit')
        ->assertRedirect(route('survey.units', $period));

    expect(RespondentProfile::firstOrFail()->eligible)->toBeTrue();
});
```

- [ ] **Step 3: Run tests and verify failures**

```powershell
php artisan test tests/Feature/Survey/AnonymousTokenTest.php tests/Feature/Survey/ConsentScreenerTest.php
```

Expected: FAIL because token service, migrations, routes, and component do not exist.

- [ ] **Step 4: Implement token configuration and persistence**

`config/survey.php`:

```php
<?php

return [
    'cookie_name' => env('SURVEY_COOKIE_NAME', 'ueq_survey_token'),
    'token_key' => env('SURVEY_TOKEN_KEY'),
    'submit_attempts_per_minute' => 10,
    'cookie_extra_days' => 7,
];
```

Add to `.env.example`:

```dotenv
SURVEY_COOKIE_NAME=ueq_survey_token
SURVEY_TOKEN_KEY=
```

Migration columns:

```php
// anonymous_respondents
$table->id();
$table->char('token_hash', 64)->unique();
$table->timestamp('first_seen_at');
$table->timestamp('last_seen_at');
$table->timestamps();

// respondent_profiles
$table->id();
$table->foreignId('evaluation_period_id')->constrained()->cascadeOnDelete();
$table->foreignId('anonymous_respondent_id')->constrained()->cascadeOnDelete();
$table->timestamp('consented_at');
$table->unsignedTinyInteger('age');
$table->boolean('is_indramayu_resident');
$table->boolean('has_used_wong_reang');
$table->boolean('eligible');
$table->timestamp('screened_at');
$table->timestamps();
$table->unique(['evaluation_period_id', 'anonymous_respondent_id']);
```

Create factory defaults:

```php
// AnonymousRespondentFactory::definition()
return [
    'token_hash' => hash('sha256', fake()->unique()->uuid()),
    'first_seen_at' => now(),
    'last_seen_at' => now(),
];

// RespondentProfileFactory::definition()
return [
    'evaluation_period_id' => EvaluationPeriod::factory(),
    'anonymous_respondent_id' => AnonymousRespondent::factory(),
    'consented_at' => now(),
    'age' => 20,
    'is_indramayu_resident' => true,
    'has_used_wong_reang' => true,
    'eligible' => true,
    'screened_at' => now(),
];
```

`SurveyTokenService` uses `Str::random(64)` and:

```php
public function hash(string $plainToken): string
{
    $key = (string) config('survey.token_key');
    throw_if($key === '', LogicException::class, 'SURVEY_TOKEN_KEY wajib diatur.');
    return hash_hmac('sha256', $plainToken, $key);
}
```

`issue()` creates `AnonymousRespondent` with the hash and returns readonly DTO `IssuedRespondent(AnonymousRespondent $respondent, string $plainToken)`. `resolve()` hashes the supplied plaintext, queries the model, and updates only `last_seen_at`.

- [ ] **Step 5: Implement entry controller, context, screener, and rejection flow**

Entry route and public pages:

```php
Route::get('/s/wong-reang/{period:slug}', SurveyEntryController::class)->name('survey.entry');
Route::get('/s/wong-reang/{period:slug}/consent', ConsentScreener::class)->name('survey.consent');
Route::view('/s/wong-reang/{period:slug}/ineligible', 'survey.ineligible')->name('survey.ineligible');
```

Controller requirements:

```php
abort_unless($period->status === PeriodStatus::Active, 404);
$plain = (string) $request->cookie(config('survey.cookie_name'));
$issued = $plain !== '' && $tokens->resolve($plain)
    ? null
    : $tokens->issue();
$response = redirect()->route('survey.consent', $period);
return $issued
    ? $response->withCookie(cookie(
        config('survey.cookie_name'), $issued->plainToken,
        max(1, now()->diffInMinutes($period->closes_at->copy()->addDays(7), false)),
        '/', null, app()->environment('production'), true, false, 'lax'
    ))
    : $response;
```

`ConsentScreener::submit()` validates consent accepted, age 17-100, and booleans. It upserts a profile. Eligible respondents redirect to `survey.units`; ineligible respondents redirect to `survey.ineligible`. Do not create a `survey_submission` here.

- [ ] **Step 6: Run tests and commit**

```powershell
php artisan test tests/Feature/Survey/AnonymousTokenTest.php tests/Feature/Survey/ConsentScreenerTest.php
php artisan test
git add application/config/survey.php application/.env.example application/app/Domain/Survey application/app/Models application/app/Http/Controllers/SurveyEntryController.php application/app/Livewire/Survey application/resources/views application/database/migrations application/routes/web.php application/tests/Feature/Survey
git commit -m "feat: add anonymous consent and eligibility flow"
```

---

### Task 5: Add survey sessions and module selection

**Files:**
- Create: `application/app/Models/SurveySession.php`
- Create: `application/app/Models/SurveySubmission.php`
- Create: `application/database/factories/SurveySessionFactory.php`
- Create: `application/database/factories/SurveySubmissionFactory.php`
- Create: `application/database/migrations/2026_08_04_000007_create_survey_sessions_table.php`
- Create: `application/database/migrations/2026_08_04_000008_create_survey_submissions_table.php`
- Create: `application/app/Application/Survey/StartSurveySession.php`
- Create: `application/app/Livewire/Survey/UnitChooser.php`
- Create: `application/resources/views/livewire/survey/unit-chooser.blade.php`
- Create: `application/tests/Feature/Survey/UnitChooserTest.php`
- Modify: `application/routes/web.php`

**Interfaces:**
- Consumes: eligible `RespondentProfile`, active period, respondent, and 13 units.
- Produces: `StartSurveySession::handle(EvaluationPeriod, AnonymousRespondent): SurveySession` and route `survey.units`; `UnitChooser::choose(int $unitId)` redirects to the wizard only for an available unit.

- [ ] **Step 1: Write failing module-selection tests**

```php
<?php

use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyTokenService;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\RespondentProfile;
use App\Models\SurveySubmission;
use App\Livewire\Survey\UnitChooser;
use Livewire\Livewire;

it('shows active units and marks an already submitted unit complete', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);
    $unit = EvaluationUnit::factory()->create(['name' => 'Ibadah-Yu']);
    $issued = app(SurveyTokenService::class)->issue();
    RespondentProfile::factory()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_respondent_id' => $issued->respondent->id,
        'eligible' => true,
    ]);
    SurveySubmission::factory()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_respondent_id' => $issued->respondent->id,
        'evaluation_unit_id' => $unit->id,
        'status' => 'submitted',
    ]);

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(UnitChooser::class, ['period' => $period])
        ->assertSee('Ibadah-Yu')
        ->assertSee('Sudah dinilai');
});

it('rejects direct selection by an ineligible respondent', function () {
    $period = EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);
    $issued = app(SurveyTokenService::class)->issue();

    Livewire::withCookie('ueq_survey_token', $issued->plainToken)
        ->test(UnitChooser::class, ['period' => $period])
        ->assertForbidden();
});
```

- [ ] **Step 2: Run tests and verify failure**

```powershell
php artisan test tests/Feature/Survey/UnitChooserTest.php
```

Expected: FAIL because session/submission schema and chooser do not exist.

- [ ] **Step 3: Create session and submission schema**

```php
// survey_sessions
$table->uuid('id')->primary();
$table->foreignId('evaluation_period_id')->constrained()->cascadeOnDelete();
$table->foreignId('anonymous_respondent_id')->constrained()->cascadeOnDelete();
$table->timestamp('started_at');
$table->timestamp('last_activity_at');
$table->unsignedTinyInteger('submitted_count')->default(0);
$table->timestamps();

// survey_submissions
$table->id();
$table->foreignId('evaluation_period_id')->constrained()->restrictOnDelete();
$table->foreignId('anonymous_respondent_id')->constrained()->restrictOnDelete();
$table->foreignUuid('survey_session_id')->constrained()->restrictOnDelete();
$table->foreignId('evaluation_unit_id')->constrained()->restrictOnDelete();
$table->uuid('idempotency_key')->unique();
$table->string('instrument_version');
$table->string('status')->default('submitted');
$table->timestamp('started_at');
$table->timestamp('completed_at');
$table->unsignedInteger('duration_seconds');
$table->unsignedTinyInteger('session_sequence');
$table->timestamps();
$table->unique(
    ['evaluation_period_id', 'anonymous_respondent_id', 'evaluation_unit_id'],
    'one_submission_per_period_respondent_unit'
);
```

Create factory defaults:

```php
// SurveySessionFactory::definition()
return [
    'id' => (string) Str::uuid(),
    'evaluation_period_id' => EvaluationPeriod::factory(),
    'anonymous_respondent_id' => AnonymousRespondent::factory(),
    'started_at' => now(),
    'last_activity_at' => now(),
    'submitted_count' => 0,
];

// SurveySubmissionFactory::definition()
return [
    'evaluation_period_id' => EvaluationPeriod::factory(),
    'anonymous_respondent_id' => AnonymousRespondent::factory(),
    'survey_session_id' => SurveySession::factory(),
    'evaluation_unit_id' => EvaluationUnit::factory(),
    'idempotency_key' => (string) Str::uuid(),
    'instrument_version' => 'UEQ-ID-26-v1',
    'status' => 'submitted',
    'started_at' => now()->subMinutes(4),
    'completed_at' => now(),
    'duration_seconds' => 240,
    'session_sequence' => 1,
];
```

`StartSurveySession` reuses a session whose `last_activity_at` is within 30 minutes; otherwise it creates a UUID session. It never counts a module until a complete submission succeeds.

- [ ] **Step 4: Implement chooser behavior and routes**

```php
Route::get('/s/wong-reang/{period:slug}/units', UnitChooser::class)
    ->name('survey.units');
Route::get('/s/wong-reang/{period:slug}/units/{unit:code}', UeqWizard::class)
    ->name('survey.wizard');
```

`UnitChooser` must:

1. Resolve respondent from the cookie.
2. Require an eligible profile for the route period.
3. Query all active units in `display_order`.
4. Query submitted unit IDs for that respondent and period.
5. Render completed units disabled.
6. Reject `choose()` when the unit is inactive or already submitted.
7. Create/reuse a session before redirecting to `survey.wizard`.

The view presents 13 touch-friendly cards, a visible `Sudah dinilai` label, no default selection, and a reminder to choose only modules actually used.

- [ ] **Step 5: Run tests and commit**

```powershell
php artisan test tests/Feature/Survey/UnitChooserTest.php
php artisan test
git add application/app/Models application/database/migrations application/app/Application/Survey application/app/Livewire/Survey/UnitChooser.php application/resources/views/livewire/survey/unit-chooser.blade.php application/routes/web.php application/tests/Feature/Survey/UnitChooserTest.php
git commit -m "feat: add respondent sessions and module chooser"
```

---

### Task 6: Build the four-step UEQ wizard and transactional idempotent submission

**Files:**
- Create: `application/app/Models/SurveyAnswer.php`
- Create: `application/database/migrations/2026_08_04_000009_create_survey_answers_table.php`
- Create: `application/app/Domain/Survey/SurveyDraftKey.php`
- Create: `application/app/Application/Survey/SubmitSurveyData.php`
- Create: `application/app/Application/Survey/SubmitSurvey.php`
- Create: `application/app/Livewire/Survey/UeqWizard.php`
- Create: `application/resources/views/livewire/survey/ueq-wizard.blade.php`
- Create: `application/app/Livewire/Survey/Complete.php`
- Create: `application/resources/views/livewire/survey/complete.blade.php`
- Create: `application/tests/Feature/Survey/UeqWizardTest.php`
- Create: `application/tests/Feature/Survey/SubmitSurveyTest.php`
- Modify: `application/tests/Pest.php`
- Modify: `application/routes/web.php`

**Interfaces:**
- Consumes: active period, eligible respondent, active `SurveySession`, selected unit, and 26 `UeqItem` records.
- Produces: readonly `SubmitSurveyData`, `SubmitSurvey::handle(SubmitSurveyData): SurveySubmission`, `SurveyDraftKey::for(periodId, respondentId, unitId, version): string`, and route `survey.complete`.

- [ ] **Step 1: Write failing domain submission tests**

```php
<?php

use App\Application\Survey\SubmitSurvey;
use App\Application\Survey\SubmitSurveyData;
use App\Models\SurveyAnswer;
use App\Models\SurveySubmission;

it('stores one submission and exactly 26 answers atomically', function () {
    $fixture = surveyFixture();
    $data = new SubmitSurveyData(
        periodId: $fixture->period->id,
        respondentId: $fixture->respondent->id,
        sessionId: $fixture->session->id,
        unitId: $fixture->unit->id,
        idempotencyKey: '11111111-1111-4111-8111-111111111111',
        instrumentVersion: $fixture->period->instrument_version,
        startedAt: now()->subMinutes(4),
        answers: array_fill_keys(range(1, 26), 4),
    );

    $submission = app(SubmitSurvey::class)->handle($data);

    expect(SurveySubmission::count())->toBe(1)
        ->and(SurveyAnswer::where('survey_submission_id', $submission->id)->count())->toBe(26);
});

it('returns the original submission for the same idempotency key', function () {
    $fixture = surveyFixture();
    $data = validSubmitSurveyData($fixture);
    $action = app(SubmitSurvey::class);

    expect($action->handle($data)->id)->toBe($action->handle($data)->id)
        ->and(SurveySubmission::count())->toBe(1);
});

it('rejects a second submission for the same respondent period and unit', function () {
    $fixture = surveyFixture();
    app(SubmitSurvey::class)->handle(validSubmitSurveyData($fixture));
    $second = validSubmitSurveyData($fixture, idempotencyKey: '22222222-2222-4222-8222-222222222222');

    expect(fn () => app(SubmitSurvey::class)->handle($second))
        ->toThrow(DomainException::class, 'Modul ini sudah pernah dinilai.');
});
```

Add these exact helpers to `tests/Pest.php`:

```php
use App\Application\Survey\SubmitSurveyData;
use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyTokenService;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\RespondentProfile;
use App\Models\SurveySession;
use App\Models\UeqItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

function surveyFixture(): object
{
    $version = 'UEQ-TEST-'.Str::uuid();
    $period = EvaluationPeriod::factory()->create([
        'status' => PeriodStatus::Active,
        'instrument_version' => $version,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addMonth(),
        'configuration_locked_at' => now(),
    ]);
    $unit = EvaluationUnit::factory()->create(['code' => 'unit-'.Str::lower(Str::random(8))]);
    foreach (range(1, 26) as $order) {
        UeqItem::factory()->create(['version' => $version, 'order' => $order]);
    }
    $issued = app(SurveyTokenService::class)->issue();
    RespondentProfile::factory()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_respondent_id' => $issued->respondent->id,
        'eligible' => true,
    ]);
    $session = SurveySession::factory()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_respondent_id' => $issued->respondent->id,
    ]);

    return (object) [
        'period' => $period,
        'unit' => $unit,
        'respondent' => $issued->respondent,
        'plainToken' => $issued->plainToken,
        'session' => $session,
    ];
}

function validSubmitSurveyData(object $fixture, ?string $idempotencyKey = null): SubmitSurveyData
{
    return new SubmitSurveyData(
        periodId: $fixture->period->id,
        respondentId: $fixture->respondent->id,
        sessionId: $fixture->session->id,
        unitId: $fixture->unit->id,
        idempotencyKey: $idempotencyKey ?? (string) Str::uuid(),
        instrumentVersion: $fixture->period->instrument_version,
        startedAt: CarbonImmutable::now()->subMinutes(4),
        answers: array_fill_keys(range(1, 26), 4),
    );
}
```

- [ ] **Step 2: Write failing wizard validation tests**

```php
<?php

use Livewire\Livewire;
use App\Livewire\Survey\UeqWizard;

it('uses four steps with boundaries 7 7 6 6', function () {
    $fixture = surveyFixture();

    Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(UeqWizard::class, ['period' => $fixture->period, 'unit' => $fixture->unit])
        ->assertSet('step', 1)
        ->assertViewHas('items', fn ($items) => $items->count() === 7)
        ->set('answers.1', 4)
        ->call('next')
        ->assertHasErrors(['answers.2']);
});

it('does not expose converted scores to the respondent', function () {
    $fixture = surveyFixture();

    Livewire::withCookie('ueq_survey_token', $fixture->plainToken)
        ->test(UeqWizard::class, ['period' => $fixture->period, 'unit' => $fixture->unit])
        ->assertDontSee('Skor terkonversi')
        ->assertDontSee('Benchmark');
});
```

- [ ] **Step 3: Run tests and verify failures**

```powershell
php artisan test tests/Feature/Survey/SubmitSurveyTest.php tests/Feature/Survey/UeqWizardTest.php
```

Expected: FAIL because answer schema, DTO, action, helper, and wizard do not exist.

- [ ] **Step 4: Implement DTO and transactional action**

`SubmitSurveyData` is a readonly class with these exact types: `int $periodId`, `int $respondentId`, `string $sessionId`, `int $unitId`, `string $idempotencyKey`, `string $instrumentVersion`, `CarbonImmutable $startedAt`, and `array<int,int> $answers`.

`SurveyDraftKey` is a stateless value helper:

```php
final class SurveyDraftKey
{
    public static function for(int $periodId, int $respondentId, int $unitId, string $version): string
    {
        return implode(':', ['ueq-draft-v1', $periodId, $respondentId, $unitId, $version]);
    }
}
```

`SubmitSurvey::handle()`:

```php
return DB::transaction(function () use ($data) {
    $existing = SurveySubmission::query()
        ->where('idempotency_key', $data->idempotencyKey)
        ->first();
    if ($existing) return $existing;

    throw_unless(count($data->answers) === 26, DomainException::class, 'Jawaban harus tepat 26 item.');
    throw_unless(array_keys($data->answers) === range(1, 26), DomainException::class, 'Nomor item harus lengkap 1 sampai 26.');
    throw_unless(collect($data->answers)->every(fn ($score) => is_int($score) && $score >= 1 && $score <= 7), DomainException::class, 'Nilai jawaban harus 1 sampai 7.');

    $duplicate = SurveySubmission::query()
        ->where('evaluation_period_id', $data->periodId)
        ->where('anonymous_respondent_id', $data->respondentId)
        ->where('evaluation_unit_id', $data->unitId)
        ->lockForUpdate()
        ->exists();
    throw_if($duplicate, DomainException::class, 'Modul ini sudah pernah dinilai.');

    $completedAt = now();
    $session = SurveySession::query()->lockForUpdate()->findOrFail($data->sessionId);
    $submission = SurveySubmission::query()->create([
        'evaluation_period_id' => $data->periodId,
        'anonymous_respondent_id' => $data->respondentId,
        'survey_session_id' => $data->sessionId,
        'evaluation_unit_id' => $data->unitId,
        'idempotency_key' => $data->idempotencyKey,
        'instrument_version' => $data->instrumentVersion,
        'started_at' => $data->startedAt,
        'completed_at' => $completedAt,
        'duration_seconds' => max(1, $data->startedAt->diffInSeconds($completedAt)),
        'session_sequence' => $session->submitted_count + 1,
        'status' => 'submitted',
    ]);

    $submission->answers()->createMany(collect($data->answers)->map(
        fn (int $score, int $itemOrder) => ['item_order' => $itemOrder, 'raw_score' => $score]
    )->values()->all());

    $session->update([
        'submitted_count' => $session->submitted_count + 1,
        'last_activity_at' => $completedAt,
    ]);

    return $submission;
}, attempts: 3);
```

The answer migration uses foreign ID, `item_order` unsigned tiny integer, `raw_score` unsigned tiny integer, timestamps, and unique submission+item. After `Schema::create`, enforce exact MySQL 8 checks while keeping SQLite test migrations portable:

```php
if (DB::getDriverName() === 'mysql') {
    DB::statement('ALTER TABLE survey_answers ADD CONSTRAINT chk_item_order CHECK (item_order BETWEEN 1 AND 26)');
    DB::statement('ALTER TABLE survey_answers ADD CONSTRAINT chk_raw_score CHECK (raw_score BETWEEN 1 AND 7)');
}
```

- [ ] **Step 5: Implement wizard state, steps, and local draft**

Wizard public state:

```php
public int $step = 1;
public array $answers = [];
public bool $confirmedExperience = false;
public string $idempotencyKey;
public string $startedAt;

private const STEP_RANGES = [1 => [1, 7], 2 => [8, 14], 3 => [15, 20], 4 => [21, 26]];
```

`mount()` resolves context, rejects inactive period/ineligible respondent/already submitted unit, creates/reuses the session, and initializes UUID idempotency key and ISO timestamp. On step 1, the respondent must check `confirmedExperience` to confirm completing at least one service process in the selected module. `next()` validates that confirmation as `accepted` on step 1 and validates every item in the current range as `required|integer|between:1,7`. `submit()` validates the confirmation and all 26 answers, calls `SubmitSurvey`, dispatches `survey-submitted` with the local draft key, and redirects to `survey.complete`.

The Blade view must include:

```blade
@if ($step === 1)
    <label>
        <input type="checkbox" wire:model="confirmedExperience">
        Saya pernah menyelesaikan minimal satu proses layanan pada modul {{ $unit->name }}.
    </label>
    @error('confirmedExperience') <p role="alert">{{ $message }}</p> @enderror
@endif

<div wire:offline class="rounded-lg bg-amber-100 p-3 text-amber-900">
    Koneksi terputus. Jawaban tetap tersimpan di perangkat; kirim setelah tersambung kembali.
</div>

@script
<script>
    const draftKey = @js($this->draftKey);
    const stored = localStorage.getItem(draftKey);
    if (stored) {
        const draft = JSON.parse(stored);
        $wire.answers = draft.answers ?? {};
        $wire.step = draft.step ?? 1;
        $wire.confirmedExperience = draft.confirmedExperience ?? false;
    }
    const saveDraft = () => localStorage.setItem(draftKey, JSON.stringify({
        answers: $wire.answers,
        step: $wire.step,
        confirmedExperience: $wire.confirmedExperience,
    }));
    $wire.$watch('answers', saveDraft);
    $wire.$watch('step', saveDraft);
    $wire.$watch('confirmedExperience', saveDraft);
    $wire.on('survey-submitted', ({ key }) => localStorage.removeItem(key));
</script>
@endscript
```

Use stable `wire:key="ueq-item-{{ $item->order }}"`, semantic fieldset/legend, seven radio inputs, visible focus, and labels at both poles. Give each radio the accessible label `Item {order} nilai {value}`, for example `aria-label="Item {{ $item->order }} nilai {{ $value }}"`, so keyboard, screen reader, and browser tests address the same control. Disable submit with `wire:loading.attr="disabled"` and `wire:offline.attr="disabled"`.

- [ ] **Step 6: Add complete page and fatigue message**

`Complete` loads the last submission for respondent+period. Show `Penilaian berhasil disimpan`. When `session_sequence >= 3`, also show `Anda telah menilai tiga modul pada sesi ini. Sebaiknya beristirahat sebelum melanjutkan.` Provide buttons to end or return to `survey.units`. Never automatically redirect.

- [ ] **Step 7: Run submission, wizard, and full tests**

```powershell
php artisan test tests/Feature/Survey/SubmitSurveyTest.php tests/Feature/Survey/UeqWizardTest.php
php artisan test
npm run build
```

Expected: targeted and full suites pass; build succeeds.

- [ ] **Step 8: Commit the complete survey flow**

```powershell
git add application/app/Models application/database/migrations application/app/Domain/Survey application/app/Application/Survey application/app/Livewire/Survey application/resources/views/livewire/survey application/routes/web.php application/tests
git commit -m "feat: submit complete UEQ evaluations safely"
```

---

### Task 7: Add admin progress dashboard and raw CSV/XLSX export

**Files:**
- Create: `application/app/Application/Reporting/ReleaseOneDashboardQuery.php`
- Create: `application/app/Application/Reporting/ReleaseOneDashboardData.php`
- Create: `application/app/Application/Reporting/UnitProgressData.php`
- Create: `application/app/Application/Reporting/RawSurveyExport.php`
- Create: `application/app/Http/Controllers/Admin/RawSurveyExportController.php`
- Create: `application/app/Livewire/Admin/Dashboard.php`
- Create: `application/resources/views/livewire/admin/dashboard.blade.php`
- Create: `application/tests/Feature/Admin/DashboardTest.php`
- Create: `application/tests/Feature/Admin/RawSurveyExportTest.php`
- Modify: `application/tests/Pest.php`
- Modify: `application/routes/web.php`

**Interfaces:**
- Consumes: periods, units, profiles, submissions, and answers from earlier tasks.
- Produces: `ReleaseOneDashboardQuery::for(EvaluationPeriod): ReleaseOneDashboardData`, `RawSurveyExport::spreadsheet(EvaluationPeriod): Spreadsheet`, routes `admin.dashboard`, `admin.exports.raw.csv`, and `admin.exports.raw.xlsx`.

- [ ] **Step 1: Write failing dashboard aggregation tests**

```php
<?php

use App\Application\Reporting\ReleaseOneDashboardQuery;

it('separates unique respondents from module evaluations', function () {
    $fixture = dashboardFixture(uniqueRespondents: 2, submissions: [
        'ibadah-yu' => 2,
        'info-yu' => 1,
    ]);

    $data = app(ReleaseOneDashboardQuery::class)->for($fixture->period);

    expect($data->uniqueRespondents)->toBe(2)
        ->and($data->totalEvaluations)->toBe(3)
        ->and($data->units->firstWhere('code', 'ibadah-yu')->valid)->toBe(2)
        ->and($data->units->firstWhere('code', 'info-yu')->valid)->toBe(1);
});

it('requires authentication for the dashboard', function () {
    $this->get('/admin/dashboard')->assertRedirect('/login');
});
```

- [ ] **Step 2: Write failing export tests**

```php
<?php

use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('exports 26 item columns without token hashes', function () {
    $fixture = completedSubmissionFixture();
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.exports.raw.xlsx', $fixture->period));
    $response->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'ueq-export-');
    file_put_contents($path, $response->streamedContent());
    $sheet = IOFactory::load($path)->getActiveSheet();
    $headers = $sheet->rangeToArray('A1:AI1')[0];

    expect($headers)->toContain('respondent_code', 'unit_code', 'item_01', 'item_26')
        ->and($headers)->not->toContain('token_hash');

    unlink($path);
});
```

Append the exact Task 7 fixture helpers to `tests/Pest.php`:

```php
use App\Application\Survey\SubmitSurvey;
use App\Models\SurveySubmission;

function dashboardFixture(int $uniqueRespondents, array $submissions): object
{
    foreach ($submissions as $code => $count) {
        if ($count < 0 || $count > $uniqueRespondents) {
            throw new InvalidArgumentException("Jumlah submission {$code} tidak valid untuk fixture.");
        }
    }

    $period = EvaluationPeriod::factory()->create([
        'status' => PeriodStatus::Active,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addMonth(),
    ]);

    $respondents = collect(range(1, $uniqueRespondents))->map(function () use ($period) {
        $issued = app(SurveyTokenService::class)->issue();
        $profile = RespondentProfile::factory()->create([
            'evaluation_period_id' => $period->id,
            'anonymous_respondent_id' => $issued->respondent->id,
            'eligible' => true,
        ]);
        $session = SurveySession::factory()->create([
            'evaluation_period_id' => $period->id,
            'anonymous_respondent_id' => $issued->respondent->id,
        ]);

        return (object) ['respondent' => $issued->respondent, 'profile' => $profile, 'session' => $session];
    });

    collect($submissions)->keys()->values()->each(function (string $code, int $index) use ($period, $respondents, $submissions) {
        $unit = EvaluationUnit::factory()->create([
            'code' => $code,
            'name' => Str::headline($code),
            'display_order' => $index + 1,
        ]);
        foreach (range(0, $submissions[$code] - 1) as $respondentIndex) {
            $owner = $respondents[$respondentIndex];
            SurveySubmission::factory()->create([
                'evaluation_period_id' => $period->id,
                'anonymous_respondent_id' => $owner->respondent->id,
                'survey_session_id' => $owner->session->id,
                'evaluation_unit_id' => $unit->id,
            ]);
        }
    });

    return (object) ['period' => $period, 'respondents' => $respondents];
}

function completedSubmissionFixture(): object
{
    $fixture = surveyFixture();
    $fixture->submission = app(SubmitSurvey::class)->handle(validSubmitSurveyData($fixture));
    return $fixture;
}
```

- [ ] **Step 3: Run tests and verify missing services**

```powershell
php artisan test tests/Feature/Admin/DashboardTest.php tests/Feature/Admin/RawSurveyExportTest.php
```

Expected: FAIL because query, DTO, controller, routes, and export do not exist.

- [ ] **Step 4: Implement the dashboard query and view**

`ReleaseOneDashboardData` is a readonly DTO with:

```php
public function __construct(
    public int $uniqueRespondents,
    public int $totalEvaluations,
    public int $eligibleRespondents,
    public Collection $units,
) {}
```

`UnitProgressData` is a readonly DTO with:

```php
public function __construct(
    public string $code,
    public string $name,
    public int $valid,
    public int $minimum,
    public int $target,
    public string $status,
) {}
```

Each unit row contains `code`, `name`, `valid`, `minimum`, `target`, and status `below_minimum|minimal_reached|target_reached`. In Rilis 1, `valid` equals submitted count because quality review is Rilis 2. Use SQL aggregate queries rather than loading all answers.

The dashboard view shows four cards and a 13-row progress table. Label respondent and evaluation values explicitly; do not combine them into one KPI.

- [ ] **Step 5: Implement privacy-preserving CSV/XLSX export**

Export columns in exact order:

```text
submission_id, respondent_code, unit_code, unit_name, instrument_version,
started_at, completed_at, duration_seconds, session_sequence,
item_01, item_02, item_03, item_04, item_05, item_06, item_07, item_08, item_09,
item_10, item_11, item_12, item_13, item_14, item_15, item_16, item_17, item_18,
item_19, item_20, item_21, item_22, item_23, item_24, item_25, item_26
```

`respondent_code` is `R-` plus the period-scoped profile ID padded to six digits. Do not export `anonymous_respondents.id`, `token_hash`, IP, user agent, or cookie.

`RawSurveyExport::spreadsheet()` creates one worksheet titled `Raw UEQ`, writes the headers, then writes one ordered row per submission. The controller uses `PhpSpreadsheet\Writer\Xlsx` for XLSX and `PhpSpreadsheet\Writer\Csv` with UTF-8 BOM and comma delimiter for CSV, writes to `php://output`, and returns `response()->streamDownload($callback, $filename, $headers)`.

- [ ] **Step 6: Add routes and authorization**

```php
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/periods/{period}/exports/raw.csv', [RawSurveyExportController::class, 'csv'])->name('exports.raw.csv');
    Route::get('/periods/{period}/exports/raw.xlsx', [RawSurveyExportController::class, 'xlsx'])->name('exports.raw.xlsx');
});
```

The controller rejects a period not belonging to the single study only if future data exists; do not introduce application tenancy.

- [ ] **Step 7: Run tests and commit**

```powershell
php artisan test tests/Feature/Admin/DashboardTest.php tests/Feature/Admin/RawSurveyExportTest.php
php artisan test
git add application/app/Application/Reporting application/app/Http/Controllers/Admin application/app/Livewire/Admin/Dashboard.php application/resources/views/livewire/admin/dashboard.blade.php application/routes/web.php application/tests/Feature/Admin
git commit -m "feat: report release one survey progress"
```

---

### Task 8: Harden Rilis 1, execute browser/UAT checks, and document operation

**Files:**
- Modify: `application/app/Application/Survey/SubmitSurvey.php`
- Create: `application/tests/Feature/Survey/SurveyRateLimitTest.php`
- Create: `application/tests/Browser/SurveyHappyPathTest.php`
- Create: `application/docs/release-1-runbook.md`
- Modify: `application/README.md`

**Interfaces:**
- Consumes: complete Rilis 1 flow.
- Produces: atomic respondent-keyed submit limit, browser proof of the critical path, MySQL migration/UAT evidence, and an operational runbook for activation, backup, restore, export, and rollback.

- [ ] **Step 1: Write failing submit rate-limit test**

```php
<?php

use App\Application\Survey\SubmitSurvey;
use Illuminate\Support\Facades\RateLimiter;

it('blocks more than ten submit attempts per respondent per minute', function () {
    $fixture = surveyFixture();
    $key = 'survey-submit:'.$fixture->respondent->id;
    $data = validSubmitSurveyData($fixture);
    RateLimiter::clear($key);

    foreach (range(1, 10) as $attempt) {
        app(SubmitSurvey::class)->handle($data);
    }

    expect(fn () => app(SubmitSurvey::class)->handle($data))
        ->toThrow(DomainException::class, 'Terlalu banyak percobaan submit. Coba kembali dalam satu menit.');
});
```

- [ ] **Step 2: Enforce the atomic submit limiter in the application action**

At the beginning of `SubmitSurvey::handle()`, before the idempotency lookup, add:

```php
$allowed = RateLimiter::attempt(
    'survey-submit:'.$data->respondentId,
    (int) config('survey.submit_attempts_per_minute'),
    fn () => true,
    60,
);

throw_unless(
    $allowed,
    DomainException::class,
    'Terlalu banyak percobaan submit. Coba kembali dalam satu menit.',
);
```

This ordering counts repeated clicks even when the idempotency key is identical while still returning the original submission for attempts 2-10.

- [ ] **Step 3: Write the critical browser test**

`tests/Browser/SurveyHappyPathTest.php`:

```php
<?php

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use Database\Seeders\WongReangStudySeeder;
use Livewire\Livewire;

it('completes one mobile survey from consent to confirmation', function () {
    $this->seed(WongReangStudySeeder::class);
    $period = EvaluationPeriod::firstOrFail();
    $period->update([
        'status' => PeriodStatus::Active,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addMonth(),
        'configuration_locked_at' => now(),
    ]);

    $page = Livewire::visit(route('survey.entry', $period))
        ->resize(360, 800)
        ->assertSee('Informasi Penelitian')
        ->check('consent')
        ->type('age', '20')
        ->check('isIndramayuResident')
        ->check('hasUsedWongReang')
        ->press('Lanjutkan')
        ->assertSee('Pilih Modul')
        ->press('Ibadah-Yu')
        ->assertSee('menyusahkan')
        ->assertSee('menyenangkan')
        ->check('Saya pernah menyelesaikan minimal satu proses layanan pada modul Ibadah-Yu.');

    foreach (range(1, 7) as $order) {
        $page->check("Item {$order} nilai 4");
    }
    $page->press('Berikutnya');

    foreach (range(8, 14) as $order) {
        $page->check("Item {$order} nilai 4");
    }
    $page->press('Berikutnya');

    foreach (range(15, 20) as $order) {
        $page->check("Item {$order} nilai 4");
    }
    $page->press('Berikutnya');

    foreach (range(21, 26) as $order) {
        $page->check("Item {$order} nilai 4");
    }
    $page->press('Kirim Penilaian')
        ->assertSee('Penilaian berhasil disimpan');
});
```

- [ ] **Step 4: Run PHP, browser, and asset verification**

```powershell
php artisan test
php artisan test tests/Browser/SurveyHappyPathTest.php
vendor\bin\pint --test
npm run build
```

Expected: all tests pass, Pint reports no formatting errors, and Vite builds production assets.

- [ ] **Step 5: Verify the application against MySQL 8**

In MySQL Workbench, connect as the local root account, create account `ueq_saw_app` for host `localhost` with a generated password stored in the password manager, then execute:

```sql
CREATE DATABASE ueq_saw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE ueq_saw_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON ueq_saw.* TO 'ueq_saw_app'@'localhost';
GRANT ALL PRIVILEGES ON ueq_saw_test.* TO 'ueq_saw_app'@'localhost';
FLUSH PRIVILEGES;
```

Configure `.env` without committing secrets:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ueq_saw
DB_USERNAME=ueq_saw_app
```

Set `DB_PASSWORD` directly in `.env` through the editor or deployment control panel without printing the value in terminal output, documentation, Git, or chat.

Run:

```powershell
php artisan config:clear
php artisan migrate:fresh --seed
php artisan app:create-admin peneliti@example.test
php artisan about
```

Expected: migrations and seed complete without MySQL constraint errors; the admin command succeeds.

- [ ] **Step 6: Write and execute the Rilis 1 runbook**

`docs/release-1-runbook.md` must contain these concrete sections and commands:

```markdown
# Rilis 1 Runbook

## Pre-activation
- Set APP_ENV=production and APP_DEBUG=false.
- Confirm APP_URL exactly matches the approved deployed HTTPS domain.
- Set SESSION_SECURE_COOKIE=true and a random SURVEY_TOKEN_KEY.
- Run `php artisan migrate --force` and `php artisan optimize`.
- Verify 13 units, 26 verified items, six verified benchmark rows, dates, target basis, and HTTPS.

## Backup
`$surveyTimestamp = Get-Date -Format 'yyyyMMdd_HHmm'`
`mysqldump --single-transaction --routines --triggers -u ueq_saw_app -p ueq_saw > "ueq_saw_$surveyTimestamp.sql"`

## Restore test
`$surveyBackupPath = Get-ChildItem -File 'ueq_saw_*.sql' | Sort-Object LastWriteTime -Descending | Select-Object -First 1 -ExpandProperty FullName`
`mysql -u ueq_saw_app -p -e "CREATE DATABASE ueq_saw_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"`
`Get-Content -Raw -LiteralPath $surveyBackupPath | mysql -u ueq_saw_app -p ueq_saw_restore`
`php artisan migrate:status --database=mysql`

## Daily operation
- Check per-module counts and server error log.
- Download raw XLSX at the end of each collection day.
- Do not modify locked instrument, targets, or benchmark rows.

## Close and rollback
- Change active period to closed before maintenance that affects submissions.
- Roll back only the latest application release; never roll back a migration that would drop collected responses.
```

Execute one backup and restore into `ueq_saw_restore`. Record the date, row counts for `survey_submissions` and `survey_answers`, and result in the runbook under `## Bukti restore`.

- [ ] **Step 7: Perform manual mobile UAT**

On an Android phone or Chrome device emulation at 360 px:

1. Complete consent and eligible screener.
2. Select one module, confirm completing at least one service process, fill all four UEQ steps, and submit.
3. Disable network during step 2, change answers, restore network, and confirm answers remain.
4. Double-click submit and confirm only one submission with 26 answers exists.
5. Return to module chooser and confirm the completed module is disabled.
6. Complete two more modules and verify the rest suggestion after the third.
7. Log in as admin and verify unique respondents differs from total evaluations.
8. Download CSV and XLSX and confirm columns item_01-item_26 and absence of token hash.

Record pass/fail, browser/device, date, and evidence path in `application/docs/release-1-runbook.md`. Any failure blocks activation.

- [ ] **Step 8: Run final release verification and commit**

```powershell
php artisan test
vendor\bin\pint --test
npm run build
git status --short
git add application/app/Application/Survey/SubmitSurvey.php application/tests application/docs application/README.md
git commit -m "test: verify release one survey workflow"
```

Expected: tests/build pass; only intended files are staged; DOCX files remain untouched and untracked unless the user separately requests versioning them.

---

## Rilis 1 Completion Gate

Do not start Rilis 2 until all statements below are true:

- [ ] Public registration is unavailable and one admin can log in.
- [ ] Period activation is blocked until instrument, benchmark, dates, target basis, and 13 units pass readiness checks.
- [ ] An eligible respondent can submit 26 answers for one module on a 360 px screen.
- [ ] Ineligible respondents cannot reach the wizard.
- [ ] Database stores only the token HMAC, never the plaintext token.
- [ ] Duplicate period-token-unit and duplicate idempotency requests do not create extra submissions.
- [ ] Local draft survives a tested connection interruption.
- [ ] The same respondent can evaluate another module and receives a rest suggestion after the third module in one session.
- [ ] Dashboard separates unique respondents from total evaluations and shows all 13 module counts.
- [ ] CSV/XLSX export contains item_01-item_26 and excludes token hash.
- [ ] Full Pest suite, browser critical path, Pint, Vite build, MySQL 8 migration, backup, and restore checks pass.

## Follow-on Plans

After this gate passes, create two separate documents rather than expanding this plan:

1. `docs/superpowers/plans/2026-08-04-ueq-saw-release-2.md` for quality review, UEQ statistics, benchmark/gap, technical informants, golden fixture, and SAW.
2. `docs/superpowers/plans/2026-08-04-ueq-saw-release-3.md` for sensitivity, expert judgment, charts, reporting, and official calculation-run locking.

Splitting the plans prevents unfinished analytical features from delaying the survey launch.
