# Custom Posts

Registers the `meetings` Custom Post Type through reusable builder classes, and
gives its archive a translated address in each of the site's four languages.

See the [project README](../../../README.md) for how this plugin relates to the
theme and the other three.

## Plugin Info

- **Version:** 1.0.0
- **Author:** Łukasz Lasota
- **Requires PHP:** 8.0+
- **Requires WordPress:** 6.4+
- **License:** GPL-2.0-or-later

## Architecture

```
custom-posts/
├── custom-posts.php                # entry: autoloader and the component list
└── src/
    ├── Core/
    │   ├── CptBuilder.php          # reusable CPT registration builder
    │   └── TaxBuilder.php          # reusable taxonomy registration builder
    └── Posts/
        ├── RegisterPosts.php       # registers the meetings CPT
        ├── MeetingsArchiveSlugs.php # one archive slug per language
        └── CustomColumns.php       # custom admin columns
```

PSR-4 autoloader: `CustomPostsPlugin\` -> `src/`.

**Why this is a plugin and not part of the theme.** A CPT registered by a theme
becomes invisible the moment the theme is switched — the rows stay in `wp_posts`
but nothing declares the type any more, so the content disappears from the admin.
Registering it here keeps the meetings editable regardless of the active theme.

## Registered Post Types

### Meetings (`meetings`)

Church meetings. Single: `/meetings/{slug}/`. The archive address depends on the
language — see below.

- Menu position: 5
- REST API enabled
- Supports: title, editor, thumbnail, excerpt, comments, custom-fields, revisions
  (set once in `CptBuilder`, shared by any future post type)

The meeting fields themselves (`_meeting_place`, `_meeting_day_hour`,
`_meeting_hover_image`) are registered by `custom-block-package`, which owns the
block that renders them.

## Per-language archive slugs

| language | archive URL |
|---|---|
| Polish | `/zaplanuj-wizyte/` |
| English | `/en/plan-your-visit/` |
| Ukrainian | `/uk/zaplanuyte-vizyt/` |
| Spanish | `/es/planifica-tu-visita/` |

Polylang gets the language **prefix** right on its own — `/en/zaplanuj-wizyte/`
resolves and returns English content — but translating the **slug** is a paid
feature. Since this plugin registers the post type, `MeetingsArchiveSlugs` writes
the rules by hand instead: one rewrite per language, so a slug cannot be reached
under the wrong prefix, plus a filter so generated links come out in the
visitor's language.

Four hooks do the work:

| hook | why |
|---|---|
| `init` (priority 20) | adds one rewrite rule per language |
| `post_type_archive_link` | so `get_post_type_archive_link()` returns the translated URL |
| `pll_translation_url` | so the language switcher points at the right slug instead of the Polish one |
| `template_redirect` | 301s a visitor who reaches a slug under the wrong prefix, or any foreign slug once Polylang is inactive |

**Rewrite rules are cached by WordPress**, so the class hooks
`deactivated_plugin` and calls `delete_option('rewrite_rules')`. Without that,
deactivating Polylang leaves four language rewrites in place with nothing to
resolve them, and the archive 404s until permalinks are saved by hand.

Everything language-related is guarded with `function_exists('pll_current_language')`.
With Polylang inactive the class registers the Polish slug only and redirects the
other three to it, so no URL is left dangling.

## Builder Classes

```php
new CptBuilder( string $slug, array $labels, int $position = 5, string|bool $archive = false );
new TaxBuilder( string $slug, string $post_type, array $labels, array $args = [] );
```

Both exist so a second post type costs a few lines rather than a copied
`register_post_type()` call with forty arguments.

## Code Quality

```bash
composer install
composer phpstan    # PHPStan level 8
composer phpcs      # WordPress Coding Standards
composer check      # both
```
