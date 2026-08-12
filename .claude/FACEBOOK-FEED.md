# Facebook feed -- implementation notes

A dynamic Facebook feed fetched through the Graph API, cached on the server and
rendered as a Gutenberg block with infinite scroll. It replaces the old, stalling
Facebook Page Plugin iframe from the previous version of the site.

## Architecture

All of it lives in the `custom-block-package` plugin: switch the plugin off and
the feed is gone.

```
custom-block-package/
├── app/
│   ├── Admin/
│   │   └── FacebookSettings.php          # settings screen, dashboard widget, admin notice
│   ├── Services/
│   │   └── FacebookFeedService.php       # Graph API client, cache, fallback, mock data
│   ├── Cron/
│   │   └── FacebookFeedCron.php          # WP Cron, refresh every 2h (configurable)
│   ├── Rest/
│   │   └── FacebookFeedController.php    # REST route /custom-block-package/v1/facebook-feed
│   └── Cache/
│       └── BlockCache.php                # FACEBOOK_FEED_PREFIX and the flush logic
├── src/blocks/facebook-feed/
│   ├── block.json                        # apiVersion 3, attributes, supports
│   ├── index.js                          # block registration
│   ├── edit.js                           # editor UI via ServerSideRender (preview = front end)
│   ├── render.php                        # server-side render: header + scroll container
│   ├── view.js                           # IntersectionObserver infinite scroll
│   ├── style.scss                        # front end and editor (imported into index.scss)
│   └── index.scss                        # editor only (@import style.scss)
└── index.php                              # bootstraps the classes, activation hooks
```

## Components

### 1. FacebookFeedService

`app/Services/FacebookFeedService.php`

**Constants:**
- `OPTION_PAGE_ID` = `cbp_fb_page_id` -- the FB page username (e.g. `Kzmielec`)
- `OPTION_ACCESS_TOKEN` = `cbp_fb_access_token` -- Page Access Token (never-expiring)
- `OPTION_CACHE_TTL` = `cbp_fb_cache_ttl` -- cache TTL in seconds (1h/2h/6h/12h/24h)
- `OPTION_LAST_SYNC` = `cbp_fb_last_sync` -- timestamp of the last successful fetch
- `OPTION_LAST_ERROR` = `cbp_fb_last_error` -- last error message from the API
- `OPTION_BACKUP_POSTS` = `cbp_fb_backup_posts` -- post backup (never expires; the fallback when the API is down)
- `OPTION_PAGE_INFO` = `cbp_fb_page_info` -- the page's name and picture URL
- `MAX_POSTS = 50` -- most posts per API call
- `DEFAULT_TTL = 2 * HOUR_IN_SECONDS`
- `API_VERSION = 'v19.0'`

**Public methods:**
- `get_posts(int $limit = 10): array` -- posts from cache
- `get_posts_range(int $offset, int $limit): array` -- pagination for infinite scroll
- `get_total_count(): int` -- how many posts are in the cache
- `get_page_info(): array{name, picture}` -- page details
- `refresh(): bool` -- force a fetch, write the cache and the backup
- `test_connection(): array{success, message, posts_count}` -- test the API with the current token
- `load_mock_data(): bool` -- injects 30 fake posts (UI testing without a token)

**Graph API endpoints:**
```
GET /{page_id}/posts
  ?fields=message,created_time,permalink_url,full_picture,attachments{media,subattachments,type}
  &limit=50
  &access_token={token}

GET /{page_id}
  ?fields=name,picture.type(large)
  &access_token={token}
```

**What `refresh()` does:**
1. Reads `page_id` and the token from the options
2. `wp_remote_get()` to the Graph API
3. Parses and validates the JSON
4. Stores it in a transient, with the TTL
5. Stores it in `OPTION_BACKUP_POSTS`, which never expires
6. Updates `OPTION_LAST_SYNC` or `OPTION_LAST_ERROR`
7. Fetches the page details (`refresh_page_info()`)

**Fallback when the API is down:** an empty transient with no successful refresh
returns the backup posts. They may be stale, which beats an empty feed.

### 2. FacebookFeedCron

`app/Cron/FacebookFeedCron.php`

- Custom interval `cbp_fb_interval`, taken from the TTL option
- Hook: `cbp_fb_cron_refresh`
- `activate()` / `deactivate()` -- via register_activation_hook / register_deactivation_hook
- `reschedule()` -- called after the TTL changes in the admin, restarts the cron event

### 3. FacebookSettings

`app/Admin/FacebookSettings.php`

**Menu:** WP Admin -> **Facebook Feed** (top level, dashicon `facebook-alt`)

**Form fields:**
- `cbp_fb_page_id` -- text input (sanitize_text_field)
- `cbp_fb_access_token` -- code textarea (sanitize_textarea_field)
- `cbp_fb_cache_ttl` -- select: 1h/2h/6h/12h/24h

**Action buttons:**
- **Test connection** -- calls `FacebookFeedService::test_connection()`
- **Refresh cache now** -- flushes the cache and forces a refresh
- **Load mock data (for testing)** -- loads 30 fake posts

**Status section:** last successful sync (through `human_time_diff`) and the last
error, if there is one.

**Admin notice (red banner):** shown on **every WP Admin screen** while
`cbp_fb_last_error` is not empty, with the error text and a button to the settings.
Hook: `admin_notices`.

**Dashboard widget:** page status, token, cached post count, last sync, error.
Hook: `wp_dashboard_setup`.

**Security:** every action carries a nonce (NONCE_SAVE, NONCE_ACTION), the
capability is `manage_options`, and all input is sanitised.

### 4. FacebookFeedController (REST)

`app/Rest/FacebookFeedController.php`

**Route:** `GET /wp-json/custom-block-package/v1/facebook-feed`

**Query parameters:**
- `offset` (int, default 0) -- which post to start from
- `limit` (int, default 5, max 20) -- how many posts to return
- `showImages` (bool, default true)
- `showDate` (bool, default true)

**Response:**
```json
{
  "html": "<article>...</article>...",
  "count": 5,
  "total": 30,
  "offset": 0,
  "hasMore": true
}
```

The `html` is pre-rendered server-side, so the browser appends markup rather than
templating it.

**Permission:** `__return_true`. The feed is public, so the route is too.

### 5. The facebook-feed block

`src/blocks/facebook-feed/`

**Attributes (block.json):**
- `anchor` (string) -- element id
- `postsCount` (number, default 5) -- how many posts to render initially
- `showImages` (bool, default true)
- `showDate` (bool, default true)
- `columns` (number, default 1, max 3)
- `containerHeight` (number, default 700) -- height of the scroll box, in px

**Render flow:**
1. `render.php` takes the first N posts from `FacebookFeedService::get_posts()`
2. Renders the header: avatar, name, and a button to the page
3. Renders N posts inside the scroll container (`.facebook-feed__scroll`)
4. Adds the sentinel and the loading indicator when `has_more`
5. The wrapper carries `data-*` attributes for view.js

**view.js (infinite scroll):**
1. `IntersectionObserver` with `root: scrollContainer` and `rootMargin: '200px 0px'`
2. Sentinel becomes visible -> fetch from the REST route with the current `offset` and `limit`
3. Append the HTML to the grid
4. Update `offset` and `hasMore`
5. Disconnect the observer once `!hasMore`

**Scroll container CSS:**
- `height: var(--cbp-fb-height, 700px)`, from the attribute
- `max-height` plus `min-height: 0` for safety
- `overflow-y: auto` and `overscroll-behavior: contain`, which isolates the scroll
- `contain: strict` -- hard layout isolation, so the page scroll is unaffected

**Block header:** a 48px avatar (from `cbp_fb_page_info[picture]`), the page name
with a subtitle, and a blue button linking to facebook.com/{page_id}. It wraps on
mobile, with the button going full width below 500px.

**Relative time:**
- under a minute: "just now"
- under a week: "X hours ago" / "X days ago" (`human_time_diff`)
- over a week: the full date (`wp_date` with the site's `date_format`)

## The Facebook token

### What is set up

**Meta app:** `KZMielec` (App ID 1315941170390773)
- Type: Business
- Use case: manage everything on your Page
- Mode: Development, which needs no App Review
- Administered by the church's Facebook page administrator

**FB page:** Pentecostal Church in Mielec (Page ID 1496572750574514)
- Username: Kzmielec
- The page administrator holds the Administrator role

**Token:**
- Type: Page Access Token
- Expires: **never** (generated through the `me/accounts` endpoint from a long-lived user token)
- Data access expires: 90 days. The Data Use Checkup has to be renewed by the page
  administrator in the Facebook settings, and the feed stops when it lapses.
- Scopes: `pages_show_list`, `business_management`, `pages_read_engagement`, `public_profile`

### Renewing the token

1. Graph API Explorer -> get a user access token with `pages_show_list` and `pages_read_engagement`
2. Copy it into the Access Token Debugger -> **Extend Access Token** -> a long-lived user token (60 days)
3. Paste the long-lived user token back into the Graph API Explorer
4. Change the endpoint to `me/accounts` -> Submit
5. From the JSON, copy the `access_token` belonging to Kzmielec -- that is the never-expiring page token
6. Paste it into WP Admin -> Facebook Feed -> Save

The walkthrough written for the page administrator is a separate document, kept
out of this repository.

## Mock data: testing without a token

**Load mock data (for testing)** in the admin injects 30 fake posts:
- image: `https://picsum.photos/seed/fbN/800/450`
- text: placeholder prose, numbered
- page details: the church name and an avatar
- dates walking backwards, one per day

It exists so the UI can be reviewed without a real token.

## Performance

| | FB Page Plugin iframe (old production) | This block |
|---|---|---|
| External JS | ~350KB | none |
| External cookies | yes (a GDPR matter) | none |
| Load time | 500-2000ms, and it stalled | under 50ms from cache |
| Lighthouse impact | a blocking tracker | none |
| Reliability | frequently blank | always renders (backup in `wp_options`) |

**Cache strategy:** a transient with the TTL from the settings (2h by default), a
backup option that never expires and is used when the API errors, and a cron job
refreshing in the background -- so no visitor ever waits on the Graph API.

## Standards

- **PSR-4:** namespaces `CustomBlockPackage\Admin\*`, `Services\*`, `Cron\*`, `Rest\*`
- **PHPStan level 8:** strict types, declared return types, zero errors
- **PHPCS, WordPress standard:** zero errors, zero warnings
- **WCAG 2.1 AA:** semantic `<article>` and `<time>`, `aria-label`, `aria-live`,
  `loading="lazy"`, `rel="noopener noreferrer"`
- **i18n:** every string through `__()` with the `custom-block-package` text domain

## Instagram: NOT implemented

Instagram stays on the free **Smash Balloon Instagram Feed** plugin, because the
`zbor_w_mielcu` Instagram account is not linked to the Kzmielec Facebook page in
Meta Business Suite, and without that link a page token cannot serve Instagram.
Doing it properly would mean converting the account to a Business one, linking it
to the FB page, and adding `instagram_basic` to the token.

If that ever happens:
1. The page administrator links Instagram to the FB page (about a minute, on a phone)
2. Add `instagram_basic` to the token
3. Write `InstagramFeedService` along the lines of `FacebookFeedService`

Reuse would be high: the same token, the same BlockCache, and the same cron, REST
and admin patterns.
