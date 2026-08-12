# Pentecostal Church in Mielec — WordPress

Website for [kzmielec.pl](https://kzmielec.pl), in Polish, English, Ukrainian and
Spanish. Migration from a hardcoded HTML5 Blank theme, where every page lived in
PHP, to a Gutenberg architecture where the same design is editable from the admin.

The design is deliberately unchanged: Cinzel display font, black-and-white
palette, anchored one-page scrolling. All existing URLs are preserved for SEO.

## Architecture

One classic theme plus four plugins. The split is not incidental — it decides
what survives a theme change. Every package documents itself:

| package | responsibility | |
|---|---|---|
| `themes/kzmielec` | presentation, design tokens, and the cross-cutting concerns that must outlive any plugin: languages, contact data, accessibility, SEO | [README](wp-content/themes/kzmielec/README.md) |
| `plugins/custom-posts` | CPT `meetings`, with a translated archive slug per language | [README](wp-content/plugins/custom-posts/README.md) |
| `plugins/custom-block-package` | 11 Gutenberg blocks + the Facebook feed service | [README](wp-content/plugins/custom-block-package/README.md) |
| `plugins/comparison-of-religions` | CPT `comparison_topic` + the accordion block | [README](wp-content/plugins/comparison-of-religions/README.md) |
| `plugins/kzmielec-translate` | migration tool: fills translations via DeepL, then can be deleted | [README](wp-content/plugins/kzmielec-translate/README.md) |

**Why content types and blocks live in plugins, not the theme.** A CPT
registered by a theme becomes invisible the moment the theme is switched — the
rows stay in `wp_posts` but nothing declares the type any more. The same applies
to blocks: 10 of the 11 render server-side from a `render.php` inside the plugin,
so their markup does not depend on theme code.

**One known coupling, accepted on purpose.** `archive-meetings.php:15` imports
`CustomBlockPackage\Admin\MeetingMeta`, so deactivating that plugin while the
theme is active fatals on the meetings archive. The scenario that matters — a
theme swap — leaves plugins active, and in the reverse case those blocks would
not render anyway. Documented rather than papered over.

**The repository holds the whole install** — WordPress core, the four
third-party plugins (Polylang, Yoast SEO, LiteSpeed Cache, Instagram Feed) and
all built assets — so `git clone` yields a site that runs. Three things are
deliberately outside it; see [Deploying](#deploying).

## Languages

Four languages managed by **Polylang 3.8.6 (free)**: 18 published pages and 37
comparison topics per language, one translation group each.

| | Polish | English | Ukrainian | Spanish |
|---|---|---|---|---|
| locale | `pl_PL` | `en_GB` | `uk` | `es_ES` |
| meetings archive | `zaplanuj-wizyte` | `plan-your-visit` | `zaplanuyte-vizyt` | `planifica-tu-visita` |

Four pieces make this work, each documented where it lives:

| piece | where |
|---|---|
| 42 interface strings, overridable from the admin | theme, `Core/StringTranslations.php` |
| language switcher, no JavaScript required | theme, `Core/LanguageFlags.php` |
| reciprocal `hreflang` — Yoast does not emit it for Polylang free | theme, `Seo/Hreflang.php` |
| translated archive slug, which is a paid Polylang feature | [`custom-posts`](wp-content/plugins/custom-posts/README.md) |

**Blocks that fetch content must narrow it to the language of the post being
rendered, not the request.** On the front end Polylang narrows the query itself,
so the defect is invisible there; the editor renders blocks through a REST route
with no language context and received all four languages at once. Any cache key
covering such content needs the language in it for the same reason.

### Surviving Polylang being switched off

`Core/TranslationGuard.php` in the theme is a safety net for one specific
accident. Without Polylang the site does not break — every Polish URL still
returns 200. What happens is subtler and worse: the 174 translated posts stop
being translations and become ordinary pages of the Polish site. Measured with
Polylang off, the page sitemap went from 18 entries to 72, with no `hreflang` to
explain it to Google.

The guard lives in the **theme**, not in `kzmielec-translate` where it started.
That plugin is a migration tool; while the guard was inside it, the plugin could
never be switched off, which is a strange requirement for a tool whose job is
finished.

## Content from one source

Two things editors used to keep in four separate copies now have one home:

- **Contact details** — address, phone, tax number, e-mail and bank account come
  from one option, edited on one screen, and reach the four language versions,
  the map block, the JSON-LD graph and the SEO description through a core Block
  Bindings source. This replaced five copies that had already drifted: every
  visible e-mail address pointed at a dead development domain.
- **Belief page selection** — one setting, made once in Polish, resolved to the
  right translation at render time by the tiles block. Asked without a named
  language, `pll_get_post()` answers in the language of the request, which the
  editor's rendering route does not have.

Contact data is a theme feature, documented in
[the theme's README](wp-content/themes/kzmielec/README.md); the tile resolution
lives in [`custom-block-package`](wp-content/plugins/custom-block-package/README.md).

## Build

Each package builds independently, because each must be installable on its own.
All use `@wordpress/scripts` with a thin `webpack.config.js` on top. The theme
outputs to `assets/`, the plugins to `build/` — where
`register_block_type_from_metadata()` looks.

Built files are committed, so deploying needs no Node on the server. There is one
optimised file per entry: no `.min` variant and no development/production branch
in PHP.

```bash
ddev theme:build      # theme -> assets/  (what to run before committing)
ddev plugin:build     # custom-block-package + comparison-of-religions
ddev build:all        # all three, stopping at the first failure
ddev theme:watch      # same file names, unminified output
ddev watch:all        # theme + both block plugins in parallel
```

Run one watch at a time: `watch` while iterating, `build` to release.
`custom-posts` has no build — its `src/` is runtime code, not sources to compile.

> **WSL2 caveat.** Native inotify events do not cross the Docker bind mount, so
> watch mode misses host-side edits. `ddev theme:build` is the reliable path.

`.githooks/pre-commit` does two things: it rebuilds and re-stages the theme's
`assets/` whenever theme sources are staged, so the repository cannot receive a
development-mode bundle; and it refuses any commit containing a host name, login
or path from the deployment environment. The patterns it looks for are read from
a file outside the repository — this repository is public.

## Design system

Every value used more than once is declared once, in three scales — type,
spacing/colour, breakpoints — read at call sites through functions that reject a
step that does not exist, so a typo fails the build. All lengths are in `rem` at
a 16px root; colours are named by role, which is what makes the accessibility
strip's contrast mode a token swap rather than a stylesheet override.

Each plugin carries its own mirror of the scale, because a plugin builds
separately and must work under any theme. Details in
[the theme's README](wp-content/themes/kzmielec/README.md).

## Quality gates

| tool | scope | command |
|---|---|---|
| Biome | JS / TS / JSON | `npm run lint:js` (repo root, all packages) |
| stylelint | SCSS | `npm run lint:css` (per package) |
| PHPCS | WordPress coding standard | `composer phpcs` (per package) |
| PHPStan | PHP static analysis, **level 8** | `composer phpstan` (per package) |

Green in all four own packages. `composer check` runs PHPStan and PHPCS together;
`npx tsc --noEmit` type-checks the theme. TypeScript is theme-only, the plugins
are plain JavaScript.

Three project checks cover what no linter can see, all reading only build output:

- `check:type-scale` — a scale function that survived into built CSS. Sass passes
  an unknown function through as plain CSS, so a missing import ships broken.
- `check:mirrors` — drift between the mirrored scale files, the breakpoints, the
  `theme.json` sizes and the content width.
- `check:sizes` — a `font-size` that is not a step of the scale. stylelint
  validates syntax, not vocabulary.

## Testing

```bash
bash scripts/tests/run-all.sh    # from the host: the runner drives `ddev wp` itself
```

Eight PHP checks covering what the linters cannot: that blocks narrow their
queries to the language of the rendered post, that contact data resolves from the
shared source in all four languages, that Scripture quotations are published
translations rather than machine paraphrases, and that the content carries no
traces of two data-mangling bugs this project has already hit.

`scripts/tests/fingerprint.php` prints 137 facts in a fixed order, with no post
ids, so the content state before and after a migration can be compared with a
plain `diff`.

## Local environment

DDEV on WSL2. `ddev start`, then the site is at
[kzmielec.ddev.site](https://kzmielec.ddev.site).

The custom commands live in `.ddev/commands/web/` and **are in the repository** —
`.gitignore` excludes the rest of `.ddev/` but re-includes `commands/`, because a
build script that is not in version control cannot be reviewed and cannot be fixed
once. They used to be ignored, so `ddev build:all` existed only on whichever
machine had typed it in. To add one, drop an executable file there with a
`## Description:` header; DDEV picks it up by itself.

## Deploying

Three things are **not** in the repository and must be handled separately:

1. **`vendor/`** — the theme's `functions.php` requires `vendor/autoload.php` and
   depends on `enshrined/svg-sanitize` at runtime. Without it the site fatals.
   Run `composer install --no-dev --optimize-autoloader` in the theme on the
   server, or rsync a `--no-dev` vendor tree.
2. **`wp-content/uploads/`** — media, transferred separately. Also why the AVIF
   and WebP siblings the theme serves need their own run there; see the theme's
   README.
3. **`wp-config.php`** — stays server-side, never overwritten. The DeepL API key
   for `kzmielec-translate` is added there by hand and never committed.

Everything else, including WordPress core and all built assets, comes from git.

Two things to know about the four-language site. **Polylang must stay active** —
`TranslationGuard` keeps the translations out of the sitemaps when it is not, but
that is a safety net, not a supported state. And the language-prefixed rewrite
rules are gated on Polylang, so `delete_option('rewrite_rules')` is needed after
activating or deactivating it.

## Further reading

`.claude/PROJECT-NOTES.md` — working notes rather than published documentation:
the current state, the decisions that still hold, and the traps that cost time to
find. Everything else documents itself in the package that owns it; the five
READMEs are linked from [Architecture](#architecture).
