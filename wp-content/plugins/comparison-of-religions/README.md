# Comparison of Religions

Side-by-side comparison of Christian denominations displayed as a responsive accordion. Built on a Custom Post Type architecture with server-side rendered Gutenberg block.

## Plugin Info

- **Version:** 1.0.0
- **Author:** Lukasz Lasota
- **Requires PHP:** 8.0+
- **Requires WordPress:** 6.4+
- **License:** GPL-2.0-or-later

## Data Model

```
CPT:  comparison_topic        — One post per sub-topic (e.g. "Baptism", "Holy Scripture")
Tax:  comparison_category     — Groups topics into accordion panels (e.g. "Sacraments", "Eschatology")
Meta: churches (post)         — [{church_name: string, description: string (HTML)}, ...]
Meta: sort_order (post)       — Integer, display order within a category
Meta: sort_order (term)       — Integer, display order of categories in the accordion
```

The CPT has `public = false` (topics are not individually viewable — all content is rendered by the accordion block). The `churches` meta field supports any number of denominations.

## Architecture

```
comparison-of-religions/
├── index.php                          # Plugin entry, cache hooks, admin import page
├── uninstall.php                      # Cleanup: deletes all posts and terms
├── app/
│   ├── Autoloader.php                 # PSR-4 autoloader (ComparisonOfReligions namespace)
│   ├── PostTypes/ComparisonTopic.php  # CPT registration
│   ├── Taxonomies/ComparisonCategory.php  # Taxonomy registration (hierarchical)
│   ├── Meta/ChurchesMeta.php          # Meta field schema + JSON validation + sanitization
│   ├── MetaBoxes/ChurchesMetaBox.php  # Admin repeater meta box (TinyMCE per church)
│   ├── MetaBoxes/meta-box.js          # Repeater JS: add/remove rows, TinyMCE init
│   ├── MetaBoxes/meta-box.css         # Repeater styles
│   ├── Blocks/RegisterBlocks.php      # Auto-discovers blocks from build/blocks/
│   ├── Cache/AccordionCache.php       # Transient cache helper (key prefix, TTL, flush)
│   └── Admin/AdminColumns.php         # Custom columns: church count, sort order (sortable)
├── src/blocks/comparison-accordion/   # Gutenberg block source
│   ├── block.json                     # Block metadata
│   ├── edit.js                        # Editor: ServerSideRender (WYSIWYG preview)
│   ├── render.php                     # Server-side render (CSS Grid table, FAQ schema)
│   ├── frontend.js                    # Accordion toggle, keyboard nav (WCAG 2.1 AA)
│   └── style.scss                     # Responsive styles (grid desktop, stacked mobile)
├── build/                             # Compiled block (npm run build)
└── tools/
    └── import-html-data.php           # JSON import/export + hardcoded seed data
```

## PHP Classes

### ComparisonTopic

Registers `comparison_topic` CPT. `public = false`, `show_in_rest = true`, supports: title, custom-fields, revisions. No editor — content lives in `churches` meta.

### ComparisonCategory

Registers `comparison_category` taxonomy. Hierarchical, `public = false`, `show_in_rest = true`, `show_admin_column = true`. Attached to `comparison_topic` only.

### ChurchesMeta

Registers post meta (`churches`, `sort_order`) and term meta (`sort_order`) with JSON schema validation. Sanitizes church names with `sanitize_text_field()`, descriptions with `wp_kses_post()`. Auth callback requires `edit_posts`.

### ChurchesMetaBox

Admin repeater meta box "Stanowiska kosciolow" on the topic edit screen:
- Sort order input
- Dynamic repeater: add/remove churches
- Full TinyMCE editor per church description
- JS handles dynamic TinyMCE initialization and cleanup
- Nonce verification, capability check, autosave skip

### AdminColumns

Adds custom columns to CPT list table:
- **Koscioly** — number of churches assigned
- **Kolejnosc** — sort order value (sortable, queries by `meta_value_num`)

### RegisterBlocks

Auto-discovers blocks from `build/blocks/` and registers via `register_block_type_from_metadata()`.

## Gutenberg Block: comparison-accordion

**Dynamic block** — no client-side save, all output from `render.php`.

### Editor (edit.js)

Uses `ServerSideRender` for WYSIWYG preview. Editor styles force all panels open and hide chevrons.

### Render (render.php)

1. Checks transient cache (30-minute TTL, skipped during REST requests for fresh editor preview)
2. Fetches categories sorted by term `sort_order`
3. Fetches all published topics sorted by post `sort_order`
4. Discovers church names dynamically from meta (no hardcoded limit)
5. Renders accordion with CSS Grid table per category
6. Generates FAQ JSON-LD schema for SEO rich snippets
7. Caches rendered HTML in transient

**Desktop layout:** CSS Grid with columns `[Topic | Church A | Church B | ...]`. Column count driven by `--cor-church-count` CSS custom property. Each paragraph gets its own grid cell for cross-church alignment.

**Mobile layout (<=768px):** `display: block`, stacked flow. Church labels become visible before each description block.

### Frontend (frontend.js)

Accordion toggle with WCAG 2.1 AA compliance:
- Smooth `max-height` transitions with browser reflow tricks
- Keyboard: Arrow Up/Down (navigate), Home/End (jump), Enter/Space (toggle)
- ARIA: `aria-expanded`, `aria-hidden`, `aria-controls`

### Cache Invalidation

Managed by `AccordionCache` class (`app/Cache/AccordionCache.php`). Key prefix and TTL defined as class constants — single source of truth for both render template and flush logic. Caches cleared automatically via hooks:
- `save_post_comparison_topic` → `AccordionCache::flush()`
- `created_comparison_category` / `edited_comparison_category` / `delete_comparison_category` → `AccordionCache::flush()`

## Data Import/Export

Admin page: **wp-admin > Porownania > Import danych** (requires `manage_options`)

Three operations:
1. **Import from JSON** — Upload `.json` file with categories + topics
2. **Export to JSON** — Download current data as `.json`
3. **Built-in import** — One-time seed with hardcoded data (Catholic vs Pentecostal, ~40 topics across 9 categories)

**JSON format:**
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

## Build

```bash
npm run build    # Production (wp-scripts build)
npm start        # Watch mode (wp-scripts start)
```

## Code Quality

```bash
composer phpstan    # PHPStan level 6
composer phpcs      # WordPress Coding Standards
composer check      # Both
```

## Uninstall

On plugin deletion (`uninstall.php`): deletes all `comparison_topic` posts (force delete) and all `comparison_category` terms. Transients expire naturally.
