# Komendy DDEV — Kzmielec

Custom komendy DDEV dla projektu. Pliki w `.ddev/commands/web/` (ignorowane przez git).

## Build

| Komenda | Opis |
|---------|------|
| `ddev theme:dev` | Build motywu (webpack dev mode) |
| `ddev theme:prod` | Build motywu (webpack prod, minified) |
| `ddev plugin:build` | Build pluginu custom-block-package (wp-scripts) |
| `ddev build:all` | Build wszystkiego: plugin + motyw dev + motyw prod |

## Watch

| Komenda | Opis |
|---------|------|
| `ddev theme:watch` | Watch motywu z auto-rebuild przy zmianach |
| `ddev watch:all` | Watch motywu + pluginu równolegle (Ctrl+C żeby zatrzymać) |

## Inne przydatne

| Komenda | Opis |
|---------|------|
| `ddev wp cache flush` | Wyczyść cache WordPressa |
| `ddev wp user list` | Lista użytkowników WP |
| `ddev wp user update <login> --user_pass=haslo` | Reset hasła |

## Ścieżki wewnętrzne (w kontenerze DDEV)

- Motyw webpack: `/var/www/html/wp-content/themes/kzmielec/webpack/`
- Plugin: `/var/www/html/wp-content/plugins/custom-block-package/`

## Jak dodać nową komendę

1. Utwórz plik w `.ddev/commands/web/nazwa-komendy`
2. Dodaj nagłówek:
   ```bash
   #!/bin/bash
   ## Description: Opis komendy
   ## Usage: nazwa-komendy
   ## Example: ddev nazwa-komendy
   ```
3. `chmod +x .ddev/commands/web/nazwa-komendy`
4. DDEV automatycznie wykryje komendę
