# Rilis 1 Runbook

## Pre-activation

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Confirm `APP_URL` exactly matches the approved deployed HTTPS domain.
- Set `SESSION_SECURE_COOKIE=true` and a random `SURVEY_TOKEN_KEY`.
- Set `CACHE_STORE=database`; the database cache store provides the atomic
  increment used by the respondent-keyed submit limiter.
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

Attempted on 2026-08-04 from the release worktree. `mysql --version` and
`mysqldump --version` both exited 1 because their executables are not available
on the current PATH. Consequently no `ueq_saw_restore` database was created,
no backup file was produced, and `survey_submissions` / `survey_answers` row
counts are unavailable. Restore evidence is a release blocker; the activation
gate remains closed until a MySQL 8 operator executes the backup and restore
commands above, records the date and both row counts, and confirms the status
command plus the restore query's migration/table/count results.

## Verification evidence

Attempted on 2026-08-04 without printing environment values or credentials:

| Check | Result | Evidence |
| --- | --- | --- |
| `php artisan test` | Blocked | Exit 1: `php` is not recognized. |
| `php artisan test tests/Browser/SurveyHappyPathTest.php` | Blocked | Exit 1: `php` is not recognized. |
| `vendor\bin\pint --test` | Blocked | Exit 1: underlying `php` is not recognized. |
| `npm run build` | Blocked | Exit 1: `npm` is not recognized. |
| MySQL 8 migration / seed / admin | Blocked | `php` and MySQL clients are unavailable. |

## Manual mobile UAT

No manual mobile UAT was executed on 2026-08-04 because a browser runtime is
not available in this environment. The required UAT remains open: at 360 px,
complete consent and screener; submit one module through four UEQ steps;
verify offline draft recovery, double-click idempotency, the completed-module
disabled state, the three-module rest message, the admin respondent-versus-
evaluation dashboard, and CSV/XLSX privacy columns. Record the device/browser,
date, pass/fail result, and evidence path here. Any failure blocks activation.
