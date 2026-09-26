# Poker Polski — Texas Hold’em online

**Poker Polski** jest kompletną, polskojęzyczną grą online Texas Hold’em dla **2–4 osób**, przygotowaną do przesłania przez FTP na zwykły hosting PHP. Serwerowa mechanika używa PHP i MySQL/MariaDB; po instalacji nie są potrzebne Node.js, SSH, Composer ani konsola.

> Gra działa wyłącznie na **punktach wirtualnych**. Nie obsługuje pieniędzy, wpłat, wypłat ani płatności.

| Obszar | Działanie |
| --- | --- |
| Wejście do gry | Gracz wpisuje tylko nick — bez obowiązkowego konta i hasła. |
| Gość tymczasowy | Nick, punkty i sesja są usuwane po **24 godzinach braku aktywności**. |
| Rezerwacja nicku | Gość może opcjonalnie zarezerwować aktualny nick, ustawiając hasło. |
| Odzyskanie hasła | Celowo niedostępne. Nie ma e-maili, resetu ani możliwości administracyjnego odzyskania hasła. |
| Liczba graczy | Od 2 do 4 osób przy jednym stole. |
| Start rozgrywki | Automatycznie, gdy przy stole znajdują się co najmniej dwie osoby. |
| Stawki | Mała ciemna 10 pkt, duża ciemna 20 pkt. |
| Punkty | 5 000 pkt na start i odnowienie do 5 000 pkt co 24 godziny. |
| Czas decyzji | 30 sekund na ruch, liczony i egzekwowany przez serwer. |
| Brak decyzji | Automatyczne czekanie bez zakładu albo pas, gdy trzeba wyrównać zakład. |
| Oprawa rozgrywki | Animacje rozdania, żetonów w puli i wygranej oraz przełączane dźwięki akcji. |
| Zaproszenie do stołu | Ikona linku w każdej karcie lobby (oraz w nagłówku stołu) kopiuje lub udostępnia adres wybranego stołu. |

## Wymagania hostingu

Potrzebny jest hosting z **PHP 7.4+**, rozszerzeniami `PDO` i `pdo_mysql`, a także jedna baza MySQL lub MariaDB. Zalecane jest PHP 8.1 albo nowsze. W panelu hostingu utwórz bazę, użytkownika bazy oraz nadaj temu użytkownikowi pełne uprawnienia do tej konkretnej bazy.

| Dana do instalacji | Skąd ją wziąć |
| --- | --- |
| Adres serwera MySQL | Panel hostingu → Bazy danych; bardzo często `localhost`. |
| Nazwa bazy | Panel hostingu → Bazy danych. |
| Użytkownik bazy | Panel hostingu → Użytkownicy MySQL. |
| Hasło bazy | Ustawione podczas tworzenia użytkownika bazy. |

## Instalacja przez FTP (bez konsoli i SSH)

1. Rozpakuj archiwum na komputerze — powstanie gotowy katalog `poker/`.
2. Prześlij przez FTP **cały katalog `poker`** do głównego katalogu strony (np. `public_html/`), tak aby powstało `public_html/poker/`.
3. Otwórz w przeglądarce adres swojej strony z dopiskiem `/poker/` — przy pierwszym wejściu gra sama przekieruje do instalatora.
4. Instalator sprawdzi serwer (wersja PHP, PDO MySQL, możliwość zapisu `config.php`). Wpisz dane bazy MySQL z panelu hostingu i wybierz **Zainstaluj Poker Polski**.
5. Gotowe — wybierz **Przejdź do gry**, podaj nick i graj.

Po instalacji instalator blokuje się automatycznie (każde kolejne wejście przekierowuje do gry). Usunięcie `install.php` przez FTP jest zalecane, ale nie jest wymagane.

Aplikacja używa wyłącznie ścieżek względnych, więc działa w podfolderze `poker/` (i w każdym innym) bez żadnej konfiguracji. Nie korzysta z zewnętrznych czcionek, bibliotek ani CDN — wszystkie zasoby są w archiwum. Ciasteczko sesji jest ograniczone do katalogu gry, więc nie koliduje z innymi aplikacjami na tej samej domenie.

Instalator utworzy tabele bazy danych, startowy **Stół Warszawa**, plik konfiguracji oraz indywidualny sekret dla harmonogramu sprzątania sesji gości. Jeżeli hosting blokuje zapis `config.php` (instalator pokaże to na liście kontrolnej), ustaw tymczasowo prawo zapisu dla katalogu `poker` w kliencie FTP (np. 755) i odśwież instalator. Nie używaj `777`, jeśli hosting nie wymaga tego wyjątkowo.

## Nick tymczasowy i rezerwacja nazwy

Domyślny przebieg jest bardzo prosty: osoba podaje nick, otrzymuje 5 000 punktów i przechodzi do lobby. Niczego nie rejestruje, nie przekazuje e-maila i nie ustawia hasła. Każde działanie w aplikacji odnawia jej aktywność. Jeżeli przez 24 godziny nie będzie żadnej aktywności, tymczasowy gracz — wraz z jego nickiem i punktami — zostanie usunięty z bazy. Nick wraca wtedy do puli dostępnych nazw.

Osoba, która chce zachować nazwę, wybiera w lobby **Zarezerwuj swój nick**. Ustawia hasło i zaznacza obowiązkowe potwierdzenie, że nie będzie możliwe jego odzyskanie. Po rezerwacji nick staje się trwały, a przy kolejnym wejściu należy wybrać zakładkę **Mam zarezerwowany nick** i podać nick oraz hasło.

> Administrator nie ma funkcji odzyskiwania, zmiany ani resetowania haseł. Utrata hasła oznacza trwałą utratę dostępu do zarezerwowanego nicku.

## Rzeczywiste usuwanie nieaktywnych gości

Aplikacja usuwa wygasłych gości automatycznie przy zwykłym ruchu na stronie. Aby zapewnić usuwanie dokładnie w tle także wtedy, gdy nikt nie odwiedza gry, ustaw w panelu hostingu zadanie URL/CRON wykonywane co godzinę. Nie wymaga to dostępu do konsoli.

Po instalacji otwórz przez FTP plik `config.php` i odczytaj wartość `cleanup_key`. Następnie dodaj w narzędziu harmonogramu panelu hostingu wywołanie adresu:

```text
[adres Twojej strony]/poker/cleanup.php?key=TWÓJ_CLEANUP_KEY
```

Ustaw częstotliwość **raz na godzinę**. Ten adres jest chroniony sekretnym kluczem. Nie publikuj go i nie udostępniaj osobom trzecim. Jeżeli hosting nie oferuje zadania URL/CRON, goście nadal będą bezpiecznie usuwani przy pierwszym następnym wejściu kogokolwiek do aplikacji.

Jeżeli wygasły gość siedział przy aktywnym stole, bieżąca ręka zostaje anulowana bezpiecznie, a zakłady pozostałych osób zostają im zwrócone. Stół wraca do stanu oczekiwania.

## Wielki Szu — gra z komputerem (wersja 8)

Wielki Szu to komputerowy przeciwnik działający **w całości na Twoim serwerze** — czysty PHP, bez usług zewnętrznych, bibliotek z CDN ani połączeń z internetem.

**Jak zagrać**
- W lobby karta „Wielki Szu”: wybierz *1 na 1*, *2 × Szu* lub *3 × Szu* i kliknij „Zagraj z Wielkim Szu”.
- Przy dowolnym stole (między rozdaniami) kliknij puste miejsce „Wielki Szu · Dosadź” albo przycisk „Zagraj z Wielkim Szu” — ludzie i komputer grają razem.
- Krzyżyk przy tabliczce komputera odprawia go (między rozdaniami). Gdy odejdzie ostatni człowiek, Szu też odchodzi, a stół utworzony przyciskiem „Zagraj z Wielkim Szu” jest usuwany.

**Jak myśli**
- Szybka ocena układów (ok. 1 µs na rękę, zgodność 100% z silnikiem gry sprawdzona na 200 000 porównań).
- Tablica siły 169 rąk startowych wyliczona offline (200 000 symulacji na pozycję) — plik `includes/szu_preflop.php`.
- Symulacja Monte Carlo (do 14 000 rozdań w ≤ 0,35 s) przeciwko **zakresom** rąk rywali, zawężanym bayesowsko po ich akcjach (podbicie, sprawdzenie, czekanie na każdej ulicy).
- Model przeciwnika: Szu zapamiętuje styl każdego gracza (VPIP, PFR, agresja, pasowanie na zakład) w tabeli `poker_szu_stats` i dostosowuje zakresy oraz częstotliwość blefów.
- Strategia: pozycja, pot odds i implied odds, efektywny stos i SPR, faktura stołu, c-bet, semi-blef, blef na riverze, slowplay, push/fold na krótkim stosie, losowe mieszanie decyzji.
- Uczciwość: Szu zna tylko własne karty, karty wspólne i publiczne akcje — nigdy talii ani kart rywali. Podlega dokładnie tym samym regułom (wspólna funkcja `applyPlayerAction`).
- Naturalne tempo: 1,2–2,9 s „namysłu” przed ruchem; stan „Wielki Szu myśli…”.

**Zasady dodatkowe**
- Komputer dokupuje żetony, gdy zabraknie mu na ciemne — gra może trwać bez końca. Punkty ludzi działają jak dotąd.
- Jeśli człowiek przy stole z komputerem nie wykona ruchu dwa razy z rzędu, gra zostaje wstrzymana (przycisk „Gram dalej”), aby nie tracił punktów pod nieobecność.
- Nick „Wielki Szu” jest zastrzeżony. Konta komputera mają typ `bot` (migracja bazy wykonuje się automatycznie, bez konsoli).

**Testy przed wydaniem** (lokalnie, `tools/arena.py`): 150 rąk 1 na 1 przeciw pięciu stylom gry (calling station, maniak, skała, TAG, losowy) i 80 rąk przeciw 3 × Szu — Szu wygrał każdy pojedynek, 0 błędów, mediana decyzji ok. 60–100 ms.

## Wygląd i integracja z serwisem 66600.PL (wersja 7)

**Motyw.** Interfejs korzysta z palety i elementów serwisu 66600.PL: jasne tło, granatowe teksty, brzoskwiniowe przyciski główne, granatowe przyciski nawigacyjne (w trybie ciemnym jasnoniebieskie), karty z cienką ramką i zaokrągleniem 16–18 px, logo w kroju Orbitron. Są dwa motywy — **jasny i ciemny**. Domyślnie obowiązuje ustawienie systemu, a przełącznik (ikona księżyca/słońca) zapisuje wybór pod tym samym kluczem co Chatroom (`66600-theme`), więc motyw jest wspólny dla gry i czatu. Czcionki i ikonę serwisu gra wczytuje ścieżką względną (`../assets/...`) tylko wtedy, gdy działa w jego podkatalogu. Samodzielnie używa czcionek systemowych.

**Wspólne logowanie.** Gdy gra leży w podkatalogu serwisu (np. `/poker/`), plik `includes/site_bridge.php` odczytuje sesję strony (tylko odczyt, bez wysyłania jej ciasteczka):
- użytkownik zalogowany na stronie (konto publiczne lub administrator) od razu gra pod tym samym loginem, bez osobnego logowania;
- aktywność w grze podtrzymuje sesję strony, więc gra nie powoduje wylogowania z serwisu;
- wylogowanie na stronie kończy też sesję w grze;
- konto jest weryfikowane w bazie serwisu co 5 minut;
- nick należący do konta serwisu nie może zostać użyty przez gościa;
- jeśli taki nick był wcześniej zajęty przez gościa, gość dostaje przyrostek (np. `Nick_12`).

Integrację można wyłączyć wpisem `'site_integration' => false` w `config.php`.

**Zaproszenie na Chatroom.** Przy stole z wolnym miejscem dostępny jest przycisk **„Zaproś kogoś z chatroom”**: na środku stołu, gdy stół czeka na graczy, oraz stale w górnym pasku. Wysyła on do Chatroomu serwisu wiadomość z linkiem do stołu (`?stol=ID`) od użytkownika „Stół pokerowy”. Limity chronią przed spamem: jedno zaproszenie na 2 minuty dla stołu i na minutę dla gracza. W Chatroomie linki prowadzące do serwisu są klikalne.

**Migracja bazy** wykonuje się automatycznie przy pierwszym wejściu po aktualizacji: typ konta `site`, kolumna `site_uid`, nicki do 32 znaków, tabele `poker_meta` i `poker_chat_invites`. Nie trzeba ponownie uruchamiać instalatora.

## Interfejs (wersja 6)

Interfejs został przebudowany od podstaw:

| Element | Działanie |
| --- | --- |
| Stół | Skaluje się do wielkości ekranu; na telefonach i tabletach w pionie stół jest pionowy, na komputerze — poziomy. Nic nie nachodzi na nagłówek ani na panel akcji. |
| Twoje miejsce | Zawsze na dole stołu, z dużymi kartami. Pozostali gracze rozmieszczeni są wokół zgodnie z kolejnością miejsc. |
| Gracze | Awatar z pierścieniem odliczającym 30 s, stos punktów, ostatnia akcja (Czeka, Sprawdza, Podbija, Pas, All-in, ciemne). |
| Zakłady | Żetony z kwotą leżą na suknie przed graczem; po zakończeniu rundy „lecą” do puli. Przycisk rozdającego **D** jest widoczny na stole. |
| Panel akcji | Pas / Czekaj lub Sprawdź / Podbij (Postaw). Suwak i pole kwoty oraz szybkie przyciski **Min, ½ puli, Pula, Max**. Na komputerze skróty klawiszowe **F**, **C**, **R**. |
| Twój układ | Bieżąca podpowiedź układu (np. „Dwie pary”) liczona w przeglądarce — rozstrzygnięcie zawsze wykonuje serwer. |
| Wynik | Bez zasłaniającego okna: zwycięzca jest podświetlony, obok widać wygraną i układ, a karty graczy biorących udział w odkryciu są odsłonięte. |
| Przebieg gry | Dziennik akcji z historią kilku ostatnich rozdań i ściągawka układów; na mniejszych ekranach jako wysuwany panel. |
| Lobby | Karty stołów z podglądem zajętych miejsc, automatyczne odświeżanie co kilka sekund, szybki powrót do własnego stołu, link zaproszenia przy każdym stole. |
| Powrót do gry | Po odświeżeniu strony gracz siedzący przy stole wraca od razu do rozgrywki. |

Poprawka prywatności: przy wygranej po spasowaniu przeciwników karty nie są już ujawniane, a przy odkryciu kart nie pokazują się karty graczy, którzy wcześniej spasowali.

## Rozgrywka

Jedna osoba może zajmować miejsce przy jednym stole. W lobby można wejść do oczekującego stołu albo stworzyć własny. Stół pomieści maksymalnie cztery osoby. Po zebraniu dwóch osób gra uruchamia rozdanie automatycznie.

Każda karta stołu w lobby zawiera przycisk z ikoną linku (dostępny też w nagłówku stołu i jako „Zaproś znajomych” podczas oczekiwania). Na telefonie otwiera on systemowy panel udostępniania, a na komputerze kopiuje adres do schowka. Osoba, która otworzy taki adres, trafi do lobby z wyróżnionym stołem i może kliknąć **Dołącz**, gdy stół oczekuje oraz ma wolne miejsce. Link zawiera wyłącznie publiczny identyfikator stołu — nie przekazuje sesji, hasła, punktów ani kart graczy.

Serwer obsługuje rozdanie kart własnych, małą i dużą ciemną, pozycję rozdającego, przedflop, flop, turn, river, odkrycie kart oraz kolejne rozdania. Dostępne są akcje **pas**, **czekaj**, **sprawdź** i **podbij**. Gra poprawnie obsługuje `ALL IN`, pule poboczne, podział puli przy remisie i wygraną po spasowaniu wszystkich przeciwników.

Każdy ruch ma **30 sekund**. Osoba, której kolej właśnie trwa, otrzymuje złote wyróżnienie miejsca i pierścień odliczający czas wokół awatara, a w nagłówku stołu widoczny jest okrągły zegar. Ostatnie 10 sekund przechodzi w czerwone ostrzeżenie. Po upływie czasu serwer sam wykonuje akcję bezpieczną dla zasad: **czekanie**, jeżeli nie ma zakładu do wyrównania, albo **pas**, jeżeli zakład istnieje. Dzięki temu stół nie blokuje się, nawet gdy uczestnik zamknie kartę przeglądarki.

Karty są animowane przy rozdaniu i odsłanianiu kart wspólnych, żetony poruszają się do puli po zmianie zakładu, a zwycięstwo otrzymuje krótką celebrację. Przycisk głośnika w nagłówku włącza lub wyłącza lekkie dźwięki rozdania, żetonów, pasowania, końcówki czasu i zwycięstwa. Ustawienie jest pamiętane lokalnie w przeglądarce.

Na telefonie Twoje prywatne karty są wyświetlane w powiększeniu przy Twoim miejscu na dole stołu; karty innych graczy pozostają rewersami aż do prawidłowego odkrycia przy wyniku rozdania.

| Układ kart | Obsługa |
| --- | --- |
| Poker królewski i poker | Tak |
| Kareta, full, kolor, strit | Tak |
| Trójka, dwie pary, para, wysoka karta | Tak |
| As jako niska karta A–2–3–4–5 | Tak |
| Remisy i podział puli | Tak |
| Pule poboczne przy `ALL IN` | Tak |

## Struktura plików

```text
poker/
├── api.php                       # Konto gościa, rezerwacja nicku, lobby i akcje gry
├── cleanup.php                   # Chronione wywołanie harmonogramu usuwania gości
├── config.php                    # Dane MySQL i sekret sprzątania; tworzone przez instalator
├── index.php                     # Polski interfejs gry
├── install.php                   # Instalator w przeglądarce
├── includes/
│   ├── .htaccess                 # Blokada dostępu z przeglądarki
│   ├── index.php                 # Blokada listowania katalogu
│   ├── bootstrap.php             # Silnik gry, sesje i reguły
│   ├── szu.php                   # Wielki Szu — sztuczna inteligencja (v8)
│   ├── szu_preflop.php           # Tablica siły rąk startowych (offline)
│   └── site_bridge.php           # Wspólne logowanie i Chatroom (v7)
├── assets/
│   ├── app.js                    # Interaktywny klient oraz odświeżanie online
│   └── style.css                 # Responsywny interfejs (bez zewnętrznych czcionek)
└── .htaccess                     # Podstawowe zabezpieczenia Apache
```

## Bezpieczeństwo i utrzymanie

Hasła zarezerwowanych nicków są przechowywane jako hashe przez `password_hash`. Aplikacja używa zapytań przygotowanych PDO, tokenów CSRF, sesji HTTP-only oraz nie wysyła kart przeciwnika do przeglądarki. Talia i prywatny stan gry pozostają po stronie PHP. Adres zaproszenia do stołu jest celowo publiczny i zawiera jedynie numer stołu; dołączenie nadal podlega normalnej kontroli limitu czterech miejsc oraz stanu stołu.

Włącz HTTPS w panelu hostingu i wykonuj kopie bazy danych. Po wdrożeniu można usunąć `install.php` (instalator i tak blokuje się sam po instalacji). Nie edytuj ręcznie `state_json` w tabeli `poker_tables` w trakcie gry.

## Walidacja wydania

Sprawdzono składnię plików PHP oraz JavaScript. Automatyczny test silnika pokrywa między innymi pokera królewskiego, karetę, fulla, strita A–2–3–4–5 i dwie pary. Test integracyjny obejmuje instalację, wejście gościa, rezerwację nicku, ponowne wejście hasłem, automatyczny start stołu oraz usunięcie gościa po 24 godzinach nieaktywności. Wersja 6 została dodatkowo przetestowana w przeglądarce na PHP 8.4 i MariaDB (instalacja, lobby, stół dla 2 i 4 graczy, podbicia, odkrycie kart) w rozdzielczościach: komputer, tablet pionowo, telefon pionowo i poziomo. Przeprowadzono również kontrolę wizualną ekranu wejścia, lobby, rezerwacji nicku, oczekiwania przy stole, aktywnej ręki, własnej tury, zegara 30 sekund i automatycznego rozstrzygnięcia po bezczynności.
