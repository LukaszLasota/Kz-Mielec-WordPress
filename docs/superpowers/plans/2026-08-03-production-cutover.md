# Production Cutover Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Put the new `kzmielec` theme, its three plugins, the local content database and the media library live on kzmielec.pl, replacing `html5blank-stable`.

**Architecture:** Files first (invisible, theme inactive), then the database import — which is itself the switchover, because the active theme is stored in `wp_options`. Production-only plugins are deleted *before* the import so their own uninstallers can clean up while they still work. Everything runs over SSH; the local database is never modified.

**Tech Stack:** WordPress 7.0.2 / PHP 8.2 both sides, WP-CLI, `mariadb` client over a UNIX socket, rsync over SSH, Composer (`--no-dev`), puppeteer + axe-core for verification.

**Source spec:** `docs/superpowers/specs/2026-08-03-production-cutover-design.md`

## Global Constraints

- **Always pass `--path=$KZ` to every `wp` command on production.** `~/public_html` is a symlink to a different site (`$OTHERSITE`). A `wp` call without `--path` can operate on the wrong site.
- **`DB_HOST` on production is literally `'/'` and must not be "fixed".** Consequence: `wp db import` and `wp db export` **fail**. Use the `mariadb` / `mariadb-dump` binaries with `--socket=/var/lib/mysql/mysql.sock --protocol=SOCKET`. WP-CLI commands that go through WordPress (`wp option`, `wp plugin`, `wp search-replace`, `wp rewrite`) work normally.
- **The theme needs `vendor/`.** `wp-content/themes/kzmielec/functions.php:13` is an unconditional `require_once .../vendor/autoload.php`. Activating the theme without it is an immediate fatal error. Ship the `--no-dev` tree: 216 KB, 27 files.
- **Never pass `--delete` to rsync.** Not for the theme, not for the plugins, not for uploads.
- **Deploy `main` at `6c35126`** — the state that passed the WCAG audit. The accessibility bar is *not* part of this cutover; it lives on branch `belka-wcag` (`51ffc2c`).
- **Do not touch `$OTHERSITE` or `$PRODUSER.vot.pl`** on the same hosting account.
- **The local DDEV database is authoritative and must not be modified.** All URL rewriting happens in an exported file via `--export`, never in place.
- Production-only plugins to delete: `autoptimize`, `jquery-manager`, `wordfence`. `litespeed-cache` **stays** and gets reactivated in Task 8.
- Administrator password stays the local one (spec D7). No password is set.
- Facebook token ships as-is (spec D6). No re-authorisation.

## Shared environment

Every task that touches production starts by exporting these. `STAMP` must be the **same value** across all tasks — generate it once in Task 1 and reuse the literal string.

```bash
export KZ=$PRODPATH
export SSHP="ssh -p 222 $PRODUSER@$PRODHOST"
export STAMP=$(date +%Y%m%d-%H%M)   # Task 1 only; afterwards paste the literal value
export BACKUP=$PRODHOME/cutover-backup-$STAMP
export LOCAL=/home/lukasz/projects/kzmielec
export TMP=$LOCAL/.deploy-tmp
```

## File Structure

Nothing in the repository changes. This plan creates only throwaway artefacts plus two helper scripts that are worth keeping.

- Create: `$TMP/` — local scratch directory for the production `vendor/` tree and the SQL export. Deleted in Task 12.
- Create: `scripts/deploy/export-plugin-options.php` — reads the production option groups the import would destroy, writes JSON. Runs via `wp eval-file`.
- Create: `scripts/deploy/restore-plugin-options.php` — writes them back byte-identically after the import.
- Create: `scripts/deploy/verify-production.js` — the post-cutover checks (axe, URL statuses, redirects).
- Server-side, created and left in place as the rollback point: `$BACKUP/` containing `prod-db.sql`, `wp-content-themes-plugins.tgz`, `htaccess.bak`, `wp-config.php.bak`, `prod-plugin-options.json`.

The two PHP helpers exist because option values are serialized PHP. Round-tripping them through `wp option get/update` on the command line mangles them; reading and writing `option_value` as raw bytes through `$wpdb` does not.

---

## Task 1: Create the backup directory and dump the production database

**Files:**
- Create (server): `$BACKUP/prod-db.sql`
- Create (local): `$TMP/prod-db.sql`

**Interfaces:**
- Produces: `$BACKUP` path and the `STAMP` literal, reused by every later task. `$BACKUP/prod-db.sql` is the rollback point for Task 11.

- [ ] **Step 1: Confirm the connection and the docroot are the ones we think they are**

```bash
export KZ=$PRODPATH
export SSHP="ssh -p 222 $PRODUSER@$PRODHOST"
$SSHP "wp --path=$KZ option get siteurl; wp --path=$KZ theme list --status=active --field=name"
```

Expected exactly:
```
https://kzmielec.pl
html5blank-stable
```

Note `wp option get` prints the value **without** the trailing slash even though the stored column has one — see Task 6 Step 1. Do not "fix" anything on that basis.

**Abort if the siteurl is anything else** — it means `--path` resolved to the wrong site (`~/public_html` is a symlink to `$OTHERSITE`'s docroot).

- [ ] **Step 2: Create the backup directory**

```bash
export STAMP=$(date +%Y%m%d-%H%M)
echo "STAMP=$STAMP   # <- write this down, reuse the literal in every later task"
export BACKUP=$PRODHOME/cutover-backup-$STAMP
$SSHP "mkdir -p $BACKUP && ls -ld $BACKUP"
```

Expected: a `drwx` line for the new directory.

- [ ] **Step 3: Dump the database over the socket**

`wp db export` cannot work here (see Global Constraints), so call `mariadb-dump` directly and read the credentials out of `wp-config.php` with WP-CLI.

```bash
$SSHP "cd $KZ && \
  DBN=\$(wp --path=$KZ config get DB_NAME) && \
  DBU=\$(wp --path=$KZ config get DB_USER) && \
  MYSQL_PWD=\"\$(wp --path=$KZ config get DB_PASSWORD)\" mariadb-dump \
    --socket=/var/lib/mysql/mysql.sock --protocol=SOCKET \
    --single-transaction --quick --default-character-set=utf8mb4 \
    -u \"\$DBU\" \"\$DBN\" > $BACKUP/prod-db.sql && \
  ls -l $BACKUP/prod-db.sql"
```

Expected: a file of several MB. **Abort if it is under 1 MB** — production has 17 pages and 196 LiteSpeed options, so a tiny file means the dump failed silently.

- [ ] **Step 4: Verify the dump is complete, not truncated**

```bash
$SSHP "tail -2 $BACKUP/prod-db.sql; grep -c 'CREATE TABLE' $BACKUP/prod-db.sql"
```

Expected: the tail contains `-- Dump completed`, and the `CREATE TABLE` count is 45 or more (12 WordPress core tables plus the 34 plugin tables).

- [ ] **Step 5: Pull the dump down to the local disk**

A backup that exists only on the server is not a backup.

```bash
export LOCAL=/home/lukasz/projects/kzmielec
export TMP=$LOCAL/.deploy-tmp
mkdir -p $TMP
scp -P 222 $PRODUSER@$PRODHOST:$BACKUP/prod-db.sql $TMP/prod-db.sql
ls -l $TMP/prod-db.sql
```

Expected: the local file size matches the remote one.

---

## Task 2: Archive the production files and capture the plugin options

**Files:**
- Create (server): `$BACKUP/wp-content-themes-plugins.tgz`, `$BACKUP/htaccess.bak`, `$BACKUP/wp-config.php.bak`, `$BACKUP/prod-plugin-options.json`
- Create (local): `scripts/deploy/export-plugin-options.php`, `$TMP/wp-content-themes-plugins.tgz`

**Interfaces:**
- Consumes: `$BACKUP` from Task 1.
- Produces: `$BACKUP/prod-plugin-options.json`, consumed by `restore-plugin-options.php` in Task 8. Its shape is `{"<option_name>": {"value": "<raw bytes>", "autoload": "yes|no"}, ...}`.

- [ ] **Step 1: Archive themes and plugins**

```bash
$SSHP "cd $KZ/wp-content && tar czf $BACKUP/wp-content-themes-plugins.tgz themes plugins && ls -l $BACKUP/wp-content-themes-plugins.tgz"
```

Expected: an archive of at least a few MB.

- [ ] **Step 2: Copy the two config files that rsync and the import must not lose**

```bash
$SSHP "cp $KZ/.htaccess $BACKUP/htaccess.bak && cp $KZ/wp-config.php $BACKUP/wp-config.php.bak && \
  grep -c 'BEGIN LSCACHE' $BACKUP/htaccess.bak; wc -l $BACKUP/htaccess.bak"
```

Expected: `1` for the LSCACHE block (record the line count — Task 8 compares against it).

- [ ] **Step 3: Write the option exporter**

Create `scripts/deploy/export-plugin-options.php`:

```php
<?php
/**
 * Exports the option groups a wholesale database import would destroy.
 *
 * Values are read straight out of `option_value` and written as raw strings, so
 * serialized PHP survives the round trip byte for byte — which passing them
 * through `wp option get` on a command line does not.
 *
 * Run with: wp eval-file scripts/deploy/export-plugin-options.php <output-path>
 *
 * @package Kzmielec\Deploy
 */

$target = $args[0] ?? '';
if ( '' === $target ) {
	WP_CLI::error( 'Pass the output path as the first argument.' );
}

$prefixes = array( 'litespeed', 'wpseo', 'sb_instagram', 'wordfence' );

global $wpdb;
$out = array();
foreach ( $prefixes as $prefix ) {
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT option_name, option_value, autoload FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( $prefix ) . '%'
		),
		ARRAY_A
	);
	foreach ( $rows as $row ) {
		$out[ $row['option_name'] ] = array(
			'value'    => $row['option_value'],
			'autoload' => $row['autoload'],
		);
	}
}

$json = wp_json_encode( $out );
if ( false === $json ) {
	WP_CLI::error( 'Could not encode the options as JSON.' );
}
if ( false === file_put_contents( $target, $json ) ) {
	WP_CLI::error( 'Could not write ' . $target );
}

WP_CLI::success( count( $out ) . ' options exported to ' . $target );
```

- [ ] **Step 4: Upload the exporter and run it on production**

```bash
scp -P 222 scripts/deploy/export-plugin-options.php $PRODUSER@$PRODHOST:$BACKUP/
$SSHP "cd $KZ && wp --path=$KZ eval-file $BACKUP/export-plugin-options.php $BACKUP/prod-plugin-options.json"
```

Expected: `Success: N options exported…` where **N is at least 208** (196 LiteSpeed + 7 Yoast + 5 Instagram, plus any Wordfence options).

**Abort if N is under 200** — the export missed a group, and Task 8 would have nothing to restore.

- [ ] **Step 5: Record the Wordfence WAF state and back the files up**

Task 3 removes these, so capture them while they exist. Also copy the two files themselves — `.htaccess` is already archived, but `.user.ini` and the WAF stub are not covered by anything else:

```bash
$SSHP "cp $KZ/.user.ini $BACKUP/user.ini.bak; cp $KZ/wordfence-waf.php $BACKUP/wordfence-waf.php.bak; ls -l $BACKUP/user.ini.bak $BACKUP/wordfence-waf.php.bak"
```

Then read the state:

```bash
$SSHP "grep -rn 'auto_prepend_file' $KZ/.user.ini $KZ/php.ini $KZ/.htaccess 2>/dev/null; \
  ls -l $KZ/wordfence-waf.php 2>/dev/null; echo '--- exit marker ---'"
```

Write down every line printed. If `auto_prepend_file` appears anywhere, Task 3 Step 4 must remove it.

- [ ] **Step 6: Pull the archive down locally**

```bash
scp -P 222 $PRODUSER@$PRODHOST:$BACKUP/wp-content-themes-plugins.tgz $TMP/
scp -P 222 $PRODUSER@$PRODHOST:$BACKUP/prod-plugin-options.json $TMP/
ls -l $TMP/
```

Expected: both files present locally. **The safety net is now complete; nothing before this point has changed production.**

---

## Task 3: Delete the production-only plugins

**Files:**
- Modify (server): production plugin set; possibly `$KZ/.user.ini` and `$KZ/wordfence-waf.php`

**Interfaces:**
- Consumes: the WAF state recorded in Task 2 Step 5.
- Produces: a production install with `autoptimize`, `jquery-manager` and `wordfence` gone, still serving HTTP 200 on the old theme.

Why now and not after the import: each plugin's own uninstaller runs only while the plugin is present and functional. After the import they would be deactivated and their config tables gone, so the cleanup would never happen.

- [ ] **Step 1: Confirm the site is healthy before touching anything**

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://kzmielec.pl/
```

Expected: `200`. **Abort the whole cutover if it is not** — you need a known-good baseline before removing a firewall.

- [ ] **Step 2: Deactivate the three plugins**

```bash
$SSHP "wp --path=$KZ plugin deactivate autoptimize jquery-manager wordfence"
curl -s -o /dev/null -w "%{http_code}\n" https://kzmielec.pl/
```

Expected: three `Success` lines, then `200`.

- [ ] **Step 3: Delete them, which runs their uninstall hooks**

```bash
$SSHP "wp --path=$KZ plugin delete autoptimize jquery-manager wordfence; wp --path=$KZ plugin list --fields=name,status"
```

Expected: the remaining list is exactly `litespeed-cache`, `instagram-feed`, `wordpress-seo`.

- [ ] **Step 4: Remove the Wordfence WAF leftovers**

**Measured on production 2026-08-03**, so this is not conditional — all three hooks exist:

- `$KZ/.user.ini` contains nothing but the Wordfence block: a `; Wordfence WAF` comment, `auto_prepend_file = '$KZ/wordfence-waf.php'`, and `; END Wordfence WAF`. The whole file can go.
- `$KZ/wordfence-waf.php` exists (436 bytes, dated Oct 2019).
- `$KZ/.htaccess` lines 58–69 hold a `# Wordfence WAF` … `# END Wordfence WAF` block whose only job is denying web access to `.user.ini`.

**Severity, stated accurately:** the stub is defensive — it wraps its `include_once` in `if (file_exists('…/plugins/wordfence/waf/bootstrap.php'))`. With the plugin directory gone that test fails and the file does nothing, so an orphaned prepend here does **not** fatal. Removing it is tidiness, not rescue. (An unguarded prepend in some other install would be a site-down bug; this one is not.)

All three files are already backed up by Task 2 (`user.ini.bak`, `wordfence-waf.php.bak`, `htaccess.bak`).

`sed` with a marker range, not a `perl` `.*?` substitution: the range form is exact and does not care whether the file ends with a newline (this one's last line *is* `# END Wordfence WAF`).

```bash
$SSHP "rm -f $KZ/.user.ini $KZ/wordfence-waf.php; \
  sed -i '/^# Wordfence WAF\$/,/^# END Wordfence WAF\$/d' $KZ/.htaccess; \
  echo -n 'linie: '; wc -l < $KZ/.htaccess; \
  echo -n 'wordfence: '; grep -c -i wordfence $KZ/.htaccess; \
  echo -n 'LSCACHE: '; grep -c 'BEGIN LSCACHE' $KZ/.htaccess; \
  ls -l $KZ/.user.ini $KZ/wordfence-waf.php 2>&1 | tail -2"
```

Expected, measured 2026-08-03: `linie: 57` (down from 69, exactly the 12-line block), `wordfence: 0`, `LSCACHE: 1`, and "No such file" for both files.

Do **not** assert anything about `BEGIN WordPress` — this `.htaccess` legitimately contains **two** such blocks and did so before the cutover.

- [ ] **Step 4b: Confirm the only change is the Wordfence block**

```bash
$SSHP "cat $KZ/.htaccess" > /tmp/htaccess.now
diff $TMP/htaccess.bak /tmp/htaccess.now
```

Expected: a single deletion hunk `58,69d57` containing the `# Wordfence WAF` … `# END Wordfence WAF` lines, and nothing else.

**Abort if any other line differs** — restore from `$BACKUP/htaccess.bak`.

**Residue left behind on purpose:** `wp-content/wflogs/` (12 MB of Wordfence logs) survives. Nothing references it — the only pointer was `WFWAF_LOG_PATH` in the stub just deleted — but it is **not** covered by the Task 2 tarball, which archived `themes` and `plugins` only. Removing unbacked-up data is not worth doing silently, so it stays; raise it with the user separately.

- [ ] **Step 5: Verify the site still serves**

```bash
for u in / /misja/ /rodo/ /roznica-wyznan/; do
  printf "%-20s %s\n" "$u" "$(curl -s -o /dev/null -w '%{http_code}' https://kzmielec.pl$u)"
done
```

Expected: `200` on all four. **If anything returns 500, run Task 11 rollback immediately** — a fatal here is the WAF prepend.

---

## Task 4: Build the production Composer tree

**Files:**
- Create (local): `$TMP/vendor/`

**Interfaces:**
- Produces: `$TMP/vendor/` — 27 files, ~216 KB, containing `autoload.php`, `composer/` and `enshrined/`. Task 5 rsyncs it to `wp-content/themes/kzmielec/vendor/`.

The working `wp-content/themes/kzmielec/vendor/` is 47 MB because it holds PHPStan and PHPCS. It must not be shipped, and it must not be destroyed either — the local gates need it. So build a separate tree from the same lockfile.

- [ ] **Step 1: Build with dev dependencies excluded**

The host `php` is Windows PHP and hangs, so this runs in the container.

```bash
cd $LOCAL
mkdir -p $TMP
ddev exec bash -c 'rm -rf /var/www/html/.deploy-tmp/vendorbuild && \
  mkdir -p /var/www/html/.deploy-tmp/vendorbuild && \
  cp wp-content/themes/kzmielec/composer.json wp-content/themes/kzmielec/composer.lock /var/www/html/.deploy-tmp/vendorbuild/ && \
  cd /var/www/html/.deploy-tmp/vendorbuild && \
  composer install --no-dev --no-interaction --optimize-autoloader'
```

Expected: Composer reports installing `enshrined/svg-sanitize` and generating optimized autoload files.

- [ ] **Step 2: Verify the tree is the lean one**

```bash
mv $TMP/vendorbuild/vendor $TMP/vendor
rm -rf $TMP/vendorbuild
du -sh $TMP/vendor && find $TMP/vendor -type f | wc -l && ls $TMP/vendor
```

Expected: about `216K`, `27` files, and exactly `autoload.php  composer  enshrined`.

**Abort if PHPStan or PHPCS appear** — that is the dev tree and it must not ship.

- [ ] **Step 3: Confirm the local dev vendor is untouched**

```bash
du -sh $LOCAL/wp-content/themes/kzmielec/vendor
```

Expected: still about `47M`.

---

## Task 5: Upload the theme, the plugins and the media

**Files:**
- Create (server): `$KZ/wp-content/themes/kzmielec/`, `$KZ/wp-content/plugins/{custom-block-package,custom-posts,comparison-of-religions}/`
- Modify (server): `$KZ/wp-content/uploads/`

**Interfaces:**
- Consumes: `$TMP/vendor/` from Task 4.
- Produces: every file the new theme needs on disk, with the theme still inactive.

Visitors see no change during this task.

- [ ] **Step 1: Confirm the working tree is the audited state**

```bash
cd $LOCAL
git rev-parse --short HEAD && git status --porcelain -- wp-content/ scripts/
```

Expected: `6c35126`, and **no output** for the second command. Untracked `.deploy-tmp/` and modified `.claude/` files are fine; anything under `wp-content/` is not — that would mean the accessibility bar, or something else unreviewed, is about to ship.

- [ ] **Step 2: Upload the theme**

```bash
rsync -az --info=stats2 -e "ssh -p 222" \
  --exclude 'node_modules' --exclude 'src' --exclude 'vendor' \
  --exclude '.git*' --exclude 'composer.json' --exclude 'composer.lock' \
  --exclude 'package.json' --exclude 'package-lock.json' \
  --exclude 'phpstan.neon' --exclude 'phpcs.xml*' --exclude 'webpack.config.js' \
  --exclude 'tsconfig.json' --exclude 'biome.json' --exclude '*.map' \
  $LOCAL/wp-content/themes/kzmielec/ \
  $PRODUSER@$PRODHOST:$KZ/wp-content/themes/kzmielec/
```

Expected: a stats block listing several hundred transferred files, no errors.

- [ ] **Step 3: Upload the production `vendor/` separately**

It was excluded above on purpose, so the 47 MB dev tree could not slip in.

```bash
rsync -az -e "ssh -p 222" $TMP/vendor/ \
  $PRODUSER@$PRODHOST:$KZ/wp-content/themes/kzmielec/vendor/
$SSHP "ls $KZ/wp-content/themes/kzmielec/vendor/ && test -f $KZ/wp-content/themes/kzmielec/vendor/autoload.php && echo AUTOLOAD-OK"
```

Expected: `autoload.php  composer  enshrined` then `AUTOLOAD-OK`.

- [ ] **Step 4: Upload the three plugins**

**Do not exclude `src/` here.** This was a real bug on 2026-08-03: `custom-posts` registers its own `spl_autoload_register` that includes `src/Core/CptBuilder.php`, `src/Core/TaxBuilder.php`, `src/Posts/RegisterPosts.php` and `src/Posts/CustomColumns.php` — it has no build step, so `src/` **is** its runtime code. Excluding it deployed a bootstrap with nothing to bootstrap: the `meetings` CPT never registered and `/zaplanuj-wizyte/` returned 404 while every other URL looked fine.

The two block plugins genuinely do not need `src/` (their eleven and one `render.php` files are mirrored into `build/`), but the saving is a few hundred kilobytes and the failure mode is silent. Ship `src/` for all three.

```bash
for p in custom-block-package custom-posts comparison-of-religions; do
  echo "=== $p ==="
  rsync -az --info=stats2 -e "ssh -p 222" \
    --exclude 'node_modules' --exclude 'vendor' \
    --exclude '.git*' --exclude 'composer.json' --exclude 'composer.lock' \
    --exclude 'package.json' --exclude 'package-lock.json' \
    --exclude 'phpstan.neon' --exclude 'phpcs.xml*' --exclude 'webpack.config.js' \
    --exclude 'tsconfig.json' --exclude 'biome.json' --exclude '*.map' \
    $LOCAL/wp-content/plugins/$p/ \
    $PRODUSER@$PRODHOST:$KZ/wp-content/plugins/$p/
done
```

Expected: three stats blocks, no errors.

- [ ] **Step 5: Verify WordPress can see the new theme and plugins**

```bash
$SSHP "wp --path=$KZ theme list --fields=name,status | grep kzmielec; wp --path=$KZ plugin list --fields=name,status"
```

Expected: `kzmielec` listed as `inactive`, and the three plugins listed as `inactive`. **Abort if the theme is missing** — WordPress cannot see `style.css`.

- [ ] **Step 6: Upload the media library, adding without removing**

Production has 33 MB and we have 26 MB. The goal is to add our 46 attachments, not to delete files production still has.

```bash
rsync -az --info=stats2 -e "ssh -p 222" \
  $LOCAL/wp-content/uploads/ \
  $PRODUSER@$PRODHOST:$KZ/wp-content/uploads/
$SSHP "du -sh $KZ/wp-content/uploads"
```

Expected: the total is now larger than 33 MB. **There must be no `--delete` in that command.**

- [ ] **Step 7: Confirm visitors still see the old site**

```bash
curl -s https://kzmielec.pl/ | grep -c 'html5blank\|a11y-bar' 
curl -s -o /dev/null -w "%{http_code}\n" https://kzmielec.pl/
```

Expected: `200`, and no `a11y-bar` anywhere. The switchover has not happened yet.

---

## Task 6: Produce the database export with URLs rewritten

**Files:**
- Create (local): `$TMP/kzmielec-prod.sql`

**Interfaces:**
- Produces: `$TMP/kzmielec-prod.sql` — the local database with `kzmielec.ddev.site` replaced by `kzmielec.pl` and the 34 plugin-owned tables omitted. Task 7 imports it.

`--export` writes the replacements to a file and leaves the local database alone, which is why production is never briefly exposed to `.ddev.site` URLs and the local install keeps working.

- [ ] **Step 1: Record the local state so Step 5 can prove it did not change**

```bash
cd $LOCAL
ddev wp eval 'global $wpdb; foreach (["siteurl","home"] as $k) { $r=$wpdb->get_row($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s", $k)); printf("%s = %s (%d)\n", $k, $r->option_value, strlen($r->option_value)); }'
```

Expected: `https://kzmielec.ddev.site/ (27)` for both. The **trailing slash is real** — read the raw column rather than `wp option get`, which prints the value in a way that hides it. Production stores `https://kzmielec.pl/ (20)`, also with the slash, so the import preserves the existing convention and no normalisation step is needed.

- [ ] **Step 2: Export with the replacement applied and the plugin tables skipped**

The skip list is written out in full rather than as a wildcard so there is no doubt about what is excluded.

```bash
ddev wp search-replace 'kzmielec.ddev.site' 'kzmielec.pl' \
  --all-tables-with-prefix \
  --skip-tables=wp_wfauditevents,wp_wfblockediplog,wp_wfblocks7,wp_wfconfig,wp_wfcrawlers,wp_wffilechanges,wp_wffilemods,wp_wfhits,wp_wfhoover,wp_wfissues,wp_wfknownfilelist,wp_wflivetraffichuman,wp_wflocs,wp_wflogins,wp_wfls_2fa_secrets,wp_wfls_role_counts,wp_wfls_settings,wp_wfnotifications,wp_wfpendingissues,wp_wfreversecache,wp_wfsecurityevents,wp_wfsnipcache,wp_wfstatus,wp_wftrafficrates,wp_wfwaffailures,wp_yoast_expiring_store,wp_yoast_indexable,wp_yoast_indexable_hierarchy,wp_yoast_migrations,wp_yoast_primary_term,wp_yoast_seo_links,wp_yoast_seo_meta,wp_litespeed_url,wp_litespeed_url_file,wp_sbi_feed_caches,wp_sbi_feeds,wp_sbi_instagram_feed_locator,wp_sbi_instagram_feeds_posts,wp_sbi_instagram_posts,wp_sbi_sources \
  --export=/var/www/html/.deploy-tmp/kzmielec-prod.sql \
  --report-changed-only
```

Expected — this command was rehearsed on 2026-08-03 and these are the measured values, not estimates:

```
wp_options   option_value    8    PHP
wp_posts     post_content   74    PHP
wp_posts     guid          186    PHP
wp_usermeta  meta_value      1    PHP
Success: Made 269 replacements and exported to …
```

The 585 hits that a naive run finds in the Wordfence and Yoast tables are absent because those tables are skipped.

- [ ] **Step 3: Verify the export replaces tables rather than merging into them**

```bash
echo "DROP=$(grep -c 'DROP TABLE IF EXISTS' $TMP/kzmielec-prod.sql) CREATE=$(grep -c 'CREATE TABLE' $TMP/kzmielec-prod.sql)"
grep -c 'CREATE TABLE `wp_wf\|CREATE TABLE `wp_yoast_\|CREATE TABLE `wp_litespeed_\|CREATE TABLE `wp_sbi_' $TMP/kzmielec-prod.sql
grep -o 'DROP TABLE IF EXISTS `[^`]*`' $TMP/kzmielec-prod.sql | sed 's/.*`\(.*\)`/\1/' | tr '\n' ' '
```

Expected, measured on 2026-08-03: `DROP=12 CREATE=12`, then `0`, then exactly these twelve WordPress core tables:

```
wp_commentmeta wp_comments wp_links wp_options wp_postmeta wp_posts
wp_term_relationships wp_term_taxonomy wp_termmeta wp_terms wp_usermeta wp_users
```

**Abort if the second count is not zero** — a skipped table leaked into the export and would overwrite production plugin state. Note the check greps for `CREATE TABLE`, not for the bare table name: option *values* legitimately mention table names in passing, and one such reference exists.

- [ ] **Step 4: Verify no ddev URL survived and the theme is the new one**

```bash
grep -c 'kzmielec.ddev.site' $TMP/kzmielec-prod.sql
grep -o "'template','kzmielec'\|'stylesheet','kzmielec'" $TMP/kzmielec-prod.sql | sort -u
ls -lh $TMP/kzmielec-prod.sql
```

Expected: `0` for the first. The second prints both `template` and `stylesheet` set to `kzmielec`. The file is a few MB.

**Abort if the first count is not zero.**

- [ ] **Step 5: Confirm the local database was not modified**

```bash
ddev wp option get siteurl && curl -sk -o /dev/null -w "%{http_code}\n" https://kzmielec.ddev.site/
```

Expected: still `https://kzmielec.ddev.site`, and `200`.

---

## Task 7: Import the database — the switchover

**Files:**
- Modify (server): the production database

**Interfaces:**
- Consumes: `$TMP/kzmielec-prod.sql` from Task 6.
- Produces: production serving the new theme. From here on, a failure means Task 11.

This is the only irreversible-feeling step, and it is over in seconds. Everything needed to undo it is already on disk from Tasks 1 and 2.

- [ ] **Step 1: Upload the dump**

```bash
scp -P 222 $TMP/kzmielec-prod.sql $PRODUSER@$PRODHOST:$BACKUP/
$SSHP "ls -l $BACKUP/kzmielec-prod.sql"
```

Expected: the remote size matches the local one.

- [ ] **Step 2: Import over the socket**

`wp db import` cannot work here. Note the import runs `DROP TABLE` per included table, so the 34 skipped tables survive untouched.

```bash
$SSHP "DBN=\$(wp --path=$KZ config get DB_NAME) && \
  DBU=\$(wp --path=$KZ config get DB_USER) && \
  MYSQL_PWD=\"\$(wp --path=$KZ config get DB_PASSWORD)\" mariadb \
    --socket=/var/lib/mysql/mysql.sock --protocol=SOCKET \
    --default-character-set=utf8mb4 -u \"\$DBU\" \"\$DBN\" < $BACKUP/kzmielec-prod.sql && \
  echo IMPORT-DONE"
```

Expected: `IMPORT-DONE` with no SQL errors printed.

- [ ] **Step 3: Verify the switchover landed**

```bash
$SSHP "wp --path=$KZ option get siteurl; \
  wp --path=$KZ theme list --status=active --field=name; \
  wp --path=$KZ option get page_on_front; \
  wp --path=$KZ option get kzmielec_belief_pages --format=json; \
  wp --path=$KZ post list --post_type=meetings --post_status=publish --format=count; \
  wp --path=$KZ post list --post_type=comparison_topic --post_status=publish --format=count"
```

Expected exactly — note the siteurl prints without the trailing slash, because `wp option get` displays it that way (the stored value keeps the slash, unchanged from before the import):
```
https://kzmielec.pl
kzmielec
131
[2,65,77,79,81,83,88,90]
3
37
```

**If the theme is not `kzmielec`, or the belief IDs differ, go to Task 11.**

- [ ] **Step 4: Verify the plugin-owned tables survived**

```bash
$SSHP "DBN=\$(wp --path=$KZ config get DB_NAME) && \
  MYSQL_PWD=\"\$(wp --path=$KZ config get DB_PASSWORD)\" mariadb --socket=/var/lib/mysql/mysql.sock --protocol=SOCKET \
    -u \$(wp --path=$KZ config get DB_USER) -N -e \
    \"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='\$DBN' AND table_name LIKE 'wp_yoast%';\" \$DBN"
```

Expected: `7`. The Yoast tables are production's own, not local's copies.

- [ ] **Step 5: First look at the live site**

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://kzmielec.pl/
curl -s https://kzmielec.pl/ | grep -c 'navigable-tiles\|site-header'
```

Expected: `200` and a non-zero count. A `500` here means the theme's `vendor/` is missing — check Task 5 Step 3 before rolling back.

---

## Task 8: Configuration after the import

**Files:**
- Create (local): `scripts/deploy/restore-plugin-options.php`
- Modify (server): `wp_options`, active plugin set, rewrite rules, cache

**Interfaces:**
- Consumes: `$BACKUP/prod-plugin-options.json` from Task 2.
- Produces: production with its own LiteSpeed / Yoast / Instagram settings back, `litespeed-cache` active, and working permalinks.

- [ ] **Step 1: Write the option restorer**

Create `scripts/deploy/restore-plugin-options.php`:

```php
<?php
/**
 * Restores the option groups the database import overwrote.
 *
 * Writes `option_value` as raw bytes for the same reason the exporter reads it
 * that way: the values are serialized PHP and must survive unchanged. Rows are
 * deleted and re-inserted rather than updated, because the imported table may
 * hold a different `option_id` for the same name.
 *
 * Run with: wp eval-file scripts/deploy/restore-plugin-options.php <input-path>
 *
 * @package Kzmielec\Deploy
 */

$source = $args[0] ?? '';
if ( '' === $source || ! is_readable( $source ) ) {
	WP_CLI::error( 'Pass a readable input path as the first argument.' );
}

$data = json_decode( (string) file_get_contents( $source ), true );
if ( ! is_array( $data ) ) {
	WP_CLI::error( 'Input is not a JSON object.' );
}

global $wpdb;
$restored = 0;
foreach ( $data as $name => $entry ) {
	if ( ! is_array( $entry ) || ! array_key_exists( 'value', $entry ) ) {
		WP_CLI::warning( 'Skipping malformed entry: ' . $name );
		continue;
	}
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", $name ) );
	$wpdb->insert(
		$wpdb->options,
		array(
			'option_name'  => $name,
			'option_value' => $entry['value'],
			'autoload'     => $entry['autoload'] ?? 'yes',
		)
	);
	++$restored;
}

wp_cache_flush();
WP_CLI::success( $restored . ' options restored.' );
```

- [ ] **Step 2: Upload and run it**

```bash
scp -P 222 scripts/deploy/restore-plugin-options.php $PRODUSER@$PRODHOST:$BACKUP/
$SSHP "wp --path=$KZ eval-file $BACKUP/restore-plugin-options.php $BACKUP/prod-plugin-options.json"
```

Expected: `Success: N options restored.` with the same N as Task 2 Step 4.

- [ ] **Step 3: Verify the counts came back**

```bash
$SSHP "wp --path=$KZ option list --search='litespeed*' --format=count; \
  wp --path=$KZ option list --search='wpseo*' --format=count; \
  wp --path=$KZ option list --search='sb_instagram*' --format=count"
```

Expected: `196`, `7`, `5`.

- [ ] **Step 4: Reactivate LiteSpeed Cache**

The import switched it off, because local `active_plugins` omits it.

```bash
$SSHP "wp --path=$KZ plugin activate litespeed-cache; wp --path=$KZ plugin list --fields=name,status"
```

Expected: `litespeed-cache` active, and the three own plugins active (their activation came with the database).

- [ ] **Step 5: Flush the rewrite rules**

`/zaplanuj-wizyte/` is the `meetings` CPT archive rewrite, not a page. Without this it 404s.

**Check the CPT is actually registered first.** A flush only writes rules for post types that exist at that moment, so flushing before the plugin's classes are all present produces a rule set with no archive in it — and the flush reports success either way.

```bash
$SSHP "wp --path=$KZ post-type list --fields=name,public,has_archive | grep -E 'name|meetings'"
```

Expected: `meetings  1  zaplanuj-wizyte`. If it says nothing, `custom-posts/src/` is missing — see Task 5 Step 4.

```bash
$SSHP "wp --path=$KZ rewrite flush --hard; wp --path=$KZ rewrite list --format=csv | grep -ci zaplanuj"
```

Expected: `Success: Rewrite rules flushed.` and `4` matching rules. A count of `0` means the CPT was not registered when the flush ran; fix that and flush again.

A `Warning: Regenerating a .htaccess file requires special configuration` line is normal here and harmless — WordPress declines to rewrite `.htaccess`, which is what we want, since the LSCACHE block lives there.

- [ ] **Step 6: Confirm the `.htaccess` cache block survived**

```bash
$SSHP "grep -c 'BEGIN LSCACHE' $KZ/.htaccess; wc -l $KZ/.htaccess"
```

Expected: `1`, and a line count close to the one recorded in Task 2 Step 2. If the block is gone, restore it from `$BACKUP/htaccess.bak`.

- [ ] **Step 7: Purge the page cache and confirm it recovers**

```bash
$SSHP "wp --path=$KZ litespeed-purge all"
curl -sI https://kzmielec.pl/ | grep -i 'x-litespeed-cache'
curl -sI https://kzmielec.pl/ | grep -i 'x-litespeed-cache'
```

Expected: the first request reports `miss`, the second `hit`.

- [ ] **Step 8: Confirm a login still works**

Per spec D7 the password is the local one now. Check the account resolves rather than waiting to discover it later.

```bash
$SSHP "wp --path=$KZ user list --fields=ID,user_login,user_email,roles"
```

Expected: one administrator, ID `1`, `wpm`. Then sign in at `https://kzmielec.pl/wp-admin/` with the **local** password and confirm the dashboard loads.

---

## Task 9: Verify the live site

**Files:**
- Create (local): `scripts/deploy/verify-production.js`

**Interfaces:**
- Consumes: a production site that is serving the new theme.
- Produces: a pass/fail report. Nothing is declared finished before this passes.

- [ ] **Step 1: Check every pre-existing URL still resolves**

None of the 17 old page URLs may break — that is the SEO contract of this migration.

```bash
for u in / /w-co-wierzymy/ /misja/ /wizja/ /wartosci/ /historia-zboru-w-mielcu/ \
         /rodo/ /prawo/ /roznica-wyznan/ /zaplanuj-wizyte/ \
         /polityka-ochrony-dzieci-przed-krzywdzeniem/ /w-sprawie-wieczerzy-panskiej/; do
  printf "%-46s %s\n" "$u" "$(curl -s -o /dev/null -w '%{http_code}' https://kzmielec.pl$u)"
done
```

Expected: `200` for every line. Note `/zaplanuj-wizyte/` proves Task 8 Step 5 worked.

- [ ] **Step 2: Check the meetings redirect**

```bash
$SSHP "wp --path=$KZ post list --post_type=meetings --post_status=publish --field=post_name"
curl -s -o /dev/null -w "%{http_code} -> %{redirect_url}\n" "https://kzmielec.pl/meetings/$(
  $SSHP "wp --path=$KZ post list --post_type=meetings --post_status=publish --field=post_name" | head -1)/"
```

Expected: `301 -> https://kzmielec.pl/zaplanuj-wizyte/#<slug>`.

- [ ] **Step 3: Check the content-bearing blocks actually rendered**

```bash
curl -s https://kzmielec.pl/roznica-wyznan/ | grep -c 'cor-accordion\|comparison-accordion'
curl -s https://kzmielec.pl/ | grep -c 'navigable-tiles'
curl -s https://kzmielec.pl/ | grep -c 'facebook-feed'
curl -s https://kzmielec.pl/ | grep -c 'map-block\|leaflet'
curl -s https://kzmielec.pl/prawo/ | grep -c 'Pobierz PDF'
curl -s https://kzmielec.pl/ | grep -c 'Przejdź do treści'
```

Expected: all six counts non-zero. A zero on the first means the 37 comparison topics did not come across; a zero on the fourth means the map block on the "Znajdź nas" section did not render; the last one confirms the skip link — and therefore the theme's own templates — is live.

- [ ] **Step 4: Write the automated checker**

Create `scripts/deploy/verify-production.js`. It is the harness written for the accessibility-bar audit, repointed at production and stripped of the request interception that is no longer needed.

```javascript
// Post-cutover accessibility check against the live site.
// Usage: node scripts/deploy/verify-production.js
const fs = require('node:fs');
const puppeteer = require('puppeteer');
const { source: axeSource } = require('axe-core');

const BASE = 'https://kzmielec.pl';
const CHROME =
    '/home/lukasz/.claude/jobs/52da36a6/tmp/.cache/chrome-headless-shell/linux-151.0.7922.47/chrome-headless-shell-linux64/chrome-headless-shell';
const PAGES = [
    ['home', '/'],
    ['belief-hub', '/w-co-wierzymy/'],
    ['belief-page', '/wizja/'],
    ['comparison', '/roznica-wyznan/'],
    ['meetings', '/zaplanuj-wizyte/'],
    ['position', '/w-sprawie-wieczerzy-panskiej/'],
    ['prawo-pdf', '/prawo/'],
    ['historia', '/historia-zboru-w-mielcu/'],
    ['rodo', '/rodo/'],
    ['ochrona-dzieci', '/polityka-ochrony-dzieci-przed-krzywdzeniem/'],
    ['search', '/?s=niedziela'],
    ['404', '/nie-ma-takiej-strony-xyz/'],
];
const VIEWPORTS = [
    ['desktop', 1280, 900],
    ['mobile', 390, 844],
    ['narrow', 320, 700],
];
const AA_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'];

(async () => {
    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'shell',
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });
    const violations = [];
    const overflow = [];
    let scans = 0;

    for (const [vpName, width, height] of VIEWPORTS) {
        for (const [pageName, path] of PAGES) {
            const page = await browser.newPage();
            await page.setViewport({ width, height });
            const label = `${vpName}/${pageName}`;
            try {
                await page.goto(BASE + path, { waitUntil: 'networkidle2', timeout: 60000 });
                const box = await page.evaluate(() => ({
                    scrollWidth: document.documentElement.scrollWidth,
                    clientWidth: document.documentElement.clientWidth,
                }));
                if (box.scrollWidth > box.clientWidth + 1) {
                    overflow.push({ label, ...box });
                }
                await page.evaluate(axeSource);
                const res = await page.evaluate(
                    async (tags) =>
                        await window.axe.run(document, { runOnly: { type: 'tag', values: tags } }),
                    AA_TAGS
                );
                scans++;
                for (const v of res.violations) {
                    violations.push({
                        label,
                        id: v.id,
                        nodes: v.nodes.length,
                        sample: v.nodes[0]?.html?.slice(0, 120),
                    });
                }
            } catch (e) {
                violations.push({ label, id: 'SCAN-ERROR', sample: String(e).slice(0, 160) });
            }
            await page.close();
        }
    }
    await browser.close();

    fs.writeFileSync(
        '/home/lukasz/projects/kzmielec/.deploy-tmp/verify-results.json',
        JSON.stringify({ scans, violations, overflow }, null, 1)
    );
    console.log(`scans: ${scans}`);
    console.log(`AA violations: ${violations.length}`);
    for (const v of violations.slice(0, 15)) {
        console.log(`  ${v.label}  ${v.id}  x${v.nodes ?? '-'}  ${v.sample ?? ''}`);
    }
    console.log(`horizontal overflow: ${overflow.length}`);
    for (const o of overflow) {
        console.log(`  ${o.label}: ${o.scrollWidth} > ${o.clientWidth}`);
    }
    process.exit(violations.length === 0 && overflow.length === 0 ? 0 : 1);
})();
```

- [ ] **Step 5: Run it**

```bash
NODE_PATH=/home/lukasz/.claude/jobs/52da36a6/tmp/node_modules \
  node /home/lukasz/projects/kzmielec/scripts/deploy/verify-production.js
```

`NODE_PATH` is required, not optional: Node resolves `require` relative to the **script's** directory, not the working directory, and `puppeteer` / `axe-core` are installed in the job scratch directory rather than in the repository. Changing directory first is not enough.

Expected: `scans: 36` (12 URLs × 3 widths), `AA violations: 0`, `horizontal overflow: 0`, exit code 0.

This is the same tooling that produced the zero-violation result locally on 2026-08-03, so a non-zero count here means the cutover changed something — most likely a stylesheet that did not upload — rather than a new accessibility defect.

- [ ] **Step 6: Hand it to the user for a human look**

Automated checks cannot judge whether the site looks right. Ask the user to open `https://kzmielec.pl/` and confirm: the front page, the belief navigation, `/roznica-wyznan/`, the Facebook feed, and the map. **The cutover is not finished until they say so.**

---

## Task 10: Record what happened

**Files:**
- Modify: `.claude/PROJECT-NOTES.md`
- Modify: `/home/lukasz/.claude/projects/-home-lukasz-projects-kzmielec/memory/migration-remaining-work.md`
- Modify: `/home/lukasz/.claude/projects/-home-lukasz-projects-kzmielec/memory/prod-access-and-cache.md`

- [ ] **Step 1: Update the memory that says production runs the old theme**

`prod-access-and-cache.md` ends with "Prod still runs OLD theme `html5blank-stable` — theme migration not yet cut over." That becomes false the moment Task 7 succeeds, and a stale note here will mislead a future session into re-planning a migration that already happened.

Replace that closing paragraph with, filling in the real `STAMP`:

```markdown
**Prod runs the new `kzmielec` theme since 2026-08-03** (cutover per
`docs/superpowers/plans/2026-08-03-production-cutover.md`). Plugins removed in the
same operation: `autoptimize`, `jquery-manager`, `wordfence`. Remaining:
`litespeed-cache`, `instagram-feed`, `wordpress-seo` plus the three own plugins.
Rollback point on the server: `$PRODHOME/cutover-backup-<STAMP>/` — holds
`prod-db.sql`, `wp-content-themes-plugins.tgz`, `htaccess.bak`,
`prod-plugin-options.json`. Do not delete it.
```

- [ ] **Step 2: Close Phase 5 in `migration-remaining-work.md`**

Mark item 5 (deploy/cutover) done with the date and the verification result. Leave item 4 (Phase 4 content) as it stands.

- [ ] **Step 3: Note the backup location and the rollback recipe**

Record `$BACKUP` on the server and that `Task 11` below is the rollback, so a future session does not have to reconstruct it.

---

## Task 11: Rollback (only if something fails)

**Files:**
- Modify (server): production database, `wp-content/themes`, `wp-content/plugins`, `.htaccess`

**Interfaces:**
- Consumes: `$BACKUP/prod-db.sql`, `$BACKUP/wp-content-themes-plugins.tgz`, `$BACKUP/htaccess.bak` from Tasks 1 and 2.

Read this before starting Task 3 so it is familiar, not improvised. The database import alone restores the old active theme; the rest repairs the plugin set.

- [ ] **Step 1: Restore the database**

```bash
$SSHP "DBN=\$(wp --path=$KZ config get DB_NAME) && \
  DBU=\$(wp --path=$KZ config get DB_USER) && \
  MYSQL_PWD=\"\$(wp --path=$KZ config get DB_PASSWORD)\" mariadb \
    --socket=/var/lib/mysql/mysql.sock --protocol=SOCKET \
    --default-character-set=utf8mb4 -u \"\$DBU\" \"\$DBN\" < $BACKUP/prod-db.sql && \
  wp --path=$KZ theme list --status=active --field=name"
```

Expected: `html5blank-stable`.

- [ ] **Step 2: Restore the themes and plugins**

```bash
$SSHP "cd $KZ/wp-content && tar xzf $BACKUP/wp-content-themes-plugins.tgz && \
  wp --path=$KZ plugin list --fields=name,status"
```

Expected: `autoptimize`, `jquery-manager`, `litespeed-cache`, `instagram-feed`, `wordfence`, `wordpress-seo` all present.

- [ ] **Step 3: Restore `.htaccess` and flush**

```bash
$SSHP "cp $BACKUP/htaccess.bak $KZ/.htaccess && \
  wp --path=$KZ rewrite flush --hard && \
  wp --path=$KZ litespeed-purge all 2>/dev/null; \
  curl -s -o /dev/null -w '%{http_code}\n' https://kzmielec.pl/"
```

Expected: `200`.

- [ ] **Step 4: Confirm the old site is back**

```bash
for u in / /misja/ /rodo/ /roznica-wyznan/ /zaplanuj-wizyte/; do
  printf "%-24s %s\n" "$u" "$(curl -s -o /dev/null -w '%{http_code}' https://kzmielec.pl$u)"
done
```

Expected: `200` everywhere. Note `/zaplanuj-wizyte/` is a real page again on the old theme.

---

## Task 12: Clean up

- [ ] **Step 1: Remove the local scratch directory**

```bash
rm -rf /home/lukasz/projects/kzmielec/.deploy-tmp
git -C /home/lukasz/projects/kzmielec status --porcelain
```

Expected: no `.deploy-tmp` entry. The three `scripts/deploy/*` files are worth keeping and will show as untracked.

- [ ] **Step 2: Leave the server backup in place**

Do **not** delete `$BACKUP`. It is the only rollback point and costs a few MB. Note the path in `.claude/PROJECT-NOTES.md` per Task 10 Step 3.

- [ ] **Step 3: Ask before committing**

The repository gained `docs/superpowers/specs/2026-08-03-production-cutover-design.md`, `docs/superpowers/plans/2026-08-03-production-cutover.md`, `scripts/deploy/*` and a modified `.claude/INSTRUCTIONS.md`. Per the project rule in `.claude/INSTRUCTIONS.md`, **ask separately before committing and before pushing.** Do not commit as part of executing this plan.
