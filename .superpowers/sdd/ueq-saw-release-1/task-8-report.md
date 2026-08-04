# Task 8 Report

## Implementation

- Added a respondent-keyed atomic `RateLimiter::increment` before the
  idempotency lookup in `SubmitSurvey::handle`. It allows ten attempts per
  minute and rejects the eleventh with the specified Indonesian error message.
- Added `SurveyRateLimitTest` for repeated submissions using the same
  idempotency key, proving that those clicks are counted before the original
  submission is returned.
- Added a 360 x 800 Pest browser critical-path test: entry, consent, eligible
  screener, Ibadah-Yu, experience confirmation, all 26 accessible item labels
  at value 4, four steps, and successful completion.
- Added the operational runbook and README link.

## Test-first evidence

`php artisan test tests/Feature/Survey/SurveyRateLimitTest.php` was attempted
before adding the limiter. It exited 1 because `php` is not recognized on the
current PATH, so the expected behavioral red result could not be observed.
The browser test was also authored before application code changes; its
runtime verification remains blocked by the same missing executable.

## Tool results (2026-08-04)

| Command | Exit | Exact result |
| --- | ---: | --- |
| `php artisan test` | 1 | `php` is not recognized as a cmdlet, function, script file, or operable program. |
| `php artisan test tests/Browser/SurveyHappyPathTest.php` | 1 | `php` is not recognized as a cmdlet, function, script file, or operable program. |
| `vendor\bin\pint --test` | 1 | `php` is not recognized as an internal or external command. |
| `npm run build` | 1 | `npm` is not recognized as a cmdlet, function, script file, or operable program. |
| `mysql --version` | 1 | `mysql` is not recognized as a cmdlet, function, script file, or operable program. |
| `mysqldump --version` | 1 | `mysqldump` is not recognized as a cmdlet, function, script file, or operable program. |

No environment values, passwords, or database credentials were printed.

## MySQL, backup, restore, and UAT

MySQL database creation, migration, seeding, administrator creation, backup,
restore, and row-count collection could not be run because both the PHP and
MySQL command-line executables are unavailable. Manual mobile UAT could not
be run because the browser test runtime cannot start without PHP. The runbook
records these blockers and the exact remaining UAT matrix.

## Rilis 1 gate

**Closed.** Pest, browser, Pint, Vite, MySQL migration, backup/restore, and
manual mobile UAT evidence is incomplete. A machine with PHP 8.3+, Node/npm,
MySQL 8 clients/server, and the browser test prerequisites must run the listed
commands and append the resulting evidence before activation.

## Follow-up: critical-path labels

On 2026-08-04, the browser contract and rendered UI were aligned to the
approved strings: `Informasi Penelitian`, `Pilih Modul`, `Berikutnya`, and
`Kirim Penilaian`. The browser test was updated before the views. Its red run
was attempted with `php artisan test tests/Browser/SurveyHappyPathTest.php`,
but PHP remains unavailable on PATH (exit 1: `php` is not recognized), so the
runtime result remains unverified and the release gate remains closed.

## Follow-up: browser Pest bootstrap

On 2026-08-04, `tests/Pest.php` was updated to bind both `Feature` and
`Browser` tests to `Tests\\TestCase` with `RefreshDatabase`. The browser test's
local duplicate `uses(RefreshDatabase::class)` declaration was removed because
the shared Pest configuration now supplies both requirements. Runtime
verification remains blocked: `php artisan test tests/Browser/SurveyHappyPathTest.php`
exits 1 because `php` is not recognized on PATH.

## Follow-up: atomic limiter and restore verification

On 2026-08-04, the limiter was changed from `RateLimiter::attempt` to the
atomic `RateLimiter::increment` return-value pattern: the eleventh hit is
rejected when the returned count exceeds the configured maximum. It remains
before idempotency lookup, so repeated clicks are counted. Production must use
`CACHE_STORE=database`, whose database cache increment supports this atomic
operation. The restore runbook now includes a credential-safe `mysql` query
that checks migration rows, required tables, and counts for
`survey_submissions` and `survey_answers`. MySQL is still unavailable in this
environment, so no restore evidence has been claimed.

## Follow-up: restore privilege separation

On 2026-08-04, the restore runbook was corrected so a DBA/root or designated
backup operator creates `ueq_saw_restore` and grants only
`ueq_saw_restore.*` to a separately provisioned restore operator. The import
and evidence query use that restore operator; the production application user
is not granted database-creation privileges. Credentials remain out of band,
and restore evidence is still unavailable because MySQL is absent.

## Follow-up: guest survey layout and dashboard route

On 2026-08-04, public survey components and the ineligible page were moved to
the new guest-safe `layouts.survey` layout, which includes the shared head,
Vite, Flux toast, and Flux scripts without dashboard routes or authenticated
user access. A regression test now requests survey entry and consent without
authentication. The admin-only layout, dashboard test, and welcome link were
corrected to use the actual `admin.dashboard` route; no unguarded dashboard
alias was added. The regression command is blocked because PHP is unavailable
on PATH, so this result remains runtime-unverified.
