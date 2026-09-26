# Wymagania serwera — 66600.PL

## Wymagane

| Element | Minimum | Uwagi |
| --- | --- | --- |
| PHP | 8.1 | zalecane 8.2+ |
| Rozszerzenia PHP | pdo, pdo_mysql, json, fileinfo, mbstring, openssl | sprawdzane przez instalator |
| Baza danych | MySQL 5.7+ / MariaDB 10.4+ | utf8mb4 |
| Serwer WWW | Apache 2.4 z mod_rewrite | `.htaccess` (AllowOverride All) |
| Pamięć | typowy hosting współdzielony | bez specjalnych wymagań |

## Opcjonalne (nie blokują instalacji)

| Element | Rola | Fallback |
| --- | --- | --- |
| gd (z AVIF) | miniatury i konwersja do AVIF | upload w oryginalnym formacie |
| curl | aktualizacja pogody z cache | neutralny komunikat pogodowy |

## Niedozwolone jako zależności

SSH/terminal, Composer, npm, Node.js, zewnętrzne CDN, biblioteki ładowane z sieci.
Całe wdrożenie musi być możliwe przez FTP oraz przeglądarkę.

## Katalogi zapisywalne

`session/`, `assets/uploads/` (w tym `thumbs/`, `category-icons/`),
`chatroom/uploads/`, `.private/` (tworzony przez instalator).

## Zadania okresowe

Konfiguracja wyłącznie przez panel hostingu — szczegóły w `docs/cron.md`.
