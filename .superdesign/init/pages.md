# Existing pages

## Administrator application

- Dashboard (`admin.dashboard`): research progress, respondent/evaluation totals, per-module status, and quick links.
- Pengaturan Studi (`admin.study-settings`): period configuration, consent text, quality rules, readiness evidence, verification, and activation controls.
- Respons (`admin.responses`): response-quality review, flags, reviewer decisions, and exclusions.
- Laporan & Ekspor (`admin.reports`): aggregate research report, official/preview run metadata, UEQ visualizations, SAW comparison, sensitivity matrix, CSV and XLSX exports.
- Perhitungan (`admin.calculations`): UEQ and SAW calculations, rankings, sensitivity analysis, expert judgment, and operational backlog.
- Penilaian Teknis (`admin.technical-assessments`): technical informants, criteria weights, and consensus.

## Account application

- Profile (`profile.edit`): administrator identity and profile data.
- Security (`security.edit`): password, passkeys, TOTP, and recovery codes.
- Appearance (`appearance.edit`): display preferences.

## Public survey flow

Consent, eligibility, unit selection, UEQ wizard, and completion pages use a separate public survey layout. They should not appear in the authenticated administrator sidebar.

## Current shell problem

The current sidebar exposes only Dashboard and Respons under a generic `Platform` group, while implemented admin pages are discoverable mainly through dashboard buttons. It also contains Repository and Documentation starter-kit links. The redesign must expose all working application pages, organize them by survey workflow, and remove both starter links.
