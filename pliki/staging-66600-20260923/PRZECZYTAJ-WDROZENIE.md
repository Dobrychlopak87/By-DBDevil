# Wdrożenie — notatka dla serwera server629599 / 66600.pl (2026-09-23)

Ten katalog (`staging-66600-20260923/`) zawiera paczkę release Etapu 9
(pliki pakietu + ta notatka). Produkcja w katalogu głównym NIE została
zmodyfikowana.

## Co już jest zrobione
- Pełna kopia FTP sprzed wdrożenia: `backup-66600-przed-wdrozeniem-20260923/`
  (z sumami kontrolnymi) + zweryfikowany eksport bazy danych
  `server629599_site66main` z 2026-09-23 08:13 UTC (lokalnie, sha256 zapisane).
- Paczka stage9 wysłana do tego katalogu.

## Blokada dostępu
Katalog jest zablokowany plikiem `.htaccess` (Require all denied).
Aby uruchomić instalator: usuń `.htaccess` z tego katalogu przez FTP.

## Kroki wdrożenia (wg RELEASE-CHECKLIST.md i DEPLOYMENT-RUNBOOK.md)
1. Panel nazwa.pl → Bazy danych: utwórz OSOBNĄ bazę testową, np.
   `server629599_staging66`, użytkownik o tej samej nazwie, nowe hasło
   (zapisz je tylko w menedżerze haseł). Kodowanie: utf8mb4.
2. Usuń `.htaccess` z tego katalogu (patrz wyżej).
3. Otwórz `https://66600.pl/staging-66600-20260923/installer/` i przejdź
   8 kroków instalatora (dane nowej bazy testowej, adres instalacji
   `https://66600.pl/staging-66600-20260923`, konto administratora).
4. Importer: `https://66600.pl/staging-66600-20260923/importer/`
   — dane bazy produkcyjnej `server629599_site66main` (host
   `mariadb123.server629599.nazwa.pl`), najpierw tryb podglądu.
   Uwaga: importer korzysta z `installer/lib/` — usuń katalog `importer/`
   i dopiero potem ewentualnie `installer/`.
5. Smoke test stagingu (strona główna, logowanie, ogłoszenia, kronika,
   kalendarz, ankiety, Puls, chatroom, panel admina).
6. Zadania cron: panel nazwa.pl → cron — konfiguracja w `docs/cron.md`.
7. Przełączenie produkcji: WYŁĄCZNIE po pozytywnym smoke teście i osobnej
   decyzji — patrz DEPLOYMENT-RUNBOOK.md (rozdział 5).

## Rollback
- Pliki: kopia `backup-66600-przed-wdrozeniem-20260923/` oraz oryginalny
  katalog produkcyjny (nienaruszony do momentu przełączenia).
- Baza: zweryfikowany eksport z 2026-09-23; przed jakimkolwiek zapisem do
  bazy produkcyjnej wykonaj kolejny eksport.
- Procedura: ROLLBACK.md.
