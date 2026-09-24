# Rollback 66600.PL

Rollback uruchom, gdy smoke test nie przejdzie, pojawią się błędy 5xx, utrata sesji,
błędne dane, niedziałające uploady/cron albo naruszenie bezpieczeństwa.

## Zasady

- Rollback plików i rollback bazy to dwie osobne operacje — przywrócenie samych
  plików nie cofa migracji bazy.
- Nie usuwaj nowej wersji przed wyjaśnieniem przyczyny.
- Zawsze przywracaj z backupu o znanej, zweryfikowanej sumie kontrolnej.

## Procedura krok po kroku

1. Zatrzymaj dalsze zmiany; zapisz czas, URL, kod odpowiedzi i objawy.
2. W panelu nazwa.pl włącz stronę konserwacyjną albo ogranicz dostęp, jeśli jest
   taka możliwość.
3. Przywróć poprzedni katalog aplikacji z drugiej kopii FTP — najpierw entrypointy
   i kod, dopiero potem pliki runtime (sesje, cache, uploady).
4. Przywróć poprzednią prywatną konfigurację (`.private/66-600-security-config.php`).
   Nie wklejaj sekretów do ticketów, logów ani repozytorium.
5. Bazę przywracaj wyłącznie z backupu wykonanego przed wdrożeniem i tylko wtedy,
   gdy migracja zmieniła schemat lub dane. Potwierdź sumę kontrolną backupu
   przed importem.
6. Wykonaj smoke test starej wersji: strona główna, logowanie, odczyt ogłoszenia,
   formularz testowy, chatroom i panel administratora.
7. Sprawdź zadania cron w panelu i prawa zapisu katalogów runtime.
8. Wyłącz stronę konserwacyjną dopiero po przejściu testów.
9. Zachowaj nową wersję, logi i raport incydentu do analizy. Nie wdrażaj ponownie
   bez ustalenia przyczyny.

## Wycofanie samej instalacji (przed przełączeniem)

Jeżeli nowa instalacja działa w osobnym katalogu i nie została przełączona:

1. Usuń katalog nowej instalacji przez FTP albo pozostaw go bez zmian —
   produkcja nadal działa na starym entrypoincie.
2. Usuń ewentualną testową bazę danych w panelu hostingu.
3. Zachowaj backupy i raport z testów.
