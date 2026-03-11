# Dokumentacja starego motywu: html5blank-stable

## Informacje ogólne

- **Nazwa motywu:** html5blank-stable
- **Baza:** HTML5 Blank (Todd Motto) - mocno customizowany
- **Projekt:** Kościół Zielonoświątkowy Zbór w Mielcu
- **Adres:** ul. Przemysłowa 2, 39-300 Mielec
- **Domena produkcyjna:** kzmielec.pl
- **Font główny:** Cinzel (Regular, Bold, Black + Decorative) - pliki OTF w `fonts/`
- **Styl CSS:** kompilowany z SCSS (`style.scss` -> `style.css` + source map)
- **jQuery:** tak, używany w nawigacji, collapse, smooth scroll

---

## Struktura plików motywu

```
html5blank-stable/
├── style.css              # Główny CSS (skompilowany z SCSS)
├── style.scss             # Źródło SCSS
├── style.css.map          # Source map
├── _mixins.scss           # SCSS mixiny
├── normalize.css          # Reset CSS
├── normalize.min.css
├── functions.php          # Funkcje motywu
├── header.php             # Nagłówek + nawigacja
├── footer.php             # Stopka + social media
├── index.php              # Strona główna (one-page z sekcjami)
├── page.php               # Szablon domyślny strony
├── single.php             # Pojedynczy post
├── loop.php               # Pętla postów
├── sidebar.php            # Sidebar
├── search.php             # Wyniki wyszukiwania
├── searchform.php         # Formularz wyszukiwania
├── 404.php                # Strona 404
├── archive.php            # Archiwum
├── author.php             # Strona autora
├── category.php           # Kategoria
├── tag.php                # Tag
├── comments.php           # Komentarze
├── pagination.php         # Paginacja
├── screenshot.png         # Zrzut ekranu motywu
├── js/
│   ├── scripts.js         # Główny JS (hamburger, sticky menu, smooth scroll)
│   └── lib/
│       ├── main_head.js   # Skrypty w <head> (picturefill polyfill)
│       ├── main_footer.js # Skrypty w footer (collapse, Google Maps, picturefill)
│       ├── conditionizr-4.3.0.min.js
│       └── modernizr-2.7.1.min.js (wyłączony)
├── img/                   # Obrazki motywu
│   ├── logo.png / logo.svg
│   ├── 1.jpg, 1m.jpg, 1s.jpg  # Banner (responsive)
│   ├── 3.png              # Overlay na banner ("Bliscy Boga, Sobie, Innym")
│   ├── stopka-f/i/y/fl.png    # Ikony social media (FB, IG, YT, Flickr)
│   ├── strzalki/          # Strzałki nawigacyjne między sekcjami
│   ├── wiara/             # Obrazki sekcji "W co wierzymy"
│   ├── wizyty/            # Obrazki sekcji "Zaplanuj wizytę"
│   └── historia/          # Obrazki historii zboru
├── fonts/                 # Cinzel (6 wariantów OTF)
├── dokumenty/             # Dokumenty do pobrania
├── material/              # Materiały
└── languages/             # Lokalizacja
```

---

## Szablony stron (Page Templates)

| Plik | Opis |
|------|------|
| `page-zaplanuj-wizyte.php` | Informacje o nabożeństwach i spotkaniach |
| `page-w-co-wierzymy.php` | Wyznanie wiary |
| `page-misja.php` | Misja zboru |
| `page-wizja.php` | Wizja zboru |
| `page-wartosci.php` | Wartości zboru |
| `page-historia.php` | Historia kościoła |
| `page-historia-zboru-w-mielcu.php` | Szczegółowa historia zboru |
| `page-roznica-wyznan.php` | Porównanie wyznań (duża tabela grid) |
| `page-prawo.php` | Prawo kościelne |
| `page-rodo.php` | Polityka RODO |
| `page-w-sprawie-chrztu-wiary-i-blogoslawienstwa-dzieci.php` | Stanowisko ws. chrztu |
| `page-w-sprawie-malzenstwa-rozwodu-powtornego-malzenstwa-oraz-planowania-rodziny.php` | Stanowisko ws. małżeństwa |
| `page-w-sprawie-sluzby-kobiet-w-kosciele.php` | Stanowisko ws. służby kobiet |
| `page-w-sprawie-sluzby-uwolnienia.php` | Stanowisko ws. uwolnienia |
| `page-w-sprawie-stosunku-do-organizacji-parakoscielnych.php` | Stanowisko ws. organizacji |
| `page-w-sprawie-stosunku-do-zjawisk-towarzyszacych-duchowemu-ozywieniu.php` | Stanowisko ws. przebudzenia |
| `page-w-sprawie-tzw-ruchu-wstawienniczego.php` | Stanowisko ws. ruchu wstawienniczego |
| `page-w-sprawie-wieczerzy-panskiej.php` | Stanowisko ws. Wieczerzy Pańskiej |
| `template-demo.php` | Demo template (z HTML5 Blank) |

---

## Strona główna (index.php) - struktura one-page

Strona główna to single-page z 4 sekcjami połączonymi strzałkami (scroll down):

1. **Banner** - zdjęcie góry Cyranowskiej + overlay "Bliscy Boga, Sobie, Innym"
2. **#one - Aktualności** - iframe Facebook (fan page Kzmielec) + Widget Area 1
3. **#two - Zaplanuj wizytę** - kafelki ze spotkaniami (nabożeństwa nd. 10:30, mała kawka, studium piątek 18:00)
4. **#three - W co i jak wierzymy** - 8 kafelków (wiara, misja, wizja, wartości, historia, różnice wyznań, prawo, RODO)
5. **#four - Znajdź nas** - adres, kontakt, Google Maps (marker na współrzędnych 50.2994, 21.4481)

Nawigacja między sekcjami: czarne kółka ze strzałkami.

---

## Nawigacja (header.php)

- **Logo:** `img/logo.png` z linkiem do strony głównej
- **Menu główne:** 4 pozycje (hardcoded, nie z WP menu):
  - Aktualności (#one)
  - Zaplanuj wizytę (#two)
  - W co i jak wierzymy (#three)
  - Znajdź nas (#four)
- **Menu sticky:** osobny div `.fixed` z klasą `.is-fixed` (pojawia się po scrollu > 240px na desktop)
- **Hamburger:** na mobile (< 800px) - menu stałe na górze (`.fix`)
- Zarejestrowane WP menu: `header-menu`, `sidebar-menu`, `extra-menu` (ale nawigacja główna jest hardcoded)

---

## Stopka (footer.php)

- Copyright z rokiem dynamicznym
- Social media (ikony PNG): Facebook, Instagram, YouTube, Flickr
- Linki:
  - FB: facebook.com/Kzmielec/
  - IG: instagram.com/zbor_w_mielcu/
  - YT: youtube.com/channel/UCJfwXmZZpWmc2YoKZJRXAfg
  - Flickr: flickr.com/photos/126772098@N08/albums

---

## JavaScript - kluczowe funkcjonalności

### scripts.js (header)
- **Hamburger menu** - toggle klasy `.is-active` i `.activ` na `.nav`
- **Sticky menu** - na scroll > 240px (desktop > 800px) dodaje `.is-fixed`
- **Mobile fix** - na < 800px menu dostaje klasę `.fix` (position: fixed)
- **Smooth scroll** - animowany scroll do anchor linków (700ms, swing)

### main_footer.js
- **Collapse/accordion** - klik na `.collapse__head` toggle `.collapse__body` (slideToggle)
- **Google Maps** - inicjalizacja mapy z custom stylem (szary, minimalistyczny)
  - Marker: Kościół Zielonoświątkowy, współrzędne: 50.2994, 21.4481
  - API key: `AIzaSyCgYY5EXm6tdyIf2ngkCSH4rpqbbvdVrWo`
- **Picturefill** - polyfill dla elementu `<picture>` (responsive images)

### main_head.js
- Picturefill polyfill (powtórzony)

---

## CSS - kluczowe klasy i breakpointy

### Breakpointy (mobile-first approach NIE - desktop-first):
- `1400px` - wrapper 80%, mniejsze logo
- `1300px` - wrapper 85%
- `1024px` - footer centrowany
- `800px` - wrapper 100%, hamburger menu, banner margin-top, kafelki 50%
- `480px` - kafelki 100%, banner overlay ukryty, mniejsze logo

### Główne klasy CSS:
- `.wrapper` - kontener 60% szerokości
- `.header`, `.menu`, `.logo`, `.nav` - nawigacja
- `.fixed`, `.is-fixed` - sticky menu
- `.banner`, `.banner_image` - baner główny
- `.section-head` - nagłówki sekcji (z linią przez środek)
- `.vb`, `.vb__item` - kafelki (wizyty, wiara)
- `.vb__image`, `.vb__image--one`, `.vb__image--two` - obrazki z overlay efektem hover
- `.black-circle` - okrągły przycisk przewijania
- `.collapse`, `.collapse__head`, `.collapse__body` - accordion
- `.find` - sekcja "Znajdź nas"
- `.footer`, `.footer__social` - stopka
- `.sub-text` - bloki tekstowe na podstronach
- `.grid-wraper-one` - CSS Grid do tabeli porównania wyznań (5 kolumn desktop, 1 mobile)

---

## functions.php - rejestrowane zasoby

### Skrypty (wp_enqueue):
- `conditionizr` - conditionizr-4.3.0.min.js
- `html5blankscripts` - js/scripts.js (zależy od jQuery)
- `main_head` - js/lib/main_head.js
- `main_footer` - js/lib/main_footer.js (w footer)
- `fb` - Facebook SDK (pl_PL)
- `map_js` - Google Maps API z callbackiem `initMap`

### Style:
- `normalize` - normalize.min.css
- `html5blank` - style.css

### Rozmiary obrazków:
- large: 700px
- medium: 250px
- small: 120px
- custom-size: 700x200px

### Widget Areas:
- Widget Area 1 (widget-area-1) - używany na stronie głównej w sekcji Aktualności
- Widget Area 2 (widget-area-2) - niezdefiniowane użycie

### Custom Post Type:
- `html5-blank` - domyślny z HTML5 Blank (nieużywany w treści)

---

## Pluginy (zainstalowane na produkcji)

| Plugin | Status | Uwagi |
|--------|--------|-------|
| Wordfence | aktywny | WAF wyłączony lokalnie (.user.ini) |
| WP Super Cache | aktywny | wyłączony lokalnie (wp-config.php) |
| Yoast SEO | aktywny | tabele wp_yoast_* w bazie |
| Smash Balloon Instagram Feed | aktywny | tabele wp_sbi_* w bazie |

---

## Dane kontaktowe (hardcoded w index.php)

- **Adres:** ul. Przemysłowa 2, 39-300 Mielec
- **Telefon:** 669 189 992 (pastor Dariusz R. Hapoń) - nie odczytuje SMS
- **NIP:** 817-18-40-461
- **Email:** zbor@kzmielec.pl
- **Konto bankowe:** 63 8642 1168 2016 6812 9206 0001
- **Google Analytics:** UA-153597386-1 (Universal Analytics - przestarzały)

---

## Znane problemy i uwagi

1. **Treść hardcoded** - nawigacja, dane kontaktowe, social media linki są wpisane bezpośrednio w szablonach PHP (nie z panelu WP)
2. **Brak child theme** - customizacje bezpośrednio w motywie bazowym
3. **jQuery dependency** - cała interaktywność oparta na jQuery
4. **Google Analytics UA** - stary Universal Analytics (Google wyłączył UA w lipcu 2023)
5. **Google Maps API key** - hardcoded w functions.php
6. **Picturefill polyfill** - załadowany 2x (main_head.js i main_footer.js) - zbędne w nowoczesnych przeglądarkach
7. **Facebook iframe** - hardcoded iframe z FB page plugin
8. **Brak responsywnych obrazków w content** - responsive images tylko na bannerze głównym
9. **CSS w jednym pliku** - brak modularności, cały CSS w jednym style.scss
10. **Zakomentowany kod JS** - dużo zakomentowanego kodu w scripts.js i main_footer.js