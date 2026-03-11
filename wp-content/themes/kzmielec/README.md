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

Webpack with separate dev/prod configs. Source in `webpack/src/`, output to `assets/`.

```bash
cd webpack
npm install
npm run dev      # Development build (source maps)
npm run watch    # Watch mode
npm run prod     # Production build (minified)
```

### Entry Points

| Entry | Source | Output |
|-------|--------|--------|
| frontend | `frontend.ts` | `frontend.js` + CSS |
| backend | `backend.ts` | `backend.css` |
| editor | `editor.ts` | `editor.css` |
| print | `print.ts` | `print.css` |
| patterns/* | Auto-discovered `src/patterns/*/` | per-pattern CSS/JS |

## SCSS Architecture

```
sass/
├── base/           # Fonts (@font-face), normalize, typography
├── abstracts/      # Variables, functions, mixins
├── apps/           # Main layout, menu, footer, WCAG
└── pages/          # Page, page-hero, single-post
```

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
