# Theme build migration → @wordpress/scripts

**Date:** 2026-07-30
**Scope:** `wp-content/themes/kzmielec` build tooling only. Plugins untouched.
**Status:** Design approved, pending spec review.

## Problem

The theme uses a hand-rolled webpack setup (`webpack/` subfolder, three configs:
`webpack.common.js` / `webpack.dev.js` / `webpack.prod.js`) that emits **two
output variants**:

- `dev` → `assets/{css,js}/[name].css|js` (unminified, sourcemaps)
- `prod` → `assets/{css,js}/[name].min.css|js` (minified)

`App/BasicTheme/RegisterAssets.php` then picks `.min` or the plain file per
environment (`get_asset_suffix()` → `.min` when `wp_get_environment_type()` is
`production`, else empty). Consequences:

- You must run **both** `dev` and `prod` every change to keep both variants
  fresh; forgetting one leaves a stale file that ships or renders locally.
- The env-based "which file" branch in PHP is fragile and confusing.
- Custom webpack config is extra surface to maintain, diverging from the
  plugins, which already use `@wordpress/scripts`.

## Goal

One toolchain, **one optimal output file, one path PHP always loads**. Keep
SCSS + TypeScript. Extensible later. Simple day-to-day.

Non-goals: touching the plugins, changing any content, markup, or visual
output. The rebuilt assets must be visually 1:1 with today.

## Target design

### Toolchain

Replace the custom webpack with **`@wordpress/scripts` v27** (same major as the
plugins). It provides SCSS + TS + minification + sourcemaps out of the box,
with a thin `webpack.config.js` override to preserve the theme's multi-entry
layout.

### New theme layout

```
themes/kzmielec/
  package.json          # NEW (theme root): wp-scripts build/start + devDeps
  webpack.config.js     # NEW (~15 lines): extends the default wp-scripts config
                        #   - restores multi-entry (frontend/editor/print
                        #     + auto-discovered patterns + block-styles)
                        #   - output.path → assets/ with js/, css/, media/, webfont/
  tsconfig.json         # NEW: TypeScript config
  src/                  # MOVED from webpack/src/ (frontend.ts, editor.ts,
                        #   print.ts, sass/, patterns/, block-styles/, fonts, img)
  assets/               # unchanged location; build writes here; committed
  webpack/              # DELETED (3 configs + old package.json + node_modules)
```

### Single optimal output (the core of the change)

- **`ddev theme:build`** = `wp-scripts build` → always **minified + sourcemaps**,
  writing `assets/css/frontend.css`, `assets/js/frontend.js`,
  `assets/css/print.css`, `assets/css/editor.css`, plus pattern/block-style
  entries. **No `.min` variant. No dev/prod split.**
- **`ddev theme:watch`** = `wp-scripts start` → watch mode, **same filenames**
  (unminified, for local debugging).
- You run **one at a time**, never both. Dev = `watch`. Ship = `build`.

### PHP: drop the suffix branch (THREE loaders, not one)

The `.min` suffix logic is duplicated across **three** asset loaders; all three
must drop it, or they will look for `*.min.*` files the single-output build no
longer produces:

1. **`App/BasicTheme/RegisterAssets.php`** — frontend `frontend.js` /
   `frontend.css`, `print.css`, and admin `backend.css`. Remove the `$suffix`
   property, `get_asset_suffix()`, and every `{$this->suffix}`.
2. **`App/Core/BlockStyles.php`** — block-style CSS
   (`assets/css/block-styles/dynamic-images-banner-hero.css`,
   `heading-section-line.css`). Remove its own `get_asset_suffix()` and the
   `$asset_suffix` interpolation on both paths.
3. **`App/Core/PatternAssets.php`** — pattern CSS/JS
   (`assets/css/patterns/{slug}-style.css`, `assets/js/patterns/{slug}-script.js`).
   Remove its own `get_asset_suffix()` and the `$asset_suffix` interpolation.
   **ALSO** update the runtime source-dir glob: `PatternAssets.php:195` scans
   `"{$theme_dir}/webpack/src/patterns/*"` to discover patterns — this must
   change to `"{$theme_dir}/src/patterns/*"` after the `src/` move, or patterns
   silently stop enqueueing.

Cache-busting stays on `filemtime` in all three (chosen over `.asset.php` to
keep the PHP change minimal; `.asset.php`-based deps/version can be adopted
later).

WordPress always loads `assets/css/frontend.css` (and the fixed pattern/
block-style paths) — one path each, identical locally and in production. Only
the file *contents* differ (watch = unminified, build = minified). Production
serves the committed built file; **no build runs on the server.**

### Pre-commit hook (build safety net)

`core.hooksPath` is already set to `.githooks` (via the theme `postinstall`),
and `.githooks/` currently exists but is empty.

Add `.githooks/pre-commit` that, when staged changes touch the theme `src/`
(or config), runs `ddev theme:build` and re-stages `assets/`, so **the
committed assets are always the optimized build** and you never have to
remember to run `build` before committing.

- Guard: only build when relevant files are staged (skip otherwise for speed).
- If `ddev` is not running, the hook fails loudly with a clear message rather
  than committing stale/unminified assets.

### ddev commands

| Before | After |
|---|---|
| `theme:dev` + `theme:prod` (two builds) | **`theme:build`** (one) |
| `theme:watch` | `theme:watch` (= `wp-scripts start`) |
| `build:all` (plugin + dev + prod) | `build:all` (plugin build + theme build) |
| `watch:all` | `watch:all` (plugin start + theme start) |

## Migration steps (ordered)

1. Add root `package.json`, `webpack.config.js`, `tsconfig.json`; `npm install`.
2. Move `webpack/src/` → `src/`. In `webpack.config.js`, keep font/image output
   at `assets/webfont/` and `assets/media/` so `header.php`'s font `preload`
   and SCSS `url()` references keep resolving unchanged.
3. `wp-scripts build`; verify `assets/css/frontend.css` etc. are produced and
   the site renders 1:1 (check homepage, 404, search, a belief page).
4. Drop the suffix in all three loaders (`RegisterAssets.php`, `BlockStyles.php`,
   `PatternAssets.php`) and fix the `PatternAssets.php` source glob
   `webpack/src/patterns/*` → `src/patterns/*`.
5. Update the ddev command scripts; delete `webpack/`.
6. Add `.githooks/pre-commit`.
7. Final `build` + visual verification + commit.

## Risks / notes

- **CSS-only entries** (block-styles, pattern `style.scss`) emit an empty
  companion `.js`. This already happens today (e.g. `heading-section-line.js`)
  and is harmless.
- **Font/image paths** are the one thing to watch: the config must keep
  emitting to `assets/webfont/` and `assets/media/` or `header.php` preloads and
  SCSS `url()`s break. Verified as a step-3 acceptance check.
- **TypeScript**: wp-scripts handles `.ts` entries given a `tsconfig.json`.
- **Visual parity** is the acceptance bar: rebuilt assets must render identically
  to the current output.

## Acceptance criteria

- One command (`ddev theme:build`) produces the shippable assets; `theme:watch`
  covers local iteration.
- None of the three loaders (`RegisterAssets`, `BlockStyles`, `PatternAssets`)
  has environment/suffix branching; one path per asset.
- `PatternAssets` globs `src/patterns/*` (not `webpack/src/patterns/*`), so
  patterns still enqueue.
- Homepage, 404, search, and a belief page render visually identical to before,
  **including pattern styles (banner-hero, page-belief, archive-meetings) and
  block styles** — verified in the browser, not just by file existence.
- Committing a theme `src/` change auto-produces optimized `assets/` via the
  pre-commit hook.
- `webpack/` folder and the `theme:dev`/`theme:prod` split are gone.
