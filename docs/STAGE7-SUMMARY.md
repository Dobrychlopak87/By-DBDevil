# Etap 7 — podsumowanie (uploady, pliki publiczne, routing, PWA)

Etap 7 wykonano wyłącznie na kopii stagingowej. Produkcja nie została zmodyfikowana —
żaden plik nie został wysłany na FTP, entrypoint produkcyjny nie został przełączony.

## Wykonane prace

### Centralna polityka uploadu
- Dodano `app/Upload/ImageUpload.php`: kontrola `UPLOAD_ERR_OK`, limit bajtów,
  rzeczywisty MIME z zawartości (finfo), wymiary obrazu, whitelist rozszerzeń
  ze zgodnością rozszerzenie↔MIME, odrzucanie nazw wielokropkowych (podwójne
  rozszerzenia), odrzucanie członów nazw typu php/phar/sh/htaccess/svg, losowa
  nazwa docelowa (oryginalna nazwa ignorowana), zapis atomowy przez plik
  tymczasowy `.part` + rename, miniatury generowane wyłącznie po pomyślnym
  zapisie oryginału, usuwanie oryginału i miniatur z walidacją nazwy.
- `includes/functions.php`: `uploadFile()` i `deleteFile()` pozostają
  cienkimi wrapperami o niezmienionych sygnaturach (kompatybilność wszystkich
  istniejących punktów wywołań do czasu testów regresji).
- `uploadCategoryIconSvg()` zapisuje ikonę SVG atomowo (temp + rename);
  dotychczasowa sanityzacja SVG pozostaje.
- Upload chatroomu (`chatroom/includes/bootstrap.php`) już spełniał politykę
  (finfo, wymiary, losowa nazwa, serwowanie wyłącznie przez `api/image.php`)
  i nie był zmieniany.

### Ochrona katalogów uploadów w paczce
- `assets/uploads/.htaccess` (chroni też `thumbs/`): blokada skryptów
  i handlerów, blokada podwójnych rozszerzeń, `RemoveHandler`/`RemoveType`,
  `Options -Indexes -ExecCGI`, nosniff, CSP `sandbox` dla SVG.
- `assets/uploads/category-icons/.htaccess`: wzmocniona wersja dotychczasowej
  ochrony produkcyjnej (CSP sandbox, blokady handlerów).
- `chatroom/uploads/.htaccess`: pełna blokada bezpośredniego dostępu HTTP
  (obrazy serwuje wyłącznie `chatroom/api/image.php`).

### Routing i `.htaccess`
- Usunięto `RewriteBase /`; wszystkie reguły są względne — działają
  w katalogu głównym i w podkatalogu (zweryfikowane na Apache 2.4).
- Fallback AVIF→JPEG przepisany na `REQUEST_URI` + `DOCUMENT_ROOT`
  (działa w podkatalogu, bez zależności od lokalizacji instalacji).
- Kanoniczny redirect starego adresu kroniki działa również w podkatalogu.
- Kanonizacja hosta i wymuszenie HTTPS przeniesione z mod_rewrite do PHP
  (`enforce_canonical_request()` w `app/Http/Url.php`), ponieważ `SetEnv`
  wykonuje się w fazie późniejszej niż reguły per-dir mod_rewrite (reguły
  ENV były dotychczas martwe). Aktywacja wyłącznie po ustawieniu
  `CANONICAL_HOST` przez operatora.
- Z pakietu usunięto twardo wpisane `SetEnv SITE_URL/CANONICAL_HOST`
  z domeną produkcyjną — są zakomentowanym przykładem dla operatora.

### PWA
- `sw.js` przepisany na ścieżki względne wobec własnej lokalizacji —
  działa w katalogu głównym i w podkatalogu bez zmian w kodzie.
- Cache wyłącznie zasobów statycznych (`assets/`, `manifest.json`,
  `browserconfig.xml`); nigdy nie są przechwytywane ani zapisywane:
  POST, `admin/`, `auth/`, `chatroom/`, katalogi prywatne, endpointy
  dynamiczne (pulse, calendar, głosowania, formularze, pogoda).
- Wersjonowanie cache przez `ASSET_VERSION` (`20260923-stage7`); stare
  cache usuwane przy aktywacji.
- `manifest.json`: `id` zmienione z `/` na `./` (przenośność podkatalogu).

### Naprawy wykryte testem czystej instalacji
- `database/schema.sql`: uzupełniono brakującą kolumnę
  `blog_posts.source_type` (wymagana przez moduł kalendarza; w produkcji
  istnieje, ale nie było jej ani w starym zrzucie, ani w schemacie paczki —
  czysta instalacja kończyła się błędem 500 `calendar-api.php`).
- `database/schema.sql`: zdublowane anonimowe nazwy więzów obcych `1`
  zmienione na jednoznaczne (`fk_ads_category`, `fk_ad_gallery_ad`,
  `fk_blog_posts_category`, `fk_blog_gallery_post`) — import na pustej bazie
  przerywał się błędem errno 121.
- `database/migrate.php`: usunięto pozorne transakcje wokół DDL
  (niejawny commit DDL w MySQL/MariaDB powodował fałszywy błąd
  „There is no active transaction” mimo poprawnego zastosowania migracji).

## Walidacja

Wszystkie testy wykonano 2026-09-23 w środowisku Apache 2.4 + PHP 8.4 + MariaDB.

- `PHP_LINT=PASS` (111 plików)
- `SW_JS_SYNTAX=PASS`
- Macierz uploadu (`tools/test-upload-matrix.php`): 33/33 PASS — JPEG/PNG/GIF/
  WebP/AVIF, PHP przemianowany na .jpg, .php, podwójne rozszerzenia w obie
  strony, SVG ze skryptem i z event handlerem, przekroczony limit bajtów
  i wymiarów, mismatch PNG→.jpg, śmieci binarne, traversal, .part cleanup,
  delete z miniaturami, delete nazw niebezpiecznych.
- Sondy Apache (`tools/probe-stage7.sh`): 52/52 PASS — instalacja w katalogu
  głównym i w podkatalogu, ochrona uploadów (403 dla .php, .php.jpg,
  .jpg.php, bezpośredniego dostępu do chatroom/uploads), fallback AVIF→JPEG,
  CSP sandbox SVG, stare adresy kroniki 301 (root i podkatalog), PWA bez
  cache, pliki wrażliwe zablokowane, kanonizacja hosta i wymuszenie HTTPS,
  endpointy JSON (pulse, kalendarz).
- Migracje idempotentne (drugi przebieg: SKIP).

## Ograniczenia i następny krok

- Ochrona `.htaccess` w katalogach uploadów musi zostać zweryfikowana na
  faktycznym hostingu po wysłaniu paczki stagingowej (bramka Etapu 10).
- Etap 7 nie obejmuje crona (Etap 8) ani instalatora/importera (Etap 9).
- `SHA256SUMS.txt` i `PACKAGE-MANIFEST.tsv` Etapu 6 pozostają w zamrożonym
  archiwum release; w drzewie roboczym zostaną wygenerowane ponownie przy
  składaniu kolejnego release (Etap 10).
