# Custom Block Package

Collection of custom Gutenberg blocks for kzmielec.pl. Each block uses server-side rendering (`render.php`) and registers via `block.json`.

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

| Block | Description |
|-------|-------------|
| **Section Block** | Section container with grid/flex layout options |
| **Custom Accordion** + **Accordion Item** | Accordion with animations and keyboard navigation |
| **Dynamic Images** | Responsive `<picture>` (desktop/tablet/mobile) |
| **Map Block** | Leaflet.js map with lazy-loading (IntersectionObserver) |
| **Image Text** | Image with text overlay and optional link |
| **Meeting List** | Meeting cards from CPT meetings (flipping cards, cached) |
| **PDF Block** | Embedded PDF with download button |
| **Circle Cards** | Universal circle cards with 3 data sources [TODO] |

## Build

```bash
npm install
npm run build    # Production build (wp-scripts)
npm start        # Watch mode
```

## Code Quality

```bash
composer install
composer phpstan    # Static analysis
composer phpcs      # WordPress Coding Standards
composer check      # Both
```
