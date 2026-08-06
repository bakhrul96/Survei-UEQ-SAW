# UEQ-SAW admin navigation design system

## Purpose

Provide a calm, research-oriented navigation shell that makes every working Release 1 administrator function directly discoverable without introducing future or disabled features.

## Navigation model

The navigation follows the administrator's operating sequence: observe progress, configure and curate collection, analyze results, then manage the account. Use the exact approved group and item labels from `init/layouts.md` and route mapping from `init/routes.md`.

## Component rules

- Use the current application logo and administrator identity controls.
- Preserve Flux's sticky desktop sidebar and collapsible mobile drawer.
- Use outline icons with consistent size and baseline.
- Give current state a filled neutral background, stronger text, and `aria-current="page"` semantics in the prototype.
- Maintain visible focus styles and readable contrast in light and dark modes.
- Keep group labels quiet but legible; group spacing must make the information architecture scannable.
- Do not show Repository, Documentation, public respondent steps, raw export actions, disabled items, badges, or placeholders.

## Prototype scope

The ground-truth draft first reproduces the current sidebar. Exactly one iteration then replaces it with the approved grouped structure. The page content may use a representative Dashboard view to demonstrate proportions; production page content is not being redesigned.

## Acceptance checks

1. Seven working destinations are visible and grouped into four areas.
2. No disabled or unrelated starter-kit items exist.
3. Desktop sidebar remains sticky and visible.
4. At 360px, a hamburger opens a drawer with the same destinations and order.
5. Active, hover, and focus states are distinguishable.
6. Account profile and logout remain available.
