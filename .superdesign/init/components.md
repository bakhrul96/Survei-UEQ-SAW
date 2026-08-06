# Existing UI components

The authenticated application shell is implemented in `application/resources/views/layouts/app/sidebar.blade.php` with Laravel Blade, Livewire navigation, Tailwind CSS, and Flux UI primitives.

## Sidebar shell

- `flux:sidebar sticky collapsible="mobile"` provides a permanently visible desktop rail and a drawer on small screens.
- Surface: `bg-zinc-50`, `border-zinc-200`; dark surface: `bg-zinc-900`, `border-zinc-700`.
- Header contains `x-app-logo` linked to `admin.dashboard` and a mobile-only collapse control.
- Primary navigation is expressed with `flux:sidebar.nav`, `flux:sidebar.group`, and `flux:sidebar.item`.
- Each item uses `wire:navigate`, a Heroicon-compatible Flux icon name, and a `current` route expression.
- `flux:spacer` pushes the desktop profile control to the bottom.
- `x-desktop-user-menu` displays the signed-in administrator and exposes account settings and logout.

## Mobile shell

- A `flux:header` is visible below the `lg` breakpoint.
- `flux:sidebar.toggle` opens the sidebar drawer.
- A profile dropdown shows user identity, settings, and logout.
- Navigation content should be identical to desktop; do not create a separate set of destinations.

## Existing page components

- Admin pages use `flux:heading`, `flux:text`, `flux:card`, `flux:button`, forms, callouts, and responsive tables.
- Settings have a secondary local `flux:navlist` for Profile, Security, and Appearance.
- Main content is rendered inside `flux:main` by `application/resources/views/layouts/app.blade.php`.

## Navigation constraints

- Preserve Flux primitives in production implementation.
- Use these verified icons: `home`, `adjustments-horizontal`, `clipboard-document-check`, `document-chart-bar`, `calculator`, `wrench-screwdriver`, `cog-6-tooth`.
- No disabled, placeholder, repository, framework documentation, or external starter-kit links.
- Keep logout in the profile menu rather than the main survey navigation.

## Source: `application/resources/views/components/app-logo.blade.php`

```blade
@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
```

## Source: `application/resources/views/components/desktop-user-menu.blade.php`

```blade
<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        :name="auth()->user()->name"
        :initials="auth()->user()->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
    />

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
            />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>
        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                {{ __('Settings') }}
            </flux:menu.item>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                    data-test="logout-button"
                >
                    {{ __('Log out') }}
                </flux:menu.item>
            </form>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
```
