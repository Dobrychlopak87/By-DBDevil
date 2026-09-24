# Checklist release i wdrożenia

## Bramka przed paczką

- [ ] Etapy 1–6 mają zatwierdzone raporty i backup jest dostępny.
- [ ] Etapy 7–9 są ukończone albo formalnie wyłączone z zakresu tego release.
- [ ] Wykonano świeży eksport bazy przez panel lub kontrolowany eksport aplikacyjny.
- [ ] Eksport bazy został zweryfikowany, zanonimizowany dla stagingu i ma sumę kontrolną.
- [ ] `PHP_LINT`, skan include/require, JSON/XML/SQL, URL/path i testy bezpieczeństwa przechodzą.
- [ ] Paczka nie zawiera `.private`, `.env`, haseł, sesji, logów, ZIP-ów, dumpów produkcyjnych ani uploadów użytkowników.
- [ ] `SHA256SUMS.txt` i `PACKAGE-MANIFEST.tsv` zostały wygenerowane po ostatniej zmianie.

## Bramka przed FTP

- [ ] Wykonano drugą pełną kopię bieżącego FTP i bazy.
- [ ] Zapisano ścieżkę katalogu produkcyjnego oraz odrębny katalog stagingowy.
- [ ] Ustalono okno wdrożeniowe i osobę uprawnioną do decyzji o rollbacku.
- [ ] Przygotowano prywatną konfigurację produkcyjną poza paczką.
- [ ] Potwierdzono dostęp do panelu nazwa.pl i FTP; dane nie są wpisywane do dokumentacji ani commitowane.

## Bramka po stagingu

- [ ] Release został wysłany do osobnego katalogu FTP.
- [ ] Installer/migracje użyły osobnej bazy testowej.
- [ ] Smoke test obejmuje stronę główną, konto, ogłoszenia, kronikę, kalendarz, ankiety, Puls, chatroom i panel admina.
- [ ] Sprawdzono uploady, sesje, cron, redirecty, JSON i błędy 4xx/5xx.
- [ ] Wyniki i checksumy zostały zapisane.

## Przełączenie produkcji

- [ ] Backup przed przełączeniem zakończył się powodzeniem.
- [ ] Stary katalog i konfiguracja pozostają dostępne do rollbacku.
- [ ] Przełączenie wykonano przez zmianę entrypointu lub kontrolowaną synchronizację, nie przez usunięcie starej wersji.
- [ ] Smoke test produkcyjny przeszedł.
- [ ] Nie ma błędów PHP, PDO, sesji ani crona w panelu/logach.
