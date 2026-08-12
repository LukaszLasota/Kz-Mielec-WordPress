# Custom Block Package

Collection of custom Gutenberg blocks for kzmielec.pl. Every block registers via
`block.json`, and all but one render server-side from a `render.php` inside the
plugin — so their markup does not depend on theme code.

See the [project README](../../../README.md) for how this plugin relates to the
theme and the other three.

## Plugin Info

- **Version:** 1.0.0
- **Author:** Łukasz Lasota
- **Requires PHP:** 7.2+
- **Requires WordPress:** 5.9+
- **License:** GPL-2.0-or-later

## Architecture

```
custom-block-package/
├── index.php                  # Plugin entry: autoloader, RegisterBlocks, AssetsManager
├── app/
│   ├── Autoloader.php         # PSR-4 autoloader (CustomBlockPackage namespace)
│   ├── Blocks/
│   │   └── RegisterBlocks.php # Auto-discovers blocks from build/blocks/
│   ├── Assets/
│   │   └── AssetsManager.php  # Leaflet CSS + Glide.js assets
│   └── Cache/
│       └── BlockCache.php     # Transient cache (30 min TTL, auto-flush on save)
├── src/blocks/                # Block source (JS, SCSS, PHP)
├── build/                     # Compiled (wp-scripts)
└── webpack.config.js          # Custom config extending wp-scripts
```

## Blocks

Eleven blocks. **Ten render server-side** from a `render.php` inside the plugin,
so their markup does not depend on theme code; only `pdf-block` is static.

| Block | Description |
|-------|-------------|
| `section-block` | Section container with grid/flex layout options |
| `custom-accordion` + `accordion-item` | Accordion with animations and keyboard navigation |
| `dynamic-images` | Responsive `<picture>` (desktop/tablet/mobile) |
| `map-block` | Leaflet.js map, lazy-loaded via IntersectionObserver; coordinates come from the theme's shared contact option |
| `image-text` | Image with text overlay and optional link |
| `navigable-tiles` | Tiles built from the `meetings` CPT or the belief pages, narrowed to the language of the post being rendered |
| `facebook-feed` | Page feed, cached, in the language of the post |
| `custom-svg` | Inline SVG with sanitisation |
| `scroll-arrow` | Anchor navigation arrow |
| `pdf-block` | Embedded PDF with download button (static) |

**Blocks that fetch content must narrow it to the language of the rendered
post.** `Services/NavigableTilesService::current_language()` takes the post's
language first and the request's second. Relying on the request is what the
original code did, and it is invisible on the front end — Polylang narrows the
query itself there. The editor renders blocks through a REST route with no
language context, so it received all four languages at once: 12 meetings instead
of 3.

## Build

```bash
npm install
npm run build    # Production build (wp-scripts)
npm start        # Watch mode
```

## Code Quality

```bash
composer install
composer phpstan    # PHPStan level 8
composer phpcs      # WordPress Coding Standards
composer check      # Both
```
