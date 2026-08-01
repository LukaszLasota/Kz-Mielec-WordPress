# NOTATKI PROJEKTU — KZMIELEC

## Informacje ogólne

- **Nazwa projektu:** Kościół Zielonoświątkowy Zbór w Mielcu (kzmielec.pl)
- **Typ:** Strona internetowa kościoła
- **Platforma:** WordPress 6.x
- **Środowisko lokalne:** DDEV
- **Ścieżka WSL:** `/home/lukasz/projects/kzmielec/`
- **URL lokalny:** https://kzmielec.ddev.site
- **URL produkcji:** https://kzmielec.pl
- **GitHub:** https://github.com/LukaszLasota/Kz-Mielec-WordPress

Migracja ze starego motywu `html5blank-stable` na nowy custom motyw `kzmielec` (oparty o motyw Church z projektu Emaus). Nowa strona = WordPress + Gutenberg blocks zamiast hardcoded HTML.

---

## Dokumentacja w tym katalogu

| Plik | Opis |
|------|------|
| [DDEV-COMMANDS.md](DDEV-COMMANDS.md) | Custom komendy DDEV (build/watch theme + plugin) |
| [FACEBOOK-FEED.md](FACEBOOK-FEED.md) | Pełna dokumentacja implementacji Facebook Feed (block + service + cache + cron + REST) |
| [INSTRUKCJA-TOKEN-FB.md](INSTRUKCJA-TOKEN-FB.md) | Instrukcja krok-po-kroku dla admina FB Page (do wysłania pastorowi) |
| [migration-plan.md](migration-plan.md) | Plan migracji z html5blank-stable do nowego motywu |
| [theme-old-docs.md](theme-old-docs.md) | Dokumentacja starego motywu (referencja) |
| [project-config.md](project-config.md) | Konfiguracja hostingu, dane logowania |
| INSTRUCTIONS.md | Zasady współpracy z Claude |

---

## Komponenty zaimplementowane

### Plugin custom-block-package (gutenberg blocks)

Architektura PSR-4, namespace `CustomBlockPackage`. Wszystkie bloki w `src/blocks/`, wszystkie klasy w `app/`.

**Bloki:**
- `accordion-item` / `custom-accordion` — accordion (rozwijane sekcje)
- `dynamic-images` — responsywne obrazy z overlay (banner hero)
- `image-text` — układ obraz + tekst
- `map-block` — mapa Leaflet z markerem
- `meeting-list` — lista nabożeństw (z CPT)
- `pdf-block` — embed PDF
- `section-block` — flex/grid container
- `custom-svg` — wstawianie inline SVG
- `scroll-arrow` — czarne kółko ze strzałką (smooth scroll do anchor)
- `facebook-feed` — feed z FB przez Graph API z infinite scroll

**Klasy (app/):**
- `Blocks/RegisterBlocks` — auto-discovery bloków z `build/blocks/`
- `Assets/AssetsManager` — Leaflet, Glide.js, arrow images
- `Cache/BlockCache` — transient cache (NEWS_SLIDER_PREFIX, MEETING_LIST_PREFIX, FACEBOOK_FEED_PREFIX)
- `Services/FacebookFeedService` — Facebook Graph API client
- `Cron/FacebookFeedCron` — WP Cron refresh feedu co 2h
- `Rest/FacebookFeedController` — REST endpoint dla infinite scroll
- `Admin/FacebookSettings` — admin page + dashboard widget + admin notice

### Motyw kzmielec

- **Block patterns:** `banner-hero`, `contact-section`, `hello-section-main-page`
- **Block style variations:** `dynamic-images / banner-hero`, `core/heading / section-line`
- **Theme components (App/):**
  - `BasicTheme/Setup` — theme supports
  - `BasicTheme/Menu` — nav menu
  - `BasicTheme/RegisterAssets` — webpack assets
  - `BasicTheme/RegisterWidgets` — widget areas
  - `Core/PatternAssets` — auto-load CSS/JS dla patterns
  - `Core/BlockStyles` — registracja block style variations
  - `Core/SvgSupport` — SVG upload
  - `Core/PerformanceOptimizer` — preload, lazy load
  - `Core/GroupLinkSupport` — `<a>` na `wp:group`
  - `Admin/ThemeSettingsPage` + subpages

---

## MOTYW WORDPRESS

### Informacje podstawowe

- **Nazwa:** Church
- **Wersja:** 1.0
- **Autor:** Łukasz Lasota
- **Opis:** Motyw dla kościoła
- **Text Domain:** church
- **Licencja:** GNU General Public License v2 or later
- **GitHub:** https://github.com/LukaszLasota/emaus-rzeszow-wordpress/tree/main/wp-content/themes

### Lokalizacja

```
wp-content/themes/church/
```

### Struktura motywu

```
church/
├── acf-json/                 # Advanced Custom Fields JSON
├── App/                      # Główna logika motywu (OOP)
│   ├── Admin/               # Panel administracyjny
│   │   └── LogoSettings.php
│   ├── BasicTheme/          # Podstawowa funkcjonalność
│   │   ├── Menu.php
│   │   ├── RegisterAssets.php
│   │   ├── Rewrite.php
│   │   └── Setup.php
│   ├── Core/                # Rozszerzenia rdzenia WordPress
│   │   ├── GroupLinkSupport.php  # Linki do grup bloków
│   │   ├── PatternAssets.php     # Zasoby CSS/JS z patterns
│   │   └── SvgSupport.php       # Wsparcie SVG w mediach
│   ├── Interfaces/          # Interfejsy PHP
│   │   ├── ActionHookInterface.php
│   │   ├── ActionHookWithArgsInterface.php
│   │   └── FilterHookInterface.php
│   ├── Widgets/
│   │   └── RegisterWidgets.php
│   └── Theme.php            # Bootstrap class (inicjalizacja)
├── assets/                   # Zasoby (CSS, JS, obrazy)
├── template-parts/           # Części szablonów
│   └── content-posts.php    # Szablon listy postów (blog/archiwum)
├── patterns/                 # Block patterns
│   ├── contact-section.php  # Sekcja kontaktowa
│   └── hello-section-main-page.php  # Sekcja powitalna
├── vendor/                   # Zależności Composer
├── webpack/                  # Konfiguracja Webpack
├── archive.php              # Szablon archiwum
├── footer.php               # Stopka
├── front-page.php           # Strona główna
├── functions.php            # Główny plik funkcji
├── header.php               # Nagłówek
├── index.php                # Główny plik szablonu
├── page.php                 # Szablon strony
├── single.php               # Szablon pojedynczego wpisu
├── style.css                # Główny arkusz stylów
├── theme.json               # Konfiguracja motywu (FSE)
├── composer.json            # Zależności PHP
├── phpstan.neon             # Konfiguracja PHPStan (level 8)
├── phpcs.xml                # Konfiguracja PHPCS (WordPress)
└── screenshot.png           # Zrzut ekranu motywu
```

### Technologie i narzędzia

- **PHP:** Architektura OOP z namespace `Church`
- **Composer:** Zarządzanie zależnościami PHP
- **Webpack:** Build system dla assets
- **ACF (Advanced Custom Fields):** Niestandardowe pola
- **Theme.json:** Full Site Editing support

### Bootstrap i inicjalizacja

**Plik główny:** `functions.php`
- Ładuje Composer autoloader (`vendor/autoload.php`)
- Inicjalizuje `Church\Theme` (bootstrap class)

**Klasa bootstrap:** `App/Theme.php`
- Centralne miejsce inicjalizacji wszystkich komponentów motywu
- Kontrola kolejności ładowania (Setup zawsze pierwszy)
- Context-aware loading: Admin classes tylko w `is_admin()`
- Optymalizacja: LogoSettings nie ładuje się na froncie

### Komponenty motywu

**Frontend + Admin:**
```
Church\BasicTheme\Setup         # Zawsze pierwszy - theme supports
Church\BasicTheme\Menu
Church\BasicTheme\RegisterAssets
Church\BasicTheme\Rewrite
Church\Widgets\RegisterWidgets
```

**Admin tylko:**
```
Church\Admin\LogoSettings       # Ładuje się tylko w is_admin()
```

### RegisterAssets.php - Szczegóły implementacji

**Lokalizacja:** `App/BasicTheme/RegisterAssets.php`

**Funkcje:**
- Zarządzanie zasobami CSS/JS motywu
- Automatyczne wersjonowanie (cache busting przez `filemtime()`)
- Build daje jeden zoptymalizowany plik na wejście, więc nie ma wariantu `.min`

**Metody:**

1. **`get_file_version(string $file_path): string|false`**
   - Cache busting przez `filemtime()`
   - Zwraca `false`, gdy pliku nie ma

2. **`enqueue_asset()`**
   - Uniwersalna metoda do `wp_enqueue_script()` i `wp_enqueue_style()`
   - Pomija zasób, którego plik nie istnieje

3. **`register_kzmielec_assets(): void`**
   - Frontend: `frontend.js`, `frontend.css`
   - AJAX localization: `redlist.ajax_url`

Motyw nie ma dziś własnych zasobów admina — `backend.css` nigdy nie powstał,
więc metoda ładująca go została usunięta (2026-08-01). Skrypt panelu logo
(`assets/js/logo.js`) ładuje `App/Admin/LogoSettings.php` osobno.

**Standard kodowania:**
- WordPress Coding Standards (WPCS)
- PHPDoc dla wszystkich metod
- Type hints (PHP 8+)
- Yoda conditions

---

## KONFIGURACJA ŚRODOWISKA

### Zmienne środowiskowe

**WordPress Environment Type:**
- **Lokalizacja:** `wp-config.php` (linia 95)
- **Zmienna:** `define('WP_ENVIRONMENT_TYPE', 'local');`
- **Ustawione przez:** Local by Flywheel automatycznie
- **Wartości:** `local`, `development`, `staging`, `production`

**PHP Environment Type (fallback dla Docker):**
- **Lokalizacja:** `wp-config.php` (linia 93, zaraz przed WP_ENVIRONMENT_TYPE)
- **Zmienna:** `putenv('ENV_TYPE=development');`
- **Cel:** Fallback dla środowisk bez `wp_get_environment_type()` (np. Docker, starsze WP)
- **Używane w:** `RegisterAssets.php::get_asset_suffix()`

### Webpack/npm - NODE_ENV Problem

**Problem:**
- VSCode ustawia `NODE_ENV=production` w terminalu
- `npm install` pomijał `devDependencies` (webpack-cli, sass, etc.)

**Rozwiązanie:**
- **Plik:** `webpack/.npmrc`
- **Zawartość:** `production=false`
- **Efekt:** npm zawsze instaluje devDependencies niezależnie od NODE_ENV

**Ważne:**
- `.npmrc` musi pozostać w projekcie
- Bez niego `npm install` nie zainstaluje webpack-cli i innych narzędzi dev

### Build system - Webpack

**Konfiguracja:** jeden `webpack.config.js` na bazie `@wordpress/scripts`
(nie ma już osobnych plików dev/prod ani wariantu `.min`).

**Wejścia:** `src/frontend.ts`, `src/editor.ts` oraz automatycznie wykrywane
`src/patterns/<slug>/` i `src/block-styles/*.scss`.

**Output:** `assets/js/[name].js` i `assets/css/[name].css`; obrazy i fonty
trafiają do `assets/media/` i `assets/webfont/`.

**Skrypty npm:**
```bash
npm run build     # build produkcyjny
npm run start     # tryb watch
npm run lint:css  # stylelint na src/**/*.scss
```

---

## WTYCZKA: CUSTOM BLOCK PACKAGE

### Informacje podstawowe

- **Nazwa:** Custom Block Package
- **Wersja:** 1.0.0
- **Autor:** Łukasz Lasota
- **Opis:** Wtyczka dodająca niestandardowe bloki Gutenberg do motywu
- **Text Domain:** custom-block-package
- **Wymagania:**
  - WordPress: 5.9+
  - PHP: 7.2+

### Lokalizacja

```
wp-content/plugins/custom-block-package/
```

### Struktura wtyczki

```
custom-block-package/
├── app/                      # Klasy PHP z namespace (PSR-4)
│   ├── Autoloader.php       # PSR-4 Autoloader
│   ├── Assets/
│   │   └── AssetsManager.php    # Zarządzanie zewnętrznymi bibliotekami
│   └── Blocks/
│       └── RegisterBlocks.php   # Rejestracja bloków Gutenberg
├── build/                    # Skompilowane pliki bloków (z Webpack)
│   ├── blocks/              # Wszystkie bloki (każdy z block.json)
│   │   ├── accordion-item/
│   │   ├── custom-accordion/
│   │   ├── dynamic-images/
│   │   │   └── render.php   # Server-side render
│   │   ├── emaus-news-slider/
│   │   │   └── render.php   # Server-side render
│   │   ├── image-text/
│   │   ├── map-block/
│   │   │   └── render.php   # Server-side render
│   │   ├── meeting-list/
│   │   │   └── render.php   # Server-side render
│   │   ├── pdf-block/
│   │   ├── responsive-image-slider/
│   │   ├── section-block/
│   │   │   └── render.php   # Server-side render (converted from static)
│   │   └── slider-block/
│   └── glide-package/       # Biblioteka Glide.js (slider)
├── src/                      # Kod źródłowy bloków (przed kompilacją Webpack)
│   └── blocks/
│       ├── dynamic-images/
│       │   ├── block.json
│       │   ├── index.js
│       │   ├── editor.scss
│       │   ├── style.scss
│       │   └── render.php   # Server-side render template
│       └── ... (pozostałe bloki)
├── composer.json             # Zależności PHP (PHPStan, PHPCS)
├── phpstan.neon              # Konfiguracja PHPStan (level 6)
├── phpstan-bootstrap.php     # Bootstrap dla PHPStan (stałe wtyczki)
├── phpcs.xml                 # Konfiguracja PHPCS (WordPress-Extra)
├── .gitignore                # Ignoruje vendor/ i node_modules/
└── index.php                # Główny plik wtyczki
```

### Architektura i namespace

Wtyczka używa **PSR-4 autoloadera** i architektury OOP z namespace.

- **Base namespace:** `CustomBlockPackage`
- **Autoloader:** `app/Autoloader.php`
- **Standard kodowania:** Strict types, PHPDoc, type hints

### Główne klasy

#### 1. Autoloader (app/Autoloader.php)

- **Namespace:** `CustomBlockPackage`
- **Funkcja:** PSR-4 autoloader mapujący namespace do folderu `app/`
- **Metody:**
  - `autoload()` - automatyczne ładowanie klas na podstawie namespace

#### 2. RegisterBlocks (app/Blocks/RegisterBlocks.php)

- **Namespace:** `CustomBlockPackage\Blocks`
- **Funkcja:** Automatyczna rejestracja bloków Gutenberg
- **Metody:**
  - `register_blocks(): void` - skanuje folder `build/blocks/` i rejestruje wszystkie bloki
  - `register_block_category(array $categories): array` - dodaje kategorię "Custom blocks for sites"

#### 3. AssetsManager (app/Assets/AssetsManager.php)

- **Namespace:** `CustomBlockPackage\Assets`
- **Funkcja:** Zarządzanie zewnętrznymi zasobami (biblioteki JS/CSS)
- **Stałe wersji:**
  - `LEAFLET_VERSION = '1.9.4'` - wersja biblioteki Leaflet
  - `GLIDE_VERSION = '3.5.2'` - wersja biblioteki Glide.js
- **Metody:**
  - `register_all_assets(): void` - rejestruje wszystkie zasoby
  - `register_leaflet_assets(): void` - rejestruje Leaflet (mapy)
  - `register_glide_assets(): void` - rejestruje Glide.js (slidery)

### Lista bloków Gutenberg

Wszystkie bloki znajdują się w kategorii **"Custom blocks for sites"** (`custom-blocks-from-scratch`).

1. **accordion-item** - Element akordeonu
2. **custom-accordion** - Niestandardowy akordeon (z interakcją JS)
3. **dynamic-images** - Dynamiczne obrazy (render PHP)
4. **emaus-news-slider** - Slider aktualności Emaus (render PHP)
5. **image-text** - Blok obraz + tekst
6. **map-block** - Mapa (Leaflet, render PHP)
7. **meeting-list** - Lista spotkań (render PHP, view.js)
8. **pdf-block** - Blok PDF
9. **responsive-image-slider** - Responsywny slider obrazów (Glide.js)
10. **section-block** - Blok sekcji z zaawansowanym layoutem
11. **slider-block** - Podstawowy slider (Swiper.js)

### Bloki z renderowaniem PHP (dynamiczne)

- `dynamic-images` → `build/blocks/dynamic-images/render.php`
- `emaus-news-slider` → `build/blocks/emaus-news-slider/render.php`
- `map-block` → `build/blocks/map-block/render.php`
- `meeting-list` → `build/blocks/meeting-list/render.php`
- `section-block` → `build/blocks/section-block/render.php` *(skonwertowany ze statycznego)*

### Bloki z frontend JavaScript

- `custom-accordion` → `frontend.js` (obsługa interakcji)
- `map-block` → `frontend.js` (inicjalizacja Leaflet)
- `meeting-list` → `view.js` (interakcja z listą)

### Stałe wtyczki (index.php)

```
UP_PLUGIN_DIR  - ścieżka do folderu wtyczki
UP_PLUGIN_FILE - ścieżka do pliku głównego
UP_PLUGIN_URL  - URL wtyczki
```

### System ładowania plików

**PSR-4 Autoloader:**
- Klasy z namespace `CustomBlockPackage` ładowane automatycznie z folderu `app/`
- Autoloader inicjalizowany w `index.php` (linia 24)
- Automatyczne mapowanie: `CustomBlockPackage\Assets\AssetsManager` → `app/Assets/AssetsManager.php`

**Bloki Gutenberg:**
- WordPress automatycznie rejestruje bloki przez `register_block_type_from_metadata()`
- Skanowanie folderu `build/blocks/` w `RegisterBlocks::register_blocks()`
- Każdy blok z `block.json` jest automatycznie wykrywany i ładowany

### System render dla bloków dynamicznych

WordPress obsługuje **dwa rodzaje bloków**:

#### Bloki statyczne (7 bloków)
- **HTML zapisany w bazie:** treść generowana przez JavaScript `save()` w edytorze
- **Kiedy używać:** Statyczna treść (obrazy, tekst, layout) bez zmiennych danych
- **Przykłady:** accordion-item, custom-accordion, image-text, pdf-block, slider-block, responsive-image-slider

#### Bloki dynamiczne - z render.php (4 bloki)
- **HTML generowany przy każdym wyświetleniu:** PHP wykonywany za każdym razem
- **Definicja w block.json:** `"render": "file:./render.php"`
- **Dostępna zmienna:** `$attributes` (automatycznie przekazywana przez WordPress)
- **Kiedy używać:**
  - Dane zmienne (najnowsze posty, query do bazy)
  - Server-side processing (cache, API calls)
  - Dynamiczny content zależny od kontekstu

**Struktura render.php:**
```php
<?php
/**
 * @var array $attributes Block attributes from block.json
 */

// Pobierz atrybuty z walidacją
$latitude = $attributes['latitude'];
$zoom = isset($attributes['zoom']) ? $attributes['zoom'] : 16;

// Bezpośredni output HTML (NIE return!)
?>
<div class="my-block">
    <?php echo esc_html($latitude); ?>
</div>
```

**5 bloków z render.php:**
1. **dynamic-images** - responsive `<picture>` z różnymi obrazami dla desktop/tablet/mobile
2. **emaus-news-slider** - WP_Query najnowszych postów + cache (15 min)
3. **map-block** - mapa Leaflet z data attributes dla JS
4. **meeting-list** - custom post type 'meetings' z ACF fields
5. **section-block** - sekcja z zaawansowanym layoutem (skonwertowany ze statycznego na dynamiczny)

### Code Quality Tools (wtyczka)

#### PHPStan - Static Analysis (Level 6)

**Konfiguracja:** `phpstan.neon`
**Bootstrap:** `phpstan-bootstrap.php` (definiuje stałe wtyczki: UP_PLUGIN_DIR, UP_PLUGIN_FILE, UP_PLUGIN_URL)
**WordPress extension:** `szepeviktor/phpstan-wordpress` v2 (stuby typów dla funkcji WordPress)

**Analizowane pliki:**
- `app/` - klasy PHP (PSR-4)
- `src/blocks/*/render.php` - szablony renderowania bloków

**Ignorowane błędy:**
- `get_field()` - brak stubów ACF
- `missingType.iterableValue` - ogólne typy tablic

**Uruchomienie:**
```bash
cd wp-content/plugins/custom-block-package
composer phpstan
```

#### PHPCS - Coding Standards (WordPress-Extra)

**Konfiguracja:** `phpcs.xml`
**Standard:** WordPress-Extra (z `wp-coding-standards/wpcs` v3)

**Sprawdzane pliki:**
- `app/` - klasy PHP
- `src/blocks/*/render.php` - szablony renderowania

**Customizacje:**
- Wykluczenie `WordPress.Files.FileName.*` (PSR-4 wymaga PascalCase.php)
- Wyłączenie `Squiz.Commenting.FileComment` (opcjonalne)
- Wyłączenie `Generic.Commenting.DocComment.MissingShort` (opcjonalne w render.php)

**Uruchomienie:**
```bash
composer phpcs   # Sprawdzenie
composer phpcbf  # Auto-fix
composer check   # PHPStan + PHPCS razem
```

**Status (2026-02-20):** ✅ 0 błędów PHPStan, 0 błędów PHPCS (2 warningi serialize())

---

## ZEWNĘTRZNE BIBLIOTEKI

### Leaflet 1.9.4
- **Cel:** Mapy interaktywne
- **Źródło:** CDN (unpkg.com)
- **Używane w:** map-block

### Glide.js 3.5.2
- **Cel:** Slidery/karuzele
- **Źródło:** Lokalne pliki w `build/glide-package/`
- **Pliki:**
  - `glide.core.css`
  - `glide.min.js`
  - `index.js` (inicjalizacja)

---

## HISTORIA ZMIAN GIT (ostatnie commity)

1. `0aee3edd` - Fix PHP code for PHPStan level 8 and PHPCS WordPress compliance (motyw - szablony)
2. `af3aa916` - Extend PHPStan and PHPCS configuration to cover all theme files (motyw - config)
3. `802bad91` - Update custom block build files (wtyczka - build)
4. `dd69582e` - Fix PHP code for PHPStan and PHPCS compliance (wtyczka - render.php)
5. `03a64b5e` - Add PHPStan and PHPCS configuration (wtyczka - config)
6. `2137f7bd` - Add contact section pattern and fix mobile responsive styles
7. `38cbcd72` - Update custom block build files
8. `f2549eb4` - Update custom block sources and webpack config
9. `7162eb17` - Refactor section-block to dynamic rendering with render.php
10. `188147e8` - Update Forminator plugin

---

## NOTATKI TECHNICZNE

### Build system

- Wtyczka używa Webpack do kompilacji bloków
- Pliki źródłowe w `src/`
- Skompilowane w `build/`

### Struktura bloków

Każdy blok zawiera:
- `block.json` - metadata bloku
- `index.js` - kod edytora (Gutenberg)
- `index.css` - style edytora
- `style-index.css` - style frontendu
- `render.php` (opcjonalnie) - renderowanie PHP
- `frontend.js` (opcjonalnie) - interakcje JS
- `view.js` (opcjonalnie) - View Script API

---

## CODE QUALITY TOOLS (motyw church)

### PHPStan - Static Analysis (Level 8)

**Konfiguracja:** `phpstan.neon` (poziom 8 - najwyższa strict mode)
**WordPress extension:** `szepeviktor/phpstan-wordpress` (automatyczna obsługa funkcji WP)

**Analizowane pliki (rozszerzone 2026-02-20):**
- `App/` - klasy PHP (PSR-4)
- `functions.php`, `header.php`, `footer.php`, `index.php`
- `front-page.php`, `archive.php`, `page.php`, `single.php`
- `template-parts/`, `patterns/`

**Ignorowane błędy:**
- `missingType.iterableValue` - ogólne typy tablic
- `get_field()` - brak stubów ACF (tylko single.php)
- `reportUnmatchedIgnoredErrors: false` - zapobiega błędom z nieaktywnymi ignore patterns

**Uruchomienie:**
```bash
cd wp-content/themes/church
composer phpstan  # Analiza kodu
```

**Historia:**
- **2026-01-05:** Pierwsza konfiguracja (tylko App/) - 30→0 błędów
- **2026-02-20:** Rozszerzenie na wszystkie pliki PHP - 12→0 błędów

**Główne poprawki (szablony 2026-02-20):**
1. `header.php` - inicjalizacja zmiennych logo, `container => ''` zamiast `false`, `(string)` cast dla wymiarów
2. `index.php` - null check dla `get_post()`
3. `content-posts.php` - `(int)` cast dla `get_post_thumbnail_id()`, `is_array()` check dla metadata
4. `single.php` - Yoda condition
5. `_e()` → `esc_html_e()`/`esc_attr_e()` (bezpieczne escape)

### PHPCS - Coding Standards (WordPress)

**Konfiguracja:** `phpcs.xml` (WordPress Coding Standards + PSR-4)
**Standard:** WordPress Coding Standards (`wp-coding-standards/wpcs` 3.0+)

**Sprawdzane pliki (rozszerzone 2026-02-20):**
- `App/` - klasy PHP
- Wszystkie szablony: `functions.php`, `header.php`, `footer.php`, `index.php`, `front-page.php`, `archive.php`, `page.php`, `single.php`
- `template-parts/`, `patterns/`

**Customizacje w phpcs.xml:**
- ✅ Zezwolenie na `[]` syntax (nowoczesny PHP)
- ✅ Zezwolenie na `camelCase` dla metod (OOP style)
- ✅ Wykluczenie `WordPress.Files.FileName.*` (PSR-4 wymaga PascalCase.php)
- ✅ Wyłączenie `Squiz.Commenting.FileComment` (opcjonalne w PSR-4)
- ✅ Wyłączenie `Squiz.PHP.CommentedOutCode` (TODO sections)

**Uruchomienie:**
```bash
cd wp-content/themes/church
composer phpcs   # Sprawdzenie
composer phpcbf  # Auto-fix
composer check   # PHPStan + PHPCS razem
```

**Historia:**
- **2026-01-05:** Pierwsza konfiguracja (tylko App/) - ~50→0 błędów
- **2026-02-20:** Rozszerzenie na wszystkie pliki - 339 auto-fixed + ~20 manualnych → 0 błędów

**Główne poprawki manualne (szablony 2026-02-20):**
1. `_e()` → `esc_html_e()`/`esc_attr_e()` (WordPress escape functions)
2. Yoda conditions (`'value' === $var`)
3. `$link` → `$pagination_item` (unikanie override globalnych WordPress)
4. `phpcs:ignore` dla DOM properties (`$firstChild` w GroupLinkSupport.php)
5. `phpcs:ignore` dla `error_log()` (właściwe użycie w WP_DEBUG)
6. `phpcs:disable`/`phpcs:enable` dla multi-line printf w render.php

### Composer Scripts

```bash
composer phpstan  # PHPStan analysis
composer phpcs    # PHPCS check
composer phpcbf   # PHPCS auto-fix
composer check    # Both PHPStan + PHPCS
```

**Status kodu motywu (2026-02-20):** ✅ 0 błędów PHPStan Level 8, 0 błędów PHPCS WordPress Standards

---

## ZNANE PROBLEMY I TODO

### Masonry - USUNIĘTY (2026-08-01)

Masonry był ładowany leniwie z `src/frontend.ts` na stronach z kontenerem
`.news` (lista wpisów). Usunięty razem ze źródłem, chunkami, zależnościami npm
(`masonry-layout`, `imagesloaded`) i deklaracjami typów, bo w serwisie nie ma
ani jednego wpisu, a układ nie był nigdzie używany.

**Konsekwencja do zapamiętania:** `.news` nie ma w CSS żadnego układu
kolumnowego — karty są `width: 100%`. Gdy w Fazie 4 pojawią się aktualności,
lista wyświetli się jako jedna kolumna, dopóki nie doda się siatki (wystarczy
`display: grid` z `repeat(auto-fill, minmax(…))` — bez JavaScriptu).

### Logo.js

`assets/js/logo.js` to plik statyczny (nie ma wejścia w webpacku), ładowany
przez `App/Admin/LogoSettings.php` z wersjonowaniem po `filemtime()`. Działa —
wcześniejsza notatka o „złej lokalizacji" dotyczyła starego motywu.

---

## DO ZAPAMIĘTANIA

1. **Przed każdą zmianą w kodzie - pytaj o pozwolenie!**
2. **Komentarze w kodzie po angielsku, komunikacja po polsku**
3. **Sprawdź plik INSTRUCTIONS.md przed pracą**
4. **Aktualizuj ten plik gdy dodajesz nowe funkcje**

---

**Ostatnia aktualizacja:** 2026-02-20
**Autor dokumentacji:** Claude (AI Assistant)
**Sesja:** PHPStan + PHPCS dla wtyczki i rozszerzenie na wszystkie pliki motywu
