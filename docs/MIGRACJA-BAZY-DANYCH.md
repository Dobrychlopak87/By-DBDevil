# Instrukcja migracji bazy danych — 66600.PL

Dokument opisuje bezpieczne przygotowanie i wykonanie migracji bazy danych dla projektu 66600.PL. Obejmuje zarówno **nową instalację**, jak i **przeniesienie danych z istniejącej instalacji**.

> **Zasada nadrzędna:** nie wykonuj pierwszej migracji bez zweryfikowanego backupu bazy, plików uploadów i prywatnej konfiguracji. Nie używaj produkcyjnej bazy jako środowiska testowego.

## 1. Wybierz właściwy scenariusz

| Sytuacja | Procedura |
| --- | --- |
| Nowa, pusta baza dla release’u stagingowego | [A. Nowa instalacja](#a-nowa-instalacja-na-pustej-bazie) |
| Istniejąca instalacja ma zostać przeniesiona do nowej | [B. Migracja danych istniejącej instalacji](#b-migracja-danych-istniejącej-instalacji) |
| Przywrócenie pełnej kopii serwisu 66600.PL z tego repozytorium | [C. Odtworzenie pełnych zrzutów SQL](#c-odtworzenie-pełnych-zrzutów-sql) |
| Aktualizacja schematu już działającej instalacji | [D. Migracje wersjonowane](#d-migracje-wersjonowane) |

Nie mieszaj procedur A, B i C bez wcześniejszego porównania schematu oraz planu konfliktów identyfikatorów.

## 2. Wymagania i dane wejściowe

Serwer powinien spełniać wymagania z [`SERVER-REQUIREMENTS.md`](../pliki/staging-66600-20260923/SERVER-REQUIREMENTS.md):

- PHP 8.1 lub nowsze,
- `pdo`, `pdo_mysql`, `json`, `fileinfo`, `mbstring`, `openssl`,
- MySQL 5.7+ lub MariaDB 10.4+ z `utf8mb4`,
- Apache 2.4 z obsługą `.htaccess` i `mod_rewrite`.

Przygotuj **poza repozytorium**:

- host bazy,
- nazwę bazy,
- użytkownika bazy,
- hasło bazy,
- dane osobnej bazy chatroomu, jeśli jest używana,
- adres URL nowej instalacji i ewentualny `base_path`,
- backup docelowej bazy, plików aplikacji, uploadów i prywatnej konfiguracji.

Nigdy nie wpisuj haseł do tego dokumentu, commitów, skryptów powłoki ani plików śledzonych przez Git. Plik `.private/66-600-security-config.php` jest lokalną konfiguracją wdrożeniową i nie należy go commitować.

## 3. Backup przed każdą migracją

### 3.1. Backup SQL

Wykonaj eksport z panelu hostingu/phpMyAdmin albo z bezpiecznego środowiska administracyjnego. Przykład dla MySQL/MariaDB:

```bash
mysqldump \
  --single-transaction \
  --routines --triggers \
  --default-character-set=utf8mb4 \
  -h DB_HOST -u DB_USER -p DB_NAME > backup-YYYYMMDD-HHMM.sql
```

Jeśli chatroom korzysta z osobnej bazy, wykonaj drugi eksport:

```bash
mysqldump \
  --single-transaction \
  --routines --triggers \
  --default-character-set=utf8mb4 \
  -h CHAT_DB_HOST -u CHAT_DB_USER -p CHAT_DB_NAME > chat-backup-YYYYMMDD-HHMM.sql
```

Nie umieszczaj haseł w parametrze `-p` wprost. Po eksporcie zapisz sumę kontrolną w bezpiecznym magazynie:

```bash
sha256sum backup-YYYYMMDD-HHMM.sql chat-backup-YYYYMMDD-HHMM.sql
```

### 3.2. Backup plików

Zachowaj kopię:

- bieżącego katalogu aplikacji,
- `assets/uploads/`,
- `chatroom/uploads/`,
- `.private/66-600-security-config.php`,
- `poker/config.php`, jeśli moduł pokera jest wdrożony,
- konfiguracji zadań cron.

Backup trzymaj poza publicznym katalogiem i poza repozytorium GitHub.

## A. Nowa instalacja na pustej bazie

Ta procedura jest właściwa dla nowego środowiska stagingowego lub nowej instalacji bez danych produkcyjnych.

### A.1. Utwórz bazę

W panelu hostingu utwórz bazę i użytkownika z minimalnymi wymaganymi uprawnieniami. Nie używaj konta root aplikacyjnie. Zanotuj dane w menedżerze sekretów.

### A.2. Wyślij pliki

Przez FTP wyślij zawartość release’u do osobnego katalogu stagingowego. Nie wysyłaj:

- dumpów SQL do publicznego katalogu,
- `.env`,
- sekretów z `.private/`,
- sesji, logów i cache,
- lokalnych backupów.

### A.3. Uruchom instalator

Otwórz:

```text
https://TWOJA-DOMENA/installer/
```

Dla podkatalogu użyj np. `https://TWOJA-DOMENA/staging/installer/`.

Przejdź kolejno przez:

1. sprawdzenie wymagań serwera,
2. test połączenia z bazą,
3. ustawienie adresu instalacji,
4. utworzenie schematu,
5. migracje,
6. dane startowe,
7. konto administratora,
8. prywatną konfigurację,
9. zakończenie instalacji.

Instalator tworzy `install.lock`. Po zakończeniu usuń katalog `installer/` przez FTP albo pozostaw go wyłącznie z aktywną blokadą — usunięcie jest zalecane.

### A.4. Weryfikacja nowej bazy

Sprawdź, czy istnieją co najmniej:

- tabele główne ogłoszeń i kroniki,
- `public_menu_items`, `polls`, `poll_options`,
- `calendar_events`,
- tabele chatroomu,
- `schema_migrations`,
- `admin_login_attempts` i `auth_rate_limits`.

Następnie wykonaj smoke test: strona główna, logowanie administratora, ogłoszenia, kronika, kalendarz, ankiety, Puls, chatroom i uploady.

## B. Migracja danych istniejącej instalacji

Ta procedura przenosi dane użytkowe do **już zainstalowanego** release’u. Nie importuje automatycznie sesji, cache, logów, statystyk ani ulotnych wiadomości chatroomu.

### B.1. Przygotuj instalację docelową

1. Wyślij release do osobnego katalogu stagingowego.
2. Utwórz pustą bazę docelową.
3. Uruchom `/installer/` i doprowadź instalację do końca.
4. Potwierdź istnienie `install.lock`.
5. Nie usuwaj jeszcze katalogu `importer/` ani `installer/`.

Importer wymaga zakończonej instalacji i obecności `install.lock`.

### B.2. Uruchom podgląd importu

Otwórz:

```text
https://TWOJA-DOMENA/importer/
```

Podaj w formularzu dane bazy źródłowej oraz stary adres instalacji. Adres jest potrzebny wyłącznie do mapowania URL w polach `ads.link` i `ads.contact_url`.

Najpierw uruchom **tryb podglądu**. Podgląd nie zapisuje danych. Sprawdź:

- liczbę rekordów źródłowych,
- konflikty identyfikatorów,
- zgodność kategorii,
- dostępne dane użytkowników,
- poprawność starego i nowego adresu URL.

### B.3. Uruchom import

Po zatwierdzeniu raportu uruchom import właściwy. Importer:

- zachowuje identyfikatory rekordów,
- pomija rekordy o istniejących ID,
- może być powtórzony bez tworzenia duplikatów,
- działa transakcjami per tabela,
- zatrzymuje się przy błędzie krytycznym.

Po zakończeniu zapisz raport zawierający liczbę rekordów zaimportowanych, pominiętych i błędnych.

### B.4. Przenieś uploady

Przez FTP skopiuj wyłącznie dozwolone pliki użytkowników:

```text
stara-instalacja/assets/uploads/   -> nowa-instalacja/assets/uploads/
stara-instalacja/chatroom/uploads/ -> nowa-instalacja/chatroom/uploads/
```

Nie kopiuj plików wykonywalnych. Zachowaj `.htaccess` zabezpieczające katalogi uploadów. Po transferze sprawdź prawa zapisu i odczytu.

### B.5. Zakończ import

Po pozytywnym smoke teście:

1. usuń katalog `importer/`,
2. usuń katalog `installer/`, jeśli jeszcze istnieje,
3. zweryfikuj brak publicznego dostępu do konfiguracji i SQL,
4. zachowaj backup oraz raport migracji,
5. dopiero po okresie obserwacji rozważ przełączenie produkcji.

Pełne zasady importera znajdują się w [`staging/MIGRATION.md`](../pliki/staging-66600-20260923/MIGRATION.md).

## C. Odtworzenie pełnych zrzutów SQL

Ta procedura służy do odtworzenia pełnej kopii serwisu z katalogu `bazy/`. Zawiera dane produkcyjne, więc wykonuj ją wyłącznie na prywatnej, właściwej bazie docelowej po backupie.

### C.1. Dostępne pliki

- `bazy/server629599_site66main.sql` — główna baza serwisu,
- `bazy/server629599_poker.sql` — baza modułu pokera,
- `bazy/server629599_site66main.kompatybilny-starsza-mariadb.sql` — wariant awaryjny dla starszej MariaDB,
- `pliki/database.sql` — starszy/alternatywny zrzut struktury aplikacji,
- `pliki/staging-66600-20260923/database/schema.sql` — czysty schemat stagingowy,
- `pliki/staging-66600-20260923/database/seed.sql` — minimalne dane startowe.

Dla serwera MariaDB 12.x użyj pliku podstawowego `server629599_site66main.sql`. Wariant kompatybilny wybierz tylko wtedy, gdy import podstawowego pliku na starszej MariaDB zakończy się konfliktem nazw kluczy obcych.

### C.2. Import z konsoli

Najpierw upewnij się, że nazwy baz w dumpie odpowiadają bazom docelowym. Dumpy zawierają instrukcje `CREATE DATABASE`/`USE` z nazwami źródłowymi.

```bash
mysql \
  --default-character-set=utf8mb4 \
  -h DB_HOST -u DB_USER -p \
  < bazy/server629599_site66main.sql

mysql \
  --default-character-set=utf8mb4 \
  -h POKER_DB_HOST -u POKER_DB_USER -p \
  < bazy/server629599_poker.sql
```

Jeśli hosting nie pozwala na `CREATE DATABASE`, usuń linie `CREATE DATABASE` i `USE` z kopii roboczej dumpa, wybierz bazę docelową w panelu i importuj plik do tej bazy. Nie modyfikuj oryginalnego backupu.

Przykład importu do wcześniej wybranej bazy:

```bash
mysql \
  --default-character-set=utf8mb4 \
  -h DB_HOST -u DB_USER -p DB_NAME \
  < site66main-do-importu.sql
```

### C.3. Import przez phpMyAdmin

1. Utwórz lub wybierz właściwą bazę.
2. Ustaw kodowanie połączenia na `utf8mb4`, jeśli panel to umożliwia.
3. Wybierz **Import** i plik SQL.
4. Nie importuj do bazy produkcyjnej bez świeżego backupu.
5. Po zakończeniu sprawdź komunikaty błędów oraz liczbę tabel.
6. Powtórz dla bazy pokera, jeśli moduł jest używany.

### C.4. Po pełnym odtworzeniu

Dostosuj prywatne pliki konfiguracyjne do docelowych danych dostępowych:

```text
pliki/.private/66-600-security-config.php
pliki/poker/config.php
```

Pliki te muszą być umieszczone poza publicznym dostępem i nie mogą trafić do repozytorium. Następnie sprawdź:

- połączenie PDO z bazą główną,
- połączenie modułu pokera,
- logowanie administratora,
- odczyt i zapis ogłoszenia testowego,
- odczyt kroniki i kalendarza,
- ankiety i Puls,
- chatroom,
- upload obrazu,
- zadania cron.

## D. Migracje wersjonowane

Wersjonowane migracje znajdują się w:

```text
pliki/staging-66600-20260923/database/migrations/
```

Aktualnie są to między innymi:

- `0001_security_and_runtime_tables.sql`,
- `0002_schema_version_and_owners.sql`.

Rejestr wykonanych migracji jest przechowywany w tabeli `schema_migrations`. Migracje są sortowane po nazwie pliku i pomijane, jeżeli dana wersja jest już zarejestrowana.

### D.1. Uruchomienie z CLI

Na serwerze z dostępem do PHP CLI, po skonfigurowaniu bezpiecznego `includes/config.php`, uruchom:

```bash
cd pliki/staging-66600-20260923
php database/migrate.php
```

Skrypt działa wyłącznie z CLI, wyświetla `APPLY` lub `SKIP` dla każdej migracji i przerywa po błędzie. DDL MySQL/MariaDB może wykonywać niejawny commit, dlatego przed uruchomieniem zawsze wykonaj backup i nie traktuj całej serii jako jednej transakcji.

> Na hostingu współdzielonym bez SSH/CLI użyj instalatora przeglądarkowego. Nie uruchamiaj `migrate.php` przez HTTP.

### D.2. Dodawanie nowej migracji

1. Utwórz następny plik, np. `0003_nazwa_zmiany.sql`.
2. Stosuj idempotentne operacje, gdy jest to możliwe (`IF NOT EXISTS`).
3. Przed `ALTER TABLE` sprawdź stan istniejących tabel/kolumn na kopii bazy.
4. Nie edytuj migracji, która trafiła już na środowisko współdzielone.
5. Przetestuj migrację na kopii stagingowej i świeżej instalacji.
6. Zaktualizuj dokumentację i checklistę release.
7. Wykonaj backup bezpośrednio przed produkcją.

## 4. Walidacja po migracji

W raporcie migracji zapisz:

- wersję commita/release’u,
- czas rozpoczęcia i zakończenia,
- nazwę środowiska i bazy bez haseł,
- sumę kontrolną backupu,
- zastosowane pliki migracji,
- liczbę tabel i kluczowych rekordów,
- wynik testu połączenia,
- wynik smoke/regression testu,
- decyzję o przełączeniu lub pozostawieniu stagingu.

Minimalny smoke test:

- strona główna i podstrony,
- logowanie administratora,
- ogłoszenie z obrazem,
- kronika i komentarz,
- kalendarz,
- ankieta i Puls,
- publiczne konto, jeśli jest włączone,
- chatroom,
- poker, jeśli jest wdrożony,
- cron i logi,
- brak dostępu HTTP do `.private/`, `config.php`, plików SQL, sesji i logów.

## 5. Rollback

Rollback plików i rollback bazy to dwie osobne operacje.

1. Zatrzymaj zmiany i zapisz objawy oraz kody HTTP.
2. Włącz stronę konserwacyjną, jeśli jest dostępna.
3. Przywróć poprzedni katalog aplikacji z backupu.
4. Przywróć poprzednią prywatną konfigurację.
5. Jeśli migracja zmieniła bazę, przywróć backup SQL wykonany bezpośrednio przed migracją.
6. Wykonaj smoke test starej wersji.
7. Zachowaj nową wersję, logi i raport incydentu.

Szczegółowa procedura znajduje się w [`ROLLBACK.md`](../pliki/staging-66600-20260923/ROLLBACK.md).

## 6. Najczęstsze błędy

| Objaw | Działanie |
| --- | --- |
| `Access denied` | Sprawdź host, nazwę bazy, użytkownika i uprawnienia; hasło wpisz ponownie z magazynu sekretów. |
| `Unknown database` | Utwórz bazę w panelu lub usuń `CREATE DATABASE`/`USE` z kopii roboczej dumpa i wybierz bazę ręcznie. |
| Błąd kodowania znaków | Użyj `--default-character-set=utf8mb4` i sprawdź kodowanie bazy/tabel. |
| Konflikt klucza obcego `errno: 121` | Na starszej MariaDB wypróbuj wariant `*.kompatybilny-starsza-mariadb.sql` po wykonaniu backupu. |
| Migracja uruchomiona przez HTTP | Przerwij; `database/migrate.php` jest przeznaczony wyłącznie dla CLI lub użyj instalatora. |
| Duplikaty po imporcie | Nie uruchamiaj kolejnego importu w ciemno; sprawdź raport, ID i backup. Importer projektu pomija istniejące ID. |
| Brak uploadów | Przenieś pliki do obu katalogów `uploads/`, zachowaj `.htaccess` i sprawdź prawa zapisu. |

## 7. Dokumenty powiązane

- [`INSTALL.md`](../pliki/staging-66600-20260923/INSTALL.md) — instalacja release’u,
- [`MIGRATION.md`](../pliki/staging-66600-20260923/MIGRATION.md) — importer danych istniejącej instalacji,
- [`DEPLOYMENT-RUNBOOK.md`](../pliki/staging-66600-20260923/DEPLOYMENT-RUNBOOK.md) — kontrolowane wdrożenie,
- [`ROLLBACK.md`](../pliki/staging-66600-20260923/ROLLBACK.md) — wycofanie,
- [`RELEASE-CHECKLIST.md`](../pliki/staging-66600-20260923/RELEASE-CHECKLIST.md) — lista przed wydaniem.
