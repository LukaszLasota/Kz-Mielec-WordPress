# Pentecostal Church in Mielec — WordPress

Website for [kzmielec.pl](https://kzmielec.pl) — migration from a hardcoded HTML5 Blank theme to a modern Gutenberg-based architecture.

## About

Website of the Pentecostal Church in Mielec, Poland. The current version uses an HTML5 Blank theme with all content hardcoded in PHP templates — nothing is editable from the WordPress admin panel. The goal is full content editability via the Gutenberg editor while preserving the existing design and all URLs (SEO).

### Goals

- All content editable from the WP admin (Gutenberg blocks)
- Design: Cinzel font, black & white color scheme, one-page layout with anchor scrolling
- Preserve all existing URLs
- Modern architecture: OOP PHP, TypeScript, SCSS, Webpack

## Tech Stack

| Layer | Technology |
|-------|------------|
| Theme | `kzmielec` — OOP PHP, PSR-4, Webpack (TypeScript + SCSS) |
| Gutenberg Blocks | `custom-block-package` — `@wordpress/scripts`, ServerSideRender + render.php |
| Custom Post Types | `custom-posts` — CptBuilder (meetings) |
| Local Environment | DDEV on WSL2 |
| Code Quality | PHPStan, PHPCS WordPress Standards, ESLint, TypeScript strict |

## Structure

```
wp-content/
├── themes/kzmielec/               # Theme (build: webpack/)
├── plugins/custom-block-package/  # Gutenberg blocks (build: wp-scripts)
└── plugins/custom-posts/          # CPT meetings
```

See individual READMEs in each directory for details.

## Development

```bash
# Theme
cd wp-content/themes/kzmielec/webpack
npm install && npm run watch

# Block plugin
cd wp-content/plugins/custom-block-package
npm install && npm start

# PHP quality (from theme or plugin directory)
composer install
composer check    # PHPStan + PHPCS
```
