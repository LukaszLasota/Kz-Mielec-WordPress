# Migracja kzmielec.pl - Plan i dokumentacja

## 1. Stan obecny

### Strona produkcyjna
- **URL:** https://kzmielec.pl
- **Motyw:** html5blank-stable (customizowany HTML5 Blank, Todd Motto)
- **Dokumentacja starego motywu:** `.claude/theme-old-docs.md`
- **Konfiguracja hostingu/DDEV:** `.claude/project-config.md`

### Problemy starego motywu
- 100% treści hardcoded w szablonach PHP - nic nie da się edytować z panelu WP
- Nawigacja hardcoded (nie z WP menu)
- Dane kontaktowe, social media wpisane w szablonach
- jQuery dependency (hamburger, sticky menu, collapse, smooth scroll)
- Google Analytics UA (przestarzały, wyłączony przez Google)
- Google Maps API key hardcoded
- Picturefill polyfill załadowany 2x (zbędny w nowoczesnych przeglądarkach)
- Brak child theme - customizacje bezpośrednio w motywie bazowym
- CSS w jednym pliku, brak modularności
- Dużo zakomentowanego kodu

---

## 2. Cel migracji

- Cała treść edytowalna z edytora Gutenberg (WordPress admin)
- Zachowanie obecnego designu (font Cinzel, czarno-biała kolorystyka, one-page layout)
- Nowoczesna architektura: OOP PHP, Webpack, SCSS, block patterns
- Bloki Gutenberg dla powtarzających się komponentów
- Łatwa edycja treści przez osoby nietechniczne
- **Zachowanie wszystkich URL-i bez zmian (SEO)**

---

## 3. Źródło nowego motywu i wtyczki

### Motyw "Church" (z projektu emaus)
- **Lokalizacja źródłowa:** `/home/lukasz/projects/emaus/wp-content/themes/church/`
- **Architektura:** OOP PHP z namespace `Church`, PSR-4 (Composer)
- **Build:** Webpack (TypeScript, SCSS), osobne dev/prod configs
- **Szablony:** front-page.php, archive.php, index.php, single.php, page.php, page-hero.php
- **Block patterns:** hello-section-main-page, contact-section
- **Fonty:** Raleway (do zmiany na Cinzel)
- **Kolory:** #008089 teal (do zmiany na czarno-białą)
- **PHP:** 8.0+, WordPress 5.9+
- **Code quality:** PHPStan level 8, PHPCS WordPress Standards

### Wtyczka "Custom Block Package" (z projektu emaus)
- **Lokalizacja źródłowa:** `/home/lukasz/projects/emaus/wp-content/plugins/custom-block-package/`
- **Build:** @wordpress/scripts (wp-scripts)
- **Namespace:** `CustomBlockPackage`, PSR-4 autoloader
- **Cache:** BlockCache class z transientami (30 min TTL)

### Wtyczka "Custom Posts" (z projektu emaus)
- **Lokalizacja źródłowa:** `/home/lukasz/projects/emaus/wp-content/plugins/custom-posts/`
- **Namespace:** `CustomPostsPlugin`, PSR-4 autoloader
- **Architektura:** `CptBuilder` class - reużywalny builder do rejestracji CPT
- **Obecne CPT:** meetings (Spotkania) - do dostosowania dla kzmielec
- **Klasy:** `CptBuilder`, `TaxBuilder`, `RegisterPosts`, `CustomColumns`

### Strona opcji motywu (z projektu emaus - church)
- **Lokalizacja:** `App/Admin/ThemeSettingsPage.php` + subpages
- **Architektura:** OOP PHP, `add_menu_page` / `add_submenu_page`, nonce security, `update_option`
- **Obecne podstrony:** Logo, Masonry
- **Do rozbudowy:** dodać zakładki Wiara, Kontakt, Social Media (bez ACF, natywne WP Options API)

### Powiązania motyw-wtyczka (do naprawienia)
- Wtyczka importuje SCSS mixiny z motywu przez relatywną ścieżkę (`../../../../../themes/church/webpack/src/sass/abstracts/mixins`) - **trzeba skopiować mixiny do wtyczki**
- Motyw pattern `contact-section.php` używa `wp:custom-block-package/section-block`
- `theme.json` definiuje style dla `custom-block-package/accordion-item`
- PHP/JS nie mają bezpośrednich zależności

---

## 4. Analiza struktury strony kzmielec.pl

### Strona główna (one-page, 5 sekcji)

```
STRONA GŁÓWNA
│
├── HEADER
│   ├── Logo (logo.png, link do strony głównej)
│   ├── Nawigacja (4 pozycje: Aktualności, Zaplanuj wizytę, W co wierzymy, Znajdź nas)
│   ├── Hamburger menu (mobile < 800px)
│   └── Sticky menu (desktop, po scroll > 240px)
│
├── BANNER
│   ├── Zdjęcie główne (góra Cyranowska, responsive: 1.jpg / 1s.jpg / 1m.jpg)
│   └── Overlay tekst: "Bliscy Boga, Sobie, Innym" (3.png)
│
├── #one AKTUALNOŚCI
│   ├── Nagłówek sekcji: "Aktualności"
│   └── Instagram Feed (plugin Smash Balloon, 9 postów z @zbor_w_mielcu)
│
├── #two ZAPLANUJ WIZYTĘ
│   ├── Nagłówek sekcji: "Zaplanuj wizytę"
│   └── 3 kółka spotkań (dane z CPT meetings):
│       ├── Nabożeństwa główne - Niedziela 10.30
│       ├── Mała kawka (bez godziny)
│       └── Studium Słowa i modlitwa - Piątek 18.00
│
├── #three W CO I JAK WIERZYMY
│   ├── Nagłówek sekcji: "W co i jak wierzymy"
│   └── 8 kółek (dane z ACF Options Page -> lista stron):
│       ├── W co wierzymy -> /w-co-wierzymy
│       ├── Misja -> /misja
│       ├── Wizja -> /wizja
│       ├── Wartości -> /wartosci
│       ├── Historia -> /historia
│       ├── Różnice wyznań -> /roznica-wyznan
│       ├── Prawo -> /prawo
│       └── RODO -> /rodo
│
├── #four ZNAJDŹ NAS
│   ├── Nagłówek sekcji: "Znajdź nas"
│   ├── Dane kontaktowe (z ACF Options Page)
│   └── Mapa Leaflet (50.2994, 21.4481)
│
└── FOOTER
    ├── Copyright
    └── Social media (z ACF Options Page): Facebook, Instagram, YouTube, Flickr
```

### Podstrony wiary - wspólny szablon

Wszystkie 9 podstron wiary mają **identyczny layout**:

```
PODSTRONA WIARY (np. /misja/)
│
├── HEADER (z motywu)
│
├── Nagłówek "W co i jak wierzymy" (automatycznie z szablonu)
│
├── Ikona strony (kółko z featured image, automatycznie z szablonu)
│
├── TREŚĆ Z GUTENBERG EDITORA
│   ├── (accordiony, tekst, tabele - zależy od strony)
│   └── edytowalne z WP admin
│
├── Separator (czarne kółko ze strzałką, automatycznie z szablonu)
│
├── Nawigacja wiary - 8 kółek (automatycznie z szablonu)
│   └── Bieżąca strona wyszarzona (bez żółtej ikony overlay)
│
└── FOOTER (z motywu)
```

**Elementy dodawane automatycznie przez szablon** (nie trzeba wstawiać w edytorze):
1. Nagłówek "W co i jak wierzymy"
2. Ikona strony (featured image w kółku)
3. Separator ze strzałką
4. Blok `belief-navigation` (8 kółek, bieżąca wyszarzona)

**Jak rozpoznać stronę wiary?** Strona opcji motywu (natywne WP Options, bez ACF) zawiera listę ID stron wiary. Osobny szablon `page-belief.php` (Template Name: Strona wiary) przypisany do tych stron - renderuje elementy automatycznie. Każda strona wiary ma ustawiony ten szablon w edytorze (Page Attributes -> Template).

### Podstrony wiary - szczegóły treści Gutenberg

| Podstrona | Treść w edytorze | Bloki potrzebne |
|-----------|-----------------|-----------------|
| /w-co-wierzymy | 3 wyznania wiary w accordion | Accordion |
| /misja | Tekst z cytatami biblijnymi | Core paragraph |
| /wizja | Tekst z cytatami biblijnymi | Core paragraph |
| /wartosci | 5 sekcji (kultury) z nagłówkami | Core heading + paragraph |
| /historia | Tekst narracyjny z listą pastorów | Core paragraph + list |
| /historia-zboru-w-mielcu | 2 sekcje accordion z fotografiami | Accordion + Core image |
| /roznica-wyznan | Tabele porównawcze w accordion | Accordion + **nowy blok tabeli porównawczej** |
| /prawo | Dokumenty prawne w accordion + linki PDF | Accordion + Core list + Core file |
| /rodo | Prosty tekst 6 punktów | Core paragraph |

### Elementy unikalne (tylko na 1 stronie)

| Element | Strona | Rozwiązanie |
|---------|--------|-------------|
| Tabela porównawcza CSS Grid | /roznica-wyznan | Nowy blok lub core Table z custom CSS |
| Listy prawne zagnieżdżone | /prawo | Core list (nested) |
| Linki do PDF | /prawo | Core file block |
| Zdjęcia portretowe z figcaption | /historia-zboru | Core image z caption |

### Strona /zaplanuj-wizyte/

Zwykła strona WP z blokiem `meeting-list` który pobiera dane z CPT meetings.

```
STRONA /zaplanuj-wizyte/
│
├── HEADER
│
├── Blok meeting-list (PHP render, dane z CPT meetings)
│   ├── Spotkanie #10: Nabożeństwo Główne (kółko + opis)
│   ├── Separator (czarne kółko ze strzałką)
│   ├── Spotkanie #11: Mała Kawka (kółko + opis)
│   ├── Separator
│   └── Spotkanie #12: Studium Słowa (kółko + opis)
│   └── Separator (strzałka do góry -> #10)
│
└── FOOTER
```

URL-e z anchorami zachowane: `/zaplanuj-wizyte/#10`, `#11`, `#12`.

### Stanowiska kościoła

8 stron z prostym tekstem. Zwykłe strony WP z core blokami (paragraph, heading, list). Bez specjalnego szablonu.

---

## 5. Architektura danych

### CPT meetings (spotkania) - Z FRONT-ENDEM

Rejestrowany przez wtyczkę **custom-posts** (skopiowaną z emaus), z użyciem `CptBuilder`:

```php
// W RegisterPosts.php
new CptBuilder('meetings', $labels, 5, 'zaplanuj-wizyte');
// CptBuilder ustawia: publicly_queryable: true, has_archive: 'zaplanuj-wizyte', rewrite: ['slug' => 'meetings']
```

**URL-e:**
```
kzmielec.pl/zaplanuj-wizyte/                        -> archive-meetings.php (lista spotkań)
kzmielec.pl/meetings/nabozenstwo-glowne/            -> single-meetings.php (szczegóły)
kzmielec.pl/meetings/mala-kawka/                     -> single-meetings.php
kzmielec.pl/meetings/studium-slowa/                  -> single-meetings.php
```

**Uwaga:** Stary URL `/zaplanuj-wizyte/` działał jako strona WP. Teraz to archiwum CPT z tym samym slugiem. Istniejąca strona WP "zaplanuj wizyte" (ID 92) musi zostać usunięta lub zmieniona na draft, żeby nie kolidowała z archiwum.

**Pola CPT meetings:**

| Pole | Typ | Przykład |
|------|-----|---------|
| Tytuł | wbudowany WP | "Nabożeństwo Główne" |
| Treść | wbudowany WP editor (Gutenberg) | Pełny opis spotkania |
| Featured image | wbudowany WP | Zdjęcie tła kółka |
| Ikona overlay | custom field (Options Page motywu) lub meta field | Żółty rysunek |
| Dzień i godzina | custom field | "Niedziela 10.30" |
| Kolejność | menu_order (wbudowany) | 1, 2, 3 |

**Szablony w motywie:**
- `archive-meetings.php` - lista wszystkich spotkań (kółko + opis + separator ze strzałką)
- `single-meetings.php` - pojedyncze spotkanie (opcjonalnie, jeśli chcemy osobne strony)

**Draft/Publish:** Kurs Alfa, Nowe Życie itd. jako draft - nie widoczne na archiwum ani w bloku na stronie głównej.

### Strona opcji motywu (bez ACF - natywne WP Options API)

Rozbudowa istniejącej strony opcji z motywu church (`ThemeSettingsPage` + subpages).

```
Ustawienia motywu (WP Admin -> Ustawienia motywu)
│
├── Logo (istniejąca z church - LogoSettings.php)
│   └── Upload logo przez Media Library
│
├── Wiara (NOWA - BeliefSettings.php)
│   └── Wybór stron WP do nawigacji wiary
│       -> Checkbox/multi-select z listą wszystkich stron
│       -> Sortowanie drag & drop (jQuery UI Sortable)
│       -> Zapisywane jako array ID-ków w wp_options
│       -> Blok belief-navigation pobiera stąd listę
│       -> Szablon page-belief.php sprawdza czy bieżąca strona jest na liście
│
├── Kontakt (NOWA - ContactSettings.php)
│   ├── Adres (text)
│   ├── Telefon (text)
│   ├── Email (email)
│   ├── NIP (text)
│   ├── Numer konta (text)
│   └── Koordynaty mapy (lat, lng - text)
│
└── Social Media (NOWA - SocialSettings.php)
    ├── Facebook URL
    ├── Instagram URL
    ├── YouTube URL
    └── Flickr URL
```

**Implementacja:** Każda zakładka to osobna klasa PHP implementująca `ActionHookInterface`, analogicznie do `LogoSettings` i `MasonrySettings`. Dane zapisywane przez `update_option()`, pobierane przez `get_option()`.

### Ikona overlay na stronach wiary

Żółta ikona na kółku (np. gołąb na "W co wierzymy", uścisk dłoni na "Misja") - przechowywana jako **custom meta field** na stronie WP. Bez ACF - natywny `register_meta()` + meta box w edytorze.

| Pole | Typ | Opis |
|------|-----|------|
| `belief_overlay_icon` | attachment ID | Żółta ikona SVG/PNG wyświetlana na kółku |

Każda strona wiary ma featured image (zdjęcie tła kółka) + meta field z żółtą ikoną overlay.

### Strony WP (Pages) - flat, bez hierarchii

Wszystkie strony pozostają równorzędne (post_parent = 0), tak jak teraz.

```
Strona główna (front-page)
Zaplanuj wizytę
W co wierzymy
Misja
Wizja
Wartości
Historia
Historia zboru w Mielcu
Różnice wyznań
Prawo
RODO
W sprawie chrztu wiary...
W sprawie małżeństwa...
W sprawie służby kobiet...
W sprawie służby uwolnienia
W sprawie stosunku do organizacji...
W sprawie zjawisk towarzyszących...
W sprawie ruchu wstawienniczego
W sprawie Wieczerzy Pańskiej
```

**URL-e identyczne jak teraz** - żadnych zmian.

---

## 6. Bloki Gutenberg - plan

### Nowy blok: `circle-cards` (uniwersalny blok kółek)

Jeden elastyczny blok Gutenberg z wyborem źródła danych w sidebarze.
Bazowany na wzorcu istniejącego `meeting-list` (ServerSideRender + render.php).

**Typ:** Dynamic block z PHP server-side render
**Wtyczka:** custom-block-package

#### Atrybuty bloku (block.json)

| Atrybut | Typ | Default | Opis |
|---------|-----|---------|------|
| `dataSource` | string | `"options"` | Źródło: `"options"` (strona opcji), `"meetings"` (CPT), `"posts"` (zwykłe posty) |
| `optionsKey` | string | `"kzmielec_belief_pages"` | Klucz z wp_options (gdy dataSource = "options") |
| `postType` | string | `"meetings"` | Typ postu (gdy dataSource = "meetings" lub "posts") |
| `numberposts` | number | -1 | Limit postów |
| `variant` | string | `"circle"` | Wariant wyświetlania: `"circle"` (kółka z overlay + tytuł + opcjonalny podtytuł) |
| `showOverlay` | boolean | true | Pokazuj czarny overlay + żółtą ikonę |
| `showSubtitle` | boolean | false | Pokazuj podtytuł (np. godzinę) |
| `linkToArchive` | string | `""` | Prefix linku z anchorem (np. "/zaplanuj-wizyte/") |
| `disableCurrent` | boolean | true | Wyszarzaj bieżącą stronę |
| `blockTitle` | string | `""` | Tytuł nad blokiem |

#### Sidebar w edytorze (InspectorControls)

```
Panel: Źródło danych
├── SelectControl "Źródło"
│   ├── "Strona opcji motywu" (options) -> pokazuje SelectControl z kluczem opcji
│   ├── "CPT Spotkania" (meetings) -> automatycznie post_type=meetings
│   └── "Posty" (posts) -> pokazuje SelectControl z typem postu
│
Panel: Wyświetlanie
├── ToggleControl "Overlay z ikoną"
├── ToggleControl "Podtytuł (godzina)"
├── ToggleControl "Wyszarzaj bieżącą"
├── TextControl "Link z anchorem" (np. /zaplanuj-wizyte/)
└── TextControl "Tytuł bloku"
```

#### Logika render.php

```php
switch ($attributes['dataSource']) {
    case 'options':
        // Pobierz listę ID stron z wp_options
        $page_ids = get_option($attributes['optionsKey'], []);
        $items = array_map('get_post', $page_ids);
        break;
    case 'meetings':
        // Query CPT meetings
        $items = get_posts([
            'post_type' => 'meetings',
            'numberposts' => $attributes['numberposts'],
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);
        break;
    case 'posts':
        // Query zwykłych postów
        $items = get_posts([
            'post_type' => $attributes['postType'],
            'numberposts' => $attributes['numberposts'],
        ]);
        break;
}

// Renderuj kółka
foreach ($items as $item) {
    $featured = get_the_post_thumbnail_url($item->ID, 'medium');
    $icon_id = get_post_meta($item->ID, 'belief_overlay_icon', true);
    $subtitle = get_post_meta($item->ID, 'day_hour', true);
    $is_current = $attributes['disableCurrent'] && (get_the_ID() === $item->ID);

    render_circle($item, $featured, $icon_id, $is_current, $subtitle, $attributes);
}
```

#### Użycie na stronie głównej

```
Strona główna (front-page.php / edytor Gutenberg)
│
├── Sekcja "Zaplanuj wizytę"
│   └── Blok circle-cards:
│       dataSource: "meetings"
│       variant: "circle"
│       showSubtitle: true
│       linkToArchive: "/zaplanuj-wizyte/"
│
├── Sekcja "W co i jak wierzymy"
│   └── Blok circle-cards:
│       dataSource: "options"
│       optionsKey: "kzmielec_belief_pages"
│       variant: "circle"
│       showOverlay: true
│       disableCurrent: false (strona główna -> wszystkie aktywne)
```

#### Użycie w szablonie page-belief.php

```php
// Na dole szablonu - renderowany z PHP, nie z edytora
// Ten sam render.php co w bloku, ale wywołany bezpośrednio
$attributes = [
    'dataSource' => 'options',
    'optionsKey' => 'kzmielec_belief_pages',
    'variant' => 'circle',
    'showOverlay' => true,
    'disableCurrent' => true, // wyszarz bieżącą stronę
];
// Render nawigacji wiary
```

**Jeden blok, trzy źródła danych.** Pełna elastyczność z edytora.

**Uwaga:** Strona `/zaplanuj-wizyte/` (archiwum CPT) NIE używa tego bloku - ma własny szablon `archive-meetings.php` z pełnym layoutem (kółko + opis + separator ze strzałką). Blok `circle-cards` służy tylko do wyświetlania kółek (strona główna, szablon page-belief.php).

### Istniejące bloki z wtyczki (do wykorzystania)

| Blok | Użycie w kzmielec | Adaptacja |
|------|-------------------|-----------|
| **Section Block** | Kontener sekcji strony głównej | Dodać wariant z nagłówkiem-separatorem |
| **Custom Accordion** | w-co-wierzymy, historia-zboru, roznica-wyznan, prawo | Gotowy, dostosować styl |
| **Accordion Item** | Elementy accordion | Gotowy |
| **Dynamic Images** | Banner strony głównej (responsive hero) | Gotowy |
| **Map Block** | Sekcja "Znajdź nas" (Leaflet) | Zmienić domyślne koordynaty |
| **Image Text** | Podstrony misja/wizja/wartości | Gotowy |
| **PDF Block** | Strona /prawo/ - dokumenty do pobrania | Gotowy |

### Bloki do usunięcia (niepotrzebne)

| Blok | Powód |
|------|-------|
| **Emaus News Slider** | Specyficzny dla emaus |
| **Responsive Image Slider** | Brak slidera na kzmielec |
| **Slider Block** | Brak slidera na kzmielec |

---

## 7. Adaptacja designu

### Zmiany w motywie Church -> kzmielec

| Element | Church (emaus) | kzmielec (docelowo) |
|---------|---------------|---------------------|
| **Font** | Raleway (400, 700) | Cinzel (Regular, Bold, Black) + CinzelDecorative |
| **Kolor główny** | #008089 (teal) | #000000 (czarny) |
| **Kolor tła** | #f9f9f9 | #ffffff (biały) |
| **Kolor akcentu** | #04A4CC | #000000 |
| **Layout strony głównej** | Wielostronicowy | One-page z anchor scrollem |
| **Nawigacja** | Standardowe linki | Anchor linki (#one, #two...) + sticky |
| **Separatory sekcji** | Brak | Czarne kółka ze strzałkami między sekcjami |
| **Nagłówki sekcji** | Standardowe | Uppercase, letter-spacing 150%, linia przez środek |
| **Szerokość wrappera** | CSS custom properties (90/80/70/60%) | 60% desktop, responsive |
| **Logo** | Emaus logo | logo.png kzmielec (krzyż) |

### Pliki do skopiowania ze starego motywu
- `fonts/Cinzel-*.otf` (6 wariantów) -> do SCSS @font-face
- `img/logo.png`, `img/logo.svg`
- `img/strzalki/` (strzałki nawigacyjne)
- `img/wiara/` (obrazki sekcji wiary)
- `img/wizyty/` (obrazki spotkań)
- `img/1.jpg`, `img/1s.jpg`, `img/1m.jpg` (banner)
- `img/3.png` (overlay banner)
- `img/stopka-*.png` (ikony social media)

---

## 8. Plan zadań - kolejność realizacji

### Faza 1: Przygotowanie infrastruktury

1. **Skopiować motyw church** do `wp-content/themes/kzmielec/`
2. **Skopiować wtyczkę custom-block-package** do `wp-content/plugins/custom-block-package/`
3. **Naprawić SCSS dependency** - skopiować shared mixiny do wtyczki
4. **Usunąć niepotrzebne bloki** z wtyczki (emaus-news-slider, responsive-image-slider, slider-block)
5. **Zainicjować git** w katalogu projektu
6. **Zainstalować ACF Pro** (lub SCF jako darmowa alternatywa)

### Faza 2: Adaptacja motywu

7. **Zmienić fonty** - Raleway -> Cinzel (SCSS, theme.json, @font-face)
8. **Zmienić kolorystykę** - teal -> czarno-biała (SCSS variables, theme.json)
9. **Skopiować assety** ze starego motywu (obrazki, logo, ikony)
10. **Dostosować header.php** - nawigacja anchor links, logo, hamburger
11. **Dostosować footer.php** - social media z ACF Options, copyright
12. **Zbudować front-page.php** - one-page layout z 5 sekcjami
13. **Zbudować szablon `page-belief.php`** (Template Name: Strona wiary) - osobny szablon z:
    - Nagłówek "W co i jak wierzymy"
    - Ikona strony (featured image w kółku)
    - Treść z Gutenberg editora (the_content)
    - Separator ze strzałką
    - Blok belief-navigation (8 kółek, bieżąca wyszarzona)
    - Przypisywany ręcznie do każdej strony wiary (Page Attributes -> Template)
14. **Dostosować style sekcji** - nagłówki z linią, separatory, wrapper 60%
15. **Sticky menu** - reimplementacja w czystym JS (bez jQuery)
16. **Smooth scroll** - reimplementacja w CSS (`scroll-behavior: smooth`)

### Faza 3: CPT + ACF + bloki

17. **Skopiować wtyczkę custom-posts** z emaus, dostosować CPT meetings (has_archive: 'zaplanuj-wizyte')
18. **Rozbudować stronę opcji motywu** - nowe zakładki: BeliefSettings, ContactSettings, SocialSettings (natywne WP Options API, bez ACF)
19. **Dodać meta field `belief_overlay_icon`** na stronach WP (register_meta + meta box)
20. **Zrobić blok `circle-cards`** - uniwersalny blok kółek z wyborem źródła (options/meetings/posts)
21. **Zbudować szablon `archive-meetings.php`** - lista spotkań z kółkiem + opis + separator (layout z PHP, nie z bloku)
23. **Dostosować blok Accordion** - styl dla kzmielec
24. **Dostosować blok Map** - domyślne koordynaty Mielec

### Faza 4: Przenoszenie treści

25. **Wypełnić stronę opcji motywu** - lista stron wiary, kontakt, social media
26. **Utworzyć CPT meetings** - 3 spotkania publish (+ draft dla starych: Kurs Alfa, Nowe Życie itd.)
27. **Usunąć/zdraftować starą stronę WP "zaplanuj wizyte"** (ID 92) - archiwum CPT przejmuje URL
27. **Przenieść treść strony głównej** do edytora Gutenberg
28. **Przenieść treści podstron wiary** (9 stron - tylko treść środkowa, reszta z szablonu)
29. **Przenieść stanowiska kościoła** (8 stron z prostym tekstem)
30. **Ustawić menu WordPress**
31. **Skonfigurować Instagram Feed** plugin
32. **Upload obrazków** do Media Library (featured images, ikony overlay)

### Faza 5: Testy i deploy

33. **Testy responsywności** - mobile (480px), tablet (800px), desktop
34. **Testy wydajności** - Lighthouse, lazy loading
35. **Porównanie z produkcją** - wizualne porównanie kzmielec.ddev.site vs kzmielec.pl
36. **Sprawdzenie URL-i** - wszystkie stare URL-e muszą działać
37. **Code quality** - PHPStan, PHPCS
38. **Deploy na produkcję**

---

## 9. Decyzje techniczne

| Decyzja | Wybór | Uzasadnienie |
|---------|-------|-------------|
| **Beliefs: CPT czy strony?** | **Strony WP** | URL-e muszą zostać identyczne (/misja/, /wizja/ itd.), stron jest 8, rzadko się zmieniają |
| **Meetings: CPT czy strony?** | **CPT z front-endem** | Strukturalne dane, draft/publish, archive + single templates |
| **Meetings: URL-e?** | **Archive: `/zaplanuj-wizyte/`, Single: `/meetings/slug/`** | Archive rewrite slug zastępuje starą stronę WP. Stara strona WP (ID 92) do usunięcia |
| **Nawigacja wiary: skąd dane?** | **Strona opcji motywu (natywne WP Options, bez ACF)** | Jedno źródło, drag & drop kolejność, multi-select stron |
| **Nawigacja wiary: jak na podstronach?** | **Osobny szablon `page-belief.php`** | Template przypisany do stron wiary, automatycznie dodaje nagłówek, ikonę, nawigację |
| **Ikona overlay wiary** | **Custom meta field `belief_overlay_icon`** | register_meta + meta box, bez ACF |
| **Bloki w wtyczce czy motywie?** | **Wtyczka** | Bloki = treść, motyw = wygląd |
| **CPT rejestracja** | **Wtyczka custom-posts** (z emaus) | Oddzielna wtyczka z CptBuilder, łatwe dodawanie nowych CPT |
| **Strona opcji** | **W motywie (ThemeSettingsPage + subpages)** | Natywne WP Options API, bez ACF |
| **Stanowiska kościoła** | **Strony WP** | Prosty tekst, rzadko zmieniane, różne layouty |
| **Google Maps czy Leaflet?** | **Leaflet** | Darmowy, bez API key |
| **jQuery?** | **Nie** | Czysty JS, CSS scroll-behavior |
| **Instagram feed?** | **Plugin Smash Balloon** | Już działa |
| **SCSS shared mixins?** | **Skopiowane do wtyczki** | Eliminacja relatywnej ścieżki |
| **Hierarchia stron?** | **Flat (bez parent)** | Strony równorzędne, logika "grupy wiary" w stronie opcji motywu |

---

**Ostatnia aktualizacja:** 2026-03-11
**Autor:** Claude (AI Assistant)
