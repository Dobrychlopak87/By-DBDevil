# Instalacja 66600.PL

Instalacja odbywa się wyłącznie przez FTP i przeglądarkę — bez SSH, terminala,
Composer'a, npm ani Node.js.

## Wymagania serwera

- PHP >= 8.1 z rozszerzeniami: pdo, pdo_mysql, json, fileinfo, mbstring, openssl;
  opcjonalnie gd (miniatury/AVIF) i curl (aktualizacja pogody),
- MySQL/MariaDB z bazą utworzoną w panelu hostingu,
- Apache z mod_rewrite i możliwością `.htaccess` (AllowOverride All),
- katalogi zapisywalne: `session/`, `assets/uploads/`, `chatroom/uploads/`, `.private/`.

## Kroki

1. **FTP:** wyślij zawartość paczki do katalogu docelowego (głównego lub podkatalogu).
   Zachowaj tryb binarny dla obrazów i fontów.
2. **Panel hostingu:** utwórz bazę danych i użytkownika bazy (zapisz dane — poda się je
   w instalatorze). Dane dostępowe wpisuj wyłącznie w formularzu instalatora;
   nie umieszczaj ich w plikach ani w dokumentacji.
3. **Przeglądarka:** otwórz `https://TWOJA-DOMENA/installer/` (dla podkatalogu:
   `https://TWOJA-DOMENA/podkatalog/installer/`).
4. Przejdź kroki instalatora:
   wymagania serwera → dane bazy (test połączenia) → adres instalacji →
   schemat i migracje → dane startowe → konto administratora →
   prywatna konfiguracja → zakończenie.
5. Instalator utworzy `install.lock` i zablokuje ponowne uruchomienie.
   Usuń katalog `installer/` przez FTP (zalecane) albo pozostaw — blokada działa.
6. Skonfiguruj zadania okresowe w panelu hostingu zgodnie z `docs/cron.md`.
7. Wykonaj test odbiorowy: strona główna, logowanie administratora
   (`/admin/login.php`), ogłoszenia, kronika, kalendarz, ankiety, Puls, chatroom.

## Adres instalacji i podkatalog

- Instalator proponuje adres automatycznie; dla instalacji w podkatalogu adres musi
  zawierać podkatalog (np. `https://example.com/home`).
- Opcjonalny kanoniczny host włącza przekierowania 301 na ten host oraz wymuszenie
  HTTPS (obsługiwane w PHP przez `enforce_canonical_request`).
- W głównym `.htaccess` można dodatkowo ustawić `SetEnv SITE_URL "..."`
  i `SetEnv CANONICAL_HOST "..."` (linie są zakomentowane jako przykład).

## Prywatna konfiguracja

Plik `.private/66-600-security-config.php` zawiera dane bazy, sekrety i token cron.
Instalator tworzy go automatycznie. Katalog `.private/` jest zablokowany przez
własny `.htaccess`. Nie publikuj tego pliku i nie commituj go.

## Bezpieczeństwo instalatora

- wszystkie formularze instalatora są chronione tokenem CSRF,
- dane bazy pozostają wyłącznie w sesji instalatora do momentu zapisu konfiguracji,
- błędy są komunikowane neutralnie (bez wyjątków i ścieżek systemowych),
- po zakończeniu instalator zwraca 404 do czasu usunięcia `install.lock`.
