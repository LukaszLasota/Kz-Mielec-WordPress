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
├── index.php                     # Plugin entry: autoloader, RegisterBlocks, AssetsManager
├── app/
│   ├── Autoloader.php            # PSR-4 autoloader (CustomBlockPackage namespace)
│   ├── Blocks/
│   │   └── RegisterBlocks.php    # Auto-discovers blocks from build/blocks/
│   ├── Assets/
│   │   └── AssetsManager.php     # Leaflet CSS + Glide.js assets
│   ├── Cache/
│   │   └── BlockCache.php        # Transient cache (30 min TTL, auto-flush on save)
│   ├── Admin/
│   │   ├── FacebookSettings.php  # Settings screen, dashboard widget, error notice
│   │   └── MeetingMeta.php       # Meeting fields (owned here, the post type is not)
│   ├── Services/
│   │   ├── FacebookFeedService.php   # Graph API client, cache, fallback, mock data
│   │   └── NavigableTilesService.php # Tile data, narrowed to the post's language
│   ├── Cron/
│   │   └── FacebookFeedCron.php  # Background refresh, interval from the TTL option
│   └── Rest/
│       └── FacebookFeedController.php # /custom-block-package/v1/facebook-feed
├── src/blocks/                   # Block source (JS, SCSS, PHP)
├── build/                        # Compiled (wp-scripts)
└── webpack.config.js             # Custom config extending wp-scripts
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

## The Facebook feed

The `facebook-feed` block replaced the Facebook Page Plugin iframe, which shipped
around 350KB of third-party JavaScript, set cookies, took up to two seconds and
frequently rendered blank. Posts are fetched server-side through the Graph API, so
the visitor's browser talks to nobody but this site.

**Three layers of cache, because the feed must never depend on the API being up.**
A transient holds the posts for the configured TTL (two hours by default); a
separate option holds the same posts and **never expires**; and a cron event
refreshes in the background. When the API errors, the never-expiring backup is
served — stale posts beat an empty section, and no visitor ever waits on Facebook.

**The token needs attention twice a year.** It is a Page Access Token that never
expires, generated through `me/accounts` from a long-lived user token. But its
*data access* lapses after 90 days unless the page administrator renews the Data
Use Checkup in the Facebook settings, and when it lapses the feed stops. The error
lands in `cbp_fb_last_error` and raises a red notice on every admin screen, which
is the only reason anybody finds out.

Settings live under **Facebook Feed** in the admin: page id, token, TTL, plus
buttons to test the connection, force a refresh, and load 30 fake posts for
reviewing the UI without a token.

Infinite scroll runs through the REST route rather than in the page: an
`IntersectionObserver` watching a sentinel inside the scroll box requests the next
slice, and the route answers with pre-rendered HTML. The scroll container uses
`overscroll-behavior: contain` and `contain: strict`, so it cannot disturb the page
scroll.

Renewing the token: Graph API Explorer → user token with `pages_show_list` and
`pages_read_engagement` → Access Token Debugger → **Extend Access Token** → paste
the long-lived token back → switch the endpoint to `me/accounts` → copy the
`access_token` for the page → save it in the admin.

**Instagram deliberately stays on Smash Balloon.** The Instagram account is not
linked to the Facebook page in Meta Business Suite, and without that link a page
token cannot serve Instagram at all.

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
