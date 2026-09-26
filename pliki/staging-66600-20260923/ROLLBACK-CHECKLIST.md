# Checklist rollbacku

Rollback uruchom, gdy smoke test nie przejdzie, pojawią się błędy 5xx, utrata sesji, błędne dane, niedziałające uploady/cron albo naruszenie bezpieczeństwa.

1. Zatrzymaj dalsze zmiany i zapisz czas, URL, kod odpowiedzi oraz objawy. Nie usuwaj nowej wersji.
2. W panelu nazwa.pl włącz stronę konserwacyjną albo ogranicz dostęp, jeśli jest taka możliwość.
3. Przywróć poprzedni katalog aplikacji z drugiej kopii FTP. Najpierw przywróć entrypointy i kod, a dopiero potem pliki runtime.
4. Przywróć poprzednią prywatną konfigurację. Nie wklejaj sekretów do ticketu, logu ani repozytorium.
5. Przywróć bazę wyłącznie z backupu wykonanego przed wdrożeniem, jeśli migracja zmieniła dane lub schemat. Nie wykonuj ślepego importu bez potwierdzenia checksumy i zgodności wersji.
6. Wykonaj smoke test starej wersji: strona główna, logowanie, odczyt ogłoszenia, formularz testowy, chatroom i panel admina.
7. Sprawdź cron i uprawnienia katalogów zapisywalnych.
8. Wyłącz stronę konserwacyjną dopiero po przejściu testów.
9. Zachowaj nową wersję, logi i raport incydentu do analizy. Nie próbuj ponownie wdrażać bez ustalenia przyczyny.

Rollback plików i rollback bazy są osobnymi operacjami. Przywrócenie samych plików nie cofa migracji bazy.
