# Navigable Tiles — Refactor Design (Modern Hybrid)

**Date:** 2026-05-26
**Scope:** Refactor existing `navigable-tiles` block to modern standards while preserving the current working visual output.
**Approach chosen:** B — Modern hybrid (modern CSS features + `@supports` fallbacks).

## Context

The block currently renders meetings (CPT) and beliefs (Pages) as circular tiles. After a long debugging session the visual is correct, achieved by:

- Cropping base PNGs to square `400×400` (removed source padding → eliminated CSS magic numbers).
- Container `aspect-ratio: 1/1` + `border-radius: 50%` + `overflow: hidden` → whole tile is a circle.
- Black overlay as `inset: 0` element (no magic numbers).
- Hover icon centered `50%/50%`, fades out on hover.

The CSS/PHP still carries legacy structure from a literal 1:1 port of the old `html5blank-stable` theme (`.vb__item` pattern): `<div>` items, `<figure>` wrappers, title `<p>` outside the link, `--black` class named by colour not role.

## Goals

1. Keep the current visual output identical.
2. Modernize CSS (container queries, `:has()`, logical properties) with `@supports` fallbacks.
3. Clean up PHP `render.php` (strict types, helper functions, less inline conditional sprawl).
4. Improve HTML semantics & accessibility (`<ul>/<li>`, drop `<figure>`, ARIA).

## Non-Goals (YAGNI)

- No Service-layer refactor (`NavigableTilesService` already clean).
- No new template-part files (single `render.php` with helpers is enough for ~80 lines).
- No schema.org microdata, Block Variations, or view transitions (deferred — option C).
- No data/DB changes.

## Backup

Before any change: copy `render.php` + `style.scss` into
`src/blocks/navigable-tiles/.backup-pre-refactor/`. (Already done.)

## Architecture

Files stay in place. Only `render.php` and `style.scss` change.

```
src/blocks/navigable-tiles/
├── .backup-pre-refactor/   ← snapshot (render.php, style.scss)
├── block.json              ← unchanged
├── index.js + edit.js      ← unchanged
├── render.php              ← refactored (strict types + helpers + semantic HTML)
└── style.scss              ← refactored (sections + container queries + :has() + fallbacks)
```

## SCSS Design

Sections, in order:

1. **Config** — all tunable values as CSS custom properties:
   `--cbp-tile-gap`, `--cbp-tile-margin-block`, `--cbp-tile-padding-inline`,
   `--cbp-image-ratio` (default `1`), `--cbp-overlay-bg`, `--cbp-overlay-opacity`,
   `--cbp-icon-width`, `--cbp-title-size`, `--cbp-title-leading`, `--cbp-transition`.
2. **Container-query setup** — `@supports (container-type: inline-size)` enables
   `container-type: inline-size` on `.navigable-tiles__grid`.
3. **Grid** — flex wrap, centered.
4. **Tile** — width via column modifier classes, `margin-block`, `padding-inline`.
5. **Image stack** — square container (`aspect-ratio`), `border-radius: 50%`,
   `overflow: hidden`; base image `object-fit: cover`; overlay `inset: 0`;
   hover icon centered, `--cbp-icon-width`.
6. **Typography** — title + meta, `rem`-based size.
7. **States** — `:focus-visible`, hover (icon fade) driven by
   `.navigable-tiles__item:not(:has(.is-current)):hover`, `:has(.is-current)` styling.
8. **Motion** — transitions gated by `prefers-reduced-motion: reduce`.

Responsiveness: container queries (`@container`) for tile sizing, with a
`@supports not (container-type: inline-size)` block that reproduces the current
`@media` breakpoints as a fallback. Tile width keeps the existing
`has-columns-N` modifier classes.

Key renames: `--black` → `--overlay` (role-based name). Logical properties
(`margin-block`, `padding-inline`) replace physical ones.

## PHP Design

`render.php` gets `declare(strict_types=1)` and file-scoped helper functions:

- `cbp_navigable_tiles_config(array $attributes): array` — parse/validate all
  attributes into one typed array (kills inline ternary sprawl).
- `cbp_navigable_tiles_items(string $data_source): array` — fetch from service.
- `cbp_navigable_tiles_wrapper_attrs(array $config): array` — build wrapper attrs.
- `cbp_navigable_tiles_render_tile(array $item, array $config): void` — render one `<li>`.

Functions are guarded with `function_exists()` to avoid redeclaration. Cache logic
stays removed (already handled by environment-based disabling in `BlockCache`).

## HTML Design

```html
<nav class="wp-block-... has-source-beliefs has-columns-4" aria-label="…">
    <h2 class="navigable-tiles__heading">…</h2>
    <ul class="navigable-tiles__grid" role="list">
        <li class="navigable-tiles__item[ is-current]">
            <a href="…" class="navigable-tiles__link"[ aria-current="page"]>
                <span class="navigable-tiles__image" aria-hidden="true">
                    <img class="navigable-tiles__image--one" alt="" loading="lazy">
                    <span class="navigable-tiles__image--overlay"></span>
                    <img class="navigable-tiles__image--two" alt="" loading="lazy">
                </span>
                <span class="navigable-tiles__title">title
                    [<span class="screen-reader-text"> (aktualna strona)</span>]
                </span>
                [<span class="navigable-tiles__meta">day_hour</span>]
            </a>
        </li>
        …
    </ul>
</nav>
```

Changes vs current: `<div>`→`<ul>/<li>`; drop `<figure>`; `<span>` image stack;
title inside the link; `--black`→`--overlay`; `role="list"` (Safari list-role bug
with `list-style:none`); `aria-hidden` on decorative image stack; `aria-label` on nav.

Overlay rendered only for `beliefs`. Hover image rendered only when not current and
a hover image exists. Meta (`day_hour`) rendered only for meetings when enabled.

## Error Handling

- No items → `return` (render nothing).
- Missing base/hover image URL → skip that `<img>` (current behavior).
- Invalid attributes → clamped/defaulted in `cbp_navigable_tiles_config()`.

## Testing

1. Visual diff vs current site (home page: both sections; a belief subpage: "you are here").
2. PHPCS WordPress Standards on `render.php`.
3. PHPStan L8 (if config covers the block).
4. Manual A11y pass: keyboard focus ring, screen-reader list announcement, `aria-current`.
5. Browser fallback check: confirm `@supports not (container-type)` path renders in a
   browser without container-query support (or via DevTools emulation).

## Rollback

Restore from `.backup-pre-refactor/render.php` and `.backup-pre-refactor/style.scss`,
rebuild (`ddev plugin:build`).