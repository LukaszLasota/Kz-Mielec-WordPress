# Custom Posts

Plugin for registering Custom Post Types and taxonomies using reusable builder classes.

## Plugin Info

- **Version:** 1.0.0
- **Author:** Łukasz Lasota
- **Requires PHP:** 8.0+
- **Requires WordPress:** 6.4+
- **License:** GPL-2.0-or-later

## Architecture

```
custom-posts/
├── custom-posts.php           # Plugin entry: autoloader, RegisterPosts + CustomColumns
└── src/
    ├── Core/
    │   ├── CptBuilder.php     # Reusable CPT registration builder
    │   └── TaxBuilder.php     # Reusable taxonomy registration builder
    └── Posts/
        ├── RegisterPosts.php  # Registers meetings CPT
        └── CustomColumns.php  # Custom admin columns
```

PSR-4 autoloader: `CustomPostsPlugin\` → `src/`.

## Registered Post Types

### Meetings (`meetings`)

Church meetings. Archive: `/zaplanuj-wizyte/`, single: `/meetings/{slug}/`.

- Menu position: 5
- REST API enabled
- Supports: title, editor, thumbnail, excerpt, custom-fields, revisions

## Builder Classes

### CptBuilder

```php
new CptBuilder( string $slug, array $labels, int $position = 5, string|bool $archive = false );
```

### TaxBuilder

```php
new TaxBuilder( string $slug, string $post_type, array $labels, array $args = [] );
```

## Code Quality

```bash
composer install
composer phpstan    # PHPStan level 6
composer phpcs      # WordPress Coding Standards
composer check      # Both
```
