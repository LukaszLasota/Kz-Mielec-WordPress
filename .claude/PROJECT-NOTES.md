# kzmielec.pl — project notes

Verified against the running site on 2026-08-14. The previous version of this file
described the state around the first commit: it listed two blocks that no longer exist
(`emaus-news-slider`, `meeting-list`), omitted seven that do, named eight theme components
where there are twenty-five, and missed the comparison plugin and the multilingual work
entirely. It has been replaced rather than patched, and kept short on purpose — anything
rediscoverable from the code in a minute does not belong here. What belongs here is what
the code does not say.

## What this is

A WordPress site for a Pentecostal congregation in Mielec, Poland, in four languages
(Polish at the root, plus `/en/`, `/uk/`, `/es/`). WordPress 7.0.4, PHP 8.2, DDEV locally.

**All four languages have been live since 2026-08-13**; production served Polish only
before that. The local database is a copy of production, not the other way round.

Own code, all in this repository:

| Package | Purpose |
|---|---|
| `wp-content/themes/kzmielec` | classic theme with blocks, namespace `Kzmielec` |
| `custom-posts` | `meetings` post type; single URLs 301 to the archive anchor |
| `custom-block-package` | own blocks: navigable tiles, map, Facebook feed, accordion, dynamic images, SVG, PDF, section, scroll arrow, image-text |
| `comparison-of-religions` | `comparison_topic` type, `comparison_category` taxonomy, the denominations accordion |
| `kzmielec-translate` | DeepL translation engine, `wp kzmielec-translate run --lang=…` |

Third-party: Polylang 3.8.6 (free), Yoast SEO 28.1, Smash Balloon Instagram Feed,
LiteSpeed Cache 7.9 — active in BOTH environments, contrary to what this file said
until 2026-08-14: it minifies and combines locally as well, which is why a grep for a
plugin stylesheet in the HTML finds nothing. Leaflet powers the map; Glide the
one remaining slider. Masonry was removed 2026-08-01.

## Commands

```bash
ddev theme:build      # webpack, entries `frontend` and `editor`
ddev plugin:build     # custom-block-package and comparison-of-religions
ddev build:all
scripts/tests/run-all.sh
```

Quality gates run through DDEV, never through the host `php` — the `php` on PATH is the
Windows build and it hangs PHPStan:

```bash
ddev exec 'cd wp-content/themes/kzmielec && php vendor/bin/phpstan analyse --no-progress'
ddev exec 'cd wp-content/themes/kzmielec && php vendor/bin/phpcs --report=summary'
```

The theme and all three plugins pass PHPStan at level 8 and PHPCS — verified 2026-08-14,
not assumed. The theme's two remaining PHPCS warnings are deliberate, for the direct SQL
in `TranslationGuard`.

**Both gates used to list the files they checked, and both left some out.** The theme's
`phpcs.xml` named eleven paths and skipped six templates (441 lines); it now checks the
whole theme with exclusions instead. The plugin's `phpstan.neon` listed five of eleven
block templates; it now lists all eleven. Nothing was broken by either hole — the
omitted files turned out clean — but a gate that decides its own scope by hand grows
holes as files are added, and the ones already there were nobody's decision.

`custom-svg/render.php` was the exception that made this visible: outside PHPStan's paths
it had 22 errors, 21 of them one nullable `preg_replace()` result carried through the
whole function, and one real — `$target_id` read outside the branch that assigned it.
Fixed 2026-08-14; the file is analysed now.

## Things that cost time to learn

**`custom-posts` has no build step.** Its `src/` is runtime code loaded by its own
autoloader. Excluding `src/` from a deployment once left the `meetings` type unregistered,
and only one URL 404'd — the failure did not announce itself.

**Never `wp rewrite flush --skip-plugins`.** Without plugins no post type registers, so the
saved rule set loses the Polish archive too.

**Yoast serves titles and descriptions from `wp_yoast_indexable`**, and writing the post
meta does not refresh it. The static front page takes its description from
`wpseo_titles['metadesc-home-wpseo']`, not from a meta field.

**Polylang 3.8 rejects raw option writes.** `update_option( 'polylang', … )` silently drops
`post_types`, `taxonomies` and `redirect_lang`. Use `PLL()->options->set()` + `save()`, and
read the value back — `set()` returns a `WP_Error` even on success and swallows a wrong type
without reporting one.

**Blocks must name the language explicitly.** Polylang narrows queries by the language of
the request, and the block editor renders through `/wp/v2/block-renderer/` where there is no
request — an unqualified query answers with all four languages at once.

**The editor canvas does not inherit the front-end stylesheet.** What the theme gives it is
`assets/css/editor.css`, through `add_editor_style()`, so anything the front end relies on
must be repeated there — `img { height: auto }` was missing once and images took their
height from their HTML attribute. Read this together with the iframe entry further down:
some other stylesheets do arrive, by a compatibility path rather than by design.

**A database import bypasses WordPress**, so no cache is invalidated. After one, purge
explicitly.

**Domain search-replace after pulling production: only with the `https://` prefix.** A bare
`kzmielec.pl → kzmielec.ddev.site` also rewrites `zbor@kzmielec.pl` into a dead address, and
a wrong e-mail looks exactly like a right one.

**`wp eval-file` runs the file through `eval()`.** A `declare(strict_types=1)` is then no
longer the first statement in the compilation unit and PHP refuses the file outright. Every
script in `scripts/` says so in a comment; the comment is the reason, not decoration.

**MySQL's default collation treats `Ę` and `ę` as the same letter.** A `LIKE '%wizytĘ%'`
looking for a capitalisation error reports a match on every page that spells the word
correctly, so it looks found when it is not there. `LIKE BINARY` is the only honest search
for a diacritic or a case.

**`wp db query` and `wp db export` do not work on production.** Use `wp eval` with `$wpdb`
for queries, and `mariadb-dump` over the socket for a dump. The reason is in the private
operator note, and it is load-bearing — do not "fix" it.

**There is a way to dump that database with no password in the command and no write on the
server.** WP-CLI's `--exec` runs before `wp-config.php` is read, and a constant already
defined cannot be redefined, so this wins:

```bash
ssh … "wp --path=\$KZ --exec=\"define('DB_HOST','localhost');\" db export -" > prod.sql
```

The dump streams to stdout and the only file written is the local one. Used on 2026-08-14
to pull production into the local copy; `wp search-replace` afterwards **only with the
`https://` prefix**, and the e-mail count is the check that the trap did not fire.

**`data-nosnippet` is honoured only on `span`, `div` and `section`.** On any other element,
`nav` included, the browser accepts the attribute and the crawler ignores it, so the markup
looks right and does nothing.

**The editor canvas is an iframe, and only three things reach it**: `add_editor_style()`, a
block's `style` declared in `block.json`, and anything hooked to `enqueue_block_assets`.
`enqueue_block_editor_assets` styles the editor's own interface, outside the frame. This is
what made the Instagram block render as a full-width blue square — an inline SVG with no
`width`/`height` attributes has no size of its own, so unstyled it fills its container.

What makes the trap hard to see: **WordPress copies the parent document's style queue into
the iframe as a compatibility path**, so being on the wrong hook fails partially rather
than completely. Measured on 2026-08-14, three of our four pattern stylesheets arrived in
the canvas that way and `banner-hero-style.css` did not. Both `PatternAssets` and
`Core\EditorFeedStyles` are on the correct hook now.

**Smash Balloon publishes expiring image URLs by default.** With `disable_js_image_loading`
off, `src` holds Instagram's signed CDN address and the plugin's own resized copies are
swapped in by JavaScript. Those signatures expire — verified 403 on a live page — and a
page cached for seven days keeps the dead link, which is why the feed broke "sometimes, on
one language". Two settings fix it: `disable_js_image_loading = true` **and**
`sb_instagram_image_res` set to anything but `auto`, because `auto` short-circuits past the
branch that builds the local URL.

**A Smash Balloon feed has one configuration for the whole site.** A hand-typed
`buttontext` showed "Wiecej" under the English and Ukrainian feeds. Emptying the field
leaves an unlabelled button; the key has to be **absent** for the plugin's own translated
string. And translate.wordpress.org has no Ukrainian pack for that plugin at all, so
`wp-content/languages/plugins/instagram-feed-uk.*` is ours, written by hand.

**The host runs a WAF in front of WordPress, and verifying a deployment trips it.**
ModSecurity answers before PHP runs, so its signature is a 403 with nothing in the PHP
error log - and it looks exactly like the site breaking: `/wp-json/` and `admin-ajax.php`
return 403 while ordinary pages stay 200, so a feed's initial render (which happens in
PHP) is fine while its "load more" fails. The rules count offences per client and ban the
whole /24, not one address: `H88_GPTBOT24_STRIKES`, `H88_META24_STRIKES`,
`H88_AMAZONBOT_STRIKES`, `H88_CLAUDEBOT24_STRIKES`.

What tripped it on 2026-08-18, all of it verification traffic: a `POST` to `xmlrpc.php`,
`?author=1` and `/author/admin/` probes, `admin-ajax.php` POSTed every 30 seconds by a
polling script, and the user agents `curl` and `HeadlessChrome`. Normal work is nowhere
near the threshold - the office address made 847 requests on 2026-08-15 for three WAF
entries.

Two things follow. Check from the server instead: `wp eval` with `wp_remote_get()` never
crosses the WAF and answers the same questions, so run a browser once at the end rather
than in a loop. And a fetch from Anthropic's infrastructure is **not** an independent
origin for this site - that subnet is banned by `H88_CLAUDEBOT24_STRIKES`, so its 403
says nothing about what a visitor sees. A phone on mobile data does.

## Decisions that still hold

Carried over from the migration plan of 2026-04-21, which is otherwise deleted: it
described a system that was not built — a `circle-cards` block, ACF Pro, single meeting
pages. What was worth keeping is the reasoning, and only where it still matches the code.

| Decision | Why |
|---|---|
| Belief pages are WP pages, not a post type | the URLs had to stay identical (`/misja/`, `/wizja/`), and eight rarely-changing pages do not need a type |
| Meetings are a post type with an archive | structured fields, draft/publish, one archive template. Single meeting URLs 301 to the archive anchor — the plan expected a `single-meetings.php` that turned out unnecessary |
| The belief list is a theme option | `kzmielec_belief_pages`, ordered by drag and drop, read by the tiles block and by `page-belief.php` |
| No ACF | the plan called for ACF Pro in phase 1. Native `register_meta()` and the Options API cover all of it, so the site carries no dependency on a paid plugin. The belief hover image is `_belief_hover_image` post meta, not the `belief_overlay_icon` the plan named |
| Blocks live in plugins, not the theme | blocks are content, the theme is appearance — and a type or block registered by a theme vanishes the moment the theme is switched |
| Leaflet, not Google Maps | free, and no API key to leak or expire |
| No jQuery | plain JS, with `scroll-behavior: smooth` in CSS |
| Instagram stays on Smash Balloon | the IG account is not linked to the FB page in Meta Business Suite, so a page token cannot serve it |
| The Facebook feed is our own block | it replaced a stalling iframe that shipped ~350KB of third-party JS and set cookies |
| Each plugin mirrors the scale files | a plugin builds separately and must work under any theme; `check:mirrors` catches the drift |
| Pages are flat, no parents | verified: every published page has `post_parent = 0` |
| Day and hour of a meeting are one structured pair on the Polish post | prose in four languages cannot be turned back into the `startDate` Google requires. `_meeting_day_hour` stays, generated on save, because the theme's search indexes it — deleting it would have broken `/?s=niedziela` while every page still rendered perfectly |
| Six dates per meeting in the schema, not one | production can serve the same HTML for seven days, so one "next Sunday" computed at render time is a past date for part of the audience |
| An address in the search result needs a Google Business Profile | there is no rich result for a church's address and hours; no amount of markup produces that box, and this was checked against Google's documentation rather than guessed |
| Instagram images are served from our own server | the plugin already stored resized copies; it just did not reference them. Signed CDN links expire, and a page cached for seven days keeps the dead one |
| LiteSpeed combines CSS but no longer JavaScript | measured 2026-08-14: combining saved **nothing** — 7 files at 284 858 B against 2 files at 285 437 B — and only turned seven requests into two on an HTTP/2 host. What it bought instead was one hash for everything, so a single stale bundle takes down every script on the page. Minification per file stays on, deferring stays on |

## Where things live

- Access data, hosts, paths: `~/private/connection-details.txt` — never in a repository.
  The pre-commit hook enforces this and reads the forbidden strings from
  `~/private/git-forbidden-strings.txt`.
- Deployment procedure, audits, plans: `docs/` on disk, currently outside version control.
- Content changes are reproduced on the server by the 23 scripts in `scripts/`. Eighteen are
  a dry run by default and write only after `go` (`convert-uploads.php` takes `--dry-run`
  instead); the remaining five are one-off setup steps that write straight away.
- **Four things live only in the production database and cannot be in git**, so a fresh
  deployment does not carry them and a database pulled from production does:
  `sb_instagram_settings['disable_js_image_loading'] = true`,
  `sb_instagram_settings['sb_instagram_image_res'] = 'full'`, the absent `buttontext` key in
  the `wp_sbi_feeds` row, and `litespeed.conf.optm-js_comb` switched off. All four were set
  on 2026-08-14 and all four have a reason written above. The local copy was aligned the
  same day; `optm-js_defer` and `optm-css_comb` stay on in both.
