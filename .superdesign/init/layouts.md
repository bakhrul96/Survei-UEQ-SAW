# Layouts and responsive behavior

## Authenticated admin layout

The default authenticated layout is `x-layouts::app.sidebar`. It contains:

1. A sticky left sidebar on desktop (`lg` and above).
2. A compact mobile header with a hamburger trigger and profile dropdown below `lg`.
3. A main content region rendered through `flux:main`.
4. A persisted Flux toast group.

Desktop sidebar width should remain close to the current Flux default, approximately 256px. Content pages center themselves independently with widths from `max-w-4xl` to `max-w-7xl` and vertical spacing of 24px.

## Sidebar information architecture

The approved desktop and mobile navigation uses four workflow groups:

- Ikhtisar: Dashboard
- Pengumpulan Data: Pengaturan Studi, Respons, Laporan & Ekspor
- Analisis: Perhitungan, Penilaian Teknis
- Akun: Pengaturan Akun

Group headings are small, quiet labels. Items are full-width rows with icon, label, hover treatment, keyboard focus, and a clear current state. Only one current page is highlighted.

## Responsive contract

- Desktop: sidebar stays visible and sticky; page content uses remaining width.
- Mobile at 360px: sidebar is initially closed, opens from the hamburger, and contains every desktop destination in the same order.
- Avoid horizontal scrolling in navigation.
- Touch targets should be at least 40px high.
- Account identity and logout remain accessible on both breakpoints.

## Settings layout

The global `Pengaturan Akun` item opens Profile. Inside the settings page, the existing local navigation continues to switch between Profile, Security, and Appearance. These three local tabs are not duplicated in the global sidebar.
