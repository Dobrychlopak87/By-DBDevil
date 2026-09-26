<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'O serwisie 66600.pl';
$pageDescription = 'Poznaj zasady działania ogłoszeń, Kroniki Miasta i Pulsu miasta na 66600.pl.';
$canonicalUrl = SITE_URL . '/about.php';
$pageType = 'WebPage';
$pageRobots = 'index, follow';

require_once __DIR__ . '/includes/header.php';
?>

<main class="about-page" aria-labelledby="about-page-title">
    <header class="about-page__hero">
        <p class="about-page__eyebrow">Poznaj 66600.pl</p>
        <h1 id="about-page-title">O serwisie 66600.pl</h1>
        <p class="about-page__lead">Ta strona powstała z myślą o Was — w formie najwygodniejszej, jak to tylko było możliwe. Prosto, szybko i bez zbędnych formalności.</p>
    </header>

    <section class="about-page__section" aria-labelledby="about-sections-title">
        <h2 id="about-sections-title">Trzy części serwisu</h2>
        <p>Już na stronie głównej znajdziesz trzy wyraźnie wydzielone przestrzenie. <a class="about-page__link" href="<?= SITE_URL ?>/category.php">Ogłoszenia</a> to miejsce, w którym mieszkańcy mogą publikować swoje ogłoszenia.</p>
        <p><a class="about-page__link" href="<?= SITE_URL ?>/blog.php">Kronika Miasta</a> jest przestrzenią wspólnej wymiany informacji istotnych dla życia mieszkańców Krosna Odrzańskiego. W założeniu daje ogólny wgląd w to, co dzieje się w mieście — a tworząc ją razem, tworzymy naszą wspólną historię.</p>
    </section>

    <section class="about-page__section" aria-labelledby="about-pulse-title">
        <h2 id="about-pulse-title"><a class="about-page__title-link" href="<?= SITE_URL ?>/pulse.php">Puls miasta</a></h2>
        <p><a class="about-page__link" href="<?= SITE_URL ?>/pulse.php">Puls miasta</a> służy do krótkich, pilnych komunikatów lokalnych. Wpis ma maksymalnie <strong>160 znaków</strong> i jest publikowany od razu; może zawierać opcjonalny, publicznie widoczny podpis lub numer telefonu.</p>
        <p>Komunikaty są widoczne przez <strong>12 godzin</strong>. Nie publikuj reklam, linków, emoji ani danych innych osób. Administracja może moderować lub usuwać wpisy naruszające zasady.</p>
    </section>

    <section class="about-page__section" aria-labelledby="about-posting-title">
        <h2 id="about-posting-title">Dodawanie wpisów — anonimowo i bez rejestracji</h2>
        <p>Zarówno ogłoszenia, jak i wpisy do Kroniki możesz dodawać <strong>całkowicie anonimowo</strong>. Nie jest wymagana rejestracja ani potwierdzanie czegokolwiek adresem e-mail, a publikacja zajmuje dosłownie chwilę.</p>
        <p>Ogłoszenia i wpisy do Kroniki są finalnie <strong>akceptowane przez administratora</strong>.</p>
    </section>

    <section class="about-page__section" aria-labelledby="about-chat-title">
        <h2 id="about-chat-title"><a class="about-page__title-link" href="<?= SITE_URL ?>/chatroom/">Chatroom</a></h2>
        <p>Na stronie działa również <a class="about-page__link" href="<?= SITE_URL ?>/chatroom/">Chatroom</a>, który — podobnie jak reszta serwisu — opiera się na zasadzie anonimowości. Możesz wejść na czat, używając <strong>nicku tymczasowego</strong>, albo zarejestrować własny nick, aby logować się nim w przyszłości.</p>
        <p>Każdy może pisać bez ograniczeń. Administracja zastrzega sobie jednak prawo do moderacji wpisów, w tym ich usuwania. W przypadku nieprzestrzegania zasad opisanych w regulaminie możliwe jest również wyciszenie, zablokowanie użytkownika lub zablokowanie jego adresu IP.</p>
    </section>

    <section class="about-page__section about-page__section--accent" aria-labelledby="about-retention-title">
        <h2 id="about-retention-title">Automatyczne usuwanie danych</h2>
        <p>Wszystkie wiadomości na czacie są usuwane po <strong>24 godzinach</strong> od momentu publikacji. Ten sam czas dotyczy nicków tymczasowych — jeśli użytkownik nie odwiedzi strony przez 24 godziny, jego nick zostanie usunięty i będzie mógł go wybrać ktokolwiek inny.</p>
    </section>

    <section class="about-page__section" aria-labelledby="about-development-title">
        <h2 id="about-development-title">Strona wciąż się rozwija</h2>
        <p>Serwis jest w ciągłym rozwoju. Planujemy ukończyć go <strong>1 września</strong>, dodając jeszcze więcej możliwości interakcji oraz nowe funkcjonalności.</p>
    </section>

    <section class="about-page__contact" aria-labelledby="about-contact-title">
        <h2 id="about-contact-title">Chcesz pomóc przy serwisie 66600.pl?</h2>
        <p>Skorzystaj z przycisku poniżej, aby napisać do nas przez istniejący formularz kontaktowy.</p>
        <button class="about-page__contact-link" type="button" data-open-contact>Skontaktuj się</button>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
