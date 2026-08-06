# UEQ-SAW Release 3 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax (`- [ ]`) for tracking.

**Goal:** Menyelesaikan Rilis 3 dengan mengimplementasikan Analisis Sensitivitas (S0, S1, S2), Catatan Expert Judgment untuk Backlog Operasional, Penguncian Hasil Resmi (Official Lock), Tampilan Visual/Grafik Laporan Bab IV, dan Ekspor Agregat Lengkap.

**Architecture:** Rilis 3 memperluas modul analitis dengan:
1. `Domain/Sensitivity`: perhitungan skenario S0 (baseline informan), S1 (0.6 UX / 0.2 Days / 0.2 Urgency), dan S2 (0.2 UX / 0.4 Days / 0.4 Urgency) beserta $\Delta Rank$.
2. `Domain/ExpertJudgment`: perekaman penyesuaian urutan prioritas operasional tanpa mengubah `saw_results`.
3. `Application/Calculation`: penguncian `official` run yang bersifat permanen (*immutable*).
4. `Application/Reporting` & Livewire `Admin/Reports`: statistik visual (grafik bar/gap/kontribusi) dan ekspor data agregat CSV/XLSX.

**Tech Stack:** PHP 8.3+, Laravel 13, Livewire 4, Flux UI, PhpSpreadsheet, Pest 5, MySQL 8 / SQLite.

---

## Task 1: Add Sensitivity Analysis Engine & Schema

**Files:**
- Create: `application/database/migrations/2026_08_06_000013_create_sensitivity_and_expert_judgment_tables.php`
- Create: `application/app/Models/SensitivityResult.php`
- Create: `application/app/Models/ExpertJudgment.php`
- Create: `application/app/Domain/Sensitivity/SensitivityScenario.php`
- Create: `application/app/Domain/Sensitivity/SensitivityResultData.php`
- Create: `application/app/Domain/Sensitivity/SensitivityCalculator.php`
- Create: `application/tests/Unit/Sensitivity/SensitivityCalculatorTest.php`

**Interfaces:**
- Consumes: Matriks ternormalisasi $R$ dari SAW, bobot konsensus informan, dan data modul.
- Produces: `SensitivityCalculator::calculate(array $sawAlternatives, array $consensusWeights): array<string, array<int, SensitivityResultData>>` untuk skenario S0, S1, S2 beserta `delta_rank`.

---

## Task 2: Integrate Sensitivity & Official Lock into Calculation Engine

**Files:**
- Create: `application/app/Application/Calculation/SensitivityResultWriter.php`
- Modify: `application/app/Application/Calculation/CalculationRunService.php`
- Modify: `application/app/Models/CalculationRun.php`
- Modify: `application/database/migrations/2026_08_05_000012_create_calculation_result_tables.php`
- Create: `application/tests/Feature/Calculation/OfficialRunLockTest.php`

**Interfaces:**
- Consumes: `CalculationRun`, `User` actor.
- Produces: `CalculationRunService::lockAsOfficial(CalculationRun $run, User $actor): CalculationRun` menetapkan `status = 'official'`, `official_locked_at`, dan mencegah penimpaan/stale.

---

## Task 3: Implement Expert Judgment & Operational Backlog

**Files:**
- Create: `application/app/Application/Quality/RecordExpertJudgment.php`
- Modify: `application/app/Models/ExpertJudgment.php`
- Create: `application/tests/Feature/Admin/ExpertJudgmentTest.php`

**Interfaces:**
- Consumes: `CalculationRun`, `EvaluationUnit`, `operational_order`, `reason`, `User` reviewer.
- Produces: Terbentuknya entri `expert_judgments` tanpa mengubah nilai $V_i$ dan `rank` pada `saw_results`.

---

## Task 4: Build Admin Sensitivity, Expert Judgment & Lock UI in Calculations

**Files:**
- Modify: `application/app/Livewire/Admin/Calculations.php`
- Modify: `application/resources/views/livewire/admin/calculations.blade.php`
- Modify: `application/tests/Feature/Admin/CalculationsTest.php`

**Interfaces:**
- Consumes: `CalculationRunService`, `RecordExpertJudgment`.
- Produces: UI perbandingan skenario S0/S1/S2, delta peringkat, form input Expert Judgment, dan tombol Penguncian Hasil Resmi (*Official Lock*).

---

## Task 5: Add Visual Reports & Aggregate Export

**Files:**
- Create: `application/app/Application/Reporting/AggregateReportData.php`
- Create: `application/app/Application/Reporting/AggregateReportQuery.php`
- Create: `application/app/Application/Reporting/AggregateReportExport.php`
- Create: `application/app/Http/Controllers/Admin/AggregateReportExportController.php`
- Create: `application/app/Livewire/Admin/Reports.php`
- Create: `application/resources/views/livewire/admin/reports.blade.php`
- Modify: `application/routes/web.php`
- Modify: `application/resources/views/livewire/admin/dashboard.blade.php`
- Create: `application/tests/Feature/Admin/ReportsTest.php`
- Create: `application/tests/Feature/Admin/AggregateReportExportTest.php`

**Interfaces:**
- Consumes: Locked/Preview `CalculationRun` dan data agregat.
- Produces: Visualisasi grafik UEQ bar, breakdown Gap, kontribusi kriteria SAW, perbandingan S0/S1/S2, serta ekspor CSV/XLSX lengkap untuk Bab IV.

---

## Task 6: End-to-End Verification & Release 3 Runbook

**Files:**
- Create: `application/tests/Browser/ReleaseThreeFlowTest.php`
- Create: `application/docs/release-3-runbook.md`

**Interfaces:**
- Consumes: Alur lengkap Rilis 3.
- Produces: Bukti pengujian e2e browser, verifikasi `composer test`, `npm run build`, dan runbook operasional Rilis 3.
