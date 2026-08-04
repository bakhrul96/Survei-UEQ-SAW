# UEQ SAW Rilis 1

Operational activation, backup, restore, daily export, and safe rollback
instructions are in [the Rilis 1 runbook](docs/release-1-runbook.md).

Before activating a period, complete every pre-activation check in that
runbook. The release gate stays closed until Pest, browser, Pint, Vite, MySQL
migration, backup/restore, and manual mobile UAT evidence are recorded.
