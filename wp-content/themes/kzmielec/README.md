# Church — Custom WordPress Theme

Custom WordPress theme for a parish website. Built with OOP PHP architecture, webpack build pipeline (TypeScript, SCSS), block patterns, and performance optimizations.

## Theme Info

- **Version:** 1.0
- **Author:** Lukasz Lasota
- **Requires PHP:** 8.0+
- **Requires WordPress:** 5.9+
- **License:** GPL-2.0-or-later

## Architecture

The theme uses a component-based PHP architecture with PSR-4 autoloading (Composer). All components are initialized in `App/Theme.php` with separate frontend and admin stacks.

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
│   ├── Menu.php                  # Menu registration + ACF icon support
│   └── Rewrite.php               # Pagination base (Polish)
├── Core/
│   ├── PerformanceOptimizer.php  # Forminator defer, reCAPTCHA lazy-load, emoji removal
│   ├── PatternAssets.php         # Smart per-pattern CSS/JS loading
│   ├── GroupLinkSupport.php      # Adds link URL to Group block
│   └── SvgSupport.php           # SVG upload with sanitization (admin only)
├── Admin/
│   └── LogoSettings.php          # Custom logo settings page
└── Widgets/
    └── RegisterWidgets.php       # Footer widget areas (3 zones)
```

### Key Components

**RegisterAssets** — Loads `.min` or unminified assets based on `wp_get_environment_type()`. Production loads `frontend.min.js` + `frontend.min.css`, development loads `frontend.js` + `frontend.css`. Versions are derived from file modification time.

**PerformanceOptimizer** — Moves Forminator scripts to footer, lazy-loads reCAPTCHA via IntersectionObserver (600px rootMargin), disables WordPress emoji detection, excludes `fetchpriority="high"` images from EWWW lazy-loading.

**PatternAssets** — Detects which block patterns are used on the current page (via `parse_blocks()` + regex fallback) and enqueues only their CSS/JS. In the block editor, all pattern assets are loaded for preview.

**GroupLinkSupport** — Adds a "Link URL" inspector control to the core Group block. Wraps the group content in an `<a>` tag using DOMDocument on render.

**SvgSupport** — Allows SVG uploads for admins only. Sanitizes uploaded files using `enshrined/svg-sanitize` to strip malicious scripts.

**Setup** — Registers theme supports: post thumbnails, HTML5 markup, post formats (image, video, quote, gallery, aside), responsive embeds, custom backgrounds, wide alignment, block templates, editor styles, footer widgets (3 areas). Custom image size: `blog-card` (600x400).

**Menu** — Registers 4 menu locations: `primary`, `footer-one`, `footer-two`, `footer-three`. Adds ACF `icon_logo` field support to menu items.

## Build System

Webpack with separate dev/prod configs. Source files in `webpack/src/`, output to `assets/`.

### Entry Points

| Entry | Source | Output |
|-------|--------|--------|
| frontend | `frontend.ts` | `frontend.js` / `frontend.min.js` + CSS |
| backend | `backend.ts` | `backend.css` / `backend.min.css` |
| editor | `editor.ts` | `editor.css` |
| print | `print.ts` | `print.css` / `print.min.css` |
| patterns/* | Auto-discovered from `src/patterns/*/` | `patterns/[name]-style.css` + `patterns/[name]-script.js` |

### Commands

```bash
cd webpack
npm run dev      # Development build (source maps, unminified)
npm run watch    # Watch mode (auto-rebuild on save)
npm run prod     # Production build (minified, tree-shaken)
```

### Loaders

- **TypeScript** — `ts-loader` (ES6 target, strict mode)
- **JavaScript** — `babel-loader` with `@babel/preset-env`
- **SCSS** — `sass-loader` → `css-loader` → `MiniCssExtractPlugin`
- **Images** — asset module → `media/[name][ext]`
- **Fonts** — asset module → `webfont/[name][ext]`

### Production Optimizations

- `TerserPlugin` for JS minification
- `CssMinimizerPlugin` for CSS minification
- Tree-shaking enabled
- Dynamic import for masonry (code splitting, loaded only on blog pages)

## SCSS Architecture

```
sass/
├── base/
│   ├── _fonts.scss          # @font-face (Raleway 400, 700 — local woff2)
│   ├── _normalize.scss
│   └── _typography.scss
├── abstracts/
│   ├── _variables.scss
│   ├── _functions.scss
│   └── _mixins.scss
├── apps/
│   ├── _main.scss           # Global styles
│   ├── _menu.scss           # Hamburger menu
│   ├── _footer.scss
│   ├── _contact.scss
│   └── _wcag.scss           # Accessibility styles
└── pages/
    ├── _page.scss
    ├── _page-hero.scss
    └── _single-post.scss
```

BEM methodology. Font: Raleway (self-hosted, latin-ext subset).

## Block Patterns

| Pattern | Category | Description |
|---------|----------|-------------|
| `hello-section-main-page` | featured, columns | Hero section with gradient background, cross/logo image, welcome text |
| `contact-section` | featured, text | Two-column layout: Forminator contact form + church details (address, phone, email, bank) |

Each pattern has its own SCSS + TypeScript in `webpack/src/patterns/[name]/`.

## Template Hierarchy

```
front-page.php    # Home page
archive.php       # Archive/category pages
index.php         # Blog listing (fallback)
single.php        # Single post
page.php          # Regular pages
page-hero.php     # Pages with full-width hero
header.php
footer.php
```

### Template Parts

- `content-posts.php` — Blog post card (thumbnail, title, excerpt 20 words, masonry grid)

## Block Editor (theme.json)

- **Font:** Raleway (400, 700) — local woff2, `font-display: swap`
- **Colors:** Base (#f9f9f9), White (#ffffff), Site Color (#008089)
- **Gradients:** Teal → dark blue (3 directions)
- **Font sizes:** Small (0.9rem) → XX-Large (2.5–3.27rem, fluid)
- **Content width:** CSS custom properties for 4 breakpoints (90%/80%/70%/60%)
- **Features:** Appearance tools, fluid typography, root padding aware alignments

## Dependencies

### PHP (Composer)

- `enshrined/svg-sanitize` ^0.22.0

### PHP Dev

- `phpstan/phpstan` ^1.10 (level 8)
- `szepeviktor/phpstan-wordpress` ^1.3
- `squizlabs/php_codesniffer` ^3.7
- `wp-coding-standards/wpcs` ^3.0

### Node.js (webpack/package.json)

- `@babel/core` ^7.26.0
- TypeScript ^5.1.6, Webpack ^5.89.0, Sass ^1.70.0, ESLint ^8.36.0

## Code Quality

```bash
composer phpstan    # PHPStan level 8
composer phpcs      # WordPress Coding Standards
composer phpcbf     # Auto-fix
composer check      # Both
```

## ACF Integration

Field groups exported to `acf-json/`:
- Menu item icon support (`icon_logo`)
- Group link URLs (`metadata/linkUrl`)
