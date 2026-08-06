# Admin Sidebar Navigation Design

**Date:** 2026-08-06

## Context

All authenticated admin pages already render through the Flux sidebar layout,
but the primary navigation exposes only Dashboard and Respons. Other working
pages require direct URLs, while two starter-kit links lead outside the survey
application. This makes operational UAT and routine study administration harder
than necessary.

## Goal

Provide one complete, workflow-oriented sidebar for every working admin page.
The navigation must remain visible on desktop, use the existing mobile drawer,
identify the current area, and contain only application functions.

## Non-goals

- No route, authorization, database, or business-process changes.
- No new admin pages or disabled placeholders.
- No redesign of page content, branding, or the account settings subnavigation.
- No direct sidebar links to period-specific CSV or XLSX download endpoints;
  those actions remain inside Laporan & Ekspor.

## Information Architecture

The sidebar uses four workflow groups in this order:

1. **Ikhtisar**
   - Dashboard → `admin.dashboard`
2. **Pengumpulan Data**
   - Pengaturan Studi → `admin.study-settings`
   - Respons → `admin.responses`
   - Laporan & Ekspor → `admin.reports`
3. **Analisis**
   - Perhitungan → `admin.calculations`
   - Penilaian Teknis → `admin.technical-assessments`
4. **Akun**
   - Pengaturan Akun → `profile.edit`

Pengaturan Akun is the single entry point for the existing Profile, Security,
and Appearance subnavigation. The Repository and Documentation starter-kit
links are removed.

## Component and Layout Behavior

The existing `resources/views/layouts/app/sidebar.blade.php` remains the single
navigation source. The implementation continues using Flux `sidebar.nav`,
`sidebar.group`, and `sidebar.item` primitives instead of introducing a menu
configuration service or duplicating desktop and mobile markup.

On desktop, the sidebar remains sticky and visible. Below the large breakpoint,
the existing hamburger button opens the same sidebar as a drawer. Group order,
labels, URLs, and active states are therefore identical at every viewport. The
authenticated user menu and logout action remain at the bottom.

## Active State

Each item resolves its URL with a named Laravel route and derives its current
state from `request()->routeIs(...)`.

- The six admin page items match their exact named routes.
- Pengaturan Akun is current for `profile.edit`, `security.edit`, and
  `appearance.edit` so the account area remains identifiable throughout its
  existing subnavigation.
- No item uses `#`, a disabled state, or a URL literal for an internal page.

Using named routes makes missing or renamed routes fail during render and test
execution rather than silently producing broken navigation.

## Accessibility

- Every destination has a visible Indonesian text label; icons are supplemental.
- Flux navigation primitives preserve navigation, grouping, current-item, and
  keyboard-focus semantics.
- The mobile hamburger and drawer remain keyboard operable.
- Active state is communicated through the component current state rather than
  color alone.
- No external starter-kit navigation competes with the application tasks.

## Error Handling

The sidebar contains no data fetches or recoverable runtime states. Laravel
named-route resolution is deliberately fail-fast. Authentication, verified
email, and confirmed 2FA continue to be enforced by the existing route
middleware before an admin page renders.

## Testing

1. Add a focused feature test using one email-verified, 2FA-confirmed admin.
2. Render an authenticated admin page and assert all group labels, destination
   labels, and named-route URLs are present.
3. Assert Repository and Documentation are absent.
4. Verify current-state output for a representative admin route and for all
   three account settings routes.
5. Add or extend a browser smoke test to verify the desktop sidebar and the
   360-pixel hamburger/drawer path.
6. Run the focused tests, `composer test`, `npm run build`, and the Release One
   browser gates before refreshing Task 9 operational evidence.

## Acceptance Criteria

- Every working admin area is reachable through the sidebar or the existing
  account settings subnavigation.
- The four approved workflow groups appear in the approved order.
- Desktop and mobile expose the same destinations.
- Current state is correct for admin pages and the entire account settings area.
- Repository and Documentation do not appear.
- Keyboard operation and visible focus remain intact.
- No application route, authorization rule, database schema, or business logic
  changes as part of this feature.
- The focused navigation tests, repository test suite, production build, and
  browser gates all exit successfully.
