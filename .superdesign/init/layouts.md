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

## Source: `application/resources/views/layouts/app.blade.php`

```blade
<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
```

## Source: `application/resources/views/layouts/app/sidebar.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Ikhtisar')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Pengumpulan Data')" class="grid">
                    <flux:sidebar.item icon="adjustments-horizontal" :href="route('admin.study-settings')" :current="request()->routeIs('admin.study-settings')" wire:navigate>
                        {{ __('Pengaturan Studi') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('admin.responses')" :current="request()->routeIs('admin.responses')" wire:navigate>
                        {{ __('Respons') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-chart-bar" :href="route('admin.reports')" :current="request()->routeIs('admin.reports')" wire:navigate>
                        {{ __('Laporan & Ekspor') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Analisis')" class="grid">
                    <flux:sidebar.item icon="calculator" :href="route('admin.calculations')" :current="request()->routeIs('admin.calculations')" wire:navigate>
                        {{ __('Perhitungan') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="wrench-screwdriver" :href="route('admin.technical-assessments')" :current="request()->routeIs('admin.technical-assessments')" wire:navigate>
                        {{ __('Penilaian Teknis') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Akun')" class="grid">
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.edit', 'security.edit', 'appearance.edit')" wire:navigate>
                        {{ __('Pengaturan Akun') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
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
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

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
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
```

## Source: `application/resources/views/layouts/app/header.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('admin.dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/laravel/livewire-starter-kit"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Documentation')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="book-open-text"
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                        :label="__('Documentation')"
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
```

`layouts/app/header.blade.php` is retained source from the starter kit but is not selected by `layouts.app`; the production admin shell resolves through `layouts.app.sidebar` only.

## Source: `application/resources/views/pages/settings/layout.blade.php`

```blade
<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
```

## Source: `application/resources/views/layouts/auth.blade.php`

```blade
<x-layouts::auth.simple :title="$title ?? null">
    {{ $slot }}
</x-layouts::auth.simple>
```

## Source: `application/resources/views/layouts/survey.blade.php`

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-900">
        <main class="min-h-screen">
            {{ $slot }}
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
```
