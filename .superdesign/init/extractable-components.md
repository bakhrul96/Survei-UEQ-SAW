# Extractable design components

## AdminSidebar

- Source: `application/resources/views/layouts/app/sidebar.blade.php`
- Role: authenticated layout component shared by every admin and settings page.
- Fixed content: brand, exact workflow group headings, labels, icons, profile identity layout, account menu, logout, desktop/mobile structure, styles, and named destination meanings.
- Variable state: `activeRoute` only.
- Petite-Vue prop: `{"name":"activeRoute","type":"string","defaultValue":"admin.dashboard"}`.
- Production boundary: keep Blade, Flux, named Laravel routes, `request()->routeIs(...)`, and Livewire `wire:navigate`.
- Estimated preview: 256 × 900 pixels for the desktop sidebar; responsive drawer at 360-pixel viewport.

The extracted component must hardcode all seven destinations and must not expose labels, icon names, group membership, styles, or visibility as props. It must contain no Repository, Documentation, disabled, or placeholder item.

## DesktopUserMenu

- Source: `application/resources/views/components/desktop-user-menu.blade.php`
- Role: bottom-of-sidebar authenticated identity menu.
- Fixed content: avatar/initials layout, name/email presentation, settings entry, logout form, Flux dropdown alignment, and icons.
- Variable state: none for the Superdesign component. Runtime identity remains supplied by Laravel authentication in production.
- Estimated preview: 232 × 180 pixels when open.

## Non-extractable primitives

Flux buttons, cards, headings, nav groups, individual sidebar items, inputs, callouts, and tables remain inline primitives. They are not separate Superdesign components because they are simple and already standardized by Flux.
