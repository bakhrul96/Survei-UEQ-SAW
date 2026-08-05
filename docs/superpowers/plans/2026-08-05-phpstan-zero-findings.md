# PHPStan Zero Findings Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (- [ ]) syntax for tracking.

**Goal:** Make the Laravel application pass PHPStan level 7 without ignores or a baseline.

**Architecture:** Preserve runtime behavior while making Eloquent casts and relationships explicit to static analysis. Replace untyped database result objects in raw export with a typed value object, and narrow framework values before using them.

**Tech Stack:** PHP 8.5, Laravel 13, Larastan, PHPStan 2, Pest 5.

## Global Constraints

- Do not use PHPStan ignore comments, ignoreErrors configuration, or a baseline.
- Keep the existing PHPStan level 7.
- Add or retain executable tests for behavior affected by each change.
- Run analysis with PHP 8.5 and a 512 MB PHPStan memory limit.

---

### Task 1: Type Eloquent metadata and relationships

**Files:**
- Modify: application/app/Models/EvaluationPeriod.php
- Modify: application/app/Models/AnonymousRespondent.php
- Modify: application/app/Models/RespondentProfile.php
- Modify: application/app/Models/SurveyAnswer.php
- Modify: application/app/Models/SurveySession.php
- Modify: application/app/Models/SurveySubmission.php

- [x] Add concrete model property and generic relationship annotations.
- [x] Run PHPStan against app/Models.
- [x] Run the survey and study Pest tests.

### Task 2: Narrow request, cookie, and datetime values

**Files:**
- Modify: application/app/Domain/Survey/SurveyContext.php
- Modify: application/app/Http/Controllers/SurveyEntryController.php
- Modify: application/app/Livewire/Admin/StudySettings.php
- Test: existing survey, study, and token tests

- [x] Reproduce the active-period cookie issuance regression in the existing token test.
- [x] Narrow cookie values and period datetimes before use.
- [x] Run the targeted tests and PHPStan on these files.

### Task 3: Type raw export rows and submission validation

**Files:**
- Create: application/app/Application/Reporting/RawSurveyExportRow.php
- Modify: application/app/Application/Reporting/RawSurveyExport.php
- Modify: application/app/Application/Survey/SubmitSurvey.php
- Test: application/tests/Feature/Admin/RawSurveyExportTest.php

- [x] Write a failing export test covering all mapped columns.
- [x] Map query records to a typed export row and keep SQL literals explicit.
- [x] Remove redundant score type guard while retaining score-range validation.
- [x] Run export and submission tests plus PHPStan.

### Task 4: Make the quality gate reproducible

**Files:**
- Modify: application/composer.json

- [x] Set PHPStan’s command memory limit to 512 MB.
- [x] Run composer test using PHP 8.5.
- [x] Run npm run build and inspect the working-tree diff.
