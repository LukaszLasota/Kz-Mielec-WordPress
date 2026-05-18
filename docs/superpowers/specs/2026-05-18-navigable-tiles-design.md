# Design — Navigable Tiles (Meetings + Beliefs)

**Data:** 2026-05-18
**Autor:** Łukasz Lasota + Claude
**Status:** Approved by user, ready for implementation plan

---

## Cel

Zastąpić **dwie hardcoded sekcje** ze starej produkcji `kzmielec.pl` dynamicznymi blokami zarządzanymi z poziomu WP Admin, zachowując dotychczasowy design i zapewniając **jedno źródło prawdy** per dziedzina danych.

**Dwie równoległe potrzeby:**

1. **Sekcja "Zaplanuj wizytę"** — kafelki spotkań na stronie głównej (3 elementy) oraz dedykowana podstrona `/zaplanuj-wizyte/` z pełnymi opisami każdego spotkania.
2. **Sekcja "W co i jak wierzymy"** — kafelki tematów wiary na stronie głównej (8 elementów) oraz 8 dedykowanych podstron (`/misja/`, `/wizja/` itd.), na których pod treścią ponownie pojawiają się 8 kafelków nawigacji wiary z oznaczeniem "tu jesteś" (current page tile bez hover image).

**Wymagania od użytkownika:**
- Dodanie nowego spotkania → pojawia się automatycznie wszędzie (homepage + archive)
- Dodanie nowej strony wiary → pojawia się automatycznie wszędzie (homepage + 8 podstron wiary)
- Brak duplikacji treści — jedno miejsce edycji per element
- Zgodność z WCAG 2.1 AA
- Zgodność z PHPStan L8 i PHPCS WordPress Standards
- Zachowanie URL-i SEO dla podstron wiary (`/misja/`, `/wizja/` itd.)
- Wsparcie dla przyszłej integracji z Polylang i live search

---

## Decyzje architektoniczne

### Data sources (różne dla każdej domeny — z uzasadnieniem)

**Meetings (Spotkania) → Custom Post Type**
- Plugin: `custom-posts` (z `CptBuilder`)
- Slug archive: `zaplanuj-wizyte` → `/zaplanuj-wizyte/`
- Single posts: `/meetings/{slug}/`
- Powód: spotkania to "wydarzenia", można ich być wiele, dynamiczne dodawanie typów

**Beliefs (Wiara) → Pages WP + Options Page**
- 8 zwykłych Pages WP — zachowuje istniejące URL-e SEO (`/misja/`, `/wizja/` itd.)
- `BeliefSettings` (subpage `ThemeSettingsPage`) — multi-select pages + drag&drop kolejność
- Każda strona ma przypisany template "Strona wiary" w Page Attributes
- Powód: SEO (URL-e nie mogą się zmienić), strony są strukturalnie samodzielne, mają własną treść Gutenberg

### Display layer (wspólny dla obu)

**Jeden block** `custom-block-package/navigable-tiles` z atrybutem `dataSource`:
- `dataSource: "meetings"` → query CPT meetings
- `dataSource: "beliefs"` → resolve via BeliefSettings → get Pages

**Powód wspólnego bloku:** HTML kafelka jest identyczny w obu przypadkach (image + hover image + title + optional meta). Tylko źródło danych się różni. Jeden block = DRY, jeden cache, jeden styl.

### Templates motywu

- `page-belief.php` (Template Name: "Strona wiary") — auto-render layoutu podstron wiary
- `archive-meetings.php` — auto-render archive CPT meetings z pełnymi opisami

### Brak ACF

Zgodnie z konwencją projektu (BeliefSettings, ContactSettings w migration-plan). Używamy:
- `register_meta()` dla custom fields
- `add_meta_box()` dla edytora UI
- Natywne WP Options API

---

## Data model

### CPT `meetings`

Rejestrowany przez `CptBuilder('meetings', $labels, 5, 'zaplanuj-wizyte')` w pluginie `custom-posts`.

| Pole | Klucz | Typ | Default | Opis |
|------|-------|-----|---------|------|
| Tytuł | wbudowane | string | — | np. "Nabożeństwo Główne" |
| Treść (Gutenberg) | wbudowane | html | — | pełny opis dla archive page |
| Featured image | wbudowane | int | — | bazowy obraz kafelka (`.vb__image--one`) |
| Hover image | `_meeting_hover_image` | int (attachment_id) | 0 | opcjonalny obraz na hover (`.vb__image--two`) |
| Dzień i godzina | `_meeting_day_hour` | string | `''` | "Niedziela 10:30" — wyświetlane tylko na kafelku homepage |
| Miejsce | `_meeting_place` | string | `''` | "ul. Dąbrowskiego 1a" |
| Anchor ID | `_meeting_anchor` | string | `''` | "10" → `/zaplanuj-wizyte/#10` |
| Kolejność | `menu_order` (wbudowane) | int | 0 | sortowanie ASC |

Wszystkie meta fields rejestrowane z `register_meta()` + `show_in_rest: true`.

### Pages (Wiara)

8 zwykłych WP Pages: `/w-co-wierzymy/`, `/misja/`, `/wizja/`, `/wartosci/`, `/historia/`, `/roznica-wyznan/`, `/prawo/`, `/rodo/`.

| Pole | Klucz | Typ | Default | Opis |
|------|-------|-----|---------|------|
| Tytuł | wbudowane | string | — | "Misja" |
| Treść (Gutenberg) | wbudowane | html | — | dowolne bloki (paragraphs, accordions, etc.) |
| Featured image | wbudowane | int | — | bazowa żółta ikona |
| Hover image | `_belief_hover_image` | int (attachment_id) | 0 | hover swap image |
| Template | wbudowane page_template | string | — | musi być `page-belief.php` |

### Options — BeliefSettings

`wp_options['kzmielec_belief_pages']` = array of page IDs in display order.

Edytowane przez `Kzmielec\Admin\BeliefSettings` — subpage `ThemeSettingsPage`.

UI:
- Lista wybranych stron z drag&drop reorder (Sortable.js)
- Search/autocomplete dodawania nowych stron z listy wszystkich Pages
- Save → `update_option()`

---

## Block `navigable-tiles`

### Lokalizacja
`wp-content/plugins/custom-block-package/src/blocks/navigable-tiles/`

### block.json

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "custom-block-package/navigable-tiles",
  "title": "Kafelki nawigacyjne",
  "category": "custom-blocks-from-scratch",
  "icon": "grid-view",
  "description": "Kafelki nawigacyjne z CPT meetings lub stron wiary.",
  "textdomain": "custom-block-package",
  "supports": {
    "anchor": true,
    "align": ["wide", "full"],
    "spacing": { "margin": true, "padding": true }
  },
  "attributes": {
    "anchor": { "type": "string" },
    "dataSource": {
      "type": "string",
      "default": "beliefs",
      "enum": ["meetings", "beliefs"]
    },
    "columns": { "type": "number", "default": 4 },
    "showDayHour": { "type": "boolean", "default": false },
    "highlightCurrent": { "type": "boolean", "default": true },
    "sectionTitle": { "type": "string", "default": "" }
  },
  "editorScript": "file:./index.js",
  "editorStyle": "file:./index.css",
  "viewStyle": "file:./style-index.css",
  "render": "file:./render.php"
}
```

### Output HTML (zgodny z WCAG 2.1 AA)

```html
<nav class="wp-block-custom-block-package-navigable-tiles has-source-beliefs"
     aria-label="W co i jak wierzymy">

  <h2 class="navigable-tiles__heading">W co i jak wierzymy</h2>

  <ul class="navigable-tiles__grid" role="list">

    <!-- Standard tile -->
    <li class="navigable-tiles__item">
      <a href="/misja/" class="navigable-tiles__link">
        <span class="navigable-tiles__image" aria-hidden="true">
          <img class="navigable-tiles__image--one" src="..." alt="" loading="lazy">
          <span class="navigable-tiles__image--black"></span>
          <img class="navigable-tiles__image--two" src="..." alt="" loading="lazy">
        </span>
        <span class="navigable-tiles__title">Misja</span>
      </a>
    </li>

    <!-- Current page tile (you are here) -->
    <li class="navigable-tiles__item is-current">
      <a href="/misja/" class="navigable-tiles__link" aria-current="page">
        <span class="navigable-tiles__image" aria-hidden="true">
          <img class="navigable-tiles__image--one" src="..." alt="" loading="lazy">
          <span class="navigable-tiles__image--black"></span>
          <!-- Brak image--two -->
        </span>
        <span class="navigable-tiles__title">
          Misja
          <span class="screen-reader-text"> (aktualna strona)</span>
        </span>
      </a>
    </li>

  </ul>
</nav>
```

### Architektura kodu

**Service class:** `app/Services/NavigableTilesService.php`

```php
namespace CustomBlockPackage\Services;

class NavigableTilesService {
    /** @return array<int, array{id: int, title: string, link: string, image_base: string, image_hover: string, day_hour: string, anchor: string}> */
    public static function get_meetings(): array;

    /** @return array<int, array{page_id: int, title: string, link: string, image_base: string, image_hover: string}> */
    public static function get_beliefs(): array;
}
```

**Render router:** `render.php` przekazuje `dataSource` do servicu, dostaje znormalizowaną tablicę, renderuje.

**Cache:** `BlockCache::NAVIGABLE_TILES_PREFIX` z TTL 2h. Invalidacja w `index.php` pluginu:
- `save_post_meetings` → flush
- `save_post_page` → flush
- `update_option_kzmielec_belief_pages` → flush

### Editor UI

`InspectorControls`:
- `SelectControl` dataSource: "Spotkania" / "Wiara"
- `RangeControl` columns: 1-6
- `ToggleControl` showDayHour (zaznaczone tylko dla meetings)
- `ToggleControl` highlightCurrent
- `TextControl` sectionTitle (opcjonalne — H2 nad gridem)

`<ServerSideRender>` jako preview — pokazuje rzeczywisty HTML z bazy.

---

## Templates motywu

### `page-belief.php`

Template Name: "Strona wiary". Przypisywany w Page Attributes każdej z 8 stron wiary.

Renderuje (w kolejności):
1. Get header
2. `<h2 class="wp-block-heading is-style-section-line">W co i jak wierzymy</h2>`
3. Hero tile (featured image + hover image + title bieżącej strony)
4. Content (`the_content()`)
5. Scroll arrow block (do anchor `#belief-nav`)
6. `<div id="belief-nav">` z blokiem `navigable-tiles dataSource=beliefs highlightCurrent=true`
7. Get footer

### `archive-meetings.php`

Auto-renderowany przez WP dla CPT archive `/zaplanuj-wizyte/`.

Renderuje (w pętli `WP_Query` meetings posortowane po `menu_order`):
1. Get header
2. Section heading "Zaplanuj wizytę"
3. Dla każdego meetings:
   - `<article id="{anchor}">` z hero tile + content
   - Scroll arrow do następnego (jeśli nie ostatni)
4. Final scroll arrow back to top
5. Get footer

### Webpack pattern styles

- `webpack/src/patterns/page-belief/style.scss`
- `webpack/src/patterns/archive-meetings/style.scss`

Auto-loadowane przez istniejący `PatternAssets.php` (skanuje className na elementach).

---

## Admin UI (motyw)

### `BeliefSettings` (subpage ThemeSettingsPage)

`App/Admin/BeliefSettings.php`:
- Implementuje `ActionHookInterface`
- Hook: `admin_menu` (sub-submenu istniejącego ThemeSettingsPage)
- Form HTML: lista wybranych stron + autocomplete select
- JS: Sortable.js dla drag&drop
- Save: nonce + `update_option('kzmielec_belief_pages', ...)`
- Capability: `manage_options`
- i18n: textdomain `kzmielec`

### `BeliefPageMeta` (meta box dla stron wiary)

`App/Admin/BeliefPageMeta.php`:
- `register_meta('_belief_hover_image', ...)` z `show_in_rest: true`
- `add_meta_box('belief_page_meta', 'Strona wiary', ...)`
- Pokazywany **tylko** dla stron z template `page-belief.php`
- UI: MediaUpload (PHP + minimal JS)
- Save w `save_post` hook z nonce + capability check

### `MeetingMeta` (meta box dla CPT meetings)

`app/Admin/MeetingMeta.php` (w pluginie):
- `register_meta()` dla wszystkich kluczy: `_meeting_hover_image`, `_meeting_day_hour`, `_meeting_place`, `_meeting_anchor`
- `add_meta_box('meeting_details', 'Szczegóły spotkania', ...)`
- Pokazywany dla `post_type=meetings`
- UI: text inputs + MediaUpload

---

## Cache strategy

```
BlockCache::NAVIGABLE_TILES_PREFIX = 'cbp_navigable_tiles_v1_'

Key per attributes:
  meetings_grid_v1_{md5(attrs)}  → dane z CPT
  beliefs_grid_v1_{md5(attrs)}   → dane z option + pages
```

**Invalidacja:**

| Hook | Akcja |
|------|-------|
| `save_post_meetings` | flush NAVIGABLE_TILES_PREFIX |
| `save_post_page` | flush NAVIGABLE_TILES_PREFIX (każdy page save) |
| `update_option_kzmielec_belief_pages` | flush NAVIGABLE_TILES_PREFIX |
| `updated_postmeta` (dla `_meeting_*` lub `_belief_*`) | flush NAVIGABLE_TILES_PREFIX |

TTL: 2h (`HOUR_IN_SECONDS * 2`).

---

## Out of scope (do osobnych iteracji)

Te tematy są **zaplanowane jako kompatybilne** z tą architekturą, ale **nie są w tej implementacji**:
- **Live search** — wymaga osobnego REST endpoint + view block + JS typeahead
- **Polylang** — kompatybilność via `pll_get_post` wrappers w service (zob. niżej), pełna integracja po aktywacji plugin
- **Custom search results grouping** (wiara vs reszta) — wymaga modyfikacji `search.php` motywu
- **Filtrowanie po taxonomii** (np. "Tylko strony wiary" checkbox w search) — wymagałoby dodania taxonomy
- **Każda strona wiary jako CPT zamiast Page** — rozważone, odrzucone (SEO URL-e priorytetem)

## Polylang ready

Service `NavigableTilesService` używa wrapperów:

```php
if ( function_exists( 'pll_get_post' ) ) {
    $page_id = pll_get_post( $original_id ) ?: $original_id;
}
```

`BeliefSettings` zapisuje ID-ki w jednym języku — Polylang resolve'uje na bieżący język użytkownika.

**Działanie out-of-the-box** po aktywacji Polylang. Brak konieczności duplikowania ustawień per język.

---

## WCAG 2.1 AA compliance

| Wymóg | Implementacja |
|-------|---------------|
| 1.1.1 Non-text Content | `alt=""` na obrazach dekoracyjnych, `aria-hidden="true"` na image wrapper |
| 1.3.1 Info and Relationships | `<nav>` + `aria-label`, `<ul>/<li>`, `<h2>` |
| 1.4.3 Contrast (Minimum) | Tekst min 4.5:1 (czarny na białym) |
| 1.4.11 Non-text Contrast | Focus ring 3px solid z 3:1 |
| 2.1.1 Keyboard | Natywny `<a>` focusable, brak custom JS interakcji |
| 2.4.7 Focus Visible | `:focus-visible` outline 3px + offset |
| 2.5.5 Target Size | Kafelki min 88x88px |
| 3.2.1/3.2.2 Predictable | Klik → przejście. Hover swap nie zmienia layoutu |
| 4.1.2 Name, Role, Value | `aria-current="page"` + screen-reader-text "(aktualna strona)" |

**Reduced motion:** `@media (prefers-reduced-motion: reduce)` resetuje transitions.

**Hover = focus:** wszystkie efekty hover replikowane na `:focus-visible`.

**Current page multi-sygnał:** ARIA + screen reader text + visual (brak hover) + CSS modifier.

---

## Standardy kodu

### PHPStan L8
- Strict types: `declare(strict_types=1)`
- Return types na wszystkich metodach
- Array shapes w docblockach (`array{key: type, ...}`)
- Brak `mixed` bez uzasadnienia

### PHPCS WordPress Standards
- Escape all output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`)
- Sanitize all input (`sanitize_text_field`, `absint`, `sanitize_textarea_field`)
- Nonce verification na wszystkich form submissions
- Capability checks (`current_user_can`) przed write operations
- `phpcs:ignore` z uzasadnieniem tylko gdy niezbędne
- Komentarze docblocks pełne (param, return, since)

### i18n
- Wszystkie user-facing strings przez `__()`, `_e()`, `esc_html__()` etc.
- Textdomain: `custom-block-package` (plugin), `kzmielec` (motyw)
- Translators comments dla sprintf placeholders

### Namespace (PSR-4)
- Plugin: `CustomBlockPackage\Admin\*`, `CustomBlockPackage\Services\*`
- Motyw: `Kzmielec\Admin\*`

### WCAG audit
- Lighthouse accessibility check po implementacji
- Manualny test keyboard navigation
- Manualny test screen reader (NVDA / VoiceOver) — sprawdzenie `aria-current` i screen-reader-text

---

## Plan implementacji (kolejność)

### Etap 1: Infrastruktura danych
1. Aktywacja plugin `custom-posts`
2. Modyfikacja CPT slug archive (jeśli potrzebne)
3. Stara strona "Zaplanuj wizytę" (ID 92) → draft
4. `MeetingMeta` w pluginie
5. `BeliefPageMeta` w motywie

### Etap 2: BeliefSettings (motyw)
6. Klasa `BeliefSettings` z UI multi-select + Sortable.js
7. Rejestracja w `Theme.php`
8. Asset: instalacja `sortablejs` przez npm w motywie (`webpack/package.json`), import w nowym entry `webpack/src/admin.ts`, kompilacja przez istniejący webpack motywu, enqueue tylko na stronie ustawień przez `admin_enqueue_scripts` z hook callback

### Etap 3: Service
9. `NavigableTilesService` z metodami `get_meetings()`, `get_beliefs()`
10. Polylang wrappers

### Etap 4: Block `navigable-tiles`
11. block.json, index.js, edit.js, render.php, style.scss, index.scss
12. `BlockCache::NAVIGABLE_TILES_PREFIX` + invalidation hooks

### Etap 5: Templates
13. `page-belief.php` w motywie
14. `archive-meetings.php` w motywie
15. Pattern styles w webpack

### Etap 6: Treść
16. Strony wiary — przypisanie templatu + featured/hover images + BeliefSettings
17. Spotkania — CPT posts (3 na start)

### Etap 7: Bloki w stronach
18. Homepage — `navigable-tiles dataSource=meetings`
19. Homepage — `navigable-tiles dataSource=beliefs`
20. (templates auto-renderują resztę)

### Etap 8: Audyt + commit
21. PHPStan + PHPCS check
22. `/check` audit (WCAG, i18n, security)
23. Browser testing (homepage, /misja/, /zaplanuj-wizyte/)
24. Keyboard nav + screen reader test
25. Commit main + merge develop

---

## Pliki do utworzenia

| Plik | Lokalizacja |
|------|-------------|
| `App/Admin/BeliefSettings.php` | motyw |
| `App/Admin/BeliefPageMeta.php` | motyw |
| `app/Admin/MeetingMeta.php` | plugin |
| `app/Services/NavigableTilesService.php` | plugin |
| `src/blocks/navigable-tiles/block.json` | plugin |
| `src/blocks/navigable-tiles/index.js` | plugin |
| `src/blocks/navigable-tiles/edit.js` | plugin |
| `src/blocks/navigable-tiles/render.php` | plugin |
| `src/blocks/navigable-tiles/style.scss` | plugin |
| `src/blocks/navigable-tiles/index.scss` | plugin |
| `page-belief.php` | motyw |
| `archive-meetings.php` | motyw |
| `webpack/src/patterns/page-belief/style.scss` | motyw |
| `webpack/src/patterns/archive-meetings/style.scss` | motyw |

## Pliki do modyfikacji

| Plik | Zmiana |
|------|--------|
| `App/Theme.php` | dodać BeliefSettings + BeliefPageMeta do `$admin_components` |
| `app/Cache/BlockCache.php` | dodać `NAVIGABLE_TILES_PREFIX` |
| plugin `index.php` | hooks cache invalidation + register MeetingMeta |
| `App/Admin/ThemeSettingsPage.php` (motyw) | dodać submenu "Wiara" |

---

## Weryfikacja end-to-end

### Test 1: Dodanie nowego spotkania
1. WP Admin → Spotkania → Dodaj nowe
2. Wypełnienie wszystkich pól
3. Publikacja
4. Sprawdzenie: kafelek na homepage + opis w `/zaplanuj-wizyte/`

### Test 2: Dodanie nowej strony wiary
1. WP Admin → Strony → Dodaj nową
2. Template "Strona wiary"
3. Featured + hover image, meta
4. BeliefSettings → dodanie do listy
5. Sprawdzenie: kafelek na homepage + kafelek w nawigacji każdej innej strony wiary

### Test 3: You are here
1. Wejście na `/misja/`
2. Sprawdzenie: kafelek "Misja" na dole bez hover image
3. Sprawdzenie z screen reader: "(aktualna strona)"
4. Sprawdzenie ARIA Inspector: `aria-current="page"`

### Test 4: Keyboard navigation
1. Tab przez kafelki
2. Focus ring widoczny
3. Enter na kafelku → przejście na stronę

### Test 5: PHPStan + PHPCS
```bash
ddev exec "cd wp-content/plugins/custom-block-package && ./vendor/bin/phpstan analyse app/ src/blocks/navigable-tiles/"
ddev exec "cd wp-content/plugins/custom-block-package && ./vendor/bin/phpcs --standard=phpcs.xml --extensions=php app/ src/blocks/navigable-tiles/"
```

### Test 6: Cache invalidation
1. Wejść na homepage, sprawdzić kafelki
2. Edytować spotkanie, zmienić tytuł
3. Hard refresh homepage
4. Sprawdzić: nowy tytuł widoczny natychmiast

### Test 7: Polylang (jeśli aktywny)
1. Włączyć Polylang
2. Stworzyć tłumaczenie strony "Misja"
3. Przełączyć język na froncie
4. Sprawdzić: kafelek pokazuje przetłumaczone dane
