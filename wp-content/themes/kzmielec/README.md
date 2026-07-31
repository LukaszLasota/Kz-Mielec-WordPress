# kzmielec — Custom WordPress Theme

WordPress theme for the Pentecostal Church in Mielec ([kzmielec.pl](https://kzmielec.pl)). Based on the Church theme, adapted for kzmielec design (Cinzel font, black & white color scheme, one-page layout with anchor scrolling).

## Theme Info

- **Version:** 1.0
- **Author:** Łukasz Lasota
- **Requires PHP:** 8.0+
- **Requires WordPress:** 5.9+
- **License:** GPL-2.0-or-later

## Architecture

Component-based OOP PHP with PSR-4 autoloading (Composer). All components initialized in `App/Theme.php`.

```
App/
├── Theme.php                     # Bootstrap — initializes all components
├── Interfaces/
│   ├── ActionHookInterface.php
│   ├── FilterHookInterface.php
│   └── ActionHookWithArgsInterface.php
├── BasicTheme/
│   ├── Setup.php                 # Theme supports, image sizes
│   ├── RegisterAssets.php        # Environment-aware asset loading
│   ├── Menu.php                  # Menu registration + icon support
│   └── Rewrite.php               # Pagination base (Polish)
├── Core/
│   ├── PerformanceOptimizer.php  # Script defer, lazy-load, emoji removal
│   ├── PatternAssets.php         # Smart per-pattern CSS/JS loading
│   ├── GroupLinkSupport.php      # Adds link URL to Group block
│   └── SvgSupport.php           # SVG upload with sanitization (admin only)
├── Admin/
│   ├── ThemeSettingsPage.php     # Main settings menu
│   ├── LogoSettings.php          # Custom logo settings
│   └── MasonrySettings.php       # Masonry column settings
└── Widgets/
    └── RegisterWidgets.php       # Footer widget areas (3 zones)
```

## Build System

`@wordpress/scripts` (same toolchain as the plugins) driven by a thin
`webpack.config.js` in the theme root. Source in `src/`, output to `assets/`.

There is **one output file per entry** — no `.min` variant, no dev/prod split —
so PHP always enqueues the same path (`assets/css/frontend.css`). Only the file
contents differ between modes.

```bash
# from the project root, inside DDEV:
ddev theme:build     # one optimized build (minified + source maps)
ddev theme:watch     # watch mode (same filenames, unminified)

# or directly in the theme directory:
npm install
npm run build
npm run start        # watch
```

Run one at a time: `watch` while iterating, `build` to ship. The pre-commit hook
(`.githooks/pre-commit`) runs the build and stages `assets/` automatically when a
commit touches `src/` or the build config.

Note: on WSL2/Docker, watch may miss host-side edits (inotify does not cross the
bind mount; `watchOptions.poll` is set as a mitigation). If a save does not show
up, run `ddev theme:build` — it is instant and reliable.

### Entry Points

| Entry | Source | Output |
|-------|--------|--------|
| frontend | `src/frontend.ts` | `assets/js/frontend.js` + `assets/css/frontend.css` |
| editor | `src/editor.ts` | `assets/css/editor.css` (via `add_editor_style`) |
| print | `src/print.ts` | `assets/css/print.css` |
| patterns/* | auto-discovered `src/patterns/*/{style.scss,script.ts}` | `assets/{css,js}/patterns/<slug>-{style,script}.*` |
| block-styles/* | auto-discovered `src/block-styles/*.scss` | `assets/css/block-styles/<name>.css` |

Fonts are emitted to `assets/webfont/` and images to `assets/media/`, both with
unhashed filenames — `header.php` preloads the font files by exact name.

## SCSS Architecture

```
src/sass/
├── abstracts/
│   ├── _tokens.scss    # SINGLE SOURCE OF TRUTH for design values
│   ├── _variables.scss # thin SCSS aliases over the tokens
│   ├── functions.scss
│   └── mixins.scss
├── base/           # Fonts (@font-face), normalize, typography
├── apps/           # Main layout, menu, footer, WCAG
└── pages/          # Page, page-hero, front-page, belief, search-404, single-post
```

Modules use `@use` (not the deprecated `@import`), so every partial declares its
own dependencies — typically `@use '../abstracts/variables' as *;`.

### Design tokens

`abstracts/_tokens.scss` holds every value used more than once: colours,
font sizes/weights/line-heights, spacing scale, radii, gradients, breakpoints.
A genuinely one-off value may stay inline at its single use site.

Two kinds, split by a hard constraint:

- **Breakpoints are SCSS variables** (`$bp-mobile` 480, `$bp-small` 600,
  `$bp-tablet` 800, `$bp-laptop` 1024, `$bp-desktop` 1400) — CSS media queries
  cannot read custom properties. Use `$bp-tablet + 1px` for a complementary
  `min-width` query.
- **Everything else is a CSS custom property**, emitted once from a mixin
  (`tokens.css-custom-properties`) so it is not duplicated into every entry.
  `frontend.scss` and `editor.scss` each include it — the editor renders in its
  own iframe and needs its own `:root`.

`_variables.scss` maps legacy names onto tokens (`$color-main: var(--color-black)`),
which is why existing call sites still read naturally. This works only because no
style passes these through a SCSS colour function (`rgba()`, `darken()`) — those
need a compile-time value.

## Template Hierarchy

```
front-page.php    # Home page (one-page, 5 sections with anchor scroll)
page.php          # Regular pages
page-hero.php     # Pages with hero banner (Template Name: Strona z banerem)
page-belief.php   # Belief pages (Template Name: Strona wiary) [TODO]
archive.php       # Archives
index.php         # Blog
single.php        # Single post
header.php        # Header with sticky menu
footer.php        # Footer with social media
```

## Code Quality

```bash
composer install
composer phpstan    # PHPStan level 8
composer phpcs      # WordPress Coding Standards
composer check      # Both
```
