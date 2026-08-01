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

### Type scale

`src/scss/abstracts/_type.scss` holds every font size, leading and tracking in
the project — ten whole-pixel steps at a 16px root (40, 32, 28, 24, 20, 18, 16,
14, 12, 10) plus four leadings and two trackings. `_tokens.scss` emits them as
`--fs-*`, `--lh-*` and `--tracking-*`, so the numbers exist in exactly one
place.

Call sites do not write `var(--fs-body)` by hand. They read one step through
`fs()` / `lh()` / `tracking()`, which add the fallback a plugin needs under a
foreign theme, or take a whole **role** — size, leading and tracking together —
with `@include type(h2)`. A role means no rule can pick a size and leave the
leading to chance; the functions reject a step that does not exist, so a typo
fails the build.

The roles are `h1`–`h6`, `subtitle` (panel and accordion headings), `body`,
`copy`, `meta` and `label`. `content-typography` declares all six heading levels,
and `editor.scss` applies the same roles inside the block editor, so what an
author sees is the front end's scale rather than an approximation. The gradient
uppercase treatment stops at h3 — below that the letterforms are too small for a
gradient fill to read as anything but grey.

Each role also declares how many steps it drops on a phone, and `type()` emits
that media query itself. On a 390px screen the ladder is 24 / 20 / 18 / 16px for
h1 / h2 / h3 / running text — the same drop for headings and text, so the
relationship between them survives the narrow screen.

There is deliberately **no parameter** for "a bit smaller here". Call sites used
to add their own `@media { @include type(h1, …) }`, and because that query
overlapped the one the mixin emits, whichever came later in the file won: a page
title rendered 32px on a phone where the role said 24px, and no linter can see
that conflict. Removing the parameter makes the bug unreachable; `check:sizes`
counts what actually shipped.

Phone sizes are also why headings are not hyphenated. Polish compounds are long —
"błogosławieństwa" is 415px wide at 2rem — and a word without a hyphen has
nowhere to break, which grew a horizontal scrollbar on a 390px screen. The fix is
the smaller heading, not `overflow-wrap`: a heading split mid-word reads worse
than a smaller heading. Verified in a real browser across 19 pages at 360, 390,
768 and 1440px; 320px is below the floor for one long title.

Prose has three roles rather than one, because a paragraph in a table cell is not
running text: `body` (20px) for content paragraphs, `copy` (18px) for secondary
copy in cards and tables, `meta` (14px) for excerpts, feed text and dates. All
three leave tracking inherited.

Component rules that are not headings take the leading from the scale but only
carry `tracking(wide)` when the component is uppercase; 2px between lowercase
letterforms reads as a spacing bug rather than a treatment.

Every role has at least one caller. A role with none is a guess about future
markup, and the scale is meant to describe what the site does.

The scale is duplicated in three places for reasons that cannot be designed
away: `_type.scss` is mirrored verbatim into both plugins (a plugin cannot import
theme sources), and the font sizes are mirrored again in `theme.json`, which is
JSON and cannot import SCSS. That mirror is what puts these steps — and, with
`defaultFontSizes` and `customFontSize` off, only these steps — in the editor's
size picker. `npm run check:mirrors` fails if any of the three drift.

### Spacing, radii, colours

`src/scss/abstracts/_design.scss` holds them, on the same contract as the type
scale: numbers once, `--space-*` / `--radius-*` / `--color-*` emitted from the
maps, call sites read `space()` / `radius()` / `palette()`.

Spacing is a **4px grid where the name is the multiple** — `space(6)` is 24px.
The previous scale was nine irregular steps (1.6 / 5 / 8 / 10 / 16 / 24 / 40 / 60
/ 80px) that two thirds of call sites bypassed across 43 different values; 58% of
those were already multiples of 4, so the grid was the shape the layout had
anyway. Numeric names beat t-shirt sizes at fourteen steps: nobody has to
remember whether `md` outranks `lg`, and the ladder extends without renaming.

Colours are named by role — `ink` and `paper`, not black and white. A role
survives a repaint, and stylelint's `color-named` rule reads a bare `black` as a
CSS named colour even inside a function call; muting a rule to fit our naming
would be the wrong way round.

`palette()` rather than `color()` because CSS has a `color()` function of its own
(Color 5 colour spaces) and Sass resolves that name to it, so a user-defined
`color()` is never called. This is the kind of collision that compiles silently —
`npm run check:type-scale` is what catches it.

Two values stay literal on purpose: the 1.6px hairline under pattern paragraphs
(the smallest grid step is 4px, and tripling a hairline is a visible change) and
one 200px offset whose nearest step is 20% away. Both carry a comment saying so.

### Content width

One standard, one mixin — `content-measure` in `abstracts/mixins.scss`:

- the **container** takes the full content column (1200px), so a wide block — a
  comparison table, an accordion, a gallery — can use all of it;
- the **prose inside** is pulled back to `--content-width-narrow` (1100px) with
  `margin-inline: auto`.

Direct children only: a heading nested inside a block belongs to that block's
layout, not to the reading measure.

It applies in all five content contexts — `.page__content-container`,
`.single-post__content-container`, `.page-hero > section`,
`.page-belief__content` and `.archive-meetings__content` — three of which used to
solve it the other way round, by constraining the container. That squeezed every
wide block on the page to the reading measure while leaving the margins empty:
the comparison table and the three `custom-accordion` pages rendered at 1068px
inside a 1200px column, even though `custom-accordion` asks for 1200px itself.

Constraining the container is the tempting fix because it is one declaration, but
it puts the limit on the wrong element. A block child cannot exceed its parent's
content box, so the only ways back out are negative margins or a viewport-keyed
media query — both of which this project tried and then deleted.

## Quality gates

Four linters, one per language, all green.

| tool | scope | command |
|---|---|---|
| Biome | JS / TS / JSON | `npm run lint:js` (repo root, covers all packages) |
| stylelint | SCSS | `npm run lint:css` (per package) |
| PHPCS | WordPress coding standard | `composer phpcs` (per package) |
| PHPStan | PHP static analysis, **level 8** in all four packages | `composer phpstan` (per package) |

`composer check` runs PHPStan and PHPCS together. `npx tsc --noEmit` type-checks
the theme.

The plugins ran at level 6 for a while. Level 7 turned out to report exactly the
same errors as level 8 — seven in total, every one of them a WordPress function
that can return `false` or an `array` being treated as a `string` — so there was
nothing between the two levels and the whole project sits at 8.

`!important` appears 15 times, and every one is a fight with a stylesheet this
project does not own: the WCAG focus indicator (which must not be overridable),
a third-party feed plugin's button, `@wordpress/components` inside the editor,
and the scroll-arrow block's margins. Core prints its constrained-layout margin
at specificity (0,1,0) — the `:where()` in that selector counts for nothing —
which ties with a block's own class, and stylesheet print order is not stable
between pages, so `!important` is what removes the coin flip. Everywhere else the
fix was specificity: matching one more class, or pulling an attribute into the
selector.

Two project-specific checks cover what no linter can see:

| check | what it catches |
|---|---|
| `npm run check:type-scale` | an `fs()` / `lh()` / `tracking()` call that survived into built CSS — Sass treats an unknown function as plain CSS and passes it through, so a missing import or a non-interpolated custom property (`--x: fs(body)`) compiles cleanly and ships broken |
| `npm run check:mirrors` | drift between the mirrored scale files, the shared breakpoints, the theme.json font sizes and the content width |
| `npm run check:sizes` | a `font-size` in built CSS that is not a step of the scale — stylelint validates syntax, not vocabulary, and `font-size: 1.3rem` is valid CSS |

Both live in `scripts/` and read only build output, so they are cheap enough to
run after every build.

Biome is configured as a **linter only** — the formatter is off, so it reports
defects instead of reflowing files. It cannot replace stylelint: Biome does not
parse SCSS and reports `.scss` files as ignored.

TypeScript is theme-only; the plugins are plain JavaScript.

## SEO

Yoast handles titles, canonicals, sitemaps and the schema graph. What it cannot
do is invent content, so `App/Seo/YoastFallbacks.php` fills the gaps an audit of
all 19 published pages found — and steps aside the moment an editor fills the
field itself:

| gap | before | after |
|---|---|---|
| `og:image` | missing on 19/19 — a shared link had no picture | site logo as the fallback |
| `meta description` | missing on 12/19 | generated from the content, blocks stripped first |
| `<title>` length | up to 117 characters, half of it truncated by Google | site name dropped past 60; longest is now 76 |
| schema | Organization only | a `Church` node with address, phone and the service times, read from the meetings CPT |
| tile images | raw `<img src>` at full size, 131 KB per photo | `wp_get_attachment_image()` with srcset and `sizes` derived from the column count |

Already correct before the audit, and worth not breaking: one `h1` per page on all
19, no skipped heading levels, `alt` on all 204 content images, `lang="pl-PL"`,
`fetchpriority="high"` on the hero and `loading="lazy"` on everything below it.

Two things are deliberately **not** code. The remaining two long titles are the
page titles themselves — shortening them is editorial, and truncating them in PHP
would only produce a worse sentence. And 34 images still ship without `width`
and `height`: 25 are the Instagram plugin's placeholders and 9 are remote
Facebook thumbnails whose size is unknown at render time. Neither causes layout
shift here, because the containers reserve space with `aspect-ratio`.

### Modern image formats

`App/Core/ModernImages.php` wraps images in `<picture>` and offers AVIF, then
WebP, then the original — but only for a format whose file actually sits next to
the source on disk. Nothing converts anything at request time; the markup adapts
to what is there, so a missing sibling is simply not offered and the `<img>`
still works.

`srcset` cannot do this: a browser picks a *width* from a srcset, not a codec.
The alternative — rewriting extensions at the server and trusting the `Accept`
header — moves the decision out of the repository and breaks the first time a CDN
caches one variant under both names.

Three hooks, because images arrive three ways: `wp_content_img_tag` for editor
content, `wp_get_attachment_image` for templates and blocks (which is also how
the theme reaches plugin markup without the plugin knowing), and `render_block`
for a block that builds its own `<picture>` for art direction — the hero does,
and there the format sources have to sit beside each existing `<source>`, keeping
its `media`, rather than wrapping it again.

`scripts/convert-uploads.php` is what puts the files there. Run it inside DDEV,
where PHP has GD with both encoders:

```bash
ddev exec php scripts/convert-uploads.php --dry-run
ddev exec php scripts/convert-uploads.php
```

It never touches an original, never overwrites a newer conversion, and discards
any output that came out larger than its source — which happens with flat line
art, where PNG is already the right format. On this library: 511 files written,
65 discarded, **25 MB of sources down to 9 MB (−64%)** in 38 seconds.

The converted files live in `wp-content/uploads/`, which is **not** in the
repository, so production needs its own run — or LiteSpeed Cache's image
optimisation, which is installed there and does the same job.

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
