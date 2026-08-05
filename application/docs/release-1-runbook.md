# Rilis 1 Runbook

## Pre-activation

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Confirm `APP_URL` exactly matches the approved deployed HTTPS domain.
- Set `SESSION_SECURE_COOKIE=true` and a random `SURVEY_TOKEN_KEY`.
- Set `CACHE_STORE=database`; the database cache store provides the atomic
  increment used by the respondent-keyed submit limiter.
- Run `php artisan app:create-admin peneliti@example.test`; confirm the admin
  email is verified and two-factor authentication is configured and confirmed
  before accessing any Admin route.
- Run `php artisan migrate --force` and `php artisan optimize`.
- Verify 13 units, 26 verified items, six verified benchmark rows, dates, target basis, and HTTPS.

## Backup

```powershell
$surveyTimestamp = Get-Date -Format 'yyyyMMdd_HHmm'
mysqldump --single-transaction --routines --triggers -u ueq_saw_app -p ueq_saw > "ueq_saw_$surveyTimestamp.sql"
```

## Restore test

```powershell
$surveyBackupPath = Get-ChildItem -File 'ueq_saw_*.sql' | Sort-Object LastWriteTime -Descending | Select-Object -First 1 -ExpandProperty FullName
# DBA/root or the designated backup operator: create the restore database and grant only this database to the pre-provisioned restore operator.
mysql -u dba_or_backup_operator -p -e "CREATE DATABASE ueq_saw_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON ueq_saw_restore.* TO 'ueq_saw_restore_operator'@'localhost'; FLUSH PRIVILEGES;"
Get-Content -Raw -LiteralPath $surveyBackupPath | mysql -u ueq_saw_restore_operator -p ueq_saw_restore
php artisan migrate:status --database=mysql
mysql -u ueq_saw_restore_operator -p ueq_saw_restore -e "SELECT COUNT(*) AS migration_rows FROM migrations; SHOW TABLES LIKE 'survey_submissions'; SHOW TABLES LIKE 'survey_answers'; SELECT COUNT(*) AS survey_submissions FROM survey_submissions; SELECT COUNT(*) AS survey_answers FROM survey_answers;"
```

Replace `dba_or_backup_operator` only with the DBA/root or backup-operator
name provisioned out of band. That operator must provision the restore
operator credential out of band and must not print it in the shell. Its grant
is scoped only to `ueq_saw_restore.*`; the production `ueq_saw_app` account
retains least privilege on the production database. The final `mysql` query
targets `ueq_saw_restore`; `migrate:status` checks the configured application
database separately.

## Daily operation

- Check per-module counts and server error log.
- Download raw XLSX at the end of each collection day.
- Do not modify locked instrument, targets, or benchmark rows.

## Close and rollback

- In Admin > Pengaturan Studi, use **Tutup periode** to change an active period
  to closed before maintenance that affects submissions.
- Roll back only the latest application release; never roll back a migration that would drop collected responses.

## Bukti restore

Executed on 2026-08-05 against the local MySQL 8 instance. The backup
`storage/app/backups/ueq_saw_20260805_111402_uat.sql` was restored into the
dedicated `ueq_saw_restore` database after recreating that database. The
restore verification query returned: 15 migration rows, 1 evaluation period,
13 evaluation units, 26 UEQ items, 6 benchmarks, 3 survey submissions, and
78 survey answers. The backup and restored schema/data checks completed
without error; no credentials were recorded in this document.

## Verification evidence

Executed on 2026-08-05 without printing environment values or credentials:

| Check | Result | Evidence |
| --- | --- | --- |
| Focused Release 1 feature tests | Pass | 19 tests / 43 assertions: wizard route, submission/idempotency, limiter, chooser, dashboard, and XLSX export. |
| MySQL 8 migration and seed | Pass | 15 migrations ran; the seeded runtime contains 1 period, 13 units, 26 items, and 6 benchmarks. |
| Pest Browser critical-path command | Pass | `php artisan test tests/Browser/SurveyHappyPathTest.php`: 1 test / 8 assertions in 3.8 seconds at 360 x 800. The test now uses visible custom checkbox controls and explicit label/input associations for UEQ radios. |
| Pest Browser offline draft recovery | Pass | `php artisan test tests/Browser/OfflineDraftTest.php`: 1 test / 6 assertions in 3.6 seconds. It saves an answer locally, triggers the browser offline event, verifies the notice, reloads the same session, and verifies the selected answer is restored. |
| Full application checks | Pass | `composer test`: Pint passed, PHPStan reported 0 errors, and Pest reported 82 tests / 233 assertions. |
| Production asset build | Pass with optional-package warning | `npm run build` completed successfully. Vite noted that `fontaine` is optional for optimized font fallbacks. |

## Manual mobile UAT

Executed on 2026-08-05 in the Codex in-app browser at a 360 x 800 viewport
against `http://127.0.0.1:8000`:

| Check | Result | Evidence |
| --- | --- | --- |
| Consent and eligible screener | Pass | Reached the module chooser after accepting consent, age 20, Indramayu residency, and Wong Reang usage. |
| Four-step UEQ completion | Pass | Submitted Ibadah-Yu, Info-Yu, and Dumas-Yu; each submission contains 26 raw responses. |
| Completed module state | Pass | Ibadah-Yu displayed `Sudah dinilai` and was disabled after submission. |
| Rest recommendation | Pass | The complete page displayed the rest recommendation after the third module. |
| Duplicate/idempotency and export privacy | Pass (automated) | Focused feature suite covers repeat idempotency and XLSX headers excluding `token_hash`. |
| Connection interruption / local draft recovery | Pass (automated browser UAT) | `tests/Browser/OfflineDraftTest.php` covers local draft storage, a browser offline event, visible interruption status, and recovery on reload. |
| Admin dashboard in browser | Blocked by administrator enrollment | The signed-in Administrator session was inspected at `/settings/security`: 2FA was not enabled. `/admin/dashboard` correctly redirects unenrolled users to Security settings through `admin.2fa`. Do not bypass this control or reset the password; enable and confirm TOTP 2FA in the administrator's authenticator, then repeat dashboard/export UI UAT. |

The browser critical-path and offline-draft blockers are closed. The only
remaining release-gate action is operational: enroll and confirm TOTP 2FA for
the real administrator before completing dashboard/export browser UAT.
