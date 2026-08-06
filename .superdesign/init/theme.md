# Visual theme

## Foundation

- UI stack: Flux UI + Tailwind CSS 4.
- Typeface: Instrument Sans, weights 400, 500, and 600.
- Base page: white in light mode and zinc-800/zinc-900 in dark mode.
- Sidebar: zinc-50 with zinc-200 border in light mode; zinc-900 with zinc-700 border in dark mode.
- Primary neutral accent: neutral/zinc-800 with white foreground; inverted in dark mode.

## Color tokens

- zinc-50 `#fafafa`
- zinc-100 `#f5f5f5`
- zinc-200 `#e5e5e5`
- zinc-300 `#d4d4d4`
- zinc-400 `#a3a3a3`
- zinc-500 `#737373`
- zinc-600 `#525252`
- zinc-700 `#404040`
- zinc-800 `#262626`
- zinc-900 `#171717`
- zinc-950 `#0a0a0a`

Use green only for genuine success/official states, red for errors, amber for warnings, and sky for preview/informational states. Navigation itself should remain neutral and restrained.

## Sidebar styling

- Group headings: 11-12px, medium weight, zinc-500, slight tracking, with clear vertical separation.
- Item labels: 14px, medium weight.
- Icons: 18-20px, outline style.
- Default item: transparent with zinc-600 text.
- Hover: zinc-100 surface and zinc-900 text.
- Current: zinc-200 or a restrained dark neutral surface with strong text; do not rely on color alone.
- Focus: visible two-pixel ring with sufficient contrast.
- Corners: rounded-lg, consistent with existing Flux controls.

## Tone

This is a focused Indonesian research operations application, not a generic SaaS dashboard. Prefer clarity, dense-but-calm workflow grouping, and direct Indonesian labels. Avoid decorative gradients, oversized branding, disabled teasers, and unrelated starter-kit links.

## Source: `application/resources/css/app.css`

```css
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css';

@source '../views';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../vendor/livewire/flux-pro/stubs/**/*.blade.php';
@source '../../vendor/livewire/flux/stubs/**/*.blade.php';

@custom-variant dark (&:where(.dark, .dark *));

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';

    --color-zinc-50: #fafafa;
    --color-zinc-100: #f5f5f5;
    --color-zinc-200: #e5e5e5;
    --color-zinc-300: #d4d4d4;
    --color-zinc-400: #a3a3a3;
    --color-zinc-500: #737373;
    --color-zinc-600: #525252;
    --color-zinc-700: #404040;
    --color-zinc-800: #262626;
    --color-zinc-900: #171717;
    --color-zinc-950: #0a0a0a;

    --color-accent: var(--color-neutral-800);
    --color-accent-content: var(--color-neutral-800);
    --color-accent-foreground: var(--color-white);
}

@layer theme {
    .dark {
        --color-accent: var(--color-white);
        --color-accent-content: var(--color-white);
        --color-accent-foreground: var(--color-neutral-800);
    }
}

@layer base {

    *,
    ::after,
    ::before,
    ::backdrop,
    ::file-selector-button {
        border-color: var(--color-gray-200, currentColor);
    }
}

[data-flux-field]:not(ui-radio, ui-checkbox) {
    @apply grid gap-2;
}

[data-flux-label] {
    @apply  !mb-0 !leading-tight;
}

input:focus[data-flux-control],
textarea:focus[data-flux-control],
select:focus[data-flux-control] {
    @apply outline-hidden ring-2 ring-accent ring-offset-2 ring-offset-accent-foreground;
}

/* \[:where(&)\]:size-4 {
    @apply size-4;
} */
```

## Source: `application/vite.config.js`

```js
import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```
