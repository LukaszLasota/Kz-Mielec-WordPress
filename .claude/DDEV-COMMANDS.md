# Komendy DDEV — Kzmielec

Custom komendy DDEV dla projektu. Pliki w `.ddev/commands/web/` (ignorowane przez git,
więc istnieją tylko lokalnie — po świeżym klonie trzeba je odtworzyć).

## Build

| Komenda | Opis |
|---------|------|
| `ddev theme:build` | Build motywu — **jedna optymalna wersja** (minified + sourcemapy) |
| `ddev plugin:build` | Build pluginu custom-block-package |
| `ddev build:all` | Build wszystkiego: plugin + motyw |

## Watch

| Komenda | Opis |
|---------|------|
| `ddev theme:watch` | Watch motywu — te same nazwy plików, wersja nieminifikowana |
| `ddev watch:all` | Watch motywu + pluginu równolegle (Ctrl+C żeby zatrzymać) |

Uruchamiasz **jedną naraz** — `watch` do iterowania, `build` do wydania.

## Jak to działa (motyw)

Motyw używa `@wordpress/scripts` (tak jak plugin) z cienkim `webpack.config.js`
w katalogu motywu. Jest **jeden plik wyjściowy** na entry, np.
`assets/css/frontend.css` — bez wariantu `.min`. PHP ładuje zawsze tę samą
ścieżkę, lokalnie i na produkcji; różni się tylko treść pliku (`watch` =
nieminifikowany, `build` = minifikowany).

Build **nie uruchamia się na serwerze** — na produkcję jedzie zbudowany,
commitowany plik z `assets/`.

Pre-commit hook (`.githooks/pre-commit`) sam robi `ddev theme:build` i dorzuca
`assets/`, gdy w commicie są zmiany w `src/` lub configu — nie trzeba pamiętać
o buildzie przed commitem.

### Uwaga: watch a WSL2/Docker

Watch bywa kapryśny przy edycjach z hosta (inotify nie przechodzi przez mount;
w configu jest `watchOptions.poll` jako mitygacja). Jeśli zapisujesz plik i nie
widzisz zmiany — odpal `ddev theme:build`, jest natychmiastowy i pewny.

## Inne przydatne

| Komenda | Opis |
|---------|------|
| `ddev wp cache flush` | Wyczyść cache WordPressa |
| `ddev wp user list` | Lista użytkowników WP |
| `ddev wp user update <login> --user_pass=haslo` | Reset hasła |

## Ścieżki wewnętrzne (w kontenerze DDEV)

- Motyw: `/var/www/html/wp-content/themes/kzmielec/` (źródła w `src/`, wynik w `assets/`)
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
