# Facebook Feed — dokumentacja implementacji

Dynamiczny feed z Facebooka pobierany przez Graph API, cache'owany serwerowo, renderowany jako blok Gutenberga z infinite scroll. Zastępuje stary, zacinający się iframe Facebook Page Plugin z poprzedniej wersji strony.

## Architektura

Wszystko w pluginie `custom-block-package` — wyłączasz plugin → feed znika.

```
custom-block-package/
├── app/
│   ├── Admin/
│   │   └── FacebookSettings.php          # Strona ustawień + dashboard widget + admin notice
│   ├── Services/
│   │   └── FacebookFeedService.php       # Klient Graph API + cache + fallback + mock data
│   ├── Cron/
│   │   └── FacebookFeedCron.php          # WP Cron, refresh co 2h (konfigurowalne)
│   ├── Rest/
│   │   └── FacebookFeedController.php    # REST endpoint /custom-block-package/v1/facebook-feed
│   └── Cache/
│       └── BlockCache.php                # FACEBOOK_FEED_PREFIX + flush logic
├── src/blocks/facebook-feed/
│   ├── block.json                        # apiVersion 3, attributes, supports
│   ├── index.js                          # Block registration
│   ├── edit.js                           # Editor UI z ServerSideRender (preview = frontend)
│   ├── render.php                        # Server-side render z header + scroll container
│   ├── view.js                           # IntersectionObserver infinite scroll
│   ├── style.scss                        # Frontend + editor (importowany do index.scss)
│   └── index.scss                        # Editor-only (@import style.scss)
└── index.php                              # Bootstrap nowych klas + activation hooks
```

## Komponenty

### 1. FacebookFeedService

`/app/Services/FacebookFeedService.php`

**Stałe:**
- `OPTION_PAGE_ID` = `cbp_fb_page_id` — username strony FB (np. `Kzmielec`)
- `OPTION_ACCESS_TOKEN` = `cbp_fb_access_token` — Page Access Token (never-expiring)
- `OPTION_CACHE_TTL` = `cbp_fb_cache_ttl` — TTL cache w sekundach (1h/2h/6h/12h/24h)
- `OPTION_LAST_SYNC` = `cbp_fb_last_sync` — timestamp ostatniego udanego fetcha
- `OPTION_LAST_ERROR` = `cbp_fb_last_error` — ostatni komunikat błędu z API
- `OPTION_BACKUP_POSTS` = `cbp_fb_backup_posts` — backup postów (nigdy nie wygasa, fallback gdy API leży)
- `OPTION_PAGE_INFO` = `cbp_fb_page_info` — name + picture URL strony FB
- `MAX_POSTS = 50` — max postów w jednym wywołaniu API
- `DEFAULT_TTL = 2 * HOUR_IN_SECONDS`
- `API_VERSION = 'v19.0'`

**Publiczne metody:**
- `get_posts(int $limit = 10): array` — zwraca posty z cache
- `get_posts_range(int $offset, int $limit): array` — paginacja dla infinite scroll
- `get_total_count(): int` — całkowita liczba postów w cache
- `get_page_info(): array{name, picture}` — info o stronie
- `refresh(): bool` — wymuś fetch z API + zapis cache + backup
- `test_connection(): array{success, message, posts_count}` — test API z obecnym tokenem
- `load_mock_data(): bool` — wstrzykuje 30 fake postów (testy UI bez tokenu)

**Endpointy Graph API:**
```
GET /{page_id}/posts
  ?fields=message,created_time,permalink_url,full_picture,attachments{media,subattachments,type}
  &limit=50
  &access_token={token}

GET /{page_id}
  ?fields=name,picture.type(large)
  &access_token={token}
```

**Flow `refresh()`:**
1. Pobierz `page_id` i `token` z opcji
2. wp_remote_get() do Graph API
3. Parsuj JSON, zwaliduj
4. Zapisz w transient (z TTL)
5. Zapisz w `OPTION_BACKUP_POSTS` (nigdy nie wygasa)
6. Update `OPTION_LAST_SYNC` lub `OPTION_LAST_ERROR`
7. Pobierz info o stronie (`refresh_page_info()`)

**Fallback gdy API leży:**
- Transient pusty + brak refresh → zwróć backup posts (mogą być stare, ale lepsze niż nic)

### 2. FacebookFeedCron

`/app/Cron/FacebookFeedCron.php`

- Custom interval `cbp_fb_interval` = TTL z opcji
- Hook: `cbp_fb_cron_refresh`
- `activate()` / `deactivate()` — register_activation_hook / register_deactivation_hook
- `reschedule()` — wywoływane po zmianie TTL w admin, restartuje cron

### 3. FacebookSettings

`/app/Admin/FacebookSettings.php`

**Menu:** WP Admin → **Facebook Feed** (top-level, dashicon `facebook-alt`)

**Pola formularza:**
- `cbp_fb_page_id` — text input (sanitize_text_field)
- `cbp_fb_access_token` — textarea code (sanitize_textarea_field)
- `cbp_fb_cache_ttl` — select 1h/2h/6h/12h/24h

**Przyciski Actions:**
- **Test connection** — wywołuje `FacebookFeedService::test_connection()`
- **Refresh cache now** — flush cache + force refresh
- **Load mock data (for testing)** — wczytaj 30 fake postów

**Status section:**
- Last successful sync (z human_time_diff)
- Last error (jeśli jest)

**Admin notice (czerwony banner):**
- Wyświetla się na **wszystkich stronach WP Admin** gdy `cbp_fb_last_error` nie jest puste
- Pokazuje treść błędu + przycisk "Otwórz ustawienia"
- Hook: `admin_notices`

**Dashboard widget:**
- Widget na Kokpicie WP: status strony, token, cache count, last sync, error
- Hook: `wp_dashboard_setup`

**Security:**
- Wszystkie akcje z nonce (NONCE_SAVE, NONCE_ACTION)
- Capability: `manage_options`
- Sanityzacja inputów

### 4. FacebookFeedController (REST)

`/app/Rest/FacebookFeedController.php`

**Endpoint:** `GET /wp-json/custom-block-package/v1/facebook-feed`

**Query params:**
- `offset` (int, default 0) — od którego posta zacząć
- `limit` (int, default 5, max 20) — ile postów zwrócić
- `showImages` (bool, default true)
- `showDate` (bool, default true)

**Response:**
```json
{
  "html": "<article>...</article>...",  // Pre-renderowany HTML
  "count": 5,
  "total": 30,
  "offset": 0,
  "hasMore": true
}
```

**Permission:** `__return_true` (publiczny — feed jest publicznie widoczny)

### 5. Block facebook-feed

`/src/blocks/facebook-feed/`

**Atrybuty (block.json):**
- `anchor` (string) — id elementu
- `postsCount` (number, default 5) — początkowa liczba postów
- `showImages` (bool, default true)
- `showDate` (bool, default true)
- `columns` (number, default 1, max 3)
- `containerHeight` (number, default 700) — wysokość scroll boxa w px

**Render flow:**
1. `render.php` pobiera N pierwszych postów z `FacebookFeedService::get_posts()`
2. Renderuje header (avatar + nazwa + przycisk "Odwiedź stronę")
3. Renderuje N postów w scroll container (`.facebook-feed__scroll`)
4. Dodaje sentinel + loading indicator jeśli `has_more`
5. Wrapper ma `data-*` atrybuty dla view.js

**View.js (infinite scroll):**
1. `IntersectionObserver` z `root: scrollContainer`, `rootMargin: '200px 0px'`
2. Gdy sentinel widoczny → fetch z REST endpoint z aktualnym `offset` i `limit`
3. Append HTML do grid
4. Update `offset`, `hasMore`
5. Disconnect observer gdy `!hasMore`

**CSS scroll container:**
- `height: var(--cbp-fb-height, 700px)` (z atrybutu)
- `max-height` + `min-height: 0` dla bezpieczeństwa
- `overflow-y: auto` + `overscroll-behavior: contain` (izoluje scroll)
- `contain: strict` — twarda izolacja layoutu (nie wpływa na page scroll)

**Header bloku:**
- Avatar 48px (z `cbp_fb_page_info[picture]`)
- Page name + "Strona na Facebooku" (subtitle)
- Niebieski przycisk "Odwiedź stronę" → link do facebook.com/{page_id}
- Wrap na mobile (przycisk pełna szerokość <500px)

**Względny czas:**
- < 1 min: "przed chwilą"
- < 1 tydzień: "X godzin temu" / "X dni temu" (`human_time_diff`)
- > 1 tydzień: pełna data (`wp_date` z `date_format` opcji)

## Token Facebook

### Setup wykonany

**Aplikacja Meta:** `KZMielec` (App ID: 1315941170390773)
- Type: Business
- Use case: Manage everything on your Page
- Mode: Development (nie wymaga App Review)
- Admin: Dariusz Hapoń

**Strona FB:** Kościół Zielonoświątkowy Zbór w Mielcu (Page ID: 1496572750574514)
- Username: Kzmielec
- Admin Dariusz: Administrator role

**Token:**
- Type: Page Access Token
- Expires: **Never** (wygenerowany przez `me/accounts` endpoint z long-lived User Tokenu)
- Data Access Expires: 90 dni (Data Use Checkup — Dariusz musi co 90 dni odnowić w Ustawieniach FB)
- Scopes: `pages_show_list`, `business_management`, `pages_read_engagement`, `public_profile`

### Procedura odnowienia tokenu (gdy wygaśnie / Dariusz nie odnowił Data Access)

1. Graph API Explorer → Get User Access Token (z permissions: `pages_show_list`, `pages_read_engagement`)
2. Skopiuj User Token → Access Token Debugger → **Extend Access Token** → long-lived User Token (60 dni)
3. Wklej long-lived User Token w Graph API Explorer
4. Zmień endpoint na `me/accounts` → Submit
5. Z JSON skopiuj `access_token` dla Kzmielec — to never-expiring Page Token
6. Wklej w WP Admin → Facebook Feed → Save

Pełna instrukcja dla admina FB: `INSTRUKCJA-TOKEN-FB.md` (do wysłania pastorowi).

## Mock data — testowanie bez tokenu

W admin panelu **Load mock data (for testing)** wstrzykuje 30 fake postów:
- Image: `https://picsum.photos/seed/fbN/800/450`
- Text: "Przykładowy post numer N — lorem ipsum..."
- Page info: "Kościół Zielonoświątkowy Zbór w Mielcu" + avatar
- Daty rosnące wstecz (dzień po dniu)

Używane do podglądu UI bez prawdziwego tokenu (development).

## Wydajność

| | iframe FB Page Plugin (stara prod) | Nasz block |
|---|---|---|
| External JS | ~350KB | 0KB |
| External cookies | Tak (GDPR) | Nie |
| Czas ładowania | 500-2000ms (zacina się) | <50ms (z cache) |
| Lighthouse impact | Tracker blokujący | Brak wpływu |
| Niezawodność | Często blank | Zawsze działa (backup w `wp_options`) |

**Cache strategy:**
- Transient: TTL z ustawień (default 2h)
- Backup option: nigdy nie wygasa, używany gdy API zwróci błąd
- Cron: refresh w tle co 2h, użytkownicy nigdy nie czekają na API

## Standardy

- **PSR-4:** namespace `CustomBlockPackage\Admin\*`, `Services\*`, `Cron\*`, `Rest\*`
- **PHPStan L8:** strict types, return types, 0 errors
- **PHPCS WP Standards:** 0 errors, 0 warningi
- **WCAG 2.1 AA:** semantyczne `<article>`, `<time>`, `aria-label`, `aria-live`, `loading="lazy"`, `rel="noopener noreferrer"`
- **i18n:** wszystkie stringi w `__()` z textdomain `custom-block-package`

## Instagram (status: NIE ZAIMPLEMENTOWANE)

Decyzja: Instagram zostaje na **Smash Balloon Instagram Feed** (free) plugin. Powody:
- Konto Instagram zbor_w_mielcu nie jest powiązane z FB Page Kzmielec w Meta Business Suite
- Bez powiązania nie da się użyć Page Tokenu dla IG
- Wymagałoby: konwersji konta IG na Business + powiązania z FB Page (pastor) + dodania `instagram_basic` do tokenu

Jeśli kiedyś:
1. Pastor połączy IG z FB Page (~1.5 min na telefonie)
2. Dodanie `instagram_basic` do tokenu (~5 min)
3. Implementacja `InstagramFeedService` analogicznie do `FacebookFeedService` (~30 min)

Reuse byłby wysoki: ten sam token, BlockCache, Cron pattern, REST pattern, admin patterns.
