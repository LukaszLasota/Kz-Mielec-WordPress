# Komendy DDEV — Kzmielec

Custom komendy DDEV dla projektu. Pliki w `.ddev/commands/web/` — **są w repo**
(`.gitignore` wyłącza resztę `.ddev/`, ale nie `commands/`), więc po klonie działają
od razu. Wcześniej były ignorowane i trzeba je było odtwarzać ręcznie, co znaczyło,
że każda maszyna mogła budować co innego.

## Build

| Komenda | Opis |
|---------|------|
| `ddev theme:build` | Build motywu — **jedna optymalna wersja** (minified + sourcemapy) |
| `ddev plugin:build` | Build **obu** wtyczek blokowych: custom-block-package + comparison-of-religions |
| `ddev build:all` | Build wszystkiego: motyw + obie wtyczki blokowe (przerywa na pierwszym błędzie) |

`custom-posts` nie ma builda — `src/` to jej kod runtime, nie źródła do kompilacji.
Do 2026-08-04 `build:all` i `plugin:build` pomijały `comparison-of-religions`
i nie sprawdzały kodu wyjścia, więc nieudany albo pominięty build kończył się
komunikatem „All builds complete".

## Watch

| Komenda | Opis |
|---------|------|
| `ddev theme:watch` | Watch motywu — te same nazwy plików, wersja nieminifikowana |
| `ddev watch:all` | Watch motywu + obu wtyczek blokowych równolegle (Ctrl+C żeby zatrzymać) |

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
