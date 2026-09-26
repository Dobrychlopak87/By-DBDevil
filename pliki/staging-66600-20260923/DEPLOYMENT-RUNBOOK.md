# Runbook wdrożenia na nazwa.pl

## Zakres i zasada bezpieczeństwa

Dokument opisuje przygotowanie i kontrolowane wdrożenie przez FTP na nazwa.pl. Nie zawiera danych logowania. Hasła należy pobierać wyłącznie z bezpiecznego magazynu operatora. Ten runbook nie upoważnia do przełączenia produkcji bez pozytywnego smoke testu i osobnej decyzji operacyjnej.

## 1. Przygotowanie

Zamroź wersję release i zapisz checksumy. Wykonaj drugą pełną kopię bieżącego drzewa FTP oraz świeży eksport bazy. Zachowaj starą konfigurację, stare pliki i backup bazy. Ustal katalog produkcyjny oraz osobny katalog stagingowy, na przykład `staging-66600-20260923`, bez nadpisywania bieżącego entrypointu.

W panelu nazwa.pl sprawdź wersję PHP, rozszerzenia PDO MySQL, ustawienia katalogu publicznego, zadania cron i dostępne mechanizmy backupu. Przygotuj prywatny plik konfiguracji poza paczką. Nie wysyłaj `.env`, `.private`, `database.sql`, sesji, logów ani danych z backupu.

## 2. Wysłanie stagingu przez FTP

Połącz się z `ftp.server629599.nazwa.pl` wyłącznie z użyciem skonfigurowanego klienta FTP i bez zapisywania hasła w skrypcie. Utwórz osobny katalog stagingowy. Wyślij zawartość paczki wraz z `SHA256SUMS.txt` i manifestem. Zachowaj tryb binarny dla obrazów, fontów, audio i archiwów.

Po transferze porównaj liczbę plików oraz checksumy, o ile klient/panel to umożliwia. Sprawdź, że `.private`, `.env`, dumpy SQL, sesje i logi nie trafiły na staging. Umieść prywatną konfigurację w lokalizacji niepublicznej i ustaw prawa zapisu tylko dla katalogów runtime.

## 3. Instalacja i baza testowa

Utwórz osobną bazę testową. Nie używaj produkcyjnej bazy do pierwszego uruchomienia. Wykonaj schema/migracje zgodnie z wersją release. Installer/importer z Etapu 9 powinien być uruchamiany jawnie, a po zakończeniu powinien utworzyć `install.lock` i zostać zablokowany.

Jeśli instalator nie jest jeszcze dostępny, zatrzymaj wdrożenie na tym kroku. Nie obchodź bramki przez ręczne kopiowanie produkcyjnego dumpa do release.

## 4. Smoke test stagingu

Sprawdź stronę główną, adresy w domenie głównej i podkatalogu, logowanie publiczne, sesje, ogłoszenia, kronikę, kalendarz, ankiety, Puls, chatroom, panel administratora, uploady oraz odpowiedzi JSON. Zweryfikuj brak błędów 5xx, poprawne nagłówki bezpieczeństwa, brak dostępu do `.private`, `config.php`, SQL i katalogów runtime. Uruchom zadania cron w trybie testowym i sprawdź retencję bez danych produkcyjnych.

## 5. Przełączenie produkcji — dopiero po akceptacji

Wykonaj drugą kopię backupową bezpośrednio przed przełączeniem. Zachowaj starą wersję pod odrębną nazwą. Przełącz entrypoint dopiero po formalnym potwierdzeniu smoke testu stagingowego. Preferuj zmianę katalogu/entrypointu nad usuwanie starej wersji. Nie kasuj starego katalogu ani backupu do czasu zakończenia okresu obserwacji.

Po przełączeniu wykonaj krótki smoke test produkcyjny i sprawdź logi oraz panel crona. Jeśli test nie przejdzie, użyj `ROLLBACK-CHECKLIST.md`. Rollback plików i bazy traktuj jako dwie osobne operacje.

## 6. Warunek zakończenia

Wdrożenie jest zakończone dopiero wtedy, gdy checksumy paczki są zapisane, smoke test produkcyjny przechodzi, cron działa, stara wersja i backup są zachowane, a raport wdrożenia zawiera czas, wersję, wynik testów i decyzję o pozostawieniu lub usunięciu starej wersji.

## Odniesienia

Plan modernizacji zatwierdzony dla projektu pozostaje dokumentem nadrzędnym procesu. Operacyjne instrukcje wdrożenia: [INSTALL.md](INSTALL.md) (instalator), [MIGRATION.md](MIGRATION.md) (import danych), [ROLLBACK.md](ROLLBACK.md) (wycofanie), [SERVER-REQUIREMENTS.md](SERVER-REQUIREMENTS.md) (wymagania), [docs/cron.md](docs/cron.md) (zadania okresowe). W paczce dostępne są też [instrukcja Docker](DOCKER.md) oraz podsumowania etapów w `docs/`.
