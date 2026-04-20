# Instrukcja: wygenerowanie Page Access Token dla strony Kzmielec

## Co ma zrobić admin strony

Wygenerować token dostępu do API Facebooka, dzięki któremu strona kzmielec.pl będzie automatycznie pobierać posty z Facebooka i wyświetlać je na stronie.

**Czas:** ~15 minut
**Koszt:** 0 zł (Facebook Developer Platform jest całkowicie darmowy)
**Co przesłać:** długi ciąg znaków (token) — skopiowany i wklejony w wiadomości

---

## Ważne: zaloguj się na Facebooku kontem, które jest adminem strony Kzmielec

Cała procedura musi być wykonana z konta, które ma uprawnienia administratora strony facebook.com/Kzmielec.

---

## Krok 1 — Utworzenie aplikacji developerskiej

1. Wejdź na https://developers.facebook.com/
2. Kliknij **"Get Started"** (jeśli pierwszy raz) i zaloguj się
3. Zaakceptuj regulamin Meta for Developers
4. W prawym górnym rogu kliknij **"My Apps"**
5. Kliknij zielony przycisk **"Create App"**

### W kreatorze aplikacji:

**Krok "App details":**
- App name: `KZMielec` (lub inne, dowolne)
- App contact email: Twój adres email
- Kliknij **Next**

**Krok "Use cases":**
- Z listy wybierz **"Manage everything on your Page"**
- Kliknij **Next**

**Krok "Business":**
- Zaznacz **"I don't want to connect a business portfolio yet"**
- Kliknij **Next**

**Krok "Requirements":**
- Kliknij **Next**

**Krok "Overview":**
- Kliknij **Create app**
- Jeśli zapyta o hasło Facebooka — wpisz je

---

## Krok 2 — Generowanie User Access Token

1. Z górnego menu wybierz **Tools** → **Graph API Explorer**
2. Po prawej stronie zobaczysz panel **"Access Token"**
3. Upewnij się, że pole **"Meta App"** pokazuje Twoją aplikację (np. KZMielec)
4. W sekcji **"Permissions"** kliknij **"Add a Permission"** i dodaj dwa uprawnienia:
   - `pages_show_list`
   - `pages_read_engagement`

   (Wpisuj po kolei w polu wyszukiwania i klikaj na wyniku z listy)

5. Kliknij duży niebieski przycisk **"Generate Access Token"**
6. Pojawi się okno logowania Facebooka — potwierdź
7. Wybierz opcję **"Dostęp tylko do strony Kzmielec"** (lub podobną) — zaznacz TYLKO stronę Kzmielec
8. Kliknij **"Kontynuuj"** i zatwierdź uprawnienia

Po zatwierdzeniu w polu **"Access Token"** pojawi się długi ciąg znaków — to jest User Token (tymczasowy, działa 1-2 godziny).

---

## Krok 3 — Przełączenie na Page Access Token

1. W panelu po prawej stronie znajdź pole **"User or Page"**
2. Rozwiń je (kliknij)
3. Zobaczysz listę — powinna tam być **strona Kzmielec**
4. Kliknij na **Kzmielec**

Po kliknięciu token w polu **"Access Token"** zostanie podmieniony na **Page Access Token** — to ten który nas interesuje.

---

## Krok 4 — Przedłużenie tokenu (żeby nie wygasał)

Page Access Token wygenerowany powyżej ma ograniczony czas ważności. Żeby zrobić go "niewygasającym" potrzebujemy go przedłużyć.

1. Skopiuj token z pola **"Access Token"** (kliknij ikonę kopiowania obok)
2. Otwórz **Tools** → **Access Token Debugger** (https://developers.facebook.com/tools/debug/accesstoken/)
3. Wklej skopiowany token w duże pole i kliknij **"Debug"**
4. Sprawdź pole **"Expires"** — powinno pokazać datę wygaśnięcia
5. Na dole strony znajdź przycisk **"Extend Access Token"** — kliknij go
6. Facebook zapyta o hasło — podaj
7. Po chwili u dołu pojawi się **nowy, przedłużony token** — skopiuj go

**Uwaga:** Jeśli pole **"Expires"** po przedłużeniu pokazuje **"Never"** — to jest nasz token. Jeśli pokazuje datę (np. 60 dni) — też jest OK, ale będzie wymagał odnowienia co 60 dni.

---

## Krok 5 — Przesłanie tokenu

Skopiuj token (długi ciąg znaków zaczynający się np. od `EAA...`) i prześlij go do Łukasza w wiadomości, najlepiej:

- ✓ Przez wiadomość w Messengerze
- ✓ Przez Signal / WhatsApp
- ✗ **NIE** przez publiczny email bez szyfrowania
- ✗ **NIE** przez SMS

Token to "klucz" do automatycznego pobierania postów — traktuj go jak hasło. Jeśli ktoś nieupoważniony go dostanie, może czytać posty (ale nie może nic opublikować ani usunąć).

---

## Co dalej?

Po otrzymaniu tokenu Łukasz wklei go w panelu WordPress strony kzmielec.pl. Od tego momentu strona będzie automatycznie pobierać nowe posty z Facebooka co kilka godzin.

Token **nie wymaga żadnej dalszej obsługi** — jeśli został przedłużony w kroku 4, będzie działał bezterminowo.

---

## Problemy?

- **Nie widzę strony Kzmielec w liście w kroku 3** → sprawdź czy jesteś zalogowany kontem admina strony, nie zwykłym profilem
- **Token wygasa szybko** → upewnij się, że wykonałeś Krok 4 (Extend Access Token)
- **Błąd "You do not have permission"** → konto musi mieć rolę "Administrator" na stronie Kzmielec (nie "Moderator" czy "Analityk")

W razie problemów skontaktuj się z Łukaszem.
