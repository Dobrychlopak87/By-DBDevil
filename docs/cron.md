# Zadania okresowe (cron) — konfiguracja przez panel hostingu

Oba zadania są dostarczane w paczce i działają bez SSH, terminala i Composer'a.
Konfiguracja odbywa się wyłącznie w panelu hostingu przez przeglądarkę.

## Zadania

| Zadanie | Plik | Zalecana częstotliwość | Zakres |
| --- | --- | --- | --- |
| Czyszczenie chatroomu | `chatroom/cron/cleanup-images.php` | co 5 minut (minimum raz na dobę) | usuwa wiadomości publiczne i prywatne oraz powiązane obrazy starsze niż 24 h (UTC), zwalnia nieaktywne nicki |
| Aktualizacja pogody | `cron/update_weather.php` | co 15 minut (opcjonalne) | pobiera dane pogody i zapisuje atomowo `weather.json`; bez tego zadania moduł pogody pokazuje neutralny komunikat lub ostatni cache |

Czyszczenie chatroomu jest idempotentne i bezpieczne przy wielokrotnym
uruchomieniu (blokada `GET_LOCK`, transakcje). Nie usuwa wiadomości młodszych
niż 24 godziny. Nie uruchamia się podczas zwykłych wejść użytkowników.

## Konfiguracja w panelu nazwa.pl (bez SSH)

1. Zaloguj się do panelu administracyjnego hostingu przez przeglądarkę.
2. Otwórz sekcję zadań okresowych / harmonogramu (cron).
3. Dodaj zadanie dla pliku `chatroom/cron/cleanup-images.php`:
   - typ uruchomienia: plik PHP (CLI) ze ścieżką do pliku w katalogu serwisu,
   - częstotliwość: co 5 minut albo — jeśli panel nie pozwala rzadziej — co najmniej raz na dobę,
   - strefa czasowa: Europe/Warsaw (retencja liczona jest w UTC niezależnie od panelu).
4. Dodaj analogicznie zadanie dla `cron/update_weather.php` (co 15 minut).
5. Po zapisaniu sprawdź w panelu czas ostatniego wykonania każdego zadania.
6. Zweryfikuj działanie: w chatroomie wiadomości sprzed ponad 24 h znikają
   po najbliższym przebiegu, a `weather.json` w katalogu serwisu ma świeży
   znacznik `updatedAt`.

Plik zadania musi istnieć na serwerze w momencie dodawania wpisu w panelu —
pliki są częścią paczki i trafiają na serwer przez FTP razem z resztą kodu.

## Ochrona przed nieautoryzowanym uruchomieniem

- `chatroom/cron/cleanup-images.php` poza CLI przyjmuje wyłącznie żądanie
  z poprawnym tokenem `?token=...` i ograniczeniem raz na 60 sekund;
  bez tokenu w prywatnej konfiguracji każde żądanie HTTP zwraca 404.
- `cron/update_weather.php` poza CLI zawsze zwraca 404.
- Katalogi `cron/` i `chatroom/cron/` są dodatkowo zablokowane przez
  `.htaccess` (`Require all denied` / `RewriteRule ^cron/ - [F,L]`).

## Hosting bez crona (wariant zastępczy)

Jeżeli hosting nie udostępnia harmonogramu:

1. W pliku `chatroom/.htaccess` usuń przez FTP linię
   `RewriteRule ^cron/ - [F,L]` (domyślnie blokuje ona dostęp HTTP do
   katalogu cron — na hostingu z cronem przez CLI ma pozostać).
2. Wygeneruj długi losowy token (np. 64 znaki hex) i dodaj go do prywatnej
   konfiguracji serwisu jako `cron_cleanup_token`.
3. Ustaw w panelu hostingu (lub w zewnętrznej usłudze harmonogramu) wywołanie
   adresu `https://TWOJA-DOMENA/chatroom/cron/cleanup-images.php?token=TOKEN`
   co najwyżej raz na 5 minut. Bez tokenu adres zwraca 404 i nic nie robi.
4. Pogoda jest w pełni opcjonalna — bez aktualizacji serwis działa dalej,
   a moduł pogody pokaże neutralny komunikat zamiast błędu.

Wariant zastępczy nie wykonuje pełnego czyszczenia przy każdym wejściu
użytkownika i nie usuwa wiadomości młodszych niż 24 godziny. Na hostingu
z cronem podstawowym i zalecanym mechanizmem pozostaje cron.
