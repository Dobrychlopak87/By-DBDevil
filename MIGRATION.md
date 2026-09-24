# Import danych istniejącej instalacji

Import jest osobną, jawną operacją. Czysta instalacja nigdy nie importuje danych
produkcyjnych automatycznie.

## Zasady

1. **Przed importem wykonaj backup** docelowej bazy i plików uploadów.
2. Importer działa dopiero po zakończonej instalacji (wymaga `install.lock`).
3. Tryb podglądu nie wykonuje żadnych zapisów — pokaże liczby rekordów
   i konflikty identyfikatorów.
4. Import zachowuje identyfikatory rekordów; rekordy o istniejących ID są
   pomijane, więc operację można bezpiecznie powtórzyć bez duplikatów.
5. Stare adresy domeny są mapowane na adres nowej instalacji wyłącznie w polach
   URL (`ads.link`, `ads.contact_url`). Treści artykułów i ogłoszeń nie są zmieniane.
6. Pomijane są: sesje, logi, cache, statystyki, tabele runtime oraz dane chatroomu
   (wiadomości chatroomu są ulotne — retencja 24 h).
7. Import zatrzymuje się przy błędzie krytycznym; wykonany fragment pozostaje
   spójny (transakcje per tabela).

## Kroki

1. Otwórz `https://TWOJA-DOMENA/importer/`.
2. Podaj dane bazy źródłowej (host, nazwa, użytkownik, hasło) oraz stary adres
   instalacji do mapowania URL. Potwierdź wykonanie backupu.
3. Uruchom podgląd i zweryfikuj raport (liczby rekordów i konflikty ID).
4. Uruchom import i sprawdź raport (zaimportowane / pominięte / błędy).
5. Przenieś pliki uploadów starej instalacji do `assets/uploads/` i
   `chatroom/uploads/` przez FTP (tylko obrazy; bez plików wykonywalnych).
6. Usuń katalog `importer/` przez FTP po zakończeniu migracji.

Uwaga: importer korzysta z biblioteki `installer/lib/`, dlatego katalog
`installer/` usuń dopiero po zakończeniu importu danych.

## Tabele importowane

`ad_categories`, `ads`, `ad_gallery`, `blog_categories`, `blog_posts`,
`blog_post_gallery`, `chronicle_comments`, `chronicle_post_votes`,
`chronicle_comment_likes`, `polls`, `poll_options`, `poll_votes`,
`calendar_events`, `pulse_notices`, `public_menu_items`, `users`, `auth_users`.

## Tabele pomijane świadomie

`schema_migrations`, `admin_login_attempts`, `auth_rate_limits`,
`site_visit_stats`, `chat_sessions`, `chat_messages`, `chat_message_images`,
`chat_private_messages`, `chat_nick_accounts`, `chat_nick_claims`,
`chat_nickname_blocks`, `chat_admin_log`.
