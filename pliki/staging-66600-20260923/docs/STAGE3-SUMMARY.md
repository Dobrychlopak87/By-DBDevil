# Etap 3 — podsumowanie

Etap 3 wykonano wyłącznie na stagingu kopia stagingowa (robocze drzewo projektu); produkcja nie została zmodyfikowana.

Dodano centralne funkcje `base_path()`, `site_url()`, `asset_url()` i `redirect_to()` w `app/Http/Url.php`, wspólne `json_response()` i `json_error()` w `app/Http/Response.php` oraz `html_escape()`, `attribute_escape()` i `url_escape()` w `app/Http/Escaping.php`. Bootstrap ładuje te moduły, a dotychczasowe `redirect()` i `sanitize()` działają jako wrappery kompatybilności.

Kontrakty obejmują instalację w domenie głównej i podkatalogu, kodowanie URL, neutralne nagłówki JSON oraz kontekstowy escaping. Wykryto i naprawiono błąd podwójnego dodawania podkatalogu przy `SITE_URL=https://host/subdir`; test został powtórzony po poprawce.

Walidacja zakończyła się pozytywnie: `STAGE3_CONTRACTS=PASS`, `PHP_LINT=PASS`, `SINGLE_SOURCE_SCAN=PASS` i `COMPATIBILITY_WRAPPERS=PASS`. Istniejące szablony nadal mogą używać `SITE_URL` bezpośrednio; ich migracja będzie wykonywana etapami z testami regresji, aby zachować działające adresy URL.
