# Custom Posts

Plugin for registering custom post types and taxonomies using reusable builder classes.

## Plugin Info

- **Version:** 1.0.0
- **Author:** Lukasz Lasota
- **Requires PHP:** 8.0+
- **Requires WordPress:** 6.4+
- **License:** GPL-2.0-or-later

## Architecture

```
custom-posts/
├── custom-posts.php           # Plugin entry: autoloader, init RegisterPosts + CustomColumns
└── src/
    ├── Core/
    │   ├── CptBuilder.php     # Reusable CPT registration builder
    │   └── TaxBuilder.php     # Reusable taxonomy registration builder
    └── Posts/
        ├── RegisterPosts.php  # Registers the meetings CPT
        └── CustomColumns.php  # Custom admin columns for meetings
```

PSR-4 autoloader maps `CustomPostsPlugin\` namespace to `src/`. Classes initialized on `plugins_loaded` hook — CptBuilder internally hooks `register()` on `init`, so it must be instantiated before `init` fires. CptBuilder accepts labels as a callable (deferred) to avoid `_load_textdomain_just_in_time` warnings — `__()` calls execute on `init` when the textdomain is loaded.

## Builder Classes

### CptBuilder

Reusable builder for registering custom post types.

```php
new CptBuilder( string $slug, array $labels, int $position = 5, string|bool $archive = false );
```

Registers a CPT with these defaults:
- `public: true`, `show_in_rest: true`
- `hierarchical: true`
- `supports: title, editor, thumbnail, excerpt, comments, custom-fields, revisions`
- `taxonomies: ['category']`
- `rewrite: ['slug' => $slug]`

### TaxBuilder

Reusable builder for registering custom taxonomies.

```php
new TaxBuilder( string $slug, string $post_type, array $labels, array $args = [] );
```

Registers a taxonomy with these defaults:
- `hierarchical: true`
- `show_ui: true`, `show_admin_column: true`
- `query_var: true`

## Registered Post Types

### Meetings (`meetings`)

Parish meeting announcements. Registered via `CptBuilder`:
- Menu position: 5
- Archive: disabled
- REST API enabled
- Uses standard categories

**Labels:** Polish (Spotkania, Spotkanie, Dodaj Nowe Spotkanie, etc.)

## Admin Columns

Custom columns for the meetings list table:

| Column | Description |
|--------|-------------|
| Tytul | Post title |
| Autor | Author display name |
| Kategorie | Assigned categories (or "Brak kategorii") |
| Tagi | Post tags (or "Brak tagow") |
| Data | Creation date |
| Ostatnia modyfikacja | Last modified date (Y-m-d H:i) |

## Code Quality

```bash
composer phpstan    # PHPStan level 6
composer phpcs      # WordPress Coding Standards
composer phpcbf     # Auto-fix
composer check      # Both
```

**Dev dependencies:** phpstan ^2.0, phpstan-wordpress ^2.0, wpcs ^3.0
