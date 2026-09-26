<?php
// Polityka prywatności — część publiczna serwisu 66600.PL.
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Polityka prywatności';
$pageDescription = 'Informacje o przetwarzaniu danych osobowych, treściach publikowanych przez użytkowników i plikach cookie w serwisie 66600.PL.';
$canonicalUrl = SITE_URL . '/privacy-policy.php';
$pageType = 'WebPage';
$pageRobots = 'index, follow';

header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/includes/header.php';
?>

<section class="legal-page" aria-labelledby="privacy-policy-title">
    <header class="legal-page__header">
        <p class="legal-page__eyebrow">Informacje prawne</p>
        <h1 id="privacy-policy-title">Polityka prywatności</h1>
        <p>Obowiązuje od 12 sierpnia 2026 r.</p>
    </header>

    <div class="legal-page__content">
        <div class="legal-page__notice" role="note">
            <strong>Nie publikuj danych, których nie chcesz udostępniać publicznie.</strong>
            <span>Treści ogłoszeń i komunikatów Pulsu miasta mogą być widoczne dla wszystkich odwiedzających serwis oraz indeksowane przez wyszukiwarki internetowe.</span>
        </div>

        <h2>1. Administrator danych</h2>

        <p>
            Administratorem danych osobowych przetwarzanych w serwisie jest
            <strong>administrator strony <?= sanitize(SITE_URL) ?></strong>,
            prowadzący serwis pod adresem
            <a href="<?= SITE_URL ?>"><?= SITE_URL ?></a>,
            dalej zwany „Administratorem”.
        </p>

        <p>
        </p>

        <h2>2. Dane przetwarzane w serwisie</h2>

        <p>
            Serwis nie wymaga podawania danych osobowych w celu opublikowania ogłoszenia.
            Pola formularza mają charakter dobrowolny, a użytkownik sam decyduje, jakie treści,
            informacje, zdjęcia lub dane kontaktowe umieszcza w ogłoszeniu.
        </p>

        <p>
            Dane osobowe mogą zostać przetworzone wyłącznie wtedy, gdy użytkownik dobrowolnie umieści je
            w treści ogłoszenia, wiadomości, formularzu kontaktowym, nazwie, opisie, zdjęciu lub innym
            publikowanym materiale. Dane mogą obejmować między innymi imię, nazwisko, numer telefonu,
            adres e-mail, adres, wizerunek albo inne informacje pozwalające na identyfikację osoby.
        </p>

        <p>
            Serwis może również przetwarzać dane techniczne niezbędne do działania i zabezpieczenia strony,
            takie jak adres IP, data i godzina wejścia, dane przeglądarki, identyfikatory sesji,
            informacje o urządzeniu oraz logi serwera.
        </p>

        <h3>Puls miasta</h3>

        <p>
            Puls miasta umożliwia dodawanie krótkich, lokalnych komunikatów pilnych. Treść komunikatu jest
            publikowana od razu i może zawierać opcjonalny podpis oraz numer telefonu. Te informacje są
            widoczne publicznie, dlatego należy podawać wyłącznie dane, które użytkownik chce udostępnić
            wszystkim odwiedzającym serwis. Moduł nie służy do publikowania reklam.
        </p>

        <p>
            Komunikat Pulsu miasta jest widoczny publicznie przez 12 godzin. Dla zapewnienia limitów
            publikacji i ochrony przed nadużyciami serwis zapisuje czas publikacji oraz techniczny skrót
            identyfikatora IP. Skrót ten nie jest prezentowany publicznie.
        </p>

        <h2>3. Treści publikowane przez użytkowników</h2>

        <p>
            Użytkownik, który publikuje ogłoszenie, wpis lub inną treść, samodzielnie odpowiada za jej
            zgodność z prawem, prawdziwość, aktualność oraz za posiadanie prawa do jej publikacji.
            Dotyczy to w szczególności danych osobowych, zdjęć, numerów telefonów, adresów e-mail,
            wizerunku oraz danych dotyczących innych osób.
        </p>

        <p>
            Użytkownik nie powinien publikować danych osobowych osób trzecich bez odpowiedniej podstawy
            prawnej, ich wiedzy lub zgody, jeżeli jest ona wymagana. Zabronione jest w szczególności
            publikowanie numerów PESEL, danych dokumentów tożsamości, danych zdrowotnych, danych bankowych,
            haseł, kodów dostępu, prywatnych adresów zamieszkania oraz innych danych wrażliwych.
        </p>

        <p>
            Administrator nie jest autorem ogłoszeń publikowanych przez użytkowników i nie ma obowiązku
            weryfikowania każdej informacji przed jej publikacją. Użytkownik ponosi odpowiedzialność za
            treść dodaną z własnej inicjatywy, w tym za dobrowolne opublikowanie własnych danych osobowych
            lub danych osób trzecich.
        </p>

        <p>
            Powyższe nie wyłącza obowiązków Administratora wynikających z bezwzględnie obowiązujących
            przepisów prawa, w szczególności obowiązku podjęcia odpowiednich działań po otrzymaniu
            wiarygodnego zgłoszenia dotyczącego treści niezgodnej z prawem lub danych opublikowanych
            bez podstawy prawnej.
        </p>

        <h2>4. Zgłoszenie usunięcia treści lub danych osobowych</h2>

        <p>
            Osoba, której dane osobowe, wizerunek, numer telefonu, adres e-mail lub inna dotycząca jej
            informacja została opublikowana w serwisie, może zgłosić żądanie usunięcia lub ograniczenia
            widoczności treści.
        </p>

        <p>
            Zgłoszenie można przesłać:
        </p>

        <ul>
            <li>e-mailem na adres <a href="mailto:<?= sanitize(CONTACT_EMAIL) ?>"><?= sanitize(CONTACT_EMAIL) ?></a>,</li>
            <li>bezpośrednio przez przycisk <strong>„Kontakt”</strong> znajdujący się w dolnym panelu strony.</li>
        </ul>

        <p>
            W zgłoszeniu warto podać link do ogłoszenia lub strony, opis treści przeznaczonej do usunięcia
            oraz przyczynę zgłoszenia. Nie należy przesyłać większej ilości danych osobowych, niż jest to
            konieczne do rozpatrzenia sprawy.
        </p>

        <p>
            Administrator analizuje zgłoszenia i podejmuje odpowiednie działania, w tym może usunąć,
            ukryć albo ograniczyć dostęp do zakwestionowanej treści. Administrator może poprosić o
            dodatkowe informacje wyłącznie wtedy, gdy są one potrzebne do odnalezienia treści lub
            rozpatrzenia zgłoszenia.
        </p>

        <h2>5. Cele i podstawy przetwarzania</h2>

        <p>
            Dane są przetwarzane w celu umożliwienia publikacji ogłoszeń i wpisów, obsługi wiadomości,
            odpowiedzi na zgłoszenia, usuwania treści, zapewnienia bezpieczeństwa serwisu, przeciwdziałania
            nadużyciom, moderacji oraz dochodzenia lub obrony roszczeń.
        </p>

        <p>
            Podstawą przetwarzania może być w szczególności wykonanie działań podejmowanych na żądanie
            użytkownika, zgoda — jeżeli jest wymagana — oraz prawnie uzasadniony interes Administratora,
            polegający na zapewnieniu bezpieczeństwa, prawidłowego działania serwisu, obsługi zgłoszeń
            i ochronie przed nadużyciami.
        </p>

        <h2>6. Publiczny charakter ogłoszeń i komunikatów</h2>

        <p>
            Treść ogłoszenia lub komunikatu Pulsu miasta opublikowanego w części publicznej serwisu jest
            dostępna dla odwiedzających internet w zakresie wynikającym z funkcjonalności strony. Może być
            również indeksowana przez wyszukiwarki internetowe lub zapisana przez osoby trzecie.
        </p>

        <p>
            Usunięcie ogłoszenia z serwisu powoduje usunięcie go ze strony 66600.PL, jednak nie gwarantuje
            natychmiastowego usunięcia kopii zapisanych przez wyszukiwarki, użytkowników lub inne podmioty,
            które niezależnie pobrały albo utrwaliły treść przed jej usunięciem.
        </p>

        <h2>7. Odbiorcy danych</h2>

        <p>
            Dane mogą być udostępniane podmiotom wspierającym działanie serwisu, w szczególności dostawcy
            hostingu, podmiotom świadczącym obsługę techniczną oraz dostawcom infrastruktury informatycznej,
            wyłącznie w zakresie niezbędnym do działania serwisu.
        </p>

        <p>
            Dane dobrowolnie opublikowane w ogłoszeniu są publicznie dostępne w zakresie wybranym przez
            użytkownika publikującego treść. Administrator nie sprzedaje danych osobowych użytkowników.
        </p>

        <p>
            Serwis może korzystać z zasobów zewnętrznych, takich jak Google Fonts. Skorzystanie z funkcji
            udostępniania może przekierować użytkownika do zewnętrznej usługi, np. Facebooka lub klienta
            poczty. Od chwili przejścia do takiej usługi zastosowanie mają zasady prywatności jej dostawcy.
        </p>

        <h2>8. Okres przechowywania danych</h2>

        <p>
            Komunikat Pulsu miasta jest wyświetlany publicznie przez 12 godzin. Po tym czasie nie jest już
            prezentowany w publicznej liście ani tickerze. Zapis techniczny może być przechowywany wyłącznie
            tak długo, jak jest to potrzebne do obsługi zgłoszeń, moderacji, przeciwdziałania nadużyciom lub
            obrony przed roszczeniami.
        </p>

        <p>
            Dane zawarte w ogłoszeniu lub wpisie są przechowywane do czasu usunięcia treści, wycofania jej
            przez użytkownika, pozytywnego rozpatrzenia zgłoszenia albo ustania celu ich przetwarzania.
        </p>

        <p>
            Dane związane z korespondencją, zgłoszeniami i bezpieczeństwem serwisu są przechowywane przez
            okres potrzebny do udzielenia odpowiedzi, rozpatrzenia sprawy, ochrony przed nadużyciami oraz
            ewentualnej obrony przed roszczeniami, nie dłużej niż jest to uzasadnione przepisami prawa
            i celem przetwarzania.
        </p>

        <h2>9. Pliki cookie i technologie lokalne</h2>

        <p>
            Serwis wykorzystuje technicznie niezbędne pliki cookie, w tym pliki sesyjne PHP, aby zapewnić
            prawidłowe działanie strony i panelu administracyjnego. Może również wykorzystywać pamięć
            przeglądarki, service worker oraz Cache Storage w celu usprawnienia ładowania zasobów i funkcji PWA.
        </p>

        <p>
            Mechanizmy te nie są wykorzystywane do profilowania marketingowego. Użytkownik może ograniczyć
            lub usunąć pliki cookie w ustawieniach swojej przeglądarki, przy czym ograniczenie plików
            niezbędnych może wpłynąć na poprawne działanie wybranych funkcji serwisu.
        </p>

        <h2>10. Prawa osoby, której dane dotyczą</h2>

        <p>
            W granicach przewidzianych prawem osoba, której dane dotyczą, ma prawo żądania dostępu do danych,
            ich sprostowania, usunięcia, ograniczenia przetwarzania, przenoszenia danych, wniesienia sprzeciwu
            wobec przetwarzania opartego na prawnie uzasadnionym interesie oraz cofnięcia zgody, jeśli dane
            są przetwarzane na jej podstawie.
        </p>

        <p>
            Wnioski dotyczące danych osobowych można składać na adres
            <a href="mailto:<?= sanitize(CONTACT_EMAIL) ?>"><?= sanitize(CONTACT_EMAIL) ?></a>
            lub przez przycisk <strong>„Kontakt”</strong> w dolnym panelu strony.
        </p>

        <p>
            Osoba, której dane dotyczą, ma także prawo wniesienia skargi do Prezesa Urzędu Ochrony Danych
            Osobowych, jeżeli uzna, że przetwarzanie danych narusza obowiązujące przepisy.
        </p>

        <h2>11. Zautomatyzowane decyzje i profilowanie</h2>

        <p>
            Dane osobowe nie są wykorzystywane do podejmowania wobec użytkowników zautomatyzowanych decyzji
            wywołujących skutki prawne ani do profilowania w takim celu.
        </p>

        <h2>12. Zmiany polityki prywatności</h2>

        <p>
            Polityka prywatności może zostać zaktualizowana w przypadku zmiany funkcjonalności serwisu,
            sposobu przetwarzania danych lub obowiązujących przepisów. Aktualna wersja dokumentu jest
            zawsze publikowana pod tym adresem wraz z datą obowiązywania.
        </p>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>