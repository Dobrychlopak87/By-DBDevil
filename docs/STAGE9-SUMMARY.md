# Etap 9 — podsumowanie (instalator przeglądarkowy i importer danych)

Etap 9 wykonano wyłącznie na kopii stagingowej i w lokalnym środowisku
testowym Apache/MariaDB. Produkcja nie została zmodyfikowana.

## Wykonane prace

### Instalator (`installer/`)
Kroki: wymagania serwera → dane bazy (test połączenia) → adres instalacji →
schemat + migracje → dane startowe → konto administratora → prywatna
konfiguracja → `install.lock`.

- `installer/lib/Requirements.php` — walidacja PHP/rozszerzeń/sesji/katalogów;
  funkcje opcjonalne (gd, curl) nie blokują instalacji.
- `installer/lib/InstallerState.php` — sesja, CSRF, dane bazy wyłącznie w sesji
  do momentu zapisu konfiguracji.
- `installer/lib/MigrationRunner.php` — podział SQL na instrukcje, schemat,
  ponumerowane migracje z rejestrem `schema_migrations`, ziarno.
- Blokada po zakończeniu: `install.lock` u korzenia aplikacji; instalator
  zwraca 404. Ponowne otwarcie niemożliwe bez usunięcia blokady przez FTP.
- Wszystkie formularze z CSRF; błędy neutralne (bez wyjątków/ścieżek);
  hasła wyłącznie `password_hash`.
- Działa w katalogu głównym i podkatalogu (adresy względne).

### Importer (`importer/`)
- `importer/lib/ImportRunner.php` — import tabelami z zachowaniem ID,
  pomijanie konfliktów (bez duplikatów), mapowanie starego adresu wyłącznie
  w polach URL, pomijanie sesji/logów/cache/runtime/chatroomu, transakcje
  per tabela, zatrzymanie przy błędzie krytycznym, tryb podglądu bez zapisu,
  powtarzalność.
- Importer wymaga zakończonej instalacji (`install.lock`) i świadomego
  potwierdzenia backupu.

### Schemat i ziarno (naprawy pod czystą instalację)
- `database/seed.sql` — minimalne dane startowe: kategorie ogłoszeń i kroniki,
  menu publiczne; bez kont administratorów; idempotentne.
- `database/schema.sql` — odtworzone z kodu aplikacji tabele chatroomu
  (`chat_sessions`, `chat_messages`, `chat_message_images`,
  `chat_private_messages`, `chat_nick_accounts`, `chat_nick_claims`,
  `chat_admin_log`), których nie było ani w starym zrzucie produkcyjnym,
  ani w dotychczasowym schemacie paczki.

### Dokumentacja
`INSTALL.md`, `MIGRATION.md`, `ROLLBACK.md`, `SERVER-REQUIREMENTS.md`.

## Walidacja (Apache + MariaDB, 2026-09-23)

- Instalator E2E przez HTTP: wszystkie 8 kroków zakończone; pusta baza
  `app66600_install` → 29 tabel, ziarno (10 kategorii ogłoszeń, 7 kroniki,
  6 pozycji menu), konto administratora z hashem bcrypt; strona działa
  na świeżej instalacji; `install.lock` utworzony; instalator = 404.
- Importer E2E przez HTTP: podgląd bez zapisu (poprawne liczby i konflikty ID),
  import: zaimportowane 2 / pominięte 5 / błędy 0; powtórzenie importu:
  zaimportowane 0 / pominięte 7 — brak duplikatów.
- `PHP_LINT=PASS` (117 plików), skany sekretów i ścieżek lokalnych czyste.

## Bramka przed Etapem 10

- Paczka zawiera kompletny kod, zasoby lokalne, schemat, ziarno, migracje,
  instalator, importer, przykładową konfigurację i dokumentację.
- Przed wysyłką na hosting: świeży, zweryfikowany eksport produkcyjnej bazy
  (panel nazwa.pl), druga kopia FTP, osobny katalog stagingowy na serwerze.
