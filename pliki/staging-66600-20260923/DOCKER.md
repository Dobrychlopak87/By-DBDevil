# Konteneryzacja stagingu

## Wymagania

Docker Engine z Compose v2 oraz co najmniej 2 GB wolnego miejsca. W tej sesji Docker nie był zainstalowany, dlatego pliki zostały zwalidowane statycznie, ale obrazów nie zbudowano.

## Pierwsze uruchomienie

```sh
cp .env.example .env
# Uzupełnij .env: DB_PASSWORD, DB_ROOT_PASSWORD i PULSE_RATE_HASH_SECRET.
# Wygeneruj sekret co najmniej 32 znaków, np. openssl rand -hex 32.
docker compose config
docker compose up -d --build
```

Aplikacja będzie dostępna pod `http://localhost:${APP_PORT:-8080}`. MariaDB nie jest publikowana na hoście; komunikuje się z aplikacją wyłącznie w sieci Compose. Przy pierwszym uruchomieniu `database/schema.sql` jest ładowany przez obraz MariaDB, a następnie należy uruchomić migracje aplikacyjne zgodnie z procedurą projektu.

## Operacje

```sh
docker compose ps
docker compose logs --tail=100 app
docker compose exec app php database/migrate.php
docker compose exec db mariadb-dump -u root -p "$DB_NAME" > backup.sql
docker compose down
```

Nie commituj `.env`, dumpów, logów ani wolumenów. Sekrety są wstrzykiwane do entrypointu i zapisywane wyłącznie w `/var/www/private`, poza katalogiem publicznym. Wolumeny sesji, cache i uploadów są odseparowane od obrazu aplikacji.

## Konfiguracja środowisk

- `APP_ENV` i `SITE_URL` powinny być ustawiane osobno dla developmentu, stagingu i produkcji.
- `SESSION_COOKIE_SECURE=1` należy stosować za HTTPS.
- `DB_HOST` w Compose pozostaje nazwą usługi `db`; przy zewnętrznej bazie należy użyć prywatnego hosta i nie montować produkcyjnych sekretów do obrazu.
- Produkcja nie jest uruchamiana ani przełączana przez ten plik.
