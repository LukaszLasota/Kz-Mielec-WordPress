# kzmielec.pl - Konfiguracja projektu

## Informacje ogólne

- **Strona:** Kościół Zielonoświątkowy Zbór w Mielcu
- **Domena:** kzmielec.pl
- **Typ:** WordPress

---

## Hosting produkcyjny (Cyber Folks)

- **Host SSH:** s140.cyber-folks.pl
- **Port SSH:** 222
- **Użytkownik:** darqha
- **Panel:** DirectAdmin
- **PHP:** 8.2.29
- **Baza danych:** MariaDB
  - DB name: darqha_kzmielec
  - DB user: darqha_kzmielec
  - Table prefix: wp_
- **Ścieżka plików:** /home/darqha/domains/kzmielec.pl/public_html/
- **Klucz SSH:** dodany z WSL (~/.ssh/id_ed25519)

---

## Środowisko lokalne (DDEV)

- **URL lokalny:** https://kzmielec.ddev.site
- **WP Admin:** https://kzmielec.ddev.site/wp-admin/
- **DDEV wersja:** v1.24.10
- **PHP:** 8.2 (zgodne z produkcją)
- **Baza:** MariaDB 10.11
- **Katalog projektu:** /home/lukasz/projects/kzmielec/
- **Platforma:** WSL2 (Linux na Windows)

---

## Konfiguracja lokalna - zmiany vs produkcja

| Plik | Zmiana | Powód |
|------|--------|-------|
| `wp-config.php` | Zakomentowane DB_NAME/USER/PASSWORD/HOST | DDEV zarządza bazą przez wp-config-ddev.php |
| `wp-config.php` | Zakomentowane WP_CACHE i WPCACHEHOME | WP Super Cache niepotrzebny lokalnie |
| `wp-config.php` | Dodany include wp-config-ddev.php | Integracja z DDEV |
| `.user.ini` | Zakomentowane auto_prepend_file | Wordfence WAF - ścieżka produkcyjna nie działa lokalnie |

---

## Aktywny motyw

- **Nazwa:** html5blank-stable (customizowany HTML5 Blank)
- **Szczegółowa dokumentacja:** `.claude/theme-old-docs.md`

---

## Zainstalowane pluginy

- Wordfence (security) - WAF wyłączony lokalnie
- WP Super Cache (cache) - wyłączony lokalnie
- Yoast SEO
- Smash Balloon Instagram Feed