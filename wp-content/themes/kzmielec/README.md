# kzmielec — Custom WordPress Theme

Theme for the Pentecostal Church in Mielec ([kzmielec.pl](https://kzmielec.pl)):
Cinzel display font, black-and-white palette, one-page layout with anchor
scrolling, four languages.

See the [project README](../../../README.md) for how the theme relates to the
four plugins and why the split is where it is.

- **Version:** 1.0 · **Requires:** PHP 8.0+, WordPress 6.5+ (Block Bindings API)
- **License:** GPL-2.0-or-later

## Architecture

Component-based OOP PHP, PSR-4 (`Kzmielec\` -> `App/`). Every component is
registered in `App/Theme.php` and hooks itself through one of the two interfaces
in `App/Interfaces/`.

```
App/
├── Theme.php     # bootstrap: the one list of every component
├── BasicTheme/   # theme supports, asset loading, menus, rewrite base
├── Core/         # cross-cutting behaviour (see below)
├── Contact/      # ContactData, ContactBindings — one source for contact details
├── Seo/          # YoastFallbacks, Hreflang, ScriptureNotice
├── Admin/        # settings screens: theme, logo, contact, belief pages
└── Widgets/      # footer widget areas
```

`Core/` holds the concerns that must survive any plugin being switched off:

| class | responsibility |
|---|---|
| `TranslationGuard` | keeps translated content out of sitemaps and queries when Polylang is inactive |
| `StringTranslations` | registers 42 interface strings with Polylang so the admin can override the `.po` files |
| `LanguageFlags` | the language switcher in the accessibility strip |
| `SocialFeedLanguage` | picks the feed language from the rendered post |
| `ModernImages` | `<picture>` with AVIF/WebP when the sibling file exists |
| `PatternAssets` | loads a pattern's CSS/JS only on pages that use it |
| `BlockStyles` | 2 registered block style variations |
| `PerformanceOptimizer` | script defer, emoji removal |
| `GroupLinkSupport` | link URL on the Group block |
| `SvgSupport` | sanitised SVG upload, admin only |
| `FeedCachePurge`, `FeedRefreshButton` | Facebook feed cache control |

## Contact data from one source

Address, phone, tax number, e-mail and bank account come from **one option**,
`kzmielec_contact`, edited on one screen and inserted into block content through
a core **Block Bindings** source, `kzmielec/contact`.

```
App/Admin/ContactSettings.php   # the one screen where the values are typed
App/Contact/ContactData.php     # the only reader of the option: 9 fields, defaults, tel:/mailto:
App/Contact/ContactBindings.php # 5 composed lines, labels from the gettext catalogue
```

One source feeds the four language versions of the contact page, the map block's
coordinates, the `Church` node in the JSON-LD graph and the SEO description.
**Data once, labels translated** — the numbers are language-neutral, so only the
surrounding prose lives in the `.po` files. It replaced five independent copies
that had already drifted apart.

**`switch_to_locale()` cannot render a line in another language here:** it refuses
locales absent from `get_available_languages()`, a list `WP_Locale_Switcher` caches
in its constructor, before the theme loads. `ContactBindings::with_locale()`
filters `locale` and `determine_locale` and reloads the text domain instead —
passing `true` to `unload_textdomain()`, or only the first switch per process
works.

## Accessibility

`template-parts/accessibility-bar.php` renders a persistent strip above the
header: **three text sizes**, a **high-contrast mode** and the language switcher,
the first two remembered between visits.

Both settings are mechanisms, not stylesheet overrides — text size multiplies the
root font size, contrast inverts the colour tokens. Because every length is in
`rem` and every colour reads a token, they reach the whole site, including plugin
markup, without a single component knowing about them. State lives on `<html>`,
set by a small head script, so it applies before first paint.

**Zero WCAG 2.1 AA violations**, verified with axe in a real browser. Two items
were deliberately left: the Facebook feed's embedded markup misses an AAA contrast
ratio the plugin controls, and the tile grid's touch targets meet AA but not AAA.

## SEO

Yoast handles titles, canonicals, sitemaps and the schema graph. It cannot invent
content, so `App/Seo/YoastFallbacks.php` fills the gaps an audit found — and steps
aside the moment an editor fills the field itself:

| gap | before | after |
|---|---|---|
| `og:image` | missing everywhere — a shared link had no picture | site logo as the fallback |
| `meta description` | missing on 12 of 19 Polish pages | hand-written per page and per language, 84 in total |
| `<title>` length | up to 117 characters, half truncated by Google | site name dropped past 60; longest is now 76 |
| schema | Organization only | a `Church` node with address, phone and service times, from the contact option and the meetings CPT |
| tile images | raw `<img src>` at full size, 131 KB per photo | `wp_get_attachment_image()` with srcset and `sizes` from the column count |
| `hreflang` | absent — Yoast does not emit it for Polylang free | reciprocal set on every translated URL (`Seo/Hreflang.php`) |

`Seo/ScriptureNotice.php` appends the required source note to the posts marked
`_kzt_scripture`, rather than to the global footer where it used to sit — most
pages quote nothing.

### Modern image formats

`Core/ModernImages.php` wraps images in `<picture>` and offers AVIF, then WebP,
then the original — but only for a format whose file actually sits next to the
source on disk, so a missing sibling is simply not offered. `srcset` cannot do
this: a browser picks a *width* from a srcset, not a codec. Three hooks, because
images arrive three ways: `wp_content_img_tag` (editor content),
`wp_get_attachment_image` (templates and blocks — also how the theme reaches
plugin markup without the plugin knowing) and `render_block` (the hero, which
builds its own `<picture>` for art direction).

`scripts/convert-uploads.php` writes the sibling files, inside DDEV where PHP has
GD with both encoders. `wp-content/uploads/` is not in the repository, so
production needs its own run.

## Design system

Every value used more than once is declared once. Three scales, one contract:
numbers in a map, emitted as CSS custom properties by a mixin, read at call sites
through a function that **rejects a step that does not exist** — so a typo fails
the build instead of shipping.

### Type scale

`src/scss/abstracts/_type.scss`: ten whole-pixel steps at a 16px root (40, 32, 28,
24, 20, 18, 16, 14, 12, 10), four leadings, two trackings.

Call sites do not write `var(--fs-body)` by hand. They read one step through
`fs()` / `lh()` / `tracking()`, or take a whole **role** — size, leading and
tracking together — with `@include type(h2)`, so no rule can pick a size and leave
the leading to chance.

Roles: `h1`-`h6`, `subtitle`, `body`, `copy`, `meta`, `label`. Prose has three
because a paragraph in a table cell is not running text — `body` (20px) for
content, `copy` (18px) for cards and tables, `meta` (14px) for excerpts and dates.
Every role has at least one caller.

Each role declares how many steps it drops on a phone and `type()` emits that
media query itself; on a 390px screen the ladder is 24 / 20 / 18 / 16px for
h1 / h2 / h3 / text. There is deliberately **no parameter** for "a bit smaller
here": a call site's own overlapping media query used to win by source order,
rendering a title 32px where the role said 24px, and no linter can see that.

Headings are not hyphenated. Long Polish compounds have nowhere to break, which
grew a horizontal scrollbar at 390px — the fix is the smaller heading, because a
heading split mid-word reads worse than a smaller one.

### Spacing, radii, colours

`src/scss/abstracts/_design.scss`, same contract, read through `space()`,
`radius()` and `palette()`.

Spacing is a **4px grid where the name is the multiple** — `space(6)` is 24px. It
replaced nine irregular steps that two thirds of call sites bypassed; most of
those bypasses were already multiples of 4, so the grid was the shape the layout
had anyway.

Colours are named by role — `ink` and `paper`, not black and white. A role
survives a repaint, and it is what makes the contrast mode a token swap.
`palette()` rather than `color()` because CSS has a `color()` function of its own
and Sass resolves that name to it, so a user-defined `color()` is never called —
silently.

Two values stay literal, each with a comment saying so: the 1.6px hairline under
pattern paragraphs and one 200px offset whose nearest step is 20% away.

### Breakpoints

SCSS variables in `_tokens.scss`, not custom properties — a media query cannot
read `var()`: `$bp-mobile` 480, `$bp-small` 600, `$bp-tablet` 800, `$bp-laptop`
1024, `$bp-desktop` 1400. Use `$bp-tablet + 1px` for a complementary `min-width`
query. They are mirrored to `--bp-*` so JavaScript reads the identical numbers
through `cssVarPx()`.

### Content width

One mixin, `content-measure` in `abstracts/mixins.scss`: the **container** takes
the full content column (1200px) so a wide block can use all of it, while the
**prose inside** is pulled back to 1100px with `margin-inline: auto`. Direct
children only — a heading nested in a block belongs to that block's layout.

Constraining the container instead is the tempting fix — one declaration — and it
squeezes every wide block to the reading measure while leaving the margins empty.
A block child cannot exceed its parent's content box, so the only ways back out
are negative margins or a viewport-keyed media query.

### Mirrors

`_type.scss` is mirrored verbatim into both block plugins (a plugin cannot import
theme sources) and the font sizes again into `theme.json`, which is JSON. That
mirror is what puts these steps — and, with `defaultFontSizes` and
`customFontSize` off, **only** these steps — in the editor's size picker.
`npm run check:mirrors` fails if any of the three drift.

## SCSS Architecture

```
src/scss/
├── abstracts/
│   ├── _tokens.scss     # emits the custom properties; breakpoints
│   ├── _type.scss       # type scale and roles
│   ├── _design.scss     # spacing, radii, colours
│   ├── _variables.scss  # thin aliases over the tokens
│   └── mixins.scss
├── base/                # @font-face, normalize, content typography
├── apps/                # main layout, menu, footer, wcag
├── pages/               # page, page-hero, front-page, single-post, search-404
├── frontend.scss
└── editor.scss
```

Modules use `@use`, so every partial declares its own dependencies.

`frontend.scss` and `editor.scss` each include the custom-property mixin — the
editor renders in its own iframe and needs its own `:root`. **The editor canvas
does not inherit `frontend.css`**; anything an author must see while editing has
to be repeated in `editor.scss`, which reaches the canvas through
`add_editor_style()`.

## Build System

`@wordpress/scripts` driven by a thin `webpack.config.js`. Source in `src/`,
output to `assets/`. One output file per entry — no `.min` variant, no dev/prod
split — so PHP always enqueues the same path.

```bash
ddev theme:build     # one optimised build (run before committing)
ddev theme:watch     # watch mode, same filenames
```

Run one at a time. `.githooks/pre-commit` runs the build and stages `assets/` when
a commit touches `src/` or the build config. On WSL2 watch may miss host-side
edits — inotify does not cross the Docker bind mount — and `ddev theme:build` is
instant anyway.

| Entry | Source | Output |
|-------|--------|--------|
| frontend | `src/frontend.ts` | `assets/js/frontend.js` + `assets/css/frontend.css` |
| editor | `src/editor.ts` | `assets/css/editor.css` (via `add_editor_style`) |
| print | `src/print.ts` | `assets/css/print.css` |
| patterns/* | auto-discovered `src/patterns/*/{style.scss,script.ts}` | `assets/{css,js}/patterns/<slug>-{style,script}.*` |
| block-styles/* | auto-discovered `src/block-styles/*.scss` | `assets/css/block-styles/<name>.css` |

Fonts go to `assets/webfont/` and images to `assets/media/` with unhashed
filenames — `header.php` preloads the font files by exact name.

## Templates

```
front-page.php          # home: one page, five anchored sections
page.php                # regular pages
page-hero.php           # pages with a hero banner (Template Name)
page-belief.php         # belief pages (Template Name)
archive-meetings.php    # meetings CPT archive, slug translated per language
archive.php  index.php  single.php  search.php  404.php
header.php  footer.php  searchform.php
template-parts/
├── accessibility-bar.php   # text size, contrast, language switcher
└── content-posts.php
```

Registered block patterns live in `patterns/`: `banner-hero` and
`hello-section-main-page`.

## Code Quality

```bash
composer install
composer phpstan    # PHPStan level 8
composer phpcs      # WordPress Coding Standards
composer check      # both
npx tsc --noEmit    # TypeScript (theme only)
```

Plus the three build-output checks in the [project README](../../../README.md):
`check:type-scale`, `check:mirrors`, `check:sizes`.

`!important` appears 15 times, always against a stylesheet this project does not
own: the WCAG focus indicator, a feed plugin's button, `@wordpress/components` in
the editor, and core's constrained-layout margin, which prints at the same
specificity as a block's own class (`:where()` counts for nothing) in a print
order that is not stable between pages. Everywhere else the fix was specificity.
