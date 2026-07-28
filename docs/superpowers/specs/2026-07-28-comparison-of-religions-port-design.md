# Port „Comparison of Religions" do kzmielec — design

**Data:** 2026-07-28
**Cel:** Domknąć ostatnią stronę wiary — `różnica wyznań` (page id 83) — przez
przeniesienie gotowej wtyczki `comparison-of-religions` z projektu `emaus`,
przebudowę bloku i dostosowanie do designu kzmielec. Treść z produkcji
`kzmielec.pl/roznica-wyznan/`.

## Źródło

`~/projects/emaus/wp-content/plugins/comparison-of-religions` (v1.0.0, autor
Łukasz Lasota). Samowystarczalna wtyczka, SSR blok Gutenberg + CPT.

### Model danych (bez zmian)
```
CPT:  comparison_topic     — 1 post = 1 pod-temat (Ojciec, Jezus, Pismo Święte…)
Tax:  comparison_category  — grupuje tematy w panele akordeonu (Bóg, Kanon wiary…)
Meta: churches (post)      — [{church_name, description(HTML)}, …] (repeater, TinyMCE)
Meta: sort_order (post/term) — kolejność
```
CPT `public=false` (tematy niewidoczne pojedynczo; całość renderuje blok).
Blok `comparison-of-religions/comparison-accordion` renderuje SSR: desktop = CSS
Grid (wyrównane kolumny), mobile = stack; akordeon z klawiaturą (WCAG 2.1 AA),
FAQ schema. Atrybut `selectedCategories` (filtr sekcji).

## Decyzje

1. **Osobna wtyczka** w `wp-content/plugins/comparison-of-religions` (zatwierdzone).
   Własny namespace `ComparisonOfReligions`, CPT `comparison_topic`, tax
   `comparison_category` — zero kolizji z `custom-block-package`/`custom-posts`.
2. **Czyszczenie emaus-specyfiki:** URL `emaus.rzeszow.pl` → kzmielec; usunąć
   `.git`, `node_modules`, `vendor` (odtworzyć), seed-data emaus w
   `tools/import-html-data.php`.
3. **Build:** przez DDEV (node/npm w kontenerze). `build/**` commitowane
   (jak w pozostałych paczkach projektu).
4. **Design:** restyl `src/blocks/comparison-accordion/style.scss` + `editor.scss`
   pod kzmielec — spójnie z `accordion-item`: czarna ramka 2px, wersaliki, serif,
   jednostki rem, paleta belief. Logika/HTML/render bez zmian.
5. **Treść:** źródło = produkcja `kzmielec.pl/roznica-wyznan/` (2 kościoły:
   Rzymskokatolicki, Zielonoświątkowy; ~9 sekcji). Najpierw sprawdzić, czy seed
   emaus już to zawiera (możliwe ~1:1); w razie różnic — import z produkcji
   (JSON przez `tools/import-html-data.php` lub wp-cli).
6. **Podpięcie:** blok wstawiony w treść **strony 83**; szablon belief renderuje
   `the_content()` + kafelki nav — bez zmian szablonu.

## Kroki wykonania

1. Kopiuj wtyczkę (bez `.git`/`node_modules`/`vendor`/`build`).
2. Wyczyść emaus-specyfikę.
3. `composer install` + `npm install` + build (DDEV).
4. Aktywuj wtyczkę; zarejestruj CPT/tax (flush rewrite).
5. Restyl bloku pod design kzmielec.
6. Import treści z produkcji (2 kościoły, 9 sekcji).
7. Wstaw blok w stronę 83; ustaw kolejność sekcji/tematów.
8. Quality gates: PHPStan + PHPCS wtyczki; WCAG AA (skill `check`) na stronie 83.

## Poza zakresem (YAGNI)
- Zmiana modelu danych / architektury wtyczki.
- Więcej niż 2 kościoły.
- Merge do istniejących paczek.

## Ryzyka
- Build node w DDEV (uważać na pamięć WSL — patrz [[cce-setup-gotchas]] podejście
  z limitami; tu build @wordpress/scripts, zwykle lekki).
- Zgodność PHPStan level wtyczki z konfiguracją projektu.
