# Extractable design components

## AppSidebar

Reusable authenticated shell component with brand, workflow navigation, spacer, and administrator profile. Variants:

- Desktop sticky rail.
- Mobile drawer controlled by the existing header hamburger.

## SidebarGroup

Accepts a concise heading and one or more `SidebarItem` children. It creates consistent inter-group spacing and semantic grouping.

## SidebarItem

Properties: label, destination, icon, current state. States: default, hover, keyboard focus, current. Items are never disabled in this release.

## UserProfileMenu

Existing desktop/mobile account control. Shows administrator name/email and contains settings plus logout. It remains separate from workflow navigation, while `Pengaturan Akun` provides a direct global route to the settings area.

## SettingsSubnavigation

Existing local navigation containing Profile, Security, and Appearance. Retain it inside settings pages; global sidebar should not expand into nested account items.

## Design extraction boundary

The Superdesign prototype may express components in Petite-Vue for interaction, but production implementation must continue using Blade, Flux, Livewire `wire:navigate`, named Laravel routes, and the existing authentication/profile controls.
