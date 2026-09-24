# Etap 4 — podsumowanie

Etap 4 wykonano wyłącznie na kopii stagingowej kopia stagingowa (robocze drzewo projektu); produkcja i produkcyjna baza danych nie zostały zmodyfikowane.

## Wykonane prace

Usunięto tworzenie i modyfikowanie schematu podczas zwykłych żądań HTTP z modułów throttlingu administratora, kont publicznych, Pulsu, kalendarza oraz chatroomu. Kod webowy sprawdza teraz gotowość wymaganych tabel i kolumn, a w przypadku braku migracji korzysta z bezpiecznego fallbacku zamiast wykonywać DDL.

Dodano czysty, strukturalny `database/schema.sql` bez wierszy produkcyjnych oraz migracje `database/migrations/0001_security_and_runtime_tables.sql` i `0002_schema_version_and_owners.sql`. Dodano też CLI-only `database/migrate.php`, które zapisuje zastosowane wersje w `schema_migrations` i nie może być uruchomione przez HTTP.

W zapytaniach Pulsu i kalendarza ograniczenia `LIMIT` są przekazywane jako parametry PDO. Pozostałe dynamiczne fragmenty wykryte w audycie są stałymi aplikacyjnymi albo listami placeholderów generowanymi z wartości całkowitych, bez bezpośredniej interpolacji danych użytkownika.

## Walidacja

`PHP_LINT=PASS` dla całego stagingu, `HTTP_DDL_SCAN=PASS`, `SCHEMA_DATA_SCAN=PASS` i `SCHEMA_READINESS_CHECKS=PASS`. Czysty schema zawiera 22 definicje tabel i nie zawiera `INSERT`, `REPLACE`, `LOAD DATA`, danych dostępowych ani danych produkcyjnych.

## Ograniczenie

Nie uruchamiano migracji przeciwko bazie, ponieważ staging nie ma prywatnego DSN ani osobnej testowej bazy. Przed wdrożeniem należy wykonać backup bazy, uruchomić migracje na testowej bazie, sprawdzić `schema_migrations`, a następnie wykonać testy funkcjonalne. Tabele bazowe chatroomu są utrzymywane poza eksportem `database.sql`; migracja `chat_nickname_blocks` nie dodaje klucza obcego do `chat_sessions`, aby nie zakładać niezinwentaryzowanego schematu.
