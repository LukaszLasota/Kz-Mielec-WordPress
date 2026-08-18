# Comparison of Religions

Side-by-side comparison of Christian denominations, rendered as a responsive
accordion. A Custom Post Type plus one server-side Gutenberg block, in four
languages.

See the [project README](../../../README.md) for how this plugin relates to the
theme and the other three.

## Plugin Info

- **Version:** 1.0.0
- **Author:** Lukasz Lasota
- **Requires PHP:** 8.0+
- **Requires WordPress:** 6.4+
- **License:** GPL-2.0-or-later

## Data Model

```
CPT:  comparison_topic     — one post per sub-topic (e.g. "Baptism", "Holy Scripture")
Tax:  comparison_category  — groups topics into accordion panels (e.g. "Sacraments")
Meta: churches (post)      — [{church_name: string, description: HTML}, ...]
Meta: sort_order (post)    — display order within a category
Meta: sort_order (term)    — display order of the categories in the accordion
```

The CPT is `public = false`: topics are never viewable on their own, because all
content is rendered by the accordion block. The `churches` meta takes any number
of denominations — the column count is discovered from the data, not configured.

**Current content: 37 topics across 9 categories, in each of the four
languages** — one translation group per topic and per category, linked by
Polylang.

`churches` is the one field on this site that holds editorial prose in post meta
rather than in post content. That is why it needs its own translator in
`kzmielec-translate` and its own entry in the content fingerprint.

## Architecture

```
comparison-of-religions/
├── index.php                              # entry: cache hooks, admin import page
├── uninstall.php                          # deletes all posts and terms
├── app/
│   ├── Autoloader.php                     # PSR-4 (ComparisonOfReligions namespace)
│   ├── PostTypes/ComparisonTopic.php      # CPT: public=false, show_in_rest, no editor
│   ├── Taxonomies/ComparisonCategory.php  # taxonomy: hierarchical, public=false
│   ├── Meta/ChurchesMeta.php              # meta schema, JSON validation, sanitisation
│   ├── MetaBoxes/ChurchesMetaBox.php      # admin repeater, one TinyMCE per church
│   ├── Blocks/RegisterBlocks.php          # auto-discovers blocks from build/blocks/
│   ├── Cache/AccordionCache.php           # transient cache: key, TTL, flush
│   └── Admin/AdminColumns.php             # church count and sort order, sortable
├── src/blocks/comparison-accordion/
│   ├── block.json  edit.js  render.php  frontend.js  style.scss
├── build/                                 # compiled block (npm run build)
└── tools/import-html-data.php             # JSON import/export + seed data
```

`ChurchesMeta` sanitises church names with `sanitize_text_field()` and
descriptions with `wp_kses_post()`; the auth callback requires `edit_posts`.

## The accordion block

**Dynamic block** — nothing is saved client-side, all output comes from
`render.php`. The editor previews it through `ServerSideRender`, with editor
styles forcing every panel open.

What `render.php` does, in order:

1. Resolves the **language to render** (see below)
2. Checks the transient cache — skipped during REST requests, so the editor
   preview is always fresh
3. Fetches categories ordered by term `sort_order`, topics by post `sort_order`
4. Discovers the church names from the meta, with no hardcoded limit
5. Renders one CSS Grid table per category
6. Emits FAQ JSON-LD for rich snippets
7. Caches the HTML

**Desktop layout:** CSS Grid, columns `[Topic | Church A | Church B | ...]`, count
driven by the `--cor-church-count` custom property. Each paragraph gets its own
grid cell so rows align across churches.

**Mobile (<=768px):** `display: block`, stacked, with the church label shown
before each description.

**Keyboard and ARIA** (`frontend.js`, WCAG 2.1 AA): Arrow Up/Down to move,
Home/End to jump, Enter/Space to toggle, and `aria-expanded` / `aria-hidden` /
`aria-controls` kept in step. Panel height animates through `max-height`.

### Rendering the right language

The block must narrow its queries to the language of the **post being rendered**,
not the language of the request. `render.php` resolves `$block_lang` from
`pll_get_post_language()` on the rendered post, falls back to
`pll_current_language()`, and passes the result as `lang` in both the term query
and the post query. An empty result narrows nothing — which is the correct
behaviour with Polylang inactive, and the reason every call sits behind
`function_exists()`.

Relying on the request is what the original code did, and the defect is
**invisible on the front end** — Polylang narrows the query itself there, so every
page looked right. The editor renders blocks through a REST route that carries no
language context, so it received all four languages at once: 148 topics instead
of 37 and 36 accordion headings instead of 9.

### Cache invalidation

`AccordionCache` owns the key prefix and the 30-minute TTL as class constants, so
the render template and the flush logic cannot disagree.

**The language is part of the cache key**, and it has to be. The block renders
content Polylang filters per language, so one key shared by four languages means
whichever language renders first is served to all of them — a Ukrainian visitor
gets the Spanish table, and the cache makes it stick for half an hour.
`function_exists()` keeps this working with Polylang switched off, where the key
simply loses its language part.

Flushed automatically on `save_post_comparison_topic` and on any
create / edit / delete of a `comparison_category` term.

## Data Import/Export

Admin page: **Porownania > Import danych** (requires `manage_options`). Import
from JSON, export to JSON, or run the one-time built-in seed.

```json
[
  {
    "category": "Sacraments",
    "title": "Baptism",
    "sort_order": 1,
    "churches": [
      { "church_name": "Roman Catholic", "description": "<p>...</p>" },
      { "church_name": "Pentecostal", "description": "<p>...</p>" }
    ]
  }
]
```

The importer creates Polish content. Translations are produced afterwards by
`kzmielec-translate`, which links each topic and each term across languages —
translating topics without their terms leaves translated rows filed under Polish
categories, which shows up as nine Polish headings above English content.

## Shipped seed data

`data/seed/{pl,en,uk,es}.json` carry the whole comparison — 37 topics and 9
categories per language — so the plugin can be installed somewhere else and
arrive with its content. The files are the starting point, not a live source:
the import writes them into the database once and lets go, and everything after
that is edited in the admin as usual.

```bash
wp comparison-of-religions seed status                 # what would happen here
wp comparison-of-religions seed import --dry-run
wp comparison-of-religions seed import
wp comparison-of-religions seed import --lang=uk
wp comparison-of-religions seed export                 # database -> files
```

### Polylang is optional

The seed works with Polylang, without it, and across it being switched on later.
The states, all of which are tested:

| State | Import |
|---|---|
| No Polylang | only `pl.json`; nothing is given a language |
| Polylang configured | every file whose language the site has, then the versions are linked |
| Polylang added afterwards | the existing Polish records are given a language and the other languages are linked TO them, not duplicated beside them |
| Site has fewer languages than there are files | the extra files are reported as unused, which is not an error |
| Site language slug is not two letters | `en-gb` and `es-mx` are served by `en.json` and `es.json`; `de` and `pt_BR` are reported as having no file rather than served the wrong one |

Two operations are refused when Polylang is inactive on a database that still
holds several languages, because nothing can tell them apart: `export`, which
would pour four languages into the source file, and `import --overwrite`, which
would write Polish over whichever translation it found first. Plain `import`
stays safe in that state.

### What protects existing content

Every record the import creates carries `_cor_seed_key` (its identity across
languages) and `_cor_seed_hash` (the content as written). A second run creates
nothing. `--overwrite` refreshes only records that this import made and nobody
has edited since — anything else is counted as hand-edited and skipped, and that
includes content the import never made, which is the safe reading of a record
with no hash.

A slug that is already taken by somebody else's topic is therefore adopted, not
overwritten and not duplicated: it keeps its title and its churches, it is
counted as hand-edited, and it becomes the source-language member of that
topic's translation group. Tested. The consequence is worth knowing before it
surprises anyone — the seed's own text for that one topic never arrives, and the
site shows the existing post instead. Adopting was chosen over the alternative,
which is a second topic with a `-2` slug and the same heading twice in the
accordion.

### Three defects these tests found

Worth keeping, because each is the kind of thing that looks fine until a second
site exists:

- **Polylang assigns the default language to every newly inserted post.** A post
  created for the English file therefore already claims to be Polish, and a
  "never override an existing language" rule silently left it that way.
- **Polylang's query filters do not run under WP-CLI**, and do not run at all for
  a post type it has not been told to translate. Trusting `'lang' => $lang` in a
  query linked all four languages to the same post, and made an export write 148
  topics into each of four files instead of 37. Both sides now verify the
  language of each record instead.
- **`taxonomy_exists( 'language' )` is false while the terms are still there.**
  Deactivating Polylang unregisters the taxonomy without deleting anything, so
  the guard that refuses an export in that state has to ask the table directly.

`app/Integrations/PolylangTypes.php` exists because of the second one: it
declares the post type and taxonomy translatable through `pll_get_post_types`
and `pll_get_taxonomies`, so the plugin behaves the same on a site where nobody
ticked any box.

## Build

```bash
npm run build    # production (wp-scripts build)
npm start        # watch mode
```

## Code Quality

```bash
composer install
composer phpstan    # PHPStan level 8
composer phpcs      # WordPress Coding Standards
composer check      # both
```

## Uninstall

`uninstall.php` force-deletes all `comparison_topic` posts and all
`comparison_category` terms. Transients expire on their own.
