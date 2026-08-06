# Routes and navigation destinations

All destinations below are implemented and protected by the existing application middleware.

| Group | Label | Route name | Path | Current-state rule |
| --- | --- | --- | --- | --- |
| Ikhtisar | Dashboard | `admin.dashboard` | `/admin/dashboard` | `admin.dashboard` |
| Pengumpulan Data | Pengaturan Studi | `admin.study-settings` | `/admin/study` | `admin.study-settings` |
| Pengumpulan Data | Respons | `admin.responses` | `/admin/responses` | `admin.responses` |
| Pengumpulan Data | Laporan & Ekspor | `admin.reports` | `/admin/reports` | `admin.reports` or `admin.exports.*` |
| Analisis | Perhitungan | `admin.calculations` | `/admin/calculations` | `admin.calculations` |
| Analisis | Penilaian Teknis | `admin.technical-assessments` | `/admin/technical-assessments` | `admin.technical-assessments` |
| Akun | Pengaturan Akun | `profile.edit` | `/settings/profile` | `profile.edit`, `security.edit`, or `appearance.edit` |

Related settings routes:

- `security.edit` -> `/settings/security`
- `appearance.edit` -> `/settings/appearance`

Survey respondent routes under `/s/wong-reang/{period}` are intentionally excluded from the administrator sidebar because they are part of the public respondent flow.

CSV/XLSX routes are actions exposed inside Dashboard and Reports rather than standalone navigation pages.
