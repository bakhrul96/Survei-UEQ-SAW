# UEQ-SAW Release 3 Audit Remediation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menutup seluruh gap audit Rilis 3 sehingga sensitivitas, expert judgment, official lock, laporan visual, dan ekspor agregat memenuhi spesifikasi desain serta dapat dibuktikan dengan test otomatis.

**Architecture:** Remediasi mempertahankan modular monolith Laravel dan memisahkan empat batas tanggung jawab: konfigurasi skenario tersimpan pada periode, validator finalisasi menentukan kelayakan official run, backlog operasional dikelola sebagai urutan atomik yang terpisah dari SAW, dan reporting membentuk satu dataset acuan untuk UI serta ekspor. Official run dipilih melalui pointer pada periode, dikunci dengan row lock dalam satu transaksi, dan seluruh hasilnya menjadi immutable serta tercatat pada audit log.

**Tech Stack:** PHP 8.3+, Laravel 13.17, Livewire 4.1, Flux UI 2.13, Tailwind CSS 4, PhpSpreadsheet 5.9, Pest 5, MySQL 8, SQLite untuk test, Vite 8.

## Global Constraints

- Sumber kebenaran requirement adalah `docs/superpowers/specs/2026-08-04-ueq-saw-ta-mvp-design.md`, khususnya bagian 13.7, 13.8, 15.2, 16, 17.5, dan 18.3.
- S0 selalu memakai bobot konsensus informan pada snapshot run; S1 selalu `0.60/0.20/0.20`; S2 selalu `0.20/0.40/0.40` pada seed awal.
- Bobot S1 dan S2 harus tersimpan pada konfigurasi periode, masuk configuration hash dan input snapshot, serta tidak dapat diedit setelah periode aktif.
- Official lock hanya boleh dilakukan pada run `preview` dari periode `closed` yang memenuhi seluruh final-calculation gate.
- Satu periode hanya boleh mempunyai satu official run. Official run tidak dapat diganti, diarsipkan, dibuat stale, diubah, atau dihapus melalui alur aplikasi.
- Koreksi setelah official lock berada di luar scope remediasi ini dan harus dimulai sebagai periode penelitian baru.
- Expert judgment tidak pernah mengubah `saw_results`; backlog operasional disimpan lengkap sebagai urutan unik `1..N`.
- Perubahan backlog hanya diizinkan sebelum official lock dan wajib menghasilkan `audit_events`.
- UI grafik menggunakan HTML/CSS/Tailwind yang dapat diakses; jangan menambah library chart JavaScript.
- Setiap grafik wajib disertai tabel angka atau label angka yang menyampaikan nilai yang sama.
- CSV agregat adalah satu tabel datar multi-section, bukan serialisasi worksheet pertama dari workbook XLSX.
- Jangan mengubah rumus UEQ atau SAW Rilis 2 kecuali untuk mengalirkan konfigurasi sensitivitas yang telah disnapshot.
- Semua perubahan menggunakan TDD: test baru harus terlihat gagal karena perilaku lama sebelum implementasi ditulis.

---

### Task 1: Persist and Lock Sensitivity Scenario Configuration

**Files:**
- Create: `application/database/migrations/2026_08_06_000019_add_sensitivity_configuration_to_evaluation_periods.php`
- Modify: `application/app/Models/EvaluationPeriod.php`
- Modify: `application/database/factories/EvaluationPeriodFactory.php`
- Modify: `application/database/seeders/WongReangStudySeeder.php`
- Modify: `application/app/Livewire/Admin/StudySettings.php`
- Modify: `application/resources/views/livewire/admin/study-settings.blade.php`
- Modify: `application/app/Domain/Study/PeriodReadinessService.php`
- Modify: `application/app/Domain/Study/StudyConfigurationHasher.php`
- Modify: `application/app/Application/Calculation/CalculationInputSnapshot.php`
- Modify: `application/app/Domain/Sensitivity/SensitivityScenario.php`
- Modify: `application/app/Domain/Sensitivity/SensitivityCalculator.php`
- Modify: `application/app/Application/Calculation/CalculationRunService.php`
- Modify: `application/tests/Fixtures/ueq-saw-golden.json`
- Modify: `docs/research/ueq-saw-golden-fixture.xlsx`
- Modify: `application/tests/Unit/Fixtures/GoldenWorkbookConsistencyTest.php`
- Modify: `application/tests/Unit/Sensitivity/SensitivityCalculatorTest.php`
- Modify: `application/tests/Feature/Study/PeriodActivationTest.php`
- Modify: `application/tests/Feature/Study/StudyConfigurationHasherTest.php`
- Modify: `application/tests/Feature/Calculation/GoldenCalculationRunTest.php`

**Interfaces:**
- Consumes: enam decimal konfigurasi periode: `sensitivity_s1_c1`, `sensitivity_s1_c2`, `sensitivity_s1_c3`, `sensitivity_s2_c1`, `sensitivity_s2_c2`, `sensitivity_s2_c3`.
- Produces: `SensitivityCalculator::calculate(array $alternatives, array $consensusWeights, array $configuredScenarios): array<string, list<SensitivityResultData>>`, dengan `$configuredScenarios` bertipe `array{S1: array{c1: float, c2: float, c3: float}, S2: array{c1: float, c2: float, c3: float}}`.
- Produces snapshot key: `configuration.sensitivity_scenarios.S1|S2.c1|c2|c3`.

- [x] **Step 1: Write failing schema, activation, hash, and calculator tests**

Tambahkan assertion berikut:

```php
it('stores locked S1 and S2 weights in the period snapshot', function (): void {
    $period = EvaluationPeriod::factory()->create([
        'sensitivity_s1_c1' => 0.60,
        'sensitivity_s1_c2' => 0.20,
        'sensitivity_s1_c3' => 0.20,
        'sensitivity_s2_c1' => 0.20,
        'sensitivity_s2_c2' => 0.40,
        'sensitivity_s2_c3' => 0.40,
    ]);

    $snapshot = app(CalculationInputSnapshot::class)->for(
        $period,
        CalculationRunService::ALGORITHM_VERSION,
    );

    expect($snapshot['configuration']['sensitivity_scenarios'])->toBe([
        'S1' => ['c1' => '0.600000', 'c2' => '0.200000', 'c3' => '0.200000'],
        'S2' => ['c1' => '0.200000', 'c2' => '0.400000', 'c3' => '0.400000'],
    ]);
});
```

Tambahkan dataset activation test yang menolak setiap skenario jika jumlah bobot bukan `1.000000`, dan hasher test yang membuktikan perubahan satu bobot mengubah configuration hash.

Ubah unit test calculator agar pemanggilan eksplisit menggunakan:

```php
$configuredScenarios = [
    'S1' => ['c1' => 0.60, 'c2' => 0.20, 'c3' => 0.20],
    'S2' => ['c1' => 0.20, 'c2' => 0.40, 'c3' => 0.40],
];

$results = $calculator->calculate($alternatives, $consensusWeights, $configuredScenarios);
```

- [x] **Step 2: Run focused tests and confirm the old implementation fails**

Run:

```bash
cd application
php artisan test \
  tests/Feature/Study/PeriodActivationTest.php \
  tests/Feature/Study/StudyConfigurationHasherTest.php \
  tests/Unit/Sensitivity/SensitivityCalculatorTest.php
```

Expected: FAIL karena kolom konfigurasi dan argumen `$configuredScenarios` belum tersedia.

- [x] **Step 3: Add the period columns and model defaults**

Migration harus menambahkan enam `decimal(8, 6)` dengan default berikut:

```php
$table->decimal('sensitivity_s1_c1', 8, 6)->default(0.600000);
$table->decimal('sensitivity_s1_c2', 8, 6)->default(0.200000);
$table->decimal('sensitivity_s1_c3', 8, 6)->default(0.200000);
$table->decimal('sensitivity_s2_c1', 8, 6)->default(0.200000);
$table->decimal('sensitivity_s2_c2', 8, 6)->default(0.400000);
$table->decimal('sensitivity_s2_c3', 8, 6)->default(0.400000);
```

Tambahkan cast `decimal:6`, factory defaults, dan seed values yang sama. `down()` harus menghapus tepat enam kolom tersebut.

- [x] **Step 4: Add editable draft-only fields and readiness validation**

Di `StudySettings`, tambahkan enam public properties, rules `numeric|min:0|max:1`, dan validasi sesudah field validation:

```php
foreach (['S1' => [$s1c1, $s1c2, $s1c3], 'S2' => [$s2c1, $s2c2, $s2c3]] as $scenario => $weights) {
    if (abs(array_sum($weights) - 1.0) > 0.000001) {
        $this->addError("sensitivity{$scenario}", "Bobot {$scenario} harus berjumlah tepat 1,000000.");
    }
}
```

Render field sebagai tiga input per skenario pada konfigurasi periode. Semua input harus `disabled` ketika `$isDraft === false` dan mempunyai label eksplisit C1/C2/C3.

`PeriodReadinessService::issues()` harus menghasilkan pesan spesifik yang sama jika jumlah S1 atau S2 tidak valid.

- [x] **Step 5: Include sensitivity configuration in hash, snapshot, and calculator input**

`StudyConfigurationHasher` harus memasukkan keenam nilai dengan format enam desimal. `CalculationInputSnapshot` harus menghasilkan bentuk canonical berikut:

```php
'sensitivity_scenarios' => [
    'S1' => [
        'c1' => number_format((float) $period->sensitivity_s1_c1, 6, '.', ''),
        'c2' => number_format((float) $period->sensitivity_s1_c2, 6, '.', ''),
        'c3' => number_format((float) $period->sensitivity_s1_c3, 6, '.', ''),
    ],
    'S2' => [
        'c1' => number_format((float) $period->sensitivity_s2_c1, 6, '.', ''),
        'c2' => number_format((float) $period->sensitivity_s2_c2, 6, '.', ''),
        'c3' => number_format((float) $period->sensitivity_s2_c3, 6, '.', ''),
    ],
],
```

Hapus `fixedWeights()` dari enum. `SensitivityCalculator` harus memvalidasi key S1/S2, tiga kriteria numerik, non-negatif, dan jumlah bobot `1.0 ± 0.000001`. `CalculationRunService` membaca weights hanya dari snapshot yang sudah dibentuk.

- [x] **Step 6: Extend the independent golden fixture with sensitivity formulas**

Pada sheet `Technical and SAW`, tambahkan kolom:

```text
L: S0 Vi       = K15 / K16
M: S0 Rank     = RANK.EQ(L15,$L$15:$L$16,0)
N: S1 Vi       = 0.6*E15+0.2*F15+0.2*G15
O: S1 Rank     = RANK.EQ(N15,$N$15:$N$16,0)
P: S2 Vi       = 0.2*E15+0.4*F15+0.4*G15
Q: S2 Rank     = RANK.EQ(P15,$P$15:$P$16,0)
R: S1 Delta    = M15-O15
S: S2 Delta    = M15-Q15
```

Expected values yang ditulis ke JSON:

```json
"sensitivity": {
  "S0": {
    "ibadah-yu": {"vi": 0.7616666666666667, "rank": 1, "delta_rank": 0},
    "info-yu":   {"vi": 0.7616666666666667, "rank": 1, "delta_rank": 0}
  },
  "S1": {
    "ibadah-yu": {"vi": 0.6075, "rank": 2, "delta_rank": -1},
    "info-yu":   {"vi": 0.9, "rank": 1, "delta_rank": 0}
  },
  "S2": {
    "ibadah-yu": {"vi": 0.7358333333333333, "rank": 2, "delta_rank": -1},
    "info-yu":   {"vi": 0.8, "rank": 1, "delta_rank": 0}
  }
}
```

`GoldenWorkbookConsistencyTest` membaca formula result L:S dan mencocokkannya dengan JSON; `GoldenCalculationRunTest` mencocokkan ketiga skenario tersimpan dengan toleransi fixture.

- [x] **Step 7: Run all Task 1 tests**

Run:

```bash
cd application
php artisan test \
  tests/Feature/Study/PeriodActivationTest.php \
  tests/Feature/Study/StudyConfigurationHasherTest.php \
  tests/Unit/Fixtures/GoldenWorkbookConsistencyTest.php \
  tests/Unit/Sensitivity/SensitivityCalculatorTest.php \
  tests/Feature/Calculation/GoldenCalculationRunTest.php
```

Expected: PASS, termasuk exact scenario keys S0/S1/S2 dan delta fixture.

- [ ] **Step 8: Commit Task 1**

```bash
git add application/database application/app application/resources application/tests docs/research/ueq-saw-golden-fixture.xlsx
git commit -m "fix: persist release three sensitivity scenarios"
```

---

### Task 2: Enforce the Final Calculation Eligibility Gate

**Files:**
- Create: `application/database/migrations/2026_08_06_000020_add_official_lock_metadata.php`
- Create: `application/app/Application/Calculation/OfficialRunEligibility.php`
- Create: `application/app/Application/Calculation/RecordMinimumSampleDeviation.php`
- Modify: `application/app/Models/CalculationRun.php`
- Modify: `application/app/Models/EvaluationPeriod.php`
- Modify: `application/app/Livewire/Admin/Calculations.php`
- Modify: `application/resources/views/livewire/admin/calculations.blade.php`
- Create: `application/tests/Feature/Calculation/OfficialRunEligibilityTest.php`
- Create: `application/tests/Feature/Calculation/MinimumSampleDeviationTest.php`

**Interfaces:**
- Produces: `OfficialRunEligibility::issues(CalculationRun $run): list<string>`.
- Produces: `OfficialRunEligibility::assertEligible(CalculationRun $run): void`, melempar `DomainException` berisi seluruh issue yang digabung dengan newline.
- Produces: `RecordMinimumSampleDeviation::handle(CalculationRun $run, string $reason, string $approvalReference, User $actor): CalculationRun`.

- [ ] **Step 1: Write failing eligibility tests for every final gate**

Gunakan test dataset terpisah untuk membuktikan setiap kondisi berikut menghasilkan pesan spesifik:

```php
it('rejects official lock eligibility for a draft period', function (): void {
    $run = app(CalculationRunService::class)->preview($this->period, $this->admin);

    expect(app(OfficialRunEligibility::class)->issues($run))
        ->toContain('Periode harus berstatus closed sebelum hasil resmi dikunci.');
});

it('rejects an empty or incomplete analytical run', function (): void {
    expect(app(OfficialRunEligibility::class)->issues($this->run))
        ->toContain('Minimal dua alternatif SAW lengkap diperlukan.')
        ->toContain('Hasil sensitivitas S0, S1, dan S2 harus lengkap untuk setiap alternatif.');
});
```

Tambahkan test untuk:

- run bukan `preview`;
- revision snapshot berbeda dari `evaluation_periods.calculation_input_revision`;
- included submission tidak mempunyai item `1..26` lengkap;
- masih ada keputusan kualitas `unreviewed`;
- satu atau lebih unit belum mencapai `minimum_per_unit` tanpa deviation approval;
- consensus teknis tidak lengkap atau jumlah informan di luar `3..5`;
- benchmark source/version atau algorithm version kosong;
- jumlah sensitivity result bukan `3 × jumlah saw result`;
- happy path closed period lengkap menghasilkan `issues() === []`.

- [ ] **Step 2: Run tests and verify the current lock path is unsafe**

Run:

```bash
cd application
php artisan test tests/Feature/Calculation/OfficialRunEligibilityTest.php
```

Expected: FAIL karena `OfficialRunEligibility` belum ada.

- [ ] **Step 3: Add official pointer and deviation metadata schema**

Migration menambahkan:

```php
Schema::table('evaluation_periods', function (Blueprint $table): void {
    $table->foreignId('official_calculation_run_id')
        ->nullable()
        ->constrained('calculation_runs')
        ->restrictOnDelete();
});

Schema::table('calculation_runs', function (Blueprint $table): void {
    $table->text('minimum_deviation_reason')->nullable();
    $table->string('minimum_deviation_approval_reference')->nullable();
    $table->foreignId('minimum_deviation_approved_by')->nullable()->constrained('users')->restrictOnDelete();
    $table->timestamp('minimum_deviation_approved_at')->nullable();
});
```

Tambahkan relasi `EvaluationPeriod::officialRun()` dan `CalculationRun::minimumDeviationApprover()`, beserta datetime cast.

- [ ] **Step 4: Implement eligibility as a read-only validator**

`issues()` harus memuat relasi period, SAW, sensitivity, dan membaca snapshot, tanpa mengubah database. Hitung minimum per unit dari `quality_decisions` yang bernilai `included`:

```php
$includedByUnit = collect($snapshot['quality_decisions'] ?? [])
    ->where('decision', QualityDecision::Included->value)
    ->countBy('evaluation_unit_id');

$belowMinimum = collect($snapshot['units'] ?? [])
    ->where('is_active', true)
    ->filter(fn (array $unit): bool => ($includedByUnit[$unit['id']] ?? 0) < $minimum);
```

Jika `$belowMinimum` tidak kosong, validator hanya mengizinkan finalisasi ketika keempat field deviation terisi. Pesan harus menyebut kode unit dan jumlah aktual, contoh `info-yu baru memiliki 18 dari minimum 20 respons included.`

Validasi 26 jawaban menggunakan key integer/string `1..26`, bukan hanya `count() === 26`.

- [ ] **Step 5: Implement deviation recording before official lock**

Action harus:

```php
if ($run->status !== 'preview') {
    throw new DomainException('Keputusan penyimpangan hanya dapat dicatat pada run preview.');
}

if (trim($reason) === '' || trim($approvalReference) === '') {
    throw new DomainException('Alasan dan referensi persetujuan pembimbing wajib diisi.');
}
```

Simpan actor/time dan buat `AuditEvent` action `calculation_run.minimum_deviation_recorded` dalam transaksi yang sama. UI hanya menampilkan form ketika validator menemukan unit di bawah minimum; form memuat reason dan approval reference.

- [ ] **Step 6: Run Task 2 tests**

Run:

```bash
cd application
php artisan test \
  tests/Feature/Calculation/OfficialRunEligibilityTest.php \
  tests/Feature/Calculation/MinimumSampleDeviationTest.php
```

Expected: PASS dan setiap invalid fixture menghasilkan pesan pemulihan yang spesifik.

- [ ] **Step 7: Commit Task 2**

```bash
git add application/database application/app application/resources application/tests
git commit -m "fix: enforce final calculation eligibility"
```

---

### Task 3: Make Official Lock Permanent, Immutable, and Auditable

**Files:**
- Modify: `application/app/Application/Calculation/CalculationRunService.php`
- Modify: `application/app/Application/Calculation/CalculationInputChangeRecorder.php`
- Modify: `application/app/Application/Calculation/SensitivityResultWriter.php`
- Modify: `application/app/Models/CalculationRun.php`
- Modify: `application/app/Models/SensitivityResult.php`
- Modify: `application/app/Models/ExpertJudgment.php`
- Modify: `application/app/Livewire/Admin/Calculations.php`
- Modify: `application/resources/views/livewire/admin/calculations.blade.php`
- Rewrite: `application/tests/Feature/Calculation/OfficialRunLockTest.php`
- Modify: `application/tests/Feature/Calculation/CalculationResultImmutabilityTest.php`
- Modify: `application/tests/Feature/Calculation/CalculationInputChangeRecorderTest.php`
- Modify: `application/tests/Feature/Admin/CalculationsTest.php`

**Interfaces:**
- Consumes: `OfficialRunEligibility::assertEligible(CalculationRun $run): void`.
- Produces: `CalculationRunService::lockAsOfficial(CalculationRun $run, User $actor): CalculationRun` yang mengubah run `preview → official`, periode `closed → locked`, dan menetapkan `official_calculation_run_id` secara atomik.

- [ ] **Step 1: Replace permissive lock tests with permanent-lock tests**

Hapus expectation yang mengarsipkan official run lama. Tambahkan test berikut:

```php
it('locks one eligible run and period atomically', function (): void {
    $official = app(CalculationRunService::class)->lockAsOfficial($this->eligibleRun, $this->admin);

    expect($official->status)->toBe('official')
        ->and($official->period->status)->toBe(PeriodStatus::Locked)
        ->and($official->period->official_calculation_run_id)->toBe($official->id);

    $this->assertDatabaseHas('audit_events', [
        'action' => 'calculation_run.locked_official',
        'auditable_type' => CalculationRun::class,
        'auditable_id' => $official->id,
        'actor_id' => $this->admin->id,
    ]);
});

it('rejects a second official run for the same period', function (): void {
    app(CalculationRunService::class)->lockAsOfficial($this->eligibleRun, $this->admin);

    expect(fn () => app(CalculationRunService::class)->lockAsOfficial($this->otherRun, $this->admin))
        ->toThrow(DomainException::class, 'Periode ini sudah mempunyai hasil resmi dan tidak dapat dikunci ulang.');
});
```

Tambahkan test rollback dengan exception setelah run update untuk membuktikan run, period pointer, period status, dan audit event kembali ke state awal.

- [ ] **Step 2: Run official-lock tests and observe failure**

Run:

```bash
cd application
php artisan test tests/Feature/Calculation/OfficialRunLockTest.php
```

Expected: FAIL karena implementasi lama mengarsipkan official run sebelumnya dan tidak mengubah periode/audit event.

- [ ] **Step 3: Lock the period row before validating and updating**

Implementasi transaksi harus mengikuti urutan tetap berikut:

```php
$lockedRun = CalculationRun::query()->lockForUpdate()->findOrFail($run->id);
$lockedPeriod = EvaluationPeriod::query()->lockForUpdate()->findOrFail($lockedRun->evaluation_period_id);

if ($lockedPeriod->official_calculation_run_id !== null) {
    throw new DomainException('Periode ini sudah mempunyai hasil resmi dan tidak dapat dikunci ulang.');
}

$this->officialEligibility->assertEligible($lockedRun);

$lockedRun->update([
    'status' => 'official',
    'locked_by' => $actor->id,
    'official_locked_at' => now(),
]);

$lockedPeriod->update([
    'status' => PeriodStatus::Locked,
    'official_calculation_run_id' => $lockedRun->id,
]);
```

Tambahkan `AuditEvent` dengan old/new values yang memuat run status, period status, input hash, algorithm version, dan `official_locked_at` dalam transaksi yang sama.

- [ ] **Step 4: Harden all official result models**

`SensitivityResult` harus memakai guard yang sama dengan `SawResult`:

```php
protected static function booted(): void
{
    static::updating(fn () => throw new LogicException('Sensitivity results are immutable.'));
    static::deleting(fn () => throw new LogicException('Sensitivity results are immutable.'));
}
```

Hapus `$run->sensitivityResults()->delete()` dari writer. Writer hanya boleh dipanggil pada run baru yang belum mempunyai sensitivity rows; jika rows sudah ada, lempar `LogicException`.

`CalculationRun` harus menolak semua update ketika original status `official`. Sebelum official, hanya transisi state yang eksplisit dan deviation metadata Task 2 yang boleh berubah. `ExpertJudgment` menolak update/delete jika relasi run berstatus official.

- [ ] **Step 5: Ensure input changes never stale or mutate official runs**

Pertahankan query stale hanya untuk `status = preview`. Tambahkan regression test yang membuat official run, menjalankan `CalculationInputChangeRecorder`, dan memastikan status, input hash, UEQ, SAW, sensitivity, serta backlog official tidak berubah.

- [ ] **Step 6: Make the UI reopen official results read-only**

Ketika run official:

- sembunyikan tombol lock;
- sembunyikan seluruh form expert judgment dan deviation;
- tampilkan badge `OFFICIAL / LOCKED`;
- reload halaman harus menampilkan input hash, Vi, dan seluruh rank yang sama;
- tombol preview dinonaktifkan ketika periode `locked`.

- [ ] **Step 7: Run Task 3 tests**

Run:

```bash
cd application
php artisan test \
  tests/Feature/Calculation/OfficialRunLockTest.php \
  tests/Feature/Calculation/CalculationResultImmutabilityTest.php \
  tests/Feature/Calculation/CalculationInputChangeRecorderTest.php \
  tests/Feature/Admin/CalculationsTest.php
```

Expected: PASS; tidak ada test yang mengharapkan status `archived`.

- [ ] **Step 8: Commit Task 3**

```bash
git add application/app application/resources application/tests
git commit -m "fix: make official calculation runs permanent"
```

---

### Task 4: Store a Complete and Conflict-Free Operational Backlog

**Files:**
- Create: `application/database/migrations/2026_08_06_000021_enforce_expert_judgment_backlog_order.php`
- Create: `application/app/Application/Quality/InitializeOperationalBacklog.php`
- Rewrite: `application/app/Application/Quality/RecordExpertJudgment.php`
- Modify: `application/app/Application/Calculation/CalculationRunService.php`
- Modify: `application/app/Application/Calculation/OfficialRunEligibility.php`
- Modify: `application/app/Models/ExpertJudgment.php`
- Modify: `application/app/Livewire/Admin/Calculations.php`
- Modify: `application/resources/views/livewire/admin/calculations.blade.php`
- Rewrite: `application/tests/Feature/Admin/ExpertJudgmentTest.php`
- Modify: `application/tests/Feature/Admin/CalculationsTest.php`

**Interfaces:**
- Produces: `InitializeOperationalBacklog::handle(CalculationRun $run): void`.
- Produces: `RecordExpertJudgment::handle(CalculationRun $run, EvaluationUnit $unit, int $operationalOrder, string $reason, User $reviewer): ExpertJudgment`.
- Invariant: untuk run dengan `N` SAW rows terdapat tepat `N` expert judgment rows dan urutannya tepat `1..N` tanpa duplikasi.

- [ ] **Step 1: Write failing backlog invariant and reorder tests**

```php
it('initializes a complete operational backlog without changing saw ranking', function (): void {
    $run = app(CalculationRunService::class)->preview($this->period, $this->admin);

    expect($run->expertJudgments)->toHaveCount($run->sawResults->count())
        ->and($run->expertJudgments->sortBy('operational_order')->pluck('operational_order')->values()->all())
        ->toBe(range(1, $run->sawResults->count()));
});

it('moves one unit and shifts the affected backlog atomically', function (): void {
    $beforeSaw = $this->run->sawResults->pluck('rank', 'evaluation_unit_id')->all();
    $last = $this->run->expertJudgments->sortByDesc('operational_order')->firstOrFail();

    app(RecordExpertJudgment::class)->handle(
        $this->run,
        $last->evaluationUnit,
        1,
        'Kebutuhan regulasi harus dikerjakan lebih dahulu.',
        $this->admin,
    );

    expect($this->run->fresh()->expertJudgments()->orderBy('operational_order')->pluck('operational_order')->all())
        ->toBe(range(1, $this->run->sawResults()->count()))
        ->and($this->run->sawResults()->pluck('rank', 'evaluation_unit_id')->all())
        ->toBe($beforeSaw);
});
```

Tambahkan test penolakan untuk unit yang tidak ada pada `saw_results`, order di luar `1..N`, reason kosong ketika order berubah, dan perubahan setelah official lock.

- [ ] **Step 2: Run tests and verify partial-row behavior fails**

Run:

```bash
cd application
php artisan test tests/Feature/Admin/ExpertJudgmentTest.php
```

Expected: FAIL karena implementasi lama hanya menyimpan baris yang disesuaikan dan mengizinkan duplicate operational order.

- [ ] **Step 3: Normalize existing rows and add the unique order constraint**

Migration harus, per calculation run:

1. membaca SAW rows terurut `rank`, lalu `evaluation_unit.code`;
2. mempertahankan reason/reviewer existing jika tersedia;
3. membuat row yang hilang dengan `decision = unchanged`, reason `Mengikuti urutan analitis SAW S0.`, reviewer memakai creator run;
4. menomori ulang seluruh row `1..N`;
5. baru menambahkan unique index `['calculation_run_id', 'operational_order']`.

Rollback menghapus unique index tanpa menghapus data backlog.

- [ ] **Step 4: Initialize the complete backlog when a preview is created**

Panggil initializer setelah `SawResultWriter::write()`. Ordering baseline harus stabil:

```php
$run->sawResults()
    ->with('unit')
    ->get()
    ->sortBy(fn (SawResult $row): string => sprintf('%06d:%s', $row->rank, $row->unit->code))
    ->values()
    ->each(fn (SawResult $row, int $index) => $run->expertJudgments()->create([
        'evaluation_unit_id' => $row->evaluation_unit_id,
        'operational_order' => $index + 1,
        'decision' => 'unchanged',
        'reason' => 'Mengikuti urutan analitis SAW S0.',
        'reviewer_id' => $run->created_by,
    ]));
```

- [ ] **Step 5: Implement an atomic reorder action with audit history**

Lock all backlog rows for the run. Jika item berpindah dari `$oldOrder` ke `$newOrder`, geser row lain satu posisi dalam memory, lalu tulis order sementara `N + 1000 + index` agar compatible dengan kolom unsigned dan unique constraint tidak berbenturan, kemudian tulis `1..N` final. Set item yang dipindahkan menjadi `decision = adjusted`, trim reason, reviewer, timestamp. Buat `AuditEvent` action `expert_judgment.backlog_reordered` dengan old/new order map.

- [ ] **Step 6: Require a complete backlog in the official eligibility gate**

`OfficialRunEligibility` harus menolak official lock jika jumlah backlog berbeda dari jumlah SAW rows, terdapat unit SAW tanpa backlog, terdapat unit backlog tanpa SAW row, atau urutannya bukan tepat `1..N`. Pesan tunggal yang digunakan: `Backlog operasional harus lengkap dan berurutan sebelum hasil resmi dikunci.` Tambahkan assertion ini ke `OfficialRunEligibilityTest` dan `ExpertJudgmentTest`.

- [ ] **Step 7: Restrict UI choices to the selected run backlog**

Hapus query `EvaluationUnit::all()` dari component. Dropdown hanya memakai `$run->expertJudgments`/`$run->sawResults`. Tabel selalu menampilkan seluruh backlog, bukan hanya adjusted rows. Bedakan badge `UNCHANGED` dan `ADJUSTED`.

- [ ] **Step 8: Run Task 4 tests**

Run:

```bash
cd application
php artisan test \
  tests/Feature/Admin/ExpertJudgmentTest.php \
  tests/Feature/Admin/CalculationsTest.php \
  tests/Feature/Calculation/OfficialRunEligibilityTest.php \
  tests/Feature/Calculation/CalculationResultImmutabilityTest.php
```

Expected: PASS; database tidak dapat menyimpan dua order yang sama untuk satu run.

- [ ] **Step 9: Commit Task 4**

```bash
git add application/database application/app application/resources application/tests
git commit -m "fix: persist a coherent operational backlog"
```

---

### Task 5: Complete Sensitivity Presentation and Accessible Report Visuals

**Files:**
- Create: `application/app/Application/Reporting/SensitivityComparisonData.php`
- Create: `application/app/Application/Reporting/SensitivityComparisonQuery.php`
- Modify: `application/app/Application/Reporting/AggregateReportData.php`
- Modify: `application/app/Application/Reporting/AggregateReportQuery.php`
- Modify: `application/app/Livewire/Admin/Calculations.php`
- Modify: `application/app/Livewire/Admin/Reports.php`
- Modify: `application/resources/views/livewire/admin/calculations.blade.php`
- Rewrite: `application/resources/views/livewire/admin/reports.blade.php`
- Modify: `application/tests/Feature/Admin/CalculationsTest.php`
- Rewrite: `application/tests/Feature/Admin/ReportsTest.php`
- Create: `application/tests/Browser/ReleaseThreeReportAccessibilityTest.php`

**Interfaces:**
- Produces: `SensitivityComparisonQuery::forRun(CalculationRun $run): SensitivityComparisonData`.
- `SensitivityComparisonData` properties: `Collection $rows`, `array{S1: bool, S2: bool} $topThreeStable`, `array{S1: list<int>, S2: list<int>} $changedTopThreeUnitIds`.
- `AggregateReportData` mempunyai property `EvaluationPeriod $period`, `?CalculationRun $selectedRun`, `bool $isOfficial`, `Collection $benchmarks`, `Collection $ueqSummary`, `Collection $sawRanking`, `Collection $sensitivityMatrix`, `Collection $operationalBacklog`, dan `SensitivityComparisonData $sensitivityComparison`. Property lama `officialRun` dan `latestRun` dihapus; official pointer dipilih lebih dahulu, fallback hanya ke latest `preview`, tidak pernah `stale` atau `archived`.

- [ ] **Step 1: Write failing query and rendering tests**

Test stability harus memakai set unit id dengan rank `<= 3`, bukan tiga row pertama:

```php
expect($comparison->topThreeStable)->toBe([
    'S1' => false,
    'S2' => true,
]);
```

Reports feature test harus mencari markers berikut:

```php
Livewire::actingAs($this->admin)
    ->test(Reports::class, ['periodId' => $this->period->id])
    ->assertSeeHtml('data-chart="ueq-mean"')
    ->assertSeeHtml('data-chart="gap-by-scale"')
    ->assertSeeHtml('data-chart="saw-contribution"')
    ->assertSeeHtml('data-chart="rank-change"')
    ->assertSee('Tabel angka UEQ')
    ->assertSee('Tabel kontribusi SAW')
    ->assertSee('STABIL')
    ->assertSee('BERUBAH');
```

- [ ] **Step 2: Run focused tests and confirm missing charts/stability labels**

Run:

```bash
cd application
php artisan test \
  tests/Feature/Admin/ReportsTest.php \
  tests/Feature/Admin/CalculationsTest.php
```

Expected: FAIL karena hanya gap chart yang ada dan stability data belum tersedia.

- [ ] **Step 3: Build one selected-run reporting query**

Selection rule:

```php
$selectedRun = $period->officialRun()->first()
    ?? CalculationRun::query()
        ->where('evaluation_period_id', $period->id)
        ->where('status', 'preview')
        ->latest('id')
        ->first();
```

Tambahkan benchmark rows dari `selectedRun.input_snapshot.benchmarks`, UEQ rows lengkap termasuk SD/SE/CI/alpha/gap, SAW contributions, sensitivity comparison, serta backlog lengkap. Semua consumer—Calculations, Reports, XLSX, CSV—harus memakai selected run yang sama.

- [ ] **Step 4: Implement top-three stability and highlight data**

Untuk S0/S1/S2, buat set unit id dengan rank `<= 3`. Skenario stabil hanya jika set scenario identik dengan set S0. `changedTopThreeUnitIds` adalah symmetric difference. Calculations dan Reports menampilkan:

- badge `STABIL` jika true;
- badge `BERUBAH` jika false;
- highlight row jika unit id berada dalam changed set;
- delta seluruh modul tetap ditampilkan.

- [ ] **Step 5: Render four accessible HTML/CSS visualizations**

Implementasikan:

1. `data-chart="ueq-mean"`: bar mean UEQ per skala pada domain `-3..+3`, dengan zero axis dan angka mean.
2. `data-chart="gap-by-scale"`: gap enam skala per modul, bukan hanya overall C1.
3. `data-chart="saw-contribution"`: stacked bar kontribusi C1/C2/C3 yang totalnya sama dengan Vi.
4. `data-chart="rank-change"`: bar/delta S1 dan S2 terhadap S0 serta label stable/changed.

Setiap region harus mempunyai `role="img"` dan `aria-label` yang menyebut unit, metrik, serta nilai. Di bawah grafik, tampilkan tabel angka lengkap dalam region keyboard-focusable dengan visible focus ring.

- [ ] **Step 6: Add the browser accessibility test**

Browser test pada viewport `1280x800` dan `360x800` harus memastikan:

- empat chart marker terlihat;
- semua `[data-chart]` mempunyai non-empty `aria-label` atau child dengan label;
- tabel dapat menerima fokus keyboard;
- tidak ada horizontal overflow pada page root di 360 px;
- badge stability dapat dibaca sebagai teks, bukan hanya warna.

- [ ] **Step 7: Run Task 5 tests**

Run:

```bash
cd application
php artisan test \
  tests/Feature/Admin/CalculationsTest.php \
  tests/Feature/Admin/ReportsTest.php \
  tests/Browser/ReleaseThreeReportAccessibilityTest.php
```

Expected: PASS pada desktop dan mobile viewport.

- [ ] **Step 8: Commit Task 5**

```bash
git add application/app application/resources application/tests
git commit -m "feat: complete release three report visuals"
```

---

### Task 6: Produce Complete XLSX and Flat Aggregate CSV Exports

**Files:**
- Create: `application/app/Application/Reporting/AggregateCsvExport.php`
- Modify: `application/app/Application/Reporting/AggregateReportExport.php`
- Modify: `application/app/Http/Controllers/Admin/AggregateReportExportController.php`
- Rewrite: `application/tests/Feature/Admin/AggregateReportExportTest.php`

**Interfaces:**
- Produces: `AggregateCsvExport::rows(EvaluationPeriod $period, CarbonInterface $generatedAt): iterable<list<string|int|float|null>>`.
- Produces: `AggregateReportExport::spreadsheet(EvaluationPeriod $period, CarbonInterface $generatedAt): Spreadsheet`.
- CSV columns: `section`, `period_name`, `instrument_version`, `benchmark_version`, `benchmark_source`, `run_id`, `run_status`, `generated_at`, `unit_code`, `unit_name`, `scale`, `scenario`, `metric`, `value`, `rank`, `delta_rank`, `reason`.

- [ ] **Step 1: Rewrite export tests to inspect content, not only headers**

CSV test harus membaca streamed content dengan `str_getcsv` dan membuktikan section berikut hadir:

```php
expect($sections)->toContain(
    'metadata',
    'benchmark',
    'ueq',
    'saw',
    'sensitivity',
    'operational_backlog',
);

expect($csv)->toContain($period->instrument_version)
    ->toContain((string) $run->id)
    ->toContain($run->status)
    ->toContain('S1')
    ->toContain('delta_rank');
```

XLSX test harus memeriksa sheet names `Metadata Run`, `Benchmark`, `Hasil UEQ`, `Peringkat SAW`, `Analisis Sensitivitas`, dan `Backlog Operasional`, lalu mencocokkan Run ID serta benchmark version/source dengan snapshot.

Tambahkan regression test: jika official run tersedia dan preview yang lebih baru juga ada, kedua format tetap mengekspor official run pointer.

- [ ] **Step 2: Run export tests and confirm CSV contains metadata only**

Run:

```bash
cd application
php artisan test tests/Feature/Admin/AggregateReportExportTest.php
```

Expected: FAIL karena CSV lama hanya menulis active worksheet `Metadata Run`.

- [ ] **Step 3: Implement a flat multi-section CSV dataset**

Baris pertama adalah exact header interface. Setiap row wajib mengulang metadata periode/run agar dapat dianalisis tanpa state antarbaris. Untuk nilai UEQ, emit satu row per metric:

```php
foreach (['n', 'mean', 'sd', 'se', 'ci95_lower', 'ci95_upper', 'alpha', 'gap'] as $metric) {
    yield $this->row(
        section: 'ueq',
        metadata: $metadata,
        unit: $unit,
        scale: $scale,
        metric: $metric,
        value: $values[$metric],
    );
}
```

SAW harus emit X, R, tiga contribution, Vi, rank, tie. Sensitivity harus emit scenario weights, Vi, rank, dan delta. Backlog harus emit operational order, decision, reason, reviewer, dan waktu.

- [ ] **Step 4: Stream CSV directly with `fputcsv`**

Controller tidak boleh memakai `PhpSpreadsheet\Writer\Csv`. Gunakan:

```php
return response()->streamDownload(function () use ($period): void {
    $handle = fopen('php://output', 'wb');
    fwrite($handle, "\xEF\xBB\xBF");

    foreach ($this->csvExport->rows($period, now()) as $row) {
        fputcsv($handle, $row, ',', '"', '\\');
    }

    fclose($handle);
}, "laporan-agregat-{$period->slug}.csv", [
    'Content-Type' => 'text/csv; charset=UTF-8',
]);
```

- [ ] **Step 5: Complete XLSX metadata and aggregate columns**

Metadata sheet harus memuat period name/slug, instrument version, algorithm version, run ID/status/hash, included/excluded, calculated at, generated at, locked by/at, dan minimum-deviation metadata. Tambahkan Benchmark sheet berisi version, scale, good threshold, source, verified at. UEQ sheet wajib menambah SE dan CI lower/upper yang saat ini hilang.

- [ ] **Step 6: Run Task 6 tests**

Run:

```bash
cd application
php artisan test tests/Feature/Admin/AggregateReportExportTest.php
```

Expected: PASS dan CSV mengandung semua section, bukan hanya metadata.

- [ ] **Step 7: Commit Task 6**

```bash
git add application/app application/tests
git commit -m "fix: export complete aggregate research results"
```

---

### Task 7: Strengthen End-to-End Evidence and Update the Release Runbook

**Files:**
- Create: `application/tests/Support/ReleaseThreeFixture.php`
- Rewrite: `application/tests/Browser/ReleaseThreeFlowTest.php`
- Modify: `application/tests/Feature/Admin/ReportsTest.php`
- Modify: `application/tests/Feature/Admin/AggregateReportExportTest.php`
- Rewrite: `application/docs/release-3-runbook.md`

**Interfaces:**
- Produces: `ReleaseThreeFixture::eligibleScenario(): object{period: EvaluationPeriod, admin: User, run: CalculationRun}` dengan 13 unit, minimum dua included submissions per unit, tiga informan lengkap, periode closed, dan preview yang eligible.
- Produces bukti E2E: review hasil → preview → reorder backlog → official lock → reopen → report → export.

- [ ] **Step 1: Build a deterministic eligible Rilis 3 fixture**

Fixture harus:

1. seed 13 unit dan 26 item;
2. set `minimum_per_unit = 2`, `target_per_unit = 2` sebelum aktivasi;
3. buat dua included submissions dengan 26 jawaban untuk setiap unit;
4. buat tiga informan dengan assessment untuk seluruh 13 unit dan bobot tepat 100;
5. tutup periode melalui state `active → closed`;
6. jalankan preview dan assert 13 SAW rows, 39 sensitivity rows, serta 13 backlog rows.

- [ ] **Step 2: Rewrite the browser flow to exercise actual Rilis 3 behavior**

Alur browser harus:

```text
Login → Calculations → open eligible preview
→ verify S0/S1/S2 and top-three stability labels
→ move one backlog unit with a reason
→ capture input hash, first SAW Vi, and S1 rank
→ lock official
→ reload Calculations
→ verify captured values unchanged and forms absent
→ open Reports
→ verify four chart regions and analytical-vs-operational table
→ verify XLSX and CSV download links exist
```

Gunakan selectors stabil `data-testid`, bukan text selector untuk action kritis.

- [ ] **Step 3: Run the rewritten E2E test**

Run:

```bash
cd application
php artisan test tests/Browser/ReleaseThreeFlowTest.php
```

Expected: PASS dan tidak lagi mengunci run kosong dari periode draft.

- [ ] **Step 4: Rewrite the operational runbook from verified behavior**

Runbook harus:

- menggunakan path POSIX `vendor/bin/pint --test`;
- menjelaskan semua final-calculation gate dan deviation approval;
- menjelaskan bahwa official lock permanen dan tidak dapat diganti;
- menjelaskan empat grafik yang benar-benar ada;
- menjelaskan struktur enam sheet XLSX dan section CSV;
- mencantumkan prosedur membuka kembali official run dan mencocokkan hash/angka;
- tidak mempertahankan angka test lama `132/544`.

- [ ] **Step 5: Run the full fresh verification gate**

Run:

```bash
cd application
composer test
npm run build
```

Expected:

- Pint PASS;
- PHPStan `errors: 0`;
- seluruh Pest unit/feature/browser tests PASS dengan `0 failed`;
- Vite build exit code `0`.

Catat jumlah test dan assertion yang benar-benar keluar dari command ini pada bagian `Bukti Pengujian Rilis 3` di runbook. Jangan menyalin angka dari run sebelumnya.

- [ ] **Step 6: Run migration round-trip verification**

Gunakan file SQLite sementara yang eksplisit agar tiga command artisan memakai database yang sama:

```bash
cd application
release3_db_path="$(mktemp)"
DB_CONNECTION=sqlite DB_DATABASE="$release3_db_path" php artisan migrate:fresh --env=testing --force
DB_CONNECTION=sqlite DB_DATABASE="$release3_db_path" php artisan migrate:rollback --env=testing --force --step=3
DB_CONNECTION=sqlite DB_DATABASE="$release3_db_path" php artisan migrate --env=testing --force
rm -f "$release3_db_path"
```

Expected: ketiga migration remediasi dapat di-rollback dan diaplikasikan ulang tanpa error.

- [ ] **Step 7: Confirm repository scope and absence of unrelated changes**

Run:

```bash
git status --short
git diff --check
git diff --stat HEAD~7..HEAD
```

Expected: hanya file Rilis 3, shared configuration/snapshot yang diperlukan, golden fixture, tests, dan runbook yang berubah; `git diff --check` tidak menghasilkan output.

- [ ] **Step 8: Commit verified runbook and E2E evidence**

```bash
git add application/tests application/docs/release-3-runbook.md
git commit -m "test: verify the complete release three workflow"
```

---

## Acceptance Traceability

| Requirement Rilis 3 | Implemented by | Automated evidence |
|---|---|---|
| S0 memakai bobot aktual informan | Task 1 | `SensitivityCalculatorTest`, `GoldenCalculationRunTest` |
| S1/S2 tersimpan, menghasilkan perbandingan dan delta | Task 1 | Golden workbook/JSON consistency and calculation run test |
| Perubahan top-three disorot dan stabilitas diberi label | Task 5 | `CalculationsTest`, `ReportsTest`, browser accessibility test |
| Expert judgment tidak mengubah SAW | Task 4 | `ExpertJudgmentTest` before/after SAW snapshot |
| Backlog berbeda mempunyai alasan, waktu, dan actor | Task 4 | reorder validation and audit-event assertions |
| Backlog adalah output lengkap dan terpisah | Task 4 | count/order invariant tests |
| Final calculation gate diterapkan | Task 2 | one test per §15.2 gate |
| Official run tunggal, immutable, dan auditable | Task 3 | official-lock, rollback, immutability, and stale-regression tests |
| Grafik UEQ, gap, kontribusi, dan rank change tersedia | Task 5 | feature and browser rendering tests |
| Laporan memilih official run sebagai acuan | Task 5–6 | newer-preview-vs-official regression test |
| XLSX dan CSV memuat hasil agregat serta metadata lengkap | Task 6 | parsed workbook and parsed CSV content tests |
| Fixture emas memuat sensitivitas | Task 1 | workbook-to-JSON and app-to-JSON tests |
| Runbook sesuai verifikasi terkini | Task 7 | full gate output recorded after fresh run |

## Final Definition of Done

- [ ] Seluruh acceptance traceability row mempunyai test yang lulus.
- [ ] Tidak ada run `draft`, `active`, `stale`, kosong, atau incomplete yang dapat menjadi official.
- [ ] Satu periode locked menunjuk tepat satu official run dan lock kedua ditolak.
- [ ] UEQ, SAW, sensitivity, backlog, input hash, dan lock metadata official tidak berubah setelah reload atau input change lain.
- [ ] Backlog mempunyai tepat satu row per SAW alternative dan order unik kontinu `1..N`.
- [ ] Reports menampilkan empat visualisasi beserta tabel/label angka yang accessible.
- [ ] CSV mengandung enam section agregat dan XLSX mengandung enam worksheet.
- [ ] `composer test` dan `npm run build` selesai dengan exit code `0`.
- [ ] Runbook memuat jumlah test/assertion dari verifikasi terakhir, bukan angka historis.
- [ ] `git diff --check` bersih dan tidak ada perubahan di luar scope remediasi Rilis 3.
