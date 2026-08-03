# Production cutover: new `kzmielec` theme → kzmielec.pl

**Date:** 2026-08-03
**Scope:** Migrating the new Gutenberg theme `kzmielec`, the three own plugins, the
content database and the media library from local DDEV onto production
(kzmielec.pl, Cyber Folks). Everything is done over SSH.
**Status:** Design approved 2026-08-03. Spec pending review.

## Problem

Production still runs the old `html5blank-stable` theme. The new theme has never
been deployed — verified 2026-08-03, the `kzmielec` theme directory does not
exist on the server, and **none of the three own plugins are installed there**.

This means there is no incremental deployment available. Making anything from the
new theme visible on kzmielec.pl requires the full cutover: files, database and
media together. This spec covers exactly that.

## Measured current state

Both sides run **WordPress 7.0.2 on PHP 8.2**, so there is no version gap.

| | Production | Local (DDEV) |
|---|---|---|
| Active theme | `html5blank-stable` | `kzmielec` |
| Own plugins | none installed | 3, all active |
| Published pages | 17 | 18 |
| Published posts | 0 | 0 |
| `meetings` CPT | — | 3 |
| `comparison_topic` | — | 37 |
| Attachments | 8 | 46 |
| Uploads on disk | 33 MB | 26 MB |
| Users | 1 (ID 1, `wpm`) | 1 (ID 1, `wpm`, same e-mail) |
| Comments | 0 | — |
| Database | — | 13 MB |

**Production content is frozen.** The most recently modified page was touched
`2019-10-18`. Nobody has edited production content in roughly seven years, so
overwriting it with the local database cannot silently revert anyone's work. This
is the single biggest reason the cutover is low-risk.

**Page IDs are identical on both sides**, because the local database descends
from a production dump:

- production: `2 65 77 79 81 83 88 90 92 101 103 106 108 110 115 117 119`
- local: the same, minus `92` (the old "zaplanuj wizyte" page, now a draft),
  plus `131` (new front page) and `307` (Polityka ochrony dzieci)

This matters because **the new theme hard-codes page IDs**: the option
`kzmielec_belief_pages` is `[2,65,77,79,81,83,88,90]`, and page `131` carries two
`navigable-tiles` blocks. A WXR export/import is therefore **ruled out** — the
WordPress importer assigns new IDs, which would silently break belief navigation
and the front-page tiles with no error message. The database must move in a way
that preserves IDs.

### Plugins on each side

| Plugin | Production | Local |
|---|---|---|
| `custom-block-package` | — | active |
| `custom-posts` | — | active |
| `comparison-of-religions` | — | active |
| `wordpress-seo` 28.1 | active | active |
| `instagram-feed` 6.11.4 | active | active |
| `litespeed-cache` 7.8.1 | active | **inactive** |
| `autoptimize` 3.1.15.1 | active | absent |
| `jquery-manager` 1.10.6 | active | absent |
| `wordfence` 8.2.2 | active | absent |

Local `active_plugins` lists only `comparison-of-religions`,
`custom-block-package`, `custom-posts`, `instagram-feed` and `wordpress-seo`.
Because that option travels with the database, the import would switch off every
plugin missing from that list — including `litespeed-cache`, which must be turned
back on deliberately (see D5).

### Settings the import would destroy

Counted on production: **196** `litespeed*` options, **7** `wpseo*`, **5**
`sb_instagram*`. These live in `wp_options`, which cannot be excluded from the
import — it carries the active theme, the front page and the theme's own options.
They are therefore exported before the import and restored after it.

Some plugins also keep state in **their own tables**, which the local database
has stale copies of: 25 `wp_wf*`, 7 `wp_yoast_*`, 6 `wp_sbi_*` and 2
`wp_litespeed_*` — **40 tables**. Wordfence in particular keeps its configuration
in `wp_wfconfig`, not in `wp_options`. The six `wp_sbi_*` tables were found while
rehearsing the export on 2026-08-03; excluding them keeps the Instagram feed's own
cache consistent with the options restored after the import.

### Two hard technical constraints

1. **`DB_HOST` on production is literally `'/'`** and it is load-bearing. WordPress's
   own mysqli connects fine, so `wp option`, `wp plugin`, `wp search-replace` all
   work — but the external `mariadb` / `mariadb-dump` binaries choke on that host,
   so **`wp db import` and `wp db export` fail**. Both dump and import must call
   `mariadb` directly with `--socket=/var/lib/mysql/mysql.sock`.
2. **The theme loads Composer unconditionally** — `functions.php:13` is a bare
   `require_once get_template_directory() . '/vendor/autoload.php'`. Without that
   directory on the server, activating the theme is an immediate fatal error. The
   theme has exactly one runtime dependency (`enshrined/svg-sanitize` ^0.22.0);
   built with `--no-dev` the tree is **216 KB / 27 files**, versus 47 MB with dev
   tools. `vendor/` is not tracked in git (0 files), so it must be built for the
   deployment. The three plugins reference no Composer autoloader at runtime and
   need no `vendor/`.

Access: `ssh -p 222 $PRODUSER@$PRODHOST`, docroot
`$PRODPATH`. **Always pass `--path` explicitly**
— `~/public_html` is a symlink to a different site (`$OTHERSITE`).

## Decisions

- **D1 — Full cutover now, no maintenance mode.** Files go first and are invisible
  (the theme stays inactive); the database import is itself the moment of
  switchover, because the active theme is stored in `wp_options`. The visible
  interruption is seconds.
- **D2 — Overwrite the whole database**, with one exception: the 40
  plugin-owned tables above are excluded from the dump. Every WordPress table is
  imported, so content and theme settings land 1:1. The exception exists so that
  a stale local `wp_wfconfig` cannot disarm production security.
- **D3 — The accessibility bar is not part of this cutover.** It is committed on
  branch `belka-wcag` (`51ffc2c`) and comes back as separate work afterwards,
  against production. The cutover therefore ships `main` at `6c35126`, the state
  that already passed the WCAG audit.
- **D4 — Delete the production-only plugins:** `autoptimize`, `jquery-manager`,
  `wordfence`. They do not exist locally. The new theme needs neither jQuery nor
  Autoptimize (it ships its own optimised build).
- **D5 — `litespeed-cache` stays** and must be reactivated after the import, since
  local `active_plugins` omits it. Its 196 options are restored from the export.
- **D6 — The local Facebook access token is the right one.** Confirmed by the user
  2026-08-03, so `cbp_fb_access_token` and its siblings ship to production as they
  are; no re-authorisation step is needed.
- **D7 — The administrator password stays the local one.** User's call 2026-08-03.
  No password is set after the import; the account is the same (`wpm`, ID 1, same
  e-mail), only the hash changes to local's.

### Non-goals

- The accessibility bar (D3).
- Reconnecting the Instagram feed — deferred; the Facebook feed block substitutes.
- Any content editing. What is local is what goes live.
- Touching `$OTHERSITE` or `$PRODUSER.vot.pl` on the same account.

## Design

### Phase 0 — Safety net (nothing has changed yet)

1. Dump the production database with `mariadb-dump` over the socket, using the
   password from `wp config get DB_PASSWORD`. Download it locally.
2. `tar` production `wp-content/themes` and `wp-content/plugins`; copy
   `.htaccess` and `wp-config.php`. Download.
3. Export the option groups the import will overwrite — `litespeed*`, `wpseo*`,
   `sb_instagram*` — as a restorable file. `wordfence*` options are captured in
   the same file even though the plugin is being deleted (D4): they are needed
   only if we roll back, and the dump alone would not carry them once the plugin's
   uninstaller has run.
4. Record the current `auto_prepend_file` state (`.user.ini`, `php.ini`,
   `.htaccess`) so the Wordfence WAF can be verified after removal.

Nothing proceeds until the dump and the tarballs are confirmed present and
non-empty **on the local disk**.

### Phase 1 — Remove the production-only plugins (before the import)

`wp plugin delete autoptimize jquery-manager wordfence`, run **while they are
still active and functional**, so each plugin's own uninstaller cleans up after
itself. Order matters: doing this after the import — when the plugins are
deactivated and their config tables are gone — would leave the cleanup undone.

The specific hazard is Wordfence: it installs `wordfence-waf.php` and points
`auto_prepend_file` at it. If those files disappear while the prepend remains,
**every PHP request fatals** and the site is down hard. After deletion, verify the
prepend is gone and that a plain page still returns HTTP 200 — before touching
anything else.

Removing `litespeed-cache` is explicitly *not* part of this phase (D5).

### Phase 2 — Files (invisible to visitors)

1. Build the production Composer tree for the theme: `composer install --no-dev`
   into a clean directory, yielding the 216 KB / 27-file `vendor/`.
2. `rsync` the theme `kzmielec` and the three plugins. Excluded, because none of it
   runs on the server: `node_modules`, `src`, `.git*`, `composer.json`,
   `composer.lock`, `package.json`, `package-lock.json`, `phpstan.neon`,
   `phpcs.xml*`, `webpack.config.js`, `tsconfig.json`, `biome.json`, `*.map` and
   every `vendor/` tree except the theme's. Included: the theme's `--no-dev`
   `vendor/`, `assets/`, `languages/`, all PHP templates, and each plugin's
   `build/`. Rsync runs **without `--delete`** here too, so nothing pre-existing on
   the server is removed by this step.
3. `rsync` `wp-content/uploads` **without `--delete`**. Production has 33 MB and
   local 26 MB; the goal is to add local's 46 attachments, not to remove
   production files that local no longer references.

The theme is on disk but not active, so visitors still see the old site.

### Phase 3 — Database (the switchover)

1. Produce the dump locally with
   `wp search-replace 'kzmielec.ddev.site' 'kzmielec.pl' --all-tables-with-prefix --export=…`,
   excluding the 40 plugin-owned tables. Using `--export` means the replacement
   happens **in the file**: the local database is never modified, and production
   is never exposed to `.ddev.site` URLs even briefly. The dry run counted **873
   replacements** — 8 in `wp_options`, 74 in `wp_posts.post_content`, 186 in
   `wp_posts.guid`, 1 in `wp_usermeta`, the rest in tables now excluded.
2. Upload the dump and import it with `mariadb --socket=…` (not `wp db import`,
   which cannot work here).

The moment the import completes, `wp_options` says the active theme is
`kzmielec`, and production serves the new site.

### Phase 4 — Configuration after the import

1. Restore the exported option groups from Phase 0.3 (`litespeed*`, `wpseo*`,
   `sb_instagram*`).
2. Reactivate `litespeed-cache` (D5).
3. `wp rewrite flush` — required, because `/zaplanuj-wizyte/` is the `meetings`
   CPT archive rewrite, not a page. Without this it 404s.
4. Purge the LiteSpeed cache and confirm `x-litespeed-cache` cycles miss → hit.
5. No password step — per D7 the local hash stands. Confirm a login still works
   before declaring the phase finished, so a broken sign-in is discovered now
   rather than later.

### Phase 5 — Verification

Automated, reusing the axe harness written for the accessibility bar, repointed at
`https://kzmielec.pl`:

- axe-core, WCAG 2.1 A + AA, across the 12 known URLs × 3 viewport widths.
- HTTP status of **all 17 pre-existing URLs** — none may break.
- `/zaplanuj-wizyte/` renders the meetings archive.
- `/meetings/<slug>/` returns 301 → `/zaplanuj-wizyte/#<slug>`.
- `/roznica-wyznan/` renders the comparison accordion (37 topics).
- The Facebook feed block returns posts rather than an error.
- The front page renders both `navigable-tiles` blocks and the map block.

Then a human look by the user. The cutover is not "done" until that happens.

## Rollback

Prepared **before** Phase 1, not improvised:

1. Import the Phase 0 database dump over the production database.
2. Unpack the `themes` / `plugins` tarballs, restoring `html5blank-stable` and
   the three deleted plugins.
3. Restore `.htaccess`.
4. `wp rewrite flush`, purge cache.

The database import alone restores the old active theme, so step 1 is the one that
brings the old site back; the rest repairs the plugin set.

## Risks and open items

- **The administrator password becomes the local one** (D7, accepted). Same
  account throughout — `wpm`, ID 1, same e-mail — only the hash changes. Whoever
  else knows the old production password loses access until it is reset from the
  dashboard.
- **The Instagram feed may stop working.** The import overwrites production's 5
  `sb_instagram*` options with local ones. If the local install is not connected,
  the feed breaks until someone re-authorises it. Accepted — the feed is a
  non-goal, and the Facebook block substitutes.
- **No page cache during the window.** `litespeed-cache` is off between the import
  and Phase 4.2, so the site serves uncached PHP for that interval.
- **Orphaned LSCACHE rules.** The `# BEGIN LSCACHE` block in `.htaccess` is
  server-level and survives the import. It is verified in Phase 4.4.
- **Wordfence WAF prepend** — the one genuine site-down risk, handled and verified
  in Phase 1.
- **No staging rehearsal.** The user chose to go straight to production (D1), so
  this procedure runs for the first time on the live site. The rollback plan and
  the pre-import dump are what compensate.

## Appendix — excluded tables

25 Wordfence: `wp_wfauditevents`, `wp_wfblockediplog`, `wp_wfblocks7`,
`wp_wfconfig`, `wp_wfcrawlers`, `wp_wffilechanges`, `wp_wffilemods`, `wp_wfhits`,
`wp_wfhoover`, `wp_wfissues`, `wp_wfknownfilelist`, `wp_wflivetraffichuman`,
`wp_wflocs`, `wp_wflogins`, `wp_wfls_2fa_secrets`, `wp_wfls_role_counts`,
`wp_wfls_settings`, `wp_wfnotifications`, `wp_wfpendingissues`,
`wp_wfreversecache`, `wp_wfsecurityevents`, `wp_wfsnipcache`, `wp_wfstatus`,
`wp_wftrafficrates`, `wp_wfwaffailures`.

7 Yoast: `wp_yoast_expiring_store`, `wp_yoast_indexable`,
`wp_yoast_indexable_hierarchy`, `wp_yoast_migrations`, `wp_yoast_primary_term`,
`wp_yoast_seo_links`, `wp_yoast_seo_meta`.

6 Instagram Feed: `wp_sbi_feed_caches`, `wp_sbi_feeds`,
`wp_sbi_instagram_feed_locator`, `wp_sbi_instagram_feeds_posts`,
`wp_sbi_instagram_posts`, `wp_sbi_sources`.

2 LiteSpeed: `wp_litespeed_url`, `wp_litespeed_url_file`.

**Rehearsed 2026-08-03.** With all 40 skipped, the export contains exactly the 12
WordPress core tables (`wp_commentmeta`, `wp_comments`, `wp_links`, `wp_options`,
`wp_postmeta`, `wp_posts`, `wp_term_relationships`, `wp_term_taxonomy`,
`wp_termmeta`, `wp_terms`, `wp_usermeta`, `wp_users`), each with a matching
`DROP TABLE IF EXISTS`, and applies 269 URL replacements.
