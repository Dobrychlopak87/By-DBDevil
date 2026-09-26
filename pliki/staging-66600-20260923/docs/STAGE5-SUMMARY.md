# Etap 5 — podsumowanie

Etap 5 wykonano na stagingu kopia stagingowa (robocze drzewo projektu); nie budowano ani nie uruchamiano kontenerów produkcyjnych.

## Wykonane prace

Dodano obraz PHP 8.3/Apache z rozszerzeniami PDO MySQL, mbstring i OPcache oraz modułami Apache rewrite i headers. Dodano `docker-compose.yml` z usługami `app` i MariaDB 11.4, healthcheckiem bazy, siecią Compose, restartem usług i wolumenami dla bazy, sesji, cache oraz uploadów.

EntryPoint kontenera generuje prywatny plik konfiguracji z sekretów środowiskowych w `/var/www/private`, poza katalogiem publicznym. Loader konfiguracji obsługuje `APP_PRIVATE_CONFIG_PATH`, a sesje obsługują `SESSION_STORAGE_PATH`, dzięki czemu runtime nie zależy od lokalnej ścieżki hosta.

Dodano `.env.example`, profile `config/environments/staging.env.example` i `config/environments/production.env.example`, `.dockerignore`, konfigurację Apache oraz instrukcję `DOCKER.md`. Wszystkie profile zawierają wyłącznie placeholdery i przykładowe hosty.

## Walidacja

`SECRET_SCAN=PASS`, `ENV_INTEGRATION=PASS`, `ISOLATION_AND_VOLUMES=PASS` i `PHP_LINT=PASS`. Docker nie jest zainstalowany w sandboxie, dlatego `docker compose config` i build obrazu pozostają do wykonania na hoście z Docker Engine. Nie traktuję braku lokalnego builda jako dowodu gotowości produkcyjnej.

## Ograniczenie i następny krok

Przed użyciem należy skopiować odpowiedni profil do `.env`, wygenerować silne sekrety, uruchomić `docker compose config`, zbudować obrazy, wykonać migracje i smoke testy na osobnej bazie. Plik Compose nie publikuje portu MariaDB na hoście i nie zawiera produkcyjnych danych.
