# Pentecostal Church in Mielec — WordPress

Website for [kzmielec.pl](https://kzmielec.pl). Migration from a hardcoded
HTML5 Blank theme, where every page lived in PHP, to a Gutenberg architecture
where the same design is editable from the admin.

The design is deliberately unchanged: Cinzel display font, black-and-white
palette, anchored one-page scrolling. All existing URLs are preserved for SEO.

## Architecture

One classic theme plus three plugins. The split is not incidental — it decides
what survives a theme change.

```
wp-content/
├── themes/kzmielec/                 # presentation: templates, patterns, block styles
├── plugins/custom-posts/            # CPT: meetings
├── plugins/custom-block-package/    # 12 Gutenberg blocks + Facebook feed service
└── plugins/comparison-of-religions/ # CPT comparison_topic + accordion block
```

**Why content types and blocks live in plugins, not the theme.** A CPT
registered by a theme becomes invisible the moment the theme is switched — the
rows stay in `wp_posts` but nothing declares the type any more. Registering it
from a plugin keeps the content editable regardless of the active theme. The
same reasoning applies to blocks: **11 of the 12** blocks in
`custom-block-package` render server-side from a `render.php` inside the plugin,
so their markup does not depend on theme code. Only `pdf-block` is static.

**What the theme owns.** Presentation only: 14 PHP templates, 2 registered
block patterns, 2 block style variations, `theme.json` presets, and the design
tokens every package reads from.

**One known coupling, accepted on purpose.** `archive-meetings.php:15` imports
`CustomBlockPackage\Admin\MeetingMeta`, so deactivating that plugin while the
theme is active fatals on the meetings archive. The scenario that matters — a
theme swap — leaves plugins active, and in the reverse case those blocks would
not render anyway. Documented rather than papered over.

## Build

Each package builds independently, because each must be installable on its own.
All three use `@wordpress/scripts` with a thin `webpack.config.js` on top.

| | output | why |
|---|---|---|
| theme | `assets/` | classic theme, enqueued from PHP with `filemtime` cache-busting |
| plugins | `build/` | `register_block_type_from_metadata()` scans `build/` and auto-detects `style-index.css` |

The outputs differ by necessity, not drift.

There is **one optimised file per entry** — no `.min` variant and no
development/production branch in PHP. Built files are committed, so deploying
needs no Node on the server. A `.githooks/pre-commit` hook rebuilds and
re-stages the theme's `assets/` whenever theme sources are staged, so the
repository cannot receive a development-mode bundle.

```bash
ddev theme:build      # theme → assets/  (what to run before committing)
ddev theme:watch      # rebuild on save; unreliable on WSL2, see below
ddev build:all        # theme + all plugins
```

Per package: `npm run build`, `npm start`.

> **WSL2 caveat.** Native inotify events do not cross the Docker bind mount, so
> watch mode misses host-side edits. `watchOptions.poll` mitigates it but is
> capricious; `ddev theme:build` is the reliable path.

## Design tokens

Every value used more than once lives in one place:
`wp-content/themes/kzmielec/src/scss/abstracts/_tokens.scss`. Genuinely one-off
values may stay inline at their single use site.

Two kinds, split by a hard constraint:

- **Breakpoints are SCSS variables** — a media query cannot read
  `var(--x)`, so they must resolve at compile time.
- **Everything else is a CSS custom property**, emitted once via a mixin, so
  the same token applies inside the block editor and is visible in devtools.

Breakpoints are mirrored to `--bp-*` custom properties so JavaScript reads the
identical numbers through `cssVarPx()` instead of hard-coding its own copy.

Each plugin carries its own `src/scss/abstracts/_tokens.scss` mirroring the
theme's scale. Duplicated on purpose: a plugin builds separately and must work
with any theme, so it cannot reach into theme sources. Keep them in sync.

All lengths are in `rem` at a 16px root. The only `px` left is `$bp-* + 1px`
arithmetic in media queries, which needs a comparable compile-time unit.

## Quality gates

Four tools, one per language, all green.

| tool | scope | command |
|---|---|---|
| Biome | JS / TS / JSON | `npm run lint:js` (repo root, covers all packages) |
| stylelint | SCSS | `npm run lint:css` (per package) |
| PHPCS | WordPress coding standard | `composer phpcs` (per package) |
| PHPStan | PHP static analysis | `composer phpstan` (per package) |

`composer check` runs PHPStan and PHPCS together. `npx tsc --noEmit` type-checks
the theme.

Biome is configured as a **linter only** — the formatter is off, so it reports
defects instead of reflowing files. It cannot replace stylelint: Biome does not
parse SCSS and reports `.scss` files as ignored.

TypeScript is theme-only; the plugins are plain JavaScript.

## Local environment

DDEV on WSL2. `ddev start`, then the site is at
[kzmielec.ddev.site](https://kzmielec.ddev.site).

Custom DDEV commands live in `.ddev/commands/web/`, which is gitignored — they
are local-only and would need un-ignoring to share.

## Deploying

Three things are **not** in the repository and must be handled separately:

1. **`vendor/`** — the theme's `functions.php` requires `vendor/autoload.php`
   and depends on `enshrined/svg-sanitize` at runtime. Without it the site
   fatals. Run `composer install --no-dev --optimize-autoloader` in the theme on
   the server, or rsync a `--no-dev` vendor tree.
2. **`wp-content/uploads/`** — media, transferred separately.
3. **`wp-config.php`** — stays server-side, never overwritten.

Everything else, including all built assets, comes from git.

## Further reading

- `wp-content/themes/kzmielec/README.md` — theme internals, entry points, SCSS layout
- `.claude/DDEV-COMMANDS.md` — full command reference
- `.claude/migration-plan.md` — migration status and remaining work
- each plugin's own `README.md`
