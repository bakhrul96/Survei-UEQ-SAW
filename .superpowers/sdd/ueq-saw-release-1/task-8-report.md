# Task 8 Report

## Implementation

- Added a respondent-keyed `RateLimiter::attempt` before the idempotency
  lookup in `SubmitSurvey::handle`. It allows ten attempts per minute and
  rejects the eleventh with the specified Indonesian error message.
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
