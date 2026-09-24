# 66600.PL — release candidate Etapu 6

Ta paczka jest kandydatem release przygotowanym na podstawie stagingu. Zawiera kod aplikacji, lokalne zasoby statyczne, przykładowe konfiguracje, czysty schemat i konfigurację Docker. Nie zawiera sekretów, sesji, uploadów użytkowników, logów, backupów, dumpów produkcyjnych ani danych użytkowników.

## Status

Paczka nie jest jeszcze zatwierdzona do przełączenia produkcji. Przed wdrożeniem muszą zostać wykonane i zaakceptowane Etapy 7–9 oraz testy smoke/regresji. W szczególności nie istnieje jeszcze finalny browser installer/importer opisany w planie.

## Integralność

`SHA256SUMS.txt` zawiera sumy wszystkich plików paczki. `PACKAGE-MANIFEST.tsv` zawiera listę i rozmiary plików. Nie zmieniaj plików po wygenerowaniu sum bez ponownej generacji manifestu.

## Zasada wdrożenia

Najpierw wdrażaj do osobnego katalogu FTP na nazwa.pl. Nie nadpisuj bieżącego katalogu produkcyjnego. Przełączenie jest dozwolone dopiero po pozytywnym smoke teście, drugiej kopii backupowej i potwierdzeniu rollbacku.

Zobacz `DEPLOYMENT-RUNBOOK.md`, `RELEASE-CHECKLIST.md` i `ROLLBACK-CHECKLIST.md`.

## GitHub Codespaces

Aby uruchomić środowisko developerskie, otwórz gałąź `installer-66600` w GitHubie i wybierz **Code → Codespaces → Create codespace on installer-66600**. Repozytorium użyje konfiguracji `.devcontainer/`, uruchomi PHP/Apache oraz MariaDB i przekieruje port aplikacji `8080`.

Codespaces korzysta wyłącznie z testowej bazy i developerskich wartości środowiskowych z `.devcontainer/docker-compose.codespaces.yml`. Nie używa produkcyjnego pliku `.env` ani produkcyjnej bazy danych.

Po uruchomieniu migracje można wykonać w terminalu Codespace:

```bash
php database/migrate.php
```
