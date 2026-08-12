# kzmielec.pl — project notes

Verified against the running site on 2026-08-12. The previous version of this file
described the state around the first commit: it listed two blocks that no longer exist
(`emaus-news-slider`, `meeting-list`), omitted seven that do, named eight theme components
where there are twenty-five, and missed the comparison plugin and the multilingual work
entirely. It has been replaced rather than patched, and kept short on purpose — anything
rediscoverable from the code in a minute does not belong here. What belongs here is what
the code does not say.

## What this is

A WordPress site for a Pentecostal congregation in Mielec, Poland, in four languages
(Polish at the root, plus `/en/`, `/uk/`, `/es/`). WordPress 7.0.2, PHP 8.2, DDEV locally.

Own code, all in this repository:

| Package | Purpose |
|---|---|
| `wp-content/themes/kzmielec` | classic theme with blocks, namespace `Kzmielec` |
| `custom-posts` | `meetings` post type; single URLs 301 to the archive anchor |
| `custom-block-package` | own blocks: navigable tiles, map, Facebook feed, accordion, dynamic images, SVG, PDF, section, scroll arrow, image-text |
| `comparison-of-religions` | `comparison_topic` type, `comparison_category` taxonomy, the denominations accordion |
| `kzmielec-translate` | DeepL translation engine, `wp kzmielec-translate run --lang=…` |

Third-party: Polylang 3.8.6 (free), Yoast SEO 28.1, Smash Balloon Instagram Feed,
LiteSpeed Cache (inactive locally, used in production). Leaflet powers the map; Glide the
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

The theme and all three plugins pass PHPStan at level 8. PHPCS is clean apart from two
deliberate warnings in `TranslationGuard` for direct SQL.

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

**The editor canvas gets only `assets/css/editor.css`.** It does not inherit the front-end
stylesheet, so anything the front end relies on must be repeated there. `img { height: auto }`
was missing and images took their height from their HTML attribute.

**A database import bypasses WordPress**, so no cache is invalidated. After one, purge
explicitly.

**Domain search-replace after pulling production: only with the `https://` prefix.** A bare
`kzmielec.pl → kzmielec.ddev.site` also rewrites `zbor@kzmielec.pl` into a dead address, and
a wrong e-mail looks exactly like a right one.

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

## Where things live

- Access data, hosts, paths: `~/private/connection-details.txt` — never in a repository.
  The pre-commit hook enforces this and reads the forbidden strings from
  `~/private/git-forbidden-strings.txt`.
- Deployment procedure, audits, plans: `docs/` on disk, currently outside version control.
- Content changes are reproduced on the server by the twenty scripts in `scripts/`, each
  with a dry run by default and writes only after `-- go`.
