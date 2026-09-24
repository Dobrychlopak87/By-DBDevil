# 66600.PL — release candidate Etapu 6

Ta paczka jest kandydatem release przygotowanym na podstawie stagingu. Zawiera kod aplikacji, lokalne zasoby statyczne, przykładowe konfiguracje, czysty schemat i konfigurację Docker. Nie zawiera sekretów, sesji, uploadów użytkowników, logów, backupów, dumpów produkcyjnych ani danych użytkowników.

## Status

Paczka nie jest jeszcze zatwierdzona do przełączenia produkcji. Przed wdrożeniem muszą zostać wykonane i zaakceptowane Etapy 7–9 oraz testy smoke/regresji. W szczególności nie istnieje jeszcze finalny browser installer/importer opisany w planie.

## Integralność

`SHA256SUMS.txt` zawiera sumy wszystkich plików paczki. `PACKAGE-MANIFEST.tsv` zawiera listę i rozmiary plików. Nie zmieniaj plików po wygenerowaniu sum bez ponownej generacji manifestu.

## Zasada wdrożenia

Najpierw wdrażaj do osobnego katalogu FTP na nazwa.pl. Nie nadpisuj bieżącego katalogu produkcyjnego. Przełączenie jest dozwolone dopiero po pozytywnym smoke teście, drugiej kopii backupowej i potwierdzeniu rollbacku.

Zobacz `DEPLOYMENT-RUNBOOK.md`, `RELEASE-CHECKLIST.md` i `ROLLBACK-CHECKLIST.md`.
