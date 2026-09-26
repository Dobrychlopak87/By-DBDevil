# 66600.PL — kopia serwisu i środowisko wdrożeniowe

Repozytorium zawiera kod aplikacji PHP, zasoby statyczne, moduły, schematy baz danych oraz produkcyjne zrzuty SQL przekazane w kopii z 26.09.2026.

## Układ

- `pliki/` — zawartość katalogu strony przeznaczona do wdrożenia na FTP.
- `bazy/` — zrzuty baz `site66main` i `poker` oraz wariant kompatybilności MariaDB.
- `pliki/staging-66600-20260923/` — kandydat release z dokumentacją Docker/runbook/checklistami.
- `pliki-lista.tsv` — manifest plików z kopii.

## Bezpieczeństwo

Repozytorium **nie powinno zawierać sekretów produkcyjnych**. Pliki `pliki/.private/66-600-security-config.php` i `pliki/poker/config.php` są lokalnie ignorowane przez Git; przed wdrożeniem należy dostarczyć je bezpiecznym kanałem na serwer. Nie commituj haseł FTP, haseł baz danych, kluczy API, sesji ani plików `.env`.

Zrzuty SQL zawierają dane produkcyjne, więc repozytorium GitLab powinno być prywatne, a dostęp ograniczony do osób uprawnionych.

## Wdrożenie

Kandydat stagingowy nakazuje wdrożenie do osobnego katalogu FTP, smoke test, drugą kopię zapasową i dopiero potem przełączenie produkcji. Główna instrukcja znajduje się w [`pliki/staging-66600-20260923/DEPLOYMENT-RUNBOOK.md`](pliki/staging-66600-20260923/DEPLOYMENT-RUNBOOK.md).

Przed wdrożeniem sprawdź wymagania serwera w `SERVER-REQUIREMENTS.md` oraz checklisty release/rollback. Nie wykonuj przełączenia produkcji bez potwierdzonego rollbacku.

## Bazy danych

- Dla obecnego serwera MariaDB użyj `bazy/server629599_site66main.sql` i `bazy/server629599_poker.sql`.
- Wariant `*.kompatybilny-starsza-mariadb.sql` jest awaryjny dla starszych instalacji, gdy wystąpi konflikt nazw kluczy obcych.
- Przed importem wykonaj backup i zweryfikuj docelowe nazwy baz.

## Status importu

To jest uporządkowana kopia źródłowa. Nie deklaruje ona jeszcze bezpiecznego wdrożenia produkcyjnego: wymagane są konfiguracja sekretów, testy PHP/HTTP, test połączeń z bazą, smoke test oraz procedura rollbacku.
