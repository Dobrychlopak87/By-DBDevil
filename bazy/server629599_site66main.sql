/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: mariadb123.server629599.nazwa.pl    Database: server629599_site66main
-- ------------------------------------------------------
-- Server version	12.3.3-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Current Database: `server629599_site66main`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `server629599_site66main` /*!40100 DEFAULT CHARACTER SET latin2 COLLATE latin2_general_ci */;

USE `server629599_site66main`;

--
-- Table structure for table `ad_categories`
--

DROP TABLE IF EXISTS `ad_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ad_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_ad_categories_parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3164 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ad_categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ad_categories` WRITE;
/*!40000 ALTER TABLE `ad_categories` DISABLE KEYS */;
INSERT INTO `ad_categories` VALUES
(1,NULL,'taxi','Taxi','🚖',1,1,'2026-08-11 12:44:26'),
(2,NULL,'fachowcy','Fachowcy','👷',2,1,'2026-08-11 12:44:26'),
(3,NULL,'beauty','Beauty','💇',3,1,'2026-08-11 12:44:26'),
(4,NULL,'gastronomia','Gastronomia','🍽️',4,1,'2026-08-11 12:44:26'),
(5,NULL,'rozrywka','Rozrywka','🎭',5,1,'2026-08-11 12:44:26'),
(7,NULL,'Pokoje','Pokoje','🛏️',6,1,'2026-08-11 17:24:03'),
(8,NULL,'handel','Handel','handel',7,1,'2026-08-13 14:00:00'),
(9,NULL,'kupie-sprzedam','Kupię - Sprzedam','kupie-sprzedam',8,1,'2026-09-10 08:53:59'),
(10,9,'kupie','Kupię','kupie',81,1,'2026-09-10 08:53:59'),
(11,9,'sprzedam','Sprzedam','sprzedam',82,1,'2026-09-10 08:53:59'),
(12,NULL,'oddam-za-darmo','Oddam za darmo','oddam-za-darmo',9,1,'2026-09-10 08:53:59'),
(13,NULL,'zagubione-znalezione','Zagubione - Znalezione','zagubione-znalezione',10,1,'2026-09-10 08:53:59');
/*!40000 ALTER TABLE `ad_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ad_gallery`
--

DROP TABLE IF EXISTS `ad_gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ad_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ad_id` (`ad_id`),
  CONSTRAINT `1` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ad_gallery`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ad_gallery` WRITE;
/*!40000 ALTER TABLE `ad_gallery` DISABLE KEYS */;
INSERT INTO `ad_gallery` VALUES
(3,1,'6a7b2933093a8.jpg','',0,'2026-08-11 13:52:51'),
(4,5,'6a7b58216e80f.jpg','',0,'2026-08-11 17:13:05'),
(5,6,'6a7b5ba542309.webp','',0,'2026-08-11 17:28:05'),
(6,7,'6a7b5ea408530.jpg','',0,'2026-08-11 17:40:52'),
(7,7,'6a7b5ea408d5e.jpg','',0,'2026-08-11 17:40:52'),
(8,7,'6a7b5ea40958e.jpg','',0,'2026-08-11 17:40:52');
/*!40000 ALTER TABLE `ad_gallery` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `admin_login_attempts`
--

DROP TABLE IF EXISTS `admin_login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_login_attempts` (
  `ip_hash` char(64) NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `window_started_at` datetime NOT NULL,
  `locked_until` datetime DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ip_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_login_attempts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `admin_login_attempts` WRITE;
/*!40000 ALTER TABLE `admin_login_attempts` DISABLE KEYS */;
INSERT INTO `admin_login_attempts` VALUES
('6b11afc8457da4b9867747af37f46682ded1d7efc152ada853a69bde826b94fa',1,'2026-09-20 23:11:48',NULL,'2026-09-20 21:11:48'),
('d71ba67c2e94a217c25327df39237ab5ea6c3a5011882554073a6703c84a046e',1,'2026-09-20 23:12:37',NULL,'2026-09-20 21:12:37');
/*!40000 ALTER TABLE `admin_login_attempts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ads`
--

DROP TABLE IF EXISTS `ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT 4.5,
  `is_featured` tinyint(1) DEFAULT 0,
  `homepage_position` tinyint(3) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `submission_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `link` varchar(500) DEFAULT NULL,
  `detail_layout` enum('legacy','profile') NOT NULL DEFAULT 'profile',
  `profile_subtitle` varchar(255) DEFAULT NULL,
  `profile_tags` text DEFAULT NULL,
  `contact_url` varchar(500) DEFAULT NULL,
  `is_city_pride` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ads_homepage_position` (`homepage_position`),
  KEY `category_id` (`category_id`),
  KEY `idx_ads_homepage_public` (`is_active`,`submission_status`,`homepage_position`,`created_at`),
  KEY `idx_ads_owner_id` (`owner_id`),
  CONSTRAINT `1` FOREIGN KEY (`category_id`) REFERENCES `ad_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ads`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ads` WRITE;
/*!40000 ALTER TABLE `ads` DISABLE KEYS */;
INSERT INTO `ads` VALUES
(1,NULL,'Mieszalnia Lakierów Samochodowych','Kolor, który trafia w punkt. R-M Mieszalnia Lakierów przy ulicy Mnichów w Krośnie Odrzańskim\r\nArtykuł promocyjny\r\n\r\nJest w Krośnie takie miejsce, gdzie samochód odzyskuje twarz\r\nKażdy kierowca zna ten moment. Wracasz do auta, obchodzisz je dookoła i widzisz świeżą rysę na drzwiach. Albo po zimie okazuje się, że próg zaczął „kwitnąć\". Albo — wersja optymistyczna — kupujesz starego kombiaka do odbudowy i wiesz, że czeka Cię pół roku szlifowania.\r\n\r\nW każdym z tych przypadków droga prowadzi w to samo miejsce: na ulicę Mnichów 32 w Krośnie Odrzańskim, do R-M Mieszalni Lakierów Huberta Lewandowskiego.\r\n\r\nBo lakier samochodowy to nie jest towar, który kupuje się „na oko\". To chemia, w której musi zgadzać się wszystko: odcień, połysk, przyczepność międzywarstwowa i czas odparowania. Wystarczy jeden zły komponent, żeby weekend pracy skończył się pęcherzami na masce.\r\n\r\nNie sprzedajemy puszek. Sprzedajemy system, który do siebie pasuje\r\nTo zdanie najlepiej oddaje filozofię mieszalni przy Mnichowie. Na stronie firmy pada bardzo konkretna deklaracja:\r\n\r\n„Kluczowym aspektem naszej działalności jest zapewnienie pełnej kompatybilności między wszystkimi warstwami, od podkładów po lakiery bezbarwne.\"\r\n\r\nI to nie jest marketingowy frazes — to opis realnego problemu, z którym mierzy się każdy lakiernik. Podkład od jednego producenta, baza od drugiego, bezbarwny od trzeciego i utwardzacz „bo był w promocji\" to najkrótsza droga do warzenia się warstw, utraty przyczepności i poprawek na koszt własny.\r\n\r\nW R-M produkty dobiera się tak, żeby tworzyły jeden spójny łańcuch technologiczny. Klient dostaje pewność, że materiały będą do siebie przylegać i nie wejdą w niepożądaną reakcję chemiczną.\r\n\r\nDlaczego naprawiony element po pięciu latach nadal wygląda jak fabryczny\r\nTo pytanie, które firma stawia wprost na swojej stronie — i na które ma bardzo techniczną odpowiedź.\r\n\r\nPo pierwsze: jakość żywic polimerowych. To one decydują o odporności powłoki na promieniowanie słoneczne, zmienne temperatury i wilgoć. Materiały o niskiej odporności chemicznej matowieją i pękają — czasem już po dwóch sezonach.\r\n\r\nPo drugie: filtry UV w lakierach bezbarwnych. To one trwale chronią głębię koloru przed blaknięciem. Bez nich czerwień staje się różowa, a czarny metalik szarzeje.\r\n\r\nPo trzecie: nowoczesne podkłady antykorozyjne, które odcinają metal od dopływu powietrza i wilgoci. Rdza nie wraca tam, gdzie nie ma do czego wrócić.\r\n\r\nPo czwarte: przewidywalny czas wysychania. To jeden z najbardziej niedocenianych parametrów. Gdy materiały schną nierównomiernie, miejsca naprawiane szpachlówką po czasie zaczynają odznaczać się pod warstwą lakieru bazowego — powstaje efekt „mapy\", widoczny pod kątem w słońcu.\r\n\r\nPo piąte: elastyczność spoiw. Ta sama chemia musi pracować i na stalowym błotniku, i na plastikowym zderzaku, który inaczej reaguje na temperaturę i drgania.\r\n\r\nEfekt? Naprawiany element nadwozia po kilku latach użytkowania wciąż nie odróżnia się od fabrycznej powłoki. O to w tym wszystkim chodzi.\r\n\r\nCo znajdziesz przy Mnichowie 32\r\nOferta obejmuje wszystkie produkty niezbędne na każdym etapie prac lakierniczych — od przygotowania powierzchni po finalne wykończenie:\r\n\r\n🎨 Lakiery samochodowe\r\nDo zastosowań od lokalnych zaprawek po kompleksowe lakierowanie całych elementów i pojazdów. Dobór według rodzaju naprawy i oczekiwanego efektu. Stabilność recepturowa przekłada się na wierne odtworzenie fabrycznego wyglądu karoserii.\r\n\r\n🧰 Materiały lakiernicze\r\nPodkłady, wypełniacze, szpachlówki, systemy ścierne, chemia pomocnicza. Wszystko, co odpowiada za prawidłowe przygotowanie podłoża i powtarzalne warunki pracy. W asortymencie znajdziesz nowoczesne formulacje wodorozcieńczalne oraz produkty o wysokiej zawartości ciał stałych (High Solid).\r\n\r\n✨ Kosmetyka samochodowa\r\nPasty i systemy polerskie, produkty do ochrony i pielęgnacji lakieru. Przydatne zarówno bezpośrednio po naprawie, jak i w codziennym utrzymaniu auta w formie.\r\n\r\n🔧 Oferta dla warsztatów\r\nStałe zaopatrzenie w często używane produkty, kompletowanie zestawów pod konkretne zlecenia i etapy procesu. To pozwala planować zakupy zgodnie z bieżącym obłożeniem i minimalizuje ryzyko przestojów spowodowanych brakami materiałowymi.\r\n\r\nDoradztwo, czyli najbardziej niedoceniany produkt w tym sklepie\r\nNajwiększą wartością R-M Mieszalni Lakierów nie jest półka. Jest nią rozmowa przy ladzie.\r\n\r\nFirma otwarcie deklaruje, że sama zna specyfikę branży od podszewki — potrafi ocenić parametry krycia i czas schnięcia konkretnego produktu, a do asortymentu wybiera wyłącznie materiały minimalizujące ryzyko typowych wad: siadania materiału czy utraty połysku.\r\n\r\nI idzie o krok dalej, niż większość punktów tego typu: pomaga w ustawieniu parametrów natrysku oraz doborze właściwych dysz do pistoletów.\r\n\r\nTo detal, który dla laika brzmi jak techniczny drobiazg, a dla praktyka jest różnicą między gładką powłoką a „skórką pomarańczową\". Zła dysza, złe ciśnienie, zła odległość — i najlepszy lakier świata wygląda przeciętnie.\r\n\r\nPodczas rozmowy z mieszalnią można omówić:\r\n\r\nzastosowanie lakieru i rodzaj podłoża,\r\nwymagany poziom połysku,\r\nodporność na czynniki zewnętrzne,\r\nkompatybilność produktów między sobą,\r\noptymalne warunki przechowywania i konserwacji materiałów.\r\nJak ujmuje to sama firma — chodzi o to, żeby klient dostał gotowe rozwiązanie bez konieczności metody prób i błędów.\r\n\r\nDla kogo?\r\nDla profesjonalnych warsztatów i lakierni z Krosna Odrzańskiego i okolic, które potrzebują niezawodnego partnera i indywidualnej oferty na stałe zaopatrzenie w lakiery, podkłady i kosmetyki samochodowe.\r\n\r\nDla osób prywatnych — firma wprost zaznacza, że dostarcza produkty ułatwiające codzienną pracę zarówno profesjonalistom, jak i klientom indywidualnym. Nikt nie zostanie tu potraktowany jak intruz dlatego, że przyszedł po jedną zaprawkę.\r\n\r\nDla pasjonatów renowacji, którzy odbudowują starsze auta i potrzebują technologii pracującej przewidywalnie na trudnych, wielokrotnie malowanych podłożach.\r\n\r\nMarka R-M — co to właściwie znaczy\r\nW nazwie firmy nie bez powodu stoi R-M. To renomowany, międzynarodowy system lakierniczy stosowany w lakiernictwie renowacyjnym, znany z zaawansowanych technologii wodorozcieńczalnych i precyzyjnego doboru kolorów. Mieszalnia przy Mnichowie pracuje właśnie na tym systemie — i regularnie wprowadza do asortymentu nowe produkty, żeby nadążać za tym, co dzieje się w branży.\r\n\r\nZajrzyj albo zadzwoń\r\nNie trzeba się znać. Nie trzeba przygotowywać listy. Wystarczy opisać problem albo podać kod lakieru — resztę mieszalnia weźmie na siebie.\r\n\r\n„Szukasz konkretnego koloru lub lakieru? Pomożemy Ci skompletować zestaw pod konkretne zamówienie.\"\r\n\r\n📍 Dane kontaktowe\r\nR-M Mieszalnia Lakierów — Hubert Lewandowski\r\n\r\nAdres	ul. Mnichów 32, 66-600 Krosno Odrzańskie\r\nTelefon	+48 609 218 740\r\nE-mail	hubertlewandowski1@op.pl\r\nStrona	www.mieszalnialakierow.com.pl\r\nNIP	926-107-72-07\r\nGodziny otwarcia\r\n\r\nPoniedziałek – Piątek: 9:00 – 16:00\r\nSobota: 9:00 – 14:00\r\nNiedziela: nieczynne\r\nMasz kod lakieru albo problem z naprawą? Zadzwoń pod 609 218 740 — dobierzemy zestaw, który po prostu zadziała.','6a7b293308ab5.jpg',2,'Krosno Odrzańskie, Polska',NULL,NULL,NULL,4.5,1,NULL,1,'approved','https://www.mieszalnialakierow.com.pl/','profile',NULL,NULL,NULL,0,'2026-08-11 12:44:26','2026-08-11 13:52:51'),
(2,NULL,'Twoja chwila piękna w sercu Krosna Odrzańskiego. Odkryj wyjątkowy świat MBeauty','Nowy wymiar pielęgnacji przy ulicy Mnichów – oaza spokoju na mapie Krosna Odrzańskiego\r\nPrzekraczając próg salonu MBeauty przy ulicy Mnichów w Krośnie Odrzańskim, od pierwszych chwil wyczuwa się wyjątkową atmosferę. To miejsce, w którym nowoczesna estetyka wnętrza spotyka się z ciepłem, dyskrecją i profesjonalizmem. W dobie powtarzalnych, pośpiesznych zabiegów, MBeauty stawia na indywidualną relację z każdą klientką oraz niespieszny komfort.\r\n\r\nKażda wizyta poprzedzona jest szczegółową konsultacją i profesjonalnym wywiadem kosmetologicznym. Specjalistka analizuje kondycję skóry, naturalny profil rzęs oraz geometrię twarzy, by dobrać zabieg szyty na miarę – taki, który idealnie wpisze się w indywidualny styl życia, oczekiwania i naturalną urodę kobiety. W MBeauty nie ma miejsca na schematy; jest za to zrozumienie potrzeb i precyzja, która przekłada się na spektakularny, ale wciąż naturalny efekt.\r\n\r\nSpojrzenie, które mówi wszystko – mistrzowska stylizacja rzęs\r\nNie bez powodu mówi się, że oczy są zwierciadłem duszy. Pięknie wymodelowane rzęsy potrafią całkowicie odmienić wyraz twarzy – otworzyć spojrzenie, dodać mu świeżości i głębi, a także wizualnie odmłodzić okolice oka. Co równie istotne, profesjonalnie wykonana stylizacja rzęs to nieoceniona wygoda na co dzień. Pozwala zrezygnować z codziennego, czasochłonnego tuszowania oraz wieczornego demakijażu, gwarantując nienaganny wygląd zarówno tuż po przebudzeniu, jak i podczas aktywności fizycznej.\r\n\r\nW ofercie MBeauty w Krośnie Odrzańskim znajdują się techniki dopasowane do każdego typu urody:\r\n\r\nMetoda 1:1 (Klasyczna elegancja) – perfekcyjne rozwiązanie dla kobiet ceniących subtelność. Do każdej naturalnej rzęsy aplikowana jest jedna rzęsa syntetyczna, co daje efekt precyzyjnie wytuszowanych, gęściejszych i dłuższych rzęs bez cienia przesady.\r\nLekkie objętości (2D–3D / Light Volume) – idealny kompromis między naturalnością a wyraźnym podkreśleniem. Kępki z najdelikatniejszych włókien nadają spojrzeniu puszystości i pięknej oprawy.\r\nStylizacje objętościowe i modelowanie oka – dla pań pragnących wyrazistego, glamour efektu, z pełnym dopasowaniem skrętu, długości i linii do kształtu powieki.\r\nLifting, laminacja i botoks rzęs – znakomita alternatywa dla przedłużania. Zabieg dedykowany osobom, które pragną maksymalnie podkręcić, odżywić, przyciemnić i optycznie wydłużyć swoje naturalne rzęsy za sprawą keratynowej odbudowy.\r\nArchitektura i stylizacja brwi – perfekcyjna rama dla Twojej twarzy\r\nBrwi pełnią kluczową rolę w symetrii twarzy – właściwie wyprofilowane potrafią zadziałać niczym naturalny lifting. W salonie MBeauty przy ul. Mnichów stylizacja brwi traktowana jest jak sztuka architektury.\r\n\r\nZamiast przypadkowej regulacji, klientki mogą liczyć na zaawansowaną geometrię i mapowanie brwi, które precyzyjnie wyznaczają idealne proporcje łuku. W menu salonu królują:\r\n\r\nHenna pudrowa z geometrią – naturalne zabarwienie włosków oraz naskórka z jednoczesnym odżywieniem ziółami. Efekt utrzymuje się do kilku tygodni, pozwalając cieszyć się idealnym kształtem bez codziennego dorysowywania kredek czy cieni.\r\nLaminacja brwi z keratynową regeneracją – hit nowoczesnej kosmetyki. Zabieg ujarzmia niesforne, trudne do ułożenia włoski, nadaje im modny, pełniejszy kształt oraz zdrowy, satynowy połysk.\r\nProfesjonalna regulacja i pielęgnacja – delikatne opracowanie kształtu przy zachowaniu naturalnej gęstości i harmonii z rysami twarzy.\r\nZabiegi pielęgnacyjne dla twarzy – blask, regeneracja i zdrowa skóra\r\nPiękna oprawa oczu najpełniej prezentuje się na tle zdrowej, zadbanej i promiennej cery. MBeauty to miejsce, w którym troska o skórę opiera się na sprawdzonych, certyfikowanych preparatach kosmetycznych o wysokim stężeniu składników aktywnych oraz na dogłębnej wiedzy kosmetologicznej.\r\n\r\nW zależności od pory roku i potrzeb skóry, salon oferuje zabiegi głęboko nawilżające, oczyszczające, rewitalizujące oraz kuracje wspierające naturalną odbudowę naskórka. Niezależnie od tego, czy Twoja skóra potrzebuje ukojenia po zimowych chłodach, regeneracji po lecie, czy natychmiastowego zastrzyku energii przed ważnym wydarzeniem – w MBeauty znajdziesz terapię dopasowaną do Twoich potrzeb.\r\n\r\nPonad 10 lat doświadczenia – pasja, która gwarantuje bezpieczeństwo\r\nW branży beauty moda i trendy zmieniają się niezwykle szybko, jednak to, co pozostaje niezmienne i kluczowe, to wiedza, doświadczenie oraz bezkompromisowe podejście do bezpieczeństwa. Właścicielka salonu MBeauty, Magda Lipińska, od ponad dekady z sukcesami dba o urodę mieszkańczeniek Krosna Odrzańskiego i okolic.\r\n\r\nWybierając MBeauty przy ul. Mnichów, klientki zyskują pewność, że powierzają swoją urodę w ręce ekspertki, która stale podnosi kwalifikacje na branżowych sympozjach i szkoleniach masterclass. W gabinecie obowiązują najwyższe standardy higieniczno-sanitarne – rygorystyczna sterylizacja narzędzi w autoklawie medycznym, stosowanie materiałów jednorazowych oraz praca wyłącznie na renomowanych, certyfikowanych produktach z bezpiecznym składem.\r\n\r\nPodsumowanie: Zainwestuj w swoje dobre samopoczucie\r\nSalon MBeauty przy ulicy Mnichów w Krośnie Odrzańskim to więcej niż gabinet kosmetyczny – to Twoja osobista przestrzeń piękna i relaksu. Niezależnie od tego, czy planujesz spektakularną metamorfozę spojrzenia przed wielkim wyjściem, czy pragniesz wprowadzić profesjonalną pielęgnację do swojej codziennej rutyny – MBeauty jest miejscem, w którym poczujesz się wyjątkowo, zaopiekowana i piękna.','6a7b2b2d09619.png',3,'Krosno Odrzańskie, Polska',NULL,NULL,NULL,5.0,1,NULL,1,'approved','https://www.facebook.com/share/1BnogqNsME/','profile',NULL,NULL,NULL,0,'2026-08-11 12:44:26','2026-08-11 14:01:17'),
(3,NULL,'Smak, który przypomina dom. Dlaczego całe Krosno Odrzańskie rozkochało się w obiadach z RataTuje?','Kulinarny ratunek na mapie Krosna Odrzańskiego – tradycja w nowoczesnym wydaniu\r\nNazwa „RataTuje” brzmi nie tylko apetycznie i znajomo, ale przede wszystkim niesie ze sobą obietnicę: to miejsce, które po prostu RATUJE nas przed codziennym dylematem „co dziś na obiad?”, brakiem czasu po pracy oraz głodem w trakcie intensywnych godzin w biurze. Prowadzone przez Patrycję Wójcicką bistro RataTuje wyrosło z pasji do autentycznej, uczciwej kuchni domowej, w której nie ma miejsca na kompromisy jakościowe.\r\n\r\nW świecie zdominowanym przez szybkie, wysoko przetworzone jedzenie, RataTuje stawia na powrót do korzeni – do smaków, które dobrze znamy z rodzinnego domu, niedzielnych obiadów u mamy czy wakacji u babci. Każda potrawa przygotowywana jest od podstaw, ze świeżych warzyw, najwyższej jakości mięs oraz naturalnych przypraw.\r\n\r\nDomowe smaki, za którymi tęsknimy najbardziej\r\nCo sprawia, że klienci tak chętnie wracają do RataTuje? Przede wszystkim powtarzalna, bezbłędna jakość i serce wkładane w każdy talerz. Menu lokalu to kwintesencja polskiej kuchni domowej w najlepszym wydaniu. Znajdziemy tu:\r\n\r\nEsencjonalne, sycące zupy – od tradycyjnego, aromatycznego rosołu na prawdziwej włoszczyźnie, przez aksamitną zupę cebulową i jarzynową, po rozgrzewający krupnik czy domowy żurek.\r\nKlasyczne drugie dania – złocisty, chrupiący kotlet schabowy, soczyste drobiowe rolady, delikatne mięsa pieczone w sosach własnych oraz pożywne gulasze.\r\nDania mączne i wegetariańskie – ręcznie lepione pierogi, domowe naleśniki z serem lub jabłkami, a także lekkie kompozycje warzywne.\r\nCodzienne zestawy surówek – świeżo szatkowane warzywa, które stanowią idealne, witaminowe uzupełnienie każdego obiadu.\r\nCodziennie inne menu – koniec z rutyną na talerzu!\r\nJednym z największych atutów RataTuje jest dynamiczna, codzienna zmienność karty dań. Zamiast nudnego, monotonnego jadłospisu, kuchnia lokalu codziennie zaskakuje klientów nowymi propozycjami zup i dań głównych. Dzięki temu stałe zamawianie obiadów w RataTuje nigdy się nie nudzi, a organizm otrzymuje zróżnicowane i pełnowartościowe posiłki.\r\n\r\nCodzienne „menu dnia” publikowane jest rano na profilu społecznościowym lokalu, co pozwala z łatwością zaplanować rodzinny obiad lub przerwę lunchową ze współpracownikami.\r\n\r\n„Domowe obiady na dowóz i nie tylko” – gorący posiłek tam, gdzie tego potrzebujesz\r\nZgodnie z mottem lokalu, RataTuje oferuje elastyczne formy korzystania ze swojej kuchni. Choć wiele osób z przyjemnością odwiedza lokal osobiście, ogromną popularnością cieszy się szybki, niezawodny dowóz na terenie Krosna Odrzańskiego i okolic.\r\n\r\nDzięki specjalistycznym, szczelnym opakowaniom termicznym posiłki docierają do klientów gorące i gotowe do spożycia. Kierowcy RataTuje codziennie dostarczają obiady do urzędów, biur, szkół, sklepów, warsztatów, na place budowy oraz pod drzwi prywatnych domów. To wygodne rozwiązanie zarówno dla osób pracujących zdalnie, jak i dla seniorów czy rodzin, które chcą zaoszczędzić czas na zakupach i gotowaniu.\r\n\r\nCatering okolicznościowy i obsługa firm – przyjęcia z klasą i smakiem\r\nOferta RataTuje wykracza daleko poza codzienne obiady dnia. Lokal specjalizuje się również w profesjonalnej obsłudze cateringowej wydarzeń okolicznościowych i firmowych. Planujesz urodziny, chrzciny, komunię, jubileusz w gronie najbliższych, a może integracyjne spotkanie w firmie lub szkolenie?\r\n\r\nZespół RataTuje przygotuje na Twoje zamówienie eleganckie półmiski mięs pieczonych, wykwintne przekąski finger food, sałatki, tradycyjne dania gorące oraz domowe ciasta, dbając o to, by oprawa kulinarna Twojego wydarzenia zachwyciła każdego gościa.\r\n\r\nPodsumowanie: Zamów, spróbuj i poczuj się jak w domu!\r\nBistro RataTuje w Krośnie Odrzańskim udowadnia, że prawdziwa kuchnia domowa nie ma sobie równych. Uczciwe porcje, pasja do gotowania, świeże produkty i niezwykła wygoda dostawy sprawiają, że to adres numer jeden dla każdego, kto ceni smak, jakość i dobrą cenę. Sprawdź dzisiejsze menu i przekonaj się, jak smakuje obiad, który naprawdę Cię ratuje!','6a7b2d4e5c9d2.webp',4,'Krosno Odrzańskie, Polska ',NULL,NULL,NULL,5.0,1,NULL,1,'approved','https://www.facebook.com/share/1EB24HYvXS/','profile',NULL,NULL,NULL,0,'2026-08-11 12:44:26','2026-08-11 14:10:32'),
(4,NULL,'Zawsze na czas, zawsze bezpiecznie. Już ponad 25 lat! ','Ponad 25 lat na drogach Krosna Odrzańskiego – tradycja, która zobowiązuje\r\nW branży transportowej zaufania nie zdobywa się z dnia na dzień. Firma Taxi Osobowe Mirosław Drezin rozpoczęła swoją działalność w Krośnie Odrzańskim w 1998 roku. Przez ponad 25 lat obecności na rynku kierowca pokonał dziesiątki tysięcy kilometrów i bezpiecznie przewiózł tysiące zadowolonych pasażerów – od mieszkańców miasta, przez turystów, aż po przedsiębiorców i gości zagranicznych.\r\n\r\nTak ogromne doświadczenie przekłada się na perfekcyjną znajomość topografii Krosna Odrzańskiego, powiatu krośnieńskiego oraz dróg całego województwa lubuskiego. Pasażerowie nie muszą obawiać się błądzenia z nawigacją – pan Mirosław zawsze wybiera optymalną, najszybszą i najbezpieczniejszą trasę przejazdu.\r\n\r\nDostępność 24/7 – taksówka na wyciągnięcie ręki o każdej porze dnia i nocy\r\nŻycie w mieście nie kończy się o 17:00. Nagła potrzeba wyjazdu do szpitala we wczesnych godzinach porannych, powrót ze spotkania towarzyskiego w środku nocy czy nagła zmiana planów podróży wymagają niezawodnego transportu. Taxi Mirosław Drezin wyróżnia się pełną gotowością do jazdy przez 24 godziny na dobę, 7 dni w tygodniu – również w niedziele i dni świąteczne.\r\n\r\nWystarczy jeden telefon pod numer 601 968 103, aby zyskać pewność, że komfortowy samochód pojawi się we wskazanym miejscu i punktualnie dowiezie nas pod wskazany adres.\r\n\r\nKomfort, bezpieczeństwo i wysoka kultura osobista\r\nDla wielu pasażerów równie ważny jak punktualność jest sam standard podróży. Taxi Mirosław Drezin stawia na nowoczesną, zadbaną i w pełni klimatyzowaną flotę pojazdów, która przechodzi regularne, rygorystyczne przeglądy techniczne. Samochód jest zawsze czysty, pachnący i przygotowany do jazdy w każdych warunkach pogodowych.\r\n\r\nTo jednak człowiek za kółkiem stanowi prawdziwe serce tej firmy. Klienci w recenzjach niezmiennie podkreślają empatię, uprzejmość i dyskrecję kierowcy. Pomoc w załadunku ciężkich walizek, spokojny i płynny styl prowadzenia pojazdu oraz kulturalna atmosfera na pokładzie sprawiają, że podróż staje się prawdziwą przyjemnością.\r\n\r\nKompleksowa oferta: Od miejskich kursów po transfery lotniskowe\r\nOferta Taxi Mirosław Drezin jest dostosowana do szerokiego grona odbiorców i obejmuje:\r\n\r\nPrzewozy pasażerskie w Krośnie Odrzańskim i powiecie krośnieńskim – szybkie i bezpieczne przejazdy na zakupy, do pracy, urzędów, przychodni czy dworców.\r\nTransfery lotniskowe i dworcowe – punktualne przewozy na kluczowe lotniska w regionie i za granicą (Berlin Brandenburg BER, Poznań Ławica, Wrocław Strachowice, Szczecin-Goleniów) oraz na stacje kolejowe w Zielonej Górze, Rzepinie czy Świebodzinie.\r\nWyjazdy dalekobieżne i przygraniczne – komfortowe przejazdy biznesowe i prywatne do Niemiec, Słubic, Gubina i innych miast Polski.\r\nObsługa wesel i imprez okolicznościowych – profesjonalny, zorganizowany transport gości weselnych, uczestników bankietów, chrzcin czy jubileuszy, z gwarancją bezpiecznego powrotu do domu.\r\nUsługi indywidualne i kurierskie – przewóz ważnych przesyłek dokumentów czy pomoc w nagłych sytuacjach logistycznych.\r\nDlaczego warto wybrać lokalnego, sprawdzonego przewoźnika?\r\nW dobie bezosobowych aplikacji transportowych, w których nie wiemy, kto usiądzie za kierownicą, lokalne, licencjonowane taxi z wieloletnią tradycją daje nieocenione poczucie bezpieczeństwa. Klient otrzymuje jasną informację o cenie, pewność zarezerwowania terminu na konkretną godzinę oraz wsparcie doświadczonego kierowcy, dla którego praca jest pasją, a satysfakcja pasażera – priorytetem.\r\n\r\nPodsumowanie: Zapisz ten numer w swoim telefonie!\r\nSzukasz pewnego, sprawdzonego transportu w Krośnie Odrzańskim i okolicach? Wybierz doświadczenie, punktualność i komfort. Zapisz numer telefonu +48 601 968 103 w swoich kontaktach i podróżuj bez stresu przez całą dobę z Taxi Osobowe Mirosław Drezin!','6a7b5635c795d.jpg',1,'Krosno Odrzańskie, Polska',NULL,NULL,NULL,5.0,1,NULL,1,'approved','https://www.orlytransportu.pl/profile-524970-miroslaw-drezin-taxi-krosno-odrzanskie-24-h','profile',NULL,NULL,NULL,0,'2026-08-11 12:44:26','2026-08-11 17:04:53'),
(5,NULL,'Kręgle, bilard, squash i bistro w jednym miejscu. Odkryj Centrum Rekreacyjne przy ul. Pocztowej 27!','Kręgielnia przy ulicy Pocztowej 27 w Krośnie Odrzańskim to obiekt z historią i nowoczesną duszą. Powstała w 2012 roku w zmodernizowanej, dawnej hali sportowej, błyskawicznie stając się centrum rekreacyjnego życia mieszkańców miasta i okolic. Dlaczego gra w kręgle (bowling) cieszy się tak ogromną popularnością? Ponieważ to jeden z nielicznych sportów, który łączy pokolenia i nie wymaga profesjonalnego przygotowania fizycznego.\r\n\r\nProste zasady, radość z każdego celnego rzutu oraz duża dawka śmiechu sprawiają, że na torach w Krośnie Odrzańskim świetnie bawią się zarówno kilkuletnie dzieci, nastolatkowie, jak i dorośli czy seniorzy.\r\n\r\nDwa profesjonalne tory bowlingowe i komfort dla graczy\r\nSercem obiektu są dwa w pełni zautomatyzowane tory do gry w kręgle, wyposażone w nowoczesne monitory wyświetlające bieżące wyniki rywalizacji. System punktacji jest intuicyjny, co pozwala skupić się wyłącznie na zabawie i dążeniu do upragnionego „strike’a”.\r\n\r\nWażnym atutem Kręgielni OSiR jest przejrzysty i bardzo atrakcyjny cennik – wypożyczenie specjalistycznego, wygodnego obuwia do gry jest całkowicie bezpłatne i wliczone w cenę rezerwacji toru. Dodatkowo dla najmłodszych gości przygotowano specjalny kącik dziecięcy oraz udogodnienia, dzięki którym rodzinna rozgrywka przebiega komfortowo i bezpiecznie.\r\n\r\nWięcej niż kręgle: Squash, Bilard i Automaty do gier\r\nCentrum Rekreacyjne w Krośnie Odrzańskim to kompleksowy obiekt, w którym każdy znajdzie formę aktywności idealnie dopasowaną do swojego nastroju i energii:\r\n\r\nKort do squasha – propozycja dla miłośników intensywnego wysiłku i dynamiki. Gra na korcie pozwala spalić mnóstwo kalorii, poprawić refleks i wyładować codzienny stres.\r\nDwa profesjonalne stoły bilardowe – doskonała alternatywa lub uzupełnienie partii kręgli. Bilard to idealna okazja do spokojniejszej rywalizacji przy rozmowie z przyjaciółmi.\r\nAutomaty zręcznościowe (Arcade) – dodatkowa dawka emocji, szczególnie doceniana przez młodzież i uczestników imprez urodzinowych.\r\nBistro i Strefa Relaksu – odpocznij po emocjonującej grze\r\nSportowa rywalizacja potrafi zaostrzyć apetyt. W kompleksie przy ul. Pocztowej działa przytulne bistro oraz strefa gastronomiczna, gdzie gracze mogą odpocząć w wygodnych lożach, zamówić napoje chłodzące, kawę, herbatę oraz przekąski. To idealne miejsce na chwilę oddechu między kolejnymi rundami lub na wspólne świętowanie wygranej.\r\n\r\nIdealna przestrzeń na urodziny, imprezy zamknięte i integrację z firmą\r\nKręgielnia w Krośnie Odrzańskim to sprawdzony gospodarz niezapomnianych wydarzeń. Obiekt oferuje możliwość wynajęcia całej przestrzeni na wyłączność w ramach imprez zamkniętych. Na wyposażeniu znajduje się profesjonalny sprzęt nagłaśniający, rzutnik multimedialny oraz ekrany telewizyjne.\r\n\r\nZ powodzeniem zorganizujesz tutaj:\r\n\r\nSportowe urodziny dla dziecka – z mini-turniejem kręglarskim i bezpieczną przestrzenią do zabawy.\r\nWieczory kawalerskie, panieńskie oraz jubileusze – w swobodnej, pełnej humoru atmosferze.\r\nFirmowe imprezy integracyjne i turnieje zakładów pracy – które wzmacniają współpracę w zespole i stanowią świetny reset po pracy.\r\nPodsumowanie: Zarezerwuj swój tor już dziś!\r\nNiezależnie od tego, czy planujesz weekendowe wyjście z rodziną, szybki mecz squasha po pracy, czy wieczorny turniej bilarda – Kręgielnia OSiR w Krośnie Odrzańskim to miejsce stworzone do aktywnego wypoczynku. Zadzwoń pod numer 576 700 501 lub skorzystaj z rezerwacji online na stronie osirkrosno.pl i dołącz do gry!','6a7b58216dcc4.jpg',5,'Krosno Odrzańskie, Polska',NULL,NULL,NULL,5.0,1,NULL,1,'approved','https://osirkrosno.pl/kregielnia/','profile',NULL,NULL,NULL,0,'2026-08-11 12:44:26','2026-08-11 17:13:05'),
(6,NULL,'Od tradycyjnego polskiego obiadu po chrupiącą pizzę. Odkryj wyjątkowe menu Restauracji Okej przy ul. 1 Maja!','Na kulinarnej mapie Krosna Odrzańskiego są miejsca, które na stałe wpisały się w historię i codzienne życie mieszkańców. Jednym z nich jest Restauracja Okej przy ulicy 1 Maja 10-12. To lokal z wyjątkową duszą, w którym tradycyjna polska gościnność łączy się z nowoczesnymi standardami gastronomii. O tym, jak ważnym punktem na mapie regionu jest to miejsce, najlepiej świadczy fakt, że Restauracja Okej od lat nieprzerwanie zdobywa statuetki w ogólnopolskim plebiscycie „Orły Gastronomii”, wyłaniającym najlepszych z najlepszych na podstawie prawdziwych, wysokich ocen klientów.\r\n\r\nPrzekraczając próg restauracji, goście trafiają do przytulnego, ciepłego wnętrza, w którym można zapomnieć o codziennym pośpiechu i w pełni delektować się doskonałym jedzeniem.\r\n\r\nMenu, w którym tradycja spotyka się ze światowymi smakami\r\nNajwiększą siłą Restauracji Okej jest niezwykła uniwersalność i różnorodność karty dań. To miejsce, które doskonale rozwiązuje odwieczny dylemat, gdzie wyjść całą rodziną lub z grupą przyjaciół o zupełnie odmiennych upodobaniach kulinarnych. W menu znajdziemy m.in.:\r\n\r\nTradycyjną kuchnię polską i europejską – prawdziwa duma kuchni. Na gości czekają aromatyczne, domowe zupy gotowane na naturalnych wywarach, złociste kotlety schabowe i drobiowe, soczyste pieczenie w aksamitnych sosach, świeże ryby, ręcznie lepione pierogi oraz wyborne naleśniki w wersji wytrawnej i na słodko.\r\nChrupiącą pizzę z pieca – jedną z najchętniej zamawianych w Krośnie Odrzańskim. Przygotowywana na cienkim, idealnie wypieczonym cieście, z prawdziwym sosem pomidorowym, ciągnącym się serem i bogactwem świeżych dodatków.\r\nNowoczesne przekąski i dania typu fast casual – m.in. popularne dania kebab, soczyste burgery, sałatki oraz elementy kuchni międzynarodowej (w tym sushi), idealne na szybki lunch czy spotkanie ze znajomymi.\r\nAtmosfera na każdą okazję – obiad z rodziną, mecz ze znajomymi czy wieczorny drink\r\nRestauracja Okej potrafi płynnie zmieniać swoje oblicze w zależności od pory dnia i potrzeb gości. W godzinach popołudniowych to spokojna, rodzinna oaza, w której można celebrować niedzielny obiad z dziećmi i dziadkami. Z kolei wieczorami lokal tętni życiem, stając się idealną przestrzenią towarzyską na spotkanie przy drinku, chłodnym piwie czy świetnie zaparzonej kawie.\r\n\r\nW sezonie wiosenno-letnim dużą atrakcją jest przytulny ogródek letni, w którym można zjeść posiłek na świeżym powietrzu. Wszystko to wspierane jest przez szybką, rzetelną i niezwykle uprzejmą obsługę kelnerską, która dba o to, by każda wizyta przebiegała w doskonałej atmosferze.\r\n\r\nJakość w dobrej cenie – porcje, które naprawdę sycą\r\nW dobie rosnących cen gastronomii, Restauracja Okej udowadnia, że można zachować najwyższą jakość składników, świeżość i duże, sycące porcje przy jednoczesnym utrzymaniu umiarkowanych, rozsądnych cen. To właśnie uczciwość kulinarna sprawia, że klienci wracają tu regularnie – zarówno na obiad po pracy, jak i na weekendowe uroczystości.\r\n\r\nZjedz na miejscu, zamów na wynos lub skorzystaj z cateringu\r\nLokal oferuje pełną elastyczność: goście mogą delektować się daniami na miejscu w sali restauracyjnej lub w ogródku, a także skorzystać ze sprawnej obsługi zamówień na wynos. Co ważne, restauracja jest w pełni przystosowana do potrzeb osób na wózkach inwalidzkich (posiada dogodne wejście, wyznaczony parking oraz toalety). Dodatkowo zespół Restauracji Okej świadczy profesjonalne usługi cateringowe na przyjęcia rodzinne, spotkania biznesowe czy bankiety w regionie.\r\n\r\nPodsumowanie: Zarezerwuj swój stolik w Restauracji Okej!\r\nNiezależnie od tego, czy masz ochotę na tradycyjnego polskiego schabowego, chrupiącą pizzę, czy po prostu chcesz spędzić przyjemny wieczór przy koktajlu ze znajomymi – Restauracja Okej w Krośnie Odrzańskim to adres, który cię nie zawiedzie. Zadzwoń pod numer 68 322 96 76, zarezerwuj stolik przy ul. 1 Maja 10-12 i przekonaj się, jak smakuje gościnność na medal!','6a7b5ba541340.webp',4,'Krosno Odrzańskie, Polska',NULL,NULL,NULL,5.0,1,NULL,1,'approved','https://www.facebook.com/share/1HkeQTeNQ8/','profile',NULL,NULL,NULL,0,'2026-08-11 12:44:26','2026-08-11 22:18:23'),
(7,NULL,'Zielone serce Krosna Odrzańskiego. Dlaczego Park Tysiąclecia to idealne miejsce na weekendowy spacer?','1. Od cmentarza Crossen an der Oder do zielonej oazy – fascynująca historia w cieniu starodrzewu\r\nKażde wyjątkowe miejsce ma swoją opowieść, ale ta stojąca za Parkiem Tysiąclecia w Krośnie Odrzańskim jest wyjątkowo poruszająca. Przemierzając dzisiaj malownicze, zacienione alejki, aż trudno uwierzyć, że w XVIII wieku założono w tym miejscu cmentarz dla mieszkańców dawnego Crossen an der Oder (tzw. Bergfriedhof).\r\n\r\nTo właśnie tutaj spoczęły wybitne postacie historyczne związane z miastem – w tym Hermann Schäde oraz słynny ekspresjonistyczny poeta, pisarz i dramaturg Alfred Henschke, tworzący pod pseudonimem Klabund. Choć w latach 60. XX wieku, w ramach przygotowań do obchodów 1000-lecia Państwa Polskiego, nekropolię przeniesiono na nowe miejsce przy ul. Kościuszki, pamięć o przeszłości pozostała wpisana w układ zabytkowego drzewostanu. Dziś spacerując po parku, możemy odnaleźć tablicę pamiątkową oraz literacki klimat dawnego miasta.\r\n\r\n2. Nowe oblicze parku – co zmieniła wielka rewitalizacja?\r\nDzięki staraniom władz samorządowych i udanej rewitalizacji, Park Tysiąclecia przeszedł w ostatnich latach spektakularną metamorfozę. Miejsce, które przez pewien czas pozostawało nieco zapomniane, dziś odzyskało pełny blask i stało się najchętniej odwiedzaną strefą rekreacji w mieście.\r\n\r\nCo zachwyca odwiedzających?\r\n\r\nKomfortowa ścieżka obwodnicowa i równe alejki – które stanowią raj dla rodziców spacerujących z wózkami, rolkarzy, rowerzystów i pasjonatów porannego joggingu.\r\nEnergooszczędne latarnie i mała architektura – nastrojowe oświetlenie po zmroku oraz liczne, wygodne ławki zachęcają do odpoczynku z książką na świeżym powietrzu.\r\nHarmonia nowości z zabytkowym charakterem – nowoczesna infrastruktura została subtelnie wkomponowana w otoczenie starych drzew, bez naruszania ich naturalnego piękna.\r\n3. Promenada na skarpie – najpiękniejszy widok na deltę Bobru i rzekę Odrę\r\nJeśli mielibyśmy wskazać absolutny „must-see” w Krośnie Odrzańskim, bez wahania wybralibyśmy parkową promenadę widokową. Położona na skraju wysokiej skarpy od strony ulicy Poznańskiej, oferuje zapierającą dech w piersiach panoramę.\r\n\r\nZ tarasu widokowego roztacza się malowniczy pejzaż na dolną część Krosna Odrzańskiego, Pradolinę Warszawsko-Berlińską oraz przede wszystkim na spektakularne ujście rzeki Bóbr do Odry. Przy dobrej widoczności wzrok sięga od Połupina aż po Dychów! To wymarzone miejsce na poranną medytację z widokiem na mgły unoszące się nad wodą, romantyczny spacer o zachodzie słońca czy wykonanie wyjątkowych zdjęć na bloga i Instagrama.\r\n\r\n4. Botaniczny skarb – błękitne dywany cebulicy i rzadkie okazy drzew\r\nPark Tysiąclecia to także prawdziwa gratka dla miłośników botaniki. Wiosną (najczęściej na przełomie kwietnia i maja) dzieje się tu prawdziwa magia – trawniki pod drzewami zakwitają tysiącami błękitnych kwiatów cebulicy syberyjskiej, tworząc baśniowy, niebieski dywan.\r\n\r\nWśród wiekowego drzewostanu możemy podziwiać dorodne lipy, strzeliste sosny amerykańskie, daglezje, tuje i klony. W upalne letnie dni korony tych drzew dają upragniony, przyjemny chłód, a jesienią mienią się niesamowitą paletą złota, czerwieni i brązu.\r\n\r\n5. Dlaczego warto wpisać Park Tysiąclecia na swoją listę weekendowych planów?\r\nNiezależnie od tego, czy jesteście mieszkańcami Krosna Odrzańskiego szukającymi wytchnienia od codziennych obowiązków, czy turystami odkrywającymi uroki województwa lubuskiego – Park Tysiąclecia oferuje idealne warunki do odpoczynku. To miejsce, w którym można naładować baterie w otoczeniu zieleni, dotknąć historii i spojrzeć na dolinę Odry z zachwycającej perspektywy.\r\n\r\nPodsumowanie: Wybierzcie się na spacer jeszcze w tym tygodniu!\r\nNie czekajcie na specjalną okazję – załóżcie wygodne buty, zabierzcie aparat i sprawdźcie na własne oczy, jak pięknie prezentuje się zrewitalizowany Park Tysiąclecia. ','6a7b5ea407bf3.jpg',5,'Krosno Odrzańskie, Polska',NULL,NULL,NULL,5.0,1,NULL,1,'approved','https://share.google/ada7IObuOjkIYL2vx','profile',NULL,NULL,NULL,0,'2026-08-11 17:40:52','2026-08-11 17:40:52'),
(11,NULL,'Strony WWW','Tworzenie stron www dla każdej z potrzeb. Jesteś przedsiębiorcą i potrzebujesz profesjonalnych rozwiązań lub poprostu chciałbyś móc pokazać się w sieci? Oferujemy szybko, profesjonalnie i w rozsądnej cenie szeroką gamę produktów cyfrowych. Od prostej wizytówki po kompletne multifunkcjonalne serwisy internetowe. Zapraszamy do kontaktu po bezpłatną wycenę. kontakt@dbdevstudio.pl','6a7b97a43845f.webp',2,'Krosno Odrzańskie',NULL,NULL,NULL,4.5,0,NULL,1,'approved','https://dbdevstudio.pl/','profile',NULL,NULL,NULL,0,'2026-08-11 21:44:04','2026-08-11 21:54:19');
/*!40000 ALTER TABLE `ads` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `auth_rate_limits`
--

DROP TABLE IF EXISTS `auth_rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_rate_limits` (
  `bucket_key` char(64) NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL DEFAULT 0,
  `window_started_at` datetime NOT NULL,
  `locked_until` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`bucket_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_rate_limits`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `auth_rate_limits` WRITE;
/*!40000 ALTER TABLE `auth_rate_limits` DISABLE KEYS */;
INSERT INTO `auth_rate_limits` VALUES
('1f4744539a8b6f182b1796aa9002774239ae53c16795278b1e1de244a2c2fcf2',2,'2026-09-16 19:01:15',NULL,'2026-09-16 17:01:30');
/*!40000 ALTER TABLE `auth_rate_limits` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `auth_users`
--

DROP TABLE IF EXISTS `auth_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_seen` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_auth_users_username` (`username`),
  KEY `idx_auth_users_last_seen` (`last_seen`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `auth_users` WRITE;
/*!40000 ALTER TABLE `auth_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `blog_categories`
--

DROP TABLE IF EXISTS `blog_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blog_categories` WRITE;
/*!40000 ALTER TABLE `blog_categories` DISABLE KEYS */;
INSERT INTO `blog_categories` VALUES
(1,'na-biezaco','Na bieżąco',NULL,1,1,'2026-08-11 12:44:26'),
(2,'zwracamy-uwage','Zwracamy uwagę',NULL,2,1,'2026-08-11 12:44:26'),
(3,'rekreacja','Rekreacja',NULL,3,1,'2026-08-11 12:44:26'),
(4,'inicjatywy','Inicjatywy',NULL,4,1,'2026-08-11 12:44:26'),
(5,'ciekawostki','Ciekawostki',NULL,5,1,'2026-08-11 12:44:26'),
(6,'historia-miasta','Historia miasta',NULL,6,1,'2026-08-11 12:44:26'),
(7,'w-planach','W planach',NULL,7,1,'2026-08-11 12:44:26');
/*!40000 ALTER TABLE `blog_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `blog_post_gallery`
--

DROP TABLE IF EXISTS `blog_post_gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_post_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_post_gallery`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blog_post_gallery` WRITE;
/*!40000 ALTER TABLE `blog_post_gallery` DISABLE KEYS */;
INSERT INTO `blog_post_gallery` VALUES
(1,4,'f49c3748c5bba3e4a957d8f7.jpg','',0,'2026-09-20 21:54:22'),
(2,4,'79a96aaae5299e4fa0c2a0da.jpg','',0,'2026-09-20 21:54:22'),
(3,5,'e9f14134afd6cb1e69357884.jpg','Ścieżka wśród zieleni i zabudowy dolnego miasta',0,'2026-09-24 18:04:14'),
(4,5,'18474e22f24c66dbb493f437.jpg','Widok trasy prowadzącej w stronę otwartych terenów nadodrzańskich',0,'2026-09-24 18:04:14'),
(5,5,'9520c284971a19a3d48de5ff.jpg','Ciąg pieszo-rowerowy w pobliżu terenów sportowych',0,'2026-09-24 18:04:14'),
(6,5,'33a226ddd47830f3457028cb.jpg','Widok na wał i dolinę Odry o zachodzie słońca',0,'2026-09-24 18:04:14'),
(7,6,'04a10578ddac8c0c43a8f09f.jpg','',0,'2026-09-24 18:20:07'),
(8,6,'e7f48ce8f0a5e7d735068513.jpg','',0,'2026-09-24 18:20:07'),
(9,6,'ed1d6632aaeb9eaaf39657be.jpg','',0,'2026-09-24 18:20:07'),
(10,7,'e7c4716c546b71204ed0c4eb.jpg','',0,'2026-09-24 19:35:16'),
(11,7,'2414f8cec09ebb7fdaa4f870.jpg','',0,'2026-09-24 19:35:16'),
(12,7,'32b04896861448aaa8f7e1a3.jpg','',0,'2026-09-24 19:35:17'),
(13,7,'705698c3910ba41ed0a8b2f5.jpg','',0,'2026-09-24 19:35:17'),
(14,7,'4970a1968d419027645fe180.jpg','',0,'2026-09-24 19:35:17'),
(15,7,'1932757d5808dddcd54ab25e.jpg','',0,'2026-09-24 19:35:17'),
(16,7,'b556c95b1e308341ed9b2d78.jpg','',0,'2026-09-24 19:35:17'),
(17,7,'487fdac611cd63fc17d8ded5.jpg','',0,'2026-09-24 19:35:17'),
(18,7,'d99c86d544f33aff0519378a.jpg','',0,'2026-09-24 19:35:17'),
(19,7,'aeb0fbb272a7d4ca0708b912.jpg','',0,'2026-09-24 19:35:17'),
(20,7,'148d40949e768cd6c152f82a.jpg','',0,'2026-09-24 19:35:18');
/*!40000 ALTER TABLE `blog_post_gallery` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `author_signature` varchar(120) DEFAULT NULL,
  `author_source` varchar(16) NOT NULL DEFAULT 'community',
  `source_type` enum('chronicle','calendar') NOT NULL DEFAULT 'chronicle',
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `submission_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `idx_blog_posts_public` (`is_active`,`submission_status`,`created_at`),
  KEY `idx_blog_posts_owner_id` (`owner_id`),
  CONSTRAINT `1` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blog_posts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `blog_posts` WRITE;
/*!40000 ALTER TABLE `blog_posts` DISABLE KEYS */;
INSERT INTO `blog_posts` VALUES
(2,NULL,'Bezpieczeństwo na drogach','<p>W Krośnie Odrzańskim trwają duże prace drogowe na drodze krajowej nr 29 (DK29) oraz budowa nowej obwodnicy miasta. Od 29 lipca 2026 roku wprowadzono kolejny etap remontu nawierzchni w ciągu DK29 (ul. Bohaterów Wojska Polskiego), co wiąże się z zamknięciem ważnego skrzyżowania i ruchem wahadłowym lub objazdami.Główne inwestycje i utrudnieniaRemont DK29: Prace prowadzi firma Strabag na odcinku ulicy Bohaterów Wojska Polskiego. Występują utrudnienia i ruch wahadłowy, a od końca lipca zamknięto ważne skrzyżowanie z wyznaczonym objazdem. Planowane zakończenie tego etapu prac przypada na 19 sierpnia 2026 roku.Budowa obwodnicy Krosna Odrzańskiego: Trwa wielka inwestycja w ciągu DK29 obejmująca budowę 11,4-kilometrowej trasy, nowych wiaduktów i mostu na Odrze. Ruch w rejonie inwestycji bywa kierowany na tymczasowe bypassy (m.in. w ciągu drogi powiatowej nr 1163F).Gdzie sprawdzić aktualny stan drogiKomunikaty policji znajdziesz na stronie Komenda Powiatowa Policji w Krośnie Odrzańskim.Bieżące informacje o utrudnieniach i ruchu wahadłowym publikuje NaviExpert Traffic.Oficjalne komunikaty o inwestycji udostępnia Generalna Dyrekcja Dróg Krajowych i Autostrad</p>','Zwracamy uwagę na trwające pracę drogowe w okolicy ulic Wojska Polskiego i Gubińskiej.',NULL,'community','chronicle','6a7b9e70d5608.avif',2,'bezpieczenstwo-na-drogach',1,1,'approved','','','2026-08-11 12:44:26','2026-08-11 22:15:30'),
(4,NULL,'Mnichów, dawniej Walki Młodych. Ściana, która pamięta Krosno lat 90-tych.','<p>Sentymentalne podróże w przeszłość.</p>\n<p>Dla jednych to dziś ulica Mnichów. Dla innych — na zawsze Walki Młodych. I właśnie tam, na długiej ścianie wśród starych zabudowań, zostały ślady, których nie pokaże żadna pocztówka z Krosna Odrzańskiego. Wyblakłe imiona, daty, pseudonimy, resztki napisów. Z pozoru zwykłe bazgroły, ale dla kogoś, kto w latach 90. znał tę okolicę, mogą być jak urwany kadr z własnej młodości.To nie jest elegancka historia z miejskiego folderu. To historia przejść między kamienicami, krótkich dróg „na miasto”, czekania na znajomych i miejsc, których nie trzeba było nikomu tłumaczyć adresem. Wystarczyło powiedzieć: „spotkamy się na Walki Młodych”. Każdy wiedział, o który kawałek Krosna chodzi.Nie ma sensu udawać, że stary mur jest zabytkiem. Jest jednak czymś równie ważnym — śladem codzienności ludzi, którzy tu dorastali. Przypomina, że Krosno Odrzańskie to nie tylko Odra, rynek i oficjalne daty. To także zakamarki takie jak ten: trochę zaniedbane, trochę zapomniane, ale pełne historii zapisanej przez mieszkańców własną ręką.Macie swoje krośnieńskie miejscówki z dawnych lat? Takie, o których mówiło się tylko po swojemu i bez podawania adresu? Pokażcie je na zdjęciach i dopiszcie kilka zdań wspomnienia. Niech ta kronika będzie także o naszym, tym codziennym, zapamiętanym od środka Krośnie Odrzańskim.</p>','Sentymentalne podróże w przeszłość. Dla jednych to dziś ulica Mnichów. Dla innych — na zawsze Walki Młodych. I właśnie tam, na długiej ścianie wśród starych za…','Administracja','community','chronicle','61f647d4e90dfdd291597cc1.jpg',6,'mnichow-dawniej-walki-mlodych-ciana-ktora-pamieta-krosno-lat-90-tych',0,1,'approved',NULL,NULL,'2026-09-20 21:54:22','2026-09-20 21:55:16'),
(5,NULL,'Ścieżka na wale w Krośnie Odrzańskim. Miejsce na spacer, rower i widoki','Są w Krośnie Odrzańskim takie miejsca, które najlepiej odwiedzić bez pośpiechu. Nie trzeba planować całodziennej wyprawy ani jechać za miasto. Wystarczy wybrać się na ścieżkę poprowadzoną wzdłuż nowych zabezpieczeń przeciwpowodziowych dolnej części miasta. Można przejść się pieszo, wsiąść na rower albo po prostu usiąść na ławce i zobaczyć, jak nadodrzański krajobraz zmienia się wraz ze światłem.\n\nTa trasa powstała przy okazji inwestycji, której najważniejszym celem jest bezpieczeństwo mieszkańców. Krosno Odrzańskie przez lata boleśnie przekonywało się, czym może być wysoka woda. Powodzie z 1997 i 2010 roku pozostały w pamięci wielu rodzin, dlatego budowa nowego systemu ochrony nie była kwestią estetyki ani wygody, lecz realnej potrzeby. Zakończone w 2024 roku prace objęły między innymi budowę dziewięciu nowych wałów o łącznej długości około 5,9 kilometra, modernizację kanałów ulgi oraz przebudowę systemu odprowadzania wód deszczowych.\n\nNajciekawsze jest jednak to, że infrastruktura zaprojektowana z myślą o sytuacji kryzysowej na co dzień nie pozostaje niedostępna. Drogi serwisowe przy wałach mogą służyć mieszkańcom i turystom jako ciągi pieszo-rowerowe. W kilku miejscach zbudowano także schody skarpowe, dzięki którym łatwiej dostać się w pobliże nadodrzańskich terenów. W praktyce powstała trasa, która pozwala obejść sporą część dolnego miasta i spojrzeć na Krosno z perspektywy, jakiej wcześniej wielu mieszkańców nie miało okazji poznać.\n\nNa zdjęciach widać, jak różne potrafią być odcinki tej ścieżki. Raz prowadzi ona między zielenią a otwartymi łąkami, innym razem biegnie przy murze oporowym, zabudowaniach, obiektach sportowych albo uporządkowanym placu z ławkami i oświetleniem. Nawierzchnia jest równa i wygodna, więc trasa dobrze nadaje się zarówno na spokojny spacer, jak i na przejażdżkę rowerem. To propozycja dla tych, którzy chcą zrobić kilka kilometrów po pracy, dla rodziny z dziećmi, dla seniora szukającego łagodnej trasy i dla każdego, kto ma ochotę przewietrzyć głowę bez opuszczania miasta.\n\nWarto wybrać się tam późnym popołudniem. Wtedy ścieżka nie jest już tylko elementem technicznej budowli. Słońce kładzie się na trawie, stare drzewa nabierają złotego koloru, a szerokie niebo nad łąkami potrafi wyglądać zupełnie inaczej niż z poziomu miejskiej ulicy. W jedną stronę widać otwartą przestrzeń doliny Odry, w drugą zabudowę Krosna i jego charakterystyczne punkty. To jeden z tych spacerów, podczas których nie trzeba szukać specjalnych atrakcji, bo widok zmienia się co kilka minut.\n\nTrasa ma też swój historyczny wymiar. W pobliżu znajduje się Zamek Piastowski, którego początki sięgają pierwszej połowy XIII wieku. Według informacji Centrum Artystyczno-Kulturalnego „Zamek” rezydencję zbudował książę śląski Henryk Brodaty, a w 1238 roku właśnie tutaj zmarł. Dziś zamek jest nie tylko zabytkiem, lecz także miejscem wystaw, wydarzeń i spotkań mieszkańców. Połączenie spaceru po wale z wizytą na zamkowym dziedzińcu daje gotowy pomysł na spokojne, lokalne popołudnie.\n\nJeszcze ciekawszy staje się moment, w którym uświadomimy sobie, że w tej samej przestrzeni spotykają się różne epoki. Obok piastowskiego zamku zachowały się ślady niemieckich umocnień z pierwszej połowy XX wieku. Opisy grupy bojowej schronów w Krośnie nad Odrą wskazują, że jeden ze schronów, oznaczony jako C.9, został wkomponowany w dawną kurtynę ziemną fortyfikacji otaczających zamek. Cały system powstał z myślą o obronie przeprawy przez Odrę. Dla spacerowicza może to być po prostu niepozorny betonowy obiekt, ale dla osoby zainteresowanej historią jest to ślad opowieści o granicy, wojsku i strategicznym znaczeniu krośnieńskiej przeprawy.\n\nW tę panoramę wpisuje się także stalowy most nad Odrą, znany dziś jako Most Chrobrego. Przez dziesięciolecia był jednym z najbardziej rozpoznawalnych elementów miasta. Jego modernizacja miała nie tylko poprawić stan techniczny przeprawy, ale również zwiększyć prześwit potrzebny do prowadzenia zimowych akcji lodołamania. To ważny szczegół, bo pokazuje, że ochrona przeciwpowodziowa nie kończy się na samym wale. Obejmuje także rzekę, kanały, mosty, odwodnienie i możliwość reagowania wtedy, gdy pojawia się zagrożenie.\n\nNowe zabezpieczenia szybko przestały być wyłącznie planem na przyszłość. W 2024 roku, kiedy fala wezbraniowa dotarła do regionu, dolna część Krosna przeszła przez ten sprawdzian niemal suchą stopą. Lokalne relacje wskazywały, że woda pojawiła się jedynie na pojedynczych działkach, podczas gdy w okolicznych miejscowościach sytuacja była znacznie trudniejsza. Nie oznacza to, że można zapomnieć o ostrożności ani że żadna powódź nie jest już możliwa. Pokazuje jednak, że inwestycja spełnia swoje podstawowe zadanie i daje mieszkańcom większe bezpieczeństwo.\n\nA kiedy nie ma wysokiej wody, ten sam system może służyć zwyczajnym, codziennym przyjemnościom. Można przejechać się rowerem, zatrzymać przy ławce, zrobić zdjęcie zachodu słońca, przejść się z psem albo pokazać dzieciom, jak wygląda miasto od strony łąk i kanałów. Warto tylko pamiętać, że jest to wspólna przestrzeń dla pieszych i rowerzystów. Rower najlepiej prowadzić spokojnie, zwalniać przy mijaniu innych osób i zwracać uwagę na oznakowanie oraz ewentualne czasowe ograniczenia związane z pracami utrzymaniowymi lub wysokim stanem wody.\n\nTa ścieżka nie konkuruje z lasem, parkiem ani klasyczną trasą rowerową. Jej siła polega na czymś innym. Łączy bezpieczeństwo, przyrodę, codzienny ruch i historię miasta. W ciągu jednego spaceru można zobaczyć szeroką dolinę Odry, zamek pamiętający czasy Piastów, ślady dawnych fortyfikacji, most i współczesną infrastrukturę, która chroni domy przed żywiołem. To dużo jak na trasę, na którą można wejść niemal prosto z miejskiej ulicy.\n\nDlatego warto dać jej szansę nie tylko wtedy, gdy przyjeżdżają goście. Najlepiej wybrać własny odcinek i wracać tam o różnych porach roku. Wiosną zobaczyć budzącą się zieleń, latem przejechać całość rowerem, jesienią obserwować kolory drzew, a zimą spojrzeć na dolne miasto w surowej, spokojniejszej scenerii. Nowy wał miał przede wszystkim chronić Krosno Odrzańskie przed powodzią, ale przy okazji stworzył mieszkańcom miejsce, w którym można odpocząć, poruszać się i zobaczyć swoje miasto z zupełnie nowej strony.\n','','Administrator','official','chronicle','ad5cb5c66a7c7ef7bf55dd09.jpg',3,'ciezka-na-wale-w-krosnie-odrzanskim-miejsce-na-spacer-rower-i-widoki',0,1,'approved','','','2026-09-24 18:04:14','2026-09-24 18:04:14'),
(6,NULL,'Zamek Piastowski w Krośnie Odrzańskim. Historia, która wciąż żyje','Zamek Piastowski w Krośnie Odrzańskim nie jest tylko zabytkiem, który mija się podczas spaceru po mieście. To miejsce, w którym historia Krosna staje się bardzo konkretna. Wystarczy przejść przez bramę i spojrzeć na mury, dziedziniec oraz zachowane skrzydła, aby zobaczyć, że przez stulecia zamek był jednocześnie domem książąt, warownią, twierdzą, koszarami, magazynem, muzeum i miejscem spotkań mieszkańców.\n\nJego początki sięgają pierwszej połowy XIII wieku. Właśnie wtedy książę śląski Henryk Brodaty zbudował w Krośnie swoją rezydencję. Zamek powstał na północno-wschodnim skraju dzisiejszego miasta, w miejscu, które nie było wcześniej zabudowane. Badania archeologiczne i dendrochronologiczne wskazują, że najstarsze ślady osadnictwa na zamkowym dziedzińcu pochodzą z początku XIII wieku. To oznacza, że kiedy Krosno otrzymywało prawa miejskie, zamek już stawał się ważnym punktem lokalnego krajobrazu.\n\nNie był to pałac zbudowany wyłącznie dla wygody. Krosno leżało przy ważnych przeprawach i na styku różnych wpływów politycznych, dlatego rezydencja musiała pełnić również funkcję obronną. Pierwotne założenie składało się prawdopodobnie z budynku mieszkalnego, dziedzińca i wysokiego muru. Z czasem zamek rozbudowywano, a jego niezależny system umocnień obejmował także fosę i mury obronne. Przez wiele lat była to jedna z głównych rezydencji Henryka Brodatego oraz miejsce, z którego organizowano wyprawy wojenne.\n\nW krośnieńskim zamku rozegrał się także ostatni rozdział życia księcia. Henryk Brodaty zmarł tutaj 19 marca 1238 roku. To jedna z tych dat, które zmieniają sposób patrzenia na dobrze znane miejsce. Dziedziniec, po którym dziś można przejść podczas zwiedzania, był świadkiem wydarzeń związanych z jednym z najważniejszych książąt śląskich swojej epoki.\n\nKilka lat później zamek znalazł się w centrum kolejnych dramatycznych wydarzeń. W 1241 roku, podczas najazdu mongolskiego, schroniła się tutaj Jadwiga Śląska wraz z księżną Anną i mniszkami z klasztoru w Trzebnicy. Nie była to przypadkowa kryjówka. Warownia miała mury, fosę i strategiczne położenie, dzięki czemu mogła zapewnić bezpieczeństwo w niespokojnych czasach. Pamięć o związkach księżnej Jadwigi z Krosnem jest obecna w mieście do dziś, między innymi w wydarzeniach organizowanych przez Centrum Artystyczno-Kulturalne „Zamek”.\n\nPrzez kolejne stulecia zamek zmieniał właścicieli, mieszkańców i wygląd. W XIV i XV wieku książęta głogowscy rozbudowali założenie o kolejne skrzydła oraz budynek bramny z wieżą. Po 1476 roku, gdy mieszkała tutaj Barbara, wdowa po Henryku XI, wnętrza przebudowano w bardziej reprezentacyjnym stylu. Zamek coraz wyraźniej stawał się siedzibą książęcą, a nie tylko obiektem obronnym.\n\nSzczególnie ciekawy ślad pozostawiła przebudowa z XVI wieku. Za czasów margrabiego Jana Jerzego powstały renesansowe krużganki południowego skrzydła. Ich arkady wychodzą na dziedziniec i do dziś są jednym z najbardziej charakterystycznych elementów zamku. W zachowanych wnętrzach można dostrzec także pozostałości gotyckiej kuchni, w tym dawne paleniska. Takie detale przypominają, że historia zabytku nie składa się wyłącznie z wielkich bitew i nazwisk władców. Tworzą ją również codzienne czynności ludzi, którzy przez lata mieszkali, pracowali i gotowali w zamkowych murach.\n\nW XVII wieku zamek ponownie przybrał bardziej wojskowy charakter. W czasie wojny trzydziestoletniej Szwedzi rozpoczęli w 1633 roku budowę twierdzy. Zamek oddzielono od miasta dodatkowym murem i fosą, usypano ziemne umocnienia, a z miastem połączył go most zwodzony. Dawna rezydencja książęca stała się siedzibą sztabu. To kolejny dowód na to, jak ważne strategicznie było Krosno i jego położenie nad Odrą.\n\nPóźniejsze dzieje nie były już dla zamku tak pomyślne. W XVIII wieku obiekt zaczął podupadać, a w następnym stuleciu wykorzystywano go jako magazyny wojskowe i prochownię. W latach 1886–1887 wojska pruskie przebudowały zabytkową budowlę na koszary. Uproszczono wtedy część elewacji i zmieniono wnętrza. Zamek tracił dawny reprezentacyjny charakter, ale nadal pozostawał ważnym elementem miasta.\n\nW pierwszej połowie XX wieku część pomieszczeń przeznaczono na muzeum i mieszkania. Później nadeszła katastrofa, która na zawsze zmieniła wygląd Krosna. W lutym 1945 roku lewobrzeżna część miasta wraz z zamkiem spłonęła. Z dawnej budowli pozostały przede wszystkim mury. Dla mieszkańców, którzy po wojnie zaczęli tworzyć w Krośnie swoje nowe życie, ruiny były jednocześnie śladem przedwojennego miasta i trudnym przypomnieniem o zniszczeniach.\n\nZamek nie został jednak pozostawiony sam sobie. W latach 1964–1966 przeprowadzono prace zabezpieczające ruiny. Później odbudowano budynek bramny, uporządkowano dziedziniec, a na początku XXI wieku odrestaurowano południowe skrzydło i dawną kaplicę, która w późniejszych czasach pełniła także funkcję magazynu i wozowni. Dzięki tym pracom zabytek nie jest dziś wyłącznie romantyczną ruiną. Można wejść na jego teren, zobaczyć zachowane fragmenty dawnego założenia i lepiej zrozumieć, jak zmieniała się architektura zamku.\n\nWłaśnie ta różnorodność jest największą wartością krośnieńskiego zamku. Zachodnie skrzydło z budynkiem bramnym, sienią wjazdową, wieżą i basteją sąsiaduje z renesansowymi krużgankami południowego skrzydła. Część północna i wschodnia pozostały trwałą ruiną. Obok siebie można więc zobaczyć kilka warstw historii: średniowieczne mury, renesansową oprawę, wojskowe przekształcenia i ślady powojennej odbudowy.\n\nDziś zamek pełni funkcję, której nie da się zamknąć w jednej definicji. W jego izbach muzealnych prezentowana jest historia Krosna Odrzańskiego, a w galeriach można oglądać prace lokalnych twórców. Na dziedzińcu odbywają się koncerty, turnieje rycerskie, warsztaty i wydarzenia dla dzieci. Działa tu Centrum Artystyczno-Kulturalne „Zamek”, Punkt Informacji Turystycznej oraz organizacje związane z lokalną kulturą. Dawna rezydencja książęca nadal służy mieszkańcom, tylko w zupełnie inny sposób niż osiemset lat temu.\n\nWarto odwiedzić zamek nie tylko przy okazji dużej imprezy. Zwykłe przejście przez dziedziniec potrafi być ciekawą lekcją historii, zwłaszcza jeśli spojrzy się na budowlę bez pośpiechu. Można wyobrazić sobie Henryka Brodatego planującego wyprawę, Jadwigę Śląską szukającą schronienia, żołnierzy wzmacniających twierdzę, a później mieszkańców, którzy po wojnie próbowali ocalić to, co zostało. Każda epoka zostawiła tutaj własny ślad.\n\nZamek Piastowski jest jednym z tych miejsc, które najlepiej pokazują, że historia Krosna Odrzańskiego nie zaczyna się ani nie kończy na jednej dacie. To opowieść o pograniczu, przeprawie nad Odrą, książęcej rezydencji, wojnach, pożarze i odbudowie. Przede wszystkim jest to jednak historia miejsca, które mimo wielu zmian nie zniknęło z mapy miasta. Nadal można przekroczyć jego bramę, stanąć na dziedzińcu i zobaczyć, jak dawne mury wracają do codziennego życia.\n','','Administrator','official','chronicle','b8a934f7db8a11b310a1960d.jpg',6,'zamek-piastowski-w-krosnie-odrzanskim-historia-ktora-wciaz-zyje',0,1,'approved','','','2026-09-24 18:20:07','2026-09-24 18:20:07'),
(7,NULL,'Most Chrobrego','Są w Krośnie Odrzańskim miejsca, które trudno wyobrazić sobie bez rzeki. Jednym z nich jest stalowa przeprawa spinająca oba brzegi Odry. Przez lata mówiono o niej po prostu „most na Odrze”. Dziś nosi imię Mostu Króla Bolesława Chrobrego i pozostaje jednym z najbardziej rozpoznawalnych elementów miejskiego krajobrazu.\r\n\r\nHistoria przeprawy jest jednak znacznie starsza niż zachowana do dziś stalowa konstrukcja. Przejście przez Odrę miało dla Krosna znaczenie już w średniowieczu. Pierwsza wzmianka o drewnianym moście pochodzi z około 1300 roku. Kolejne drewniane przeprawy służyły mieszkańcom przez stulecia, ale pod koniec XIX wieku coraz wyraźniej było widać, że rozwijający się ruch rzeczny i drogowy potrzebuje rozwiązania trwalszego oraz bardziej funkcjonalnego.\r\n\r\nNowy most powstał w latach 1903–1905 na trasie prowadzącej z Zielonej Góry w stronę Słubic. Projekt przygotował Ziegler, a wykonanie powierzono firmie Beuchelt & Co. z Zielonej Góry, jednej z ważnych śląskich fabryk specjalizujących się w budowie mostów, konstrukcji stalowych i wagonów. Uroczyste przejście nową przeprawą odbyło się w lipcu 1905 roku, podczas obchodów dziewięćsetlecia miasta.\r\n\r\nKonstrukcja mostu od początku robiła wrażenie. To stalowa, nitowana przeprawa kratownicowa o trzech przęsłach. Jej całkowita długość wynosi około 164 metrów, a ciężar konstrukcji stalowej szacuje się na około 580 ton. Most opiera się na dwóch betonowych przyczółkach oraz dwóch filarach ustawionych w nurcie rzeki. Filary oblicowano granitem, dzięki czemu techniczna budowla otrzymała także trwały, architektoniczny charakter.\r\n\r\nO wyglądzie mostu decydują nie tylko belki i nity. Przy wjazdach zachowały się ażurowe portale, a na konstrukcji widoczne są dekoracyjne wsporniki pod lampy. Dawne projekty pokazują również ozdobne elementy heraldyczne związane z epoką, w której przeprawa powstawała. To połączenie inżynierii i dekoracji sprawia, że most nie jest anonimowym odcinkiem drogi, lecz jednym z ciekawszych zabytków techniki w regionie.\r\n\r\nPrzez dziesięciolecia stalowa konstrukcja musiała znosić nie tylko codzienny ruch, lecz także wydarzenia, które zmieniały miasto. W 1936 roku most poszerzono i wzmocniono. W ostatnich miesiącach wojny, 15 lutego 1945 roku, został wysadzony przez wycofujące się wojska niemieckie. Zniszczoną przeprawę zastępowały najpierw most pontonowy, a później drewniana konstrukcja. Mieszkańcy korzystali także z prowizorycznej kładki przeprowadzonej pomiędzy zachowanymi fragmentami mostu.\r\n\r\nOdbudowa była konieczna nie tylko dla wygody mieszkańców. Krosno Odrzańskie od zawsze było miastem przeprawy, a połączenie obu części miasta miało znaczenie dla codziennego życia, transportu i rozwoju gospodarczego. Most odbudowano po wojnie, a w 1948 roku ponownie służył komunikacji. W 1960 roku na jezdni położono asfalt. Kolejne prace remontowe pozwoliły zachować obiekt i przystosować go do współczesnego użytkowania.\r\n\r\nDziś most jest jednocześnie drogą, punktem widokowym i świadectwem historii. Z jego okolic można spojrzeć na dolne i górne miasto, nurt Odry oraz nadodrzański krajobraz. W pobliżu spotykają się ślady różnych epok: średniowieczne dzieje Krosna, piastowski zamek, dawne umocnienia i stalowa przeprawa z początku XX wieku.\r\n\r\nSzczególnie interesujące jest to, że zachowane materiały projektowe pozwalają zajrzeć do mostu głębiej niż podczas zwykłego spaceru. Rzuty, przekroje, detale węzłów i rozwiązania podpór pokazują skalę pracy inżynierów oraz rzemieślników, którzy ponad sto lat temu zaprojektowali i zbudowali tę przeprawę. Każda nitowana blacha i każdy element kratownicy przypominają, że most powstał jako precyzyjnie obliczona całość.\r\n\r\nNadanie przeprawie imienia Króla Bolesława Chrobrego podkreśla związek mostu z najstarszą historią miasta. Krosno pojawia się w źródłach już na początku XI wieku, a jego położenie nad Odrą od stuleci decydowało o znaczeniu miejscowości. Nowa nazwa nie zmienia stalowej konstrukcji, ale porządkuje sposób, w jaki mieszkańcy mogą o niej mówić i jak mogą ją zapamiętać.\r\n\r\nMost Chrobrego nie jest zabytkiem zamkniętym w muzeum. Nadal pozostaje częścią miasta i codziennej drogi. Właśnie dlatego jego historia jest tak bliska mieszkańcom. Można przejść obok niego bez pośpiechu, zwrócić uwagę na rytm kratownicy, kamienne filary i detale przy wjazdach, a potem spojrzeć na Odrę. W tej jednej przeprawie spotykają się technika, pamięć i krajobraz Krosna Odrzańskiego.','Historia jednej z najbardziej rozpoznawalnych przepraw Krosna Odrzańskiego — od średniowiecznych drewnianych mostów po stalową konstrukcję z początku XX wieku.','Administracja','official','chronicle','6c7b1534ef2eee2999dda8fd.jpg',5,'most-chrobrego',0,1,'approved','','','2026-09-24 19:35:16','2026-09-24 19:35:16');
/*!40000 ALTER TABLE `blog_posts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `calendar_events`
--

DROP TABLE IF EXISTS `calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_date` date NOT NULL,
  `event_time` varchar(20) NOT NULL DEFAULT '',
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(180) DEFAULT NULL,
  `category` varchar(40) NOT NULL DEFAULT 'Mieszkańcy',
  `chronicle_post_id` int(11) DEFAULT NULL,
  `created_ip_hash` char(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_calendar_date` (`event_date`),
  KEY `idx_calendar_active` (`event_date`,`id`),
  KEY `idx_calendar_chronicle` (`chronicle_post_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_events`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `calendar_events` WRITE;
/*!40000 ALTER TABLE `calendar_events` DISABLE KEYS */;
INSERT INTO `calendar_events` VALUES
(1,'2026-09-21','','Testuję','Test opis','','Miasto',NULL,'e04e2abec47a33888036f6e54a7788f0e1ac609aad417e586527dadc600eb0df','2026-09-21 09:04:20');
/*!40000 ALTER TABLE `calendar_events` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chat_admin_log`
--

DROP TABLE IF EXISTS `chat_admin_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_admin_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_session_id` bigint(20) unsigned NOT NULL,
  `target_session_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(48) NOT NULL,
  `details` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chat_admin_log_actor_time` (`actor_session_id`,`created_at`),
  KEY `idx_chat_admin_log_target_time` (`target_session_id`,`created_at`),
  CONSTRAINT `fk_chat_admin_log_actor` FOREIGN KEY (`actor_session_id`) REFERENCES `chat_sessions` (`id`),
  CONSTRAINT `fk_chat_admin_log_target` FOREIGN KEY (`target_session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_admin_log`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chat_admin_log` WRITE;
/*!40000 ALTER TABLE `chat_admin_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_admin_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chat_message_images`
--

DROP TABLE IF EXISTS `chat_message_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_message_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `storage_name` varchar(64) DEFAULT NULL,
  `mime_type` enum('image/jpeg','image/png','image/webp') NOT NULL,
  `file_size` int(10) unsigned NOT NULL,
  `expires_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_message_images_message` (`message_id`),
  KEY `idx_chat_message_images_expiry` (`expires_at`,`deleted_at`),
  CONSTRAINT `fk_chat_message_images_message` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_message_images`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chat_message_images` WRITE;
/*!40000 ALTER TABLE `chat_message_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_message_images` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `author_session_id` bigint(20) unsigned NOT NULL,
  `author_role` enum('guest','moderator','admin') NOT NULL DEFAULT 'guest',
  `body` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by_session_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_chat_messages_active_feed` (`id`,`deleted_at`),
  KEY `idx_chat_messages_author_created` (`author_session_id`,`created_at`),
  KEY `fk_chat_messages_deleted_by` (`deleted_by_session_id`),
  KEY `idx_chat_messages_retention_created` (`created_at`),
  CONSTRAINT `fk_chat_messages_author` FOREIGN KEY (`author_session_id`) REFERENCES `chat_sessions` (`id`),
  CONSTRAINT `fk_chat_messages_deleted_by` FOREIGN KEY (`deleted_by_session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` VALUES
(2,6,'admin','Zapraszam do korzystania. Wszystko za darmo bez żadnych formalności','2026-09-24 21:54:43',NULL,NULL),
(3,8,'admin','Zapraszam do korzystania. Wszystko za darmo bez żadnych formalności','2026-09-25 15:13:15',NULL,NULL),
(5,9,'guest','Przy stole ktoś oczekuje właśnie na rozgrywkę. Czy chcesz zagrać teraz w pokera? Jeżeli tak, to zapraszamy do udziału poprzez ten link: https://66600.pl/poker/?stol=1','2026-09-25 16:26:50',NULL,NULL),
(6,9,'guest','Przy stole ktoś oczekuje właśnie na rozgrywkę. Czy chcesz zagrać teraz w pokera? Jeżeli tak, to zapraszamy do udziału poprzez ten link: https://66600.pl/poker/?stol=5','2026-09-25 19:07:06',NULL,NULL);
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chat_nick_accounts`
--

DROP TABLE IF EXISTS `chat_nick_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_nick_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(32) NOT NULL,
  `nickname_key` varchar(160) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `terms_version` varchar(24) NOT NULL DEFAULT '2026-08-16',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_nick_accounts_nickname_key` (`nickname_key`),
  KEY `idx_chat_nick_accounts_active` (`is_active`,`nickname_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_nick_accounts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chat_nick_accounts` WRITE;
/*!40000 ALTER TABLE `chat_nick_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_nick_accounts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chat_nick_claims`
--

DROP TABLE IF EXISTS `chat_nick_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_nick_claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(32) NOT NULL,
  `nickname_key` varchar(128) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `approved_by_admin_user_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_nick_claims_nickname_key` (`nickname_key`),
  KEY `idx_chat_nick_claims_token_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_nick_claims`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chat_nick_claims` WRITE;
/*!40000 ALTER TABLE `chat_nick_claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_nick_claims` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chat_nickname_blocks`
--

DROP TABLE IF EXISTS `chat_nickname_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_nickname_blocks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(32) NOT NULL,
  `nickname_key` varchar(160) NOT NULL,
  `blocked_until` datetime NOT NULL,
  `blocked_by_session_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_nickname_blocks_key` (`nickname_key`),
  KEY `idx_chat_nickname_blocks_until` (`blocked_until`),
  KEY `fk_chat_nickname_blocks_actor` (`blocked_by_session_id`),
  CONSTRAINT `fk_chat_nickname_blocks_actor` FOREIGN KEY (`blocked_by_session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_nickname_blocks`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chat_nickname_blocks` WRITE;
/*!40000 ALTER TABLE `chat_nickname_blocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_nickname_blocks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chat_private_messages`
--

DROP TABLE IF EXISTS `chat_private_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_private_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sender_session_id` bigint(20) unsigned NOT NULL,
  `recipient_session_id` bigint(20) unsigned DEFAULT NULL,
  `admin_user_id` int(10) unsigned NOT NULL,
  `body` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_chat_private_recipient_feed` (`recipient_session_id`,`id`),
  KEY `idx_chat_private_admin_inbox` (`admin_user_id`,`id`),
  KEY `idx_chat_private_sender_recipient` (`sender_session_id`,`recipient_session_id`,`id`),
  KEY `idx_chat_private_retention_created` (`created_at`),
  CONSTRAINT `fk_chat_private_recipient` FOREIGN KEY (`recipient_session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_chat_private_sender` FOREIGN KEY (`sender_session_id`) REFERENCES `chat_sessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_private_messages`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chat_private_messages` WRITE;
/*!40000 ALTER TABLE `chat_private_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_private_messages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chat_sessions`
--

DROP TABLE IF EXISTS `chat_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token_hash` char(64) DEFAULT NULL,
  `nickname` varchar(32) NOT NULL,
  `nickname_key` varchar(160) NOT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `role` enum('guest','moderator','admin') NOT NULL DEFAULT 'guest',
  `owner_admin_user_id` int(10) unsigned DEFAULT NULL,
  `moderator_until` datetime DEFAULT NULL,
  `muted_until` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen` datetime NOT NULL DEFAULT current_timestamp(),
  `terms_accepted_at` datetime DEFAULT NULL,
  `last_message_at` datetime DEFAULT NULL,
  `last_read_public_message_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chat_sessions_nickname_active` (`nickname_key`,`is_active`),
  UNIQUE KEY `uq_chat_sessions_token_hash` (`token_hash`),
  UNIQUE KEY `uq_chat_sessions_admin_owner` (`owner_admin_user_id`),
  KEY `idx_chat_sessions_active_last_seen` (`is_active`,`last_seen`),
  KEY `idx_chat_sessions_role_until` (`role`,`moderator_until`),
  KEY `idx_chat_sessions_muted_until` (`muted_until`),
  KEY `idx_chat_sessions_account_id` (`account_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_sessions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chat_sessions` WRITE;
/*!40000 ALTER TABLE `chat_sessions` DISABLE KEYS */;
INSERT INTO `chat_sessions` VALUES
(1,NULL,'_expired_1','_expired_1',NULL,'guest',NULL,NULL,NULL,0,'2026-09-10 14:30:13','2026-09-10 14:06:21','2026-09-10 14:30:04',0,'2026-09-10 16:06:17'),
(2,NULL,'Administrator','_admin_expired_2',NULL,'guest',NULL,NULL,NULL,0,'2026-09-16 17:00:09','2026-09-16 16:57:00',NULL,0,'2026-09-16 18:56:59'),
(3,NULL,'Administrator','_admin_expired_3',NULL,'guest',NULL,NULL,NULL,0,'2026-09-17 00:16:38','2026-09-17 00:16:26',NULL,0,'2026-09-17 02:16:23'),
(4,NULL,'_expired_4','_expired_4',NULL,'guest',NULL,NULL,NULL,0,'2026-09-17 23:47:24','2026-09-17 23:47:19',NULL,0,'2026-09-18 01:47:16'),
(5,NULL,'Administrator','_admin_expired_5',NULL,'guest',NULL,NULL,NULL,0,'2026-09-20 21:57:14','2026-09-20 21:56:10',NULL,0,'2026-09-20 23:56:08'),
(6,NULL,'Administrator','_admin_expired_6',NULL,'guest',NULL,NULL,NULL,0,'2026-09-24 19:54:55','2026-09-24 19:25:46','2026-09-24 19:54:43',0,'2026-09-24 21:25:43'),
(7,NULL,'Administrator','_admin_expired_7',NULL,'guest',NULL,NULL,NULL,0,'2026-09-24 20:26:08','2026-09-24 20:21:02',NULL,2,'2026-09-24 22:20:59'),
(8,NULL,'Administrator','_admin_expired_8',NULL,'guest',NULL,NULL,NULL,0,'2026-09-25 14:28:15','2026-09-25 13:13:01','2026-09-25 13:13:15',5,'2026-09-25 15:12:59'),
(9,NULL,'Stół pokerowy','stół pokerowy',NULL,'guest',NULL,NULL,NULL,1,'2026-09-25 17:07:06','2026-09-25 13:39:40','2026-09-25 17:07:06',0,'2026-09-25 15:39:40'),
(12,NULL,'Administrator','_admin_expired_12',NULL,'guest',NULL,NULL,NULL,0,'2026-09-25 15:49:50','2026-09-25 15:48:34',NULL,5,'2026-09-25 17:48:32'),
(13,NULL,'Administrator','_admin_expired_13',NULL,'guest',NULL,NULL,NULL,0,'2026-09-25 17:09:00','2026-09-25 17:07:47',NULL,6,'2026-09-25 19:07:45'),
(14,NULL,'Administrator','_admin_expired_14',NULL,'guest',NULL,NULL,NULL,0,'2026-09-25 20:02:32','2026-09-25 19:52:37',NULL,6,'2026-09-25 21:52:35');
/*!40000 ALTER TABLE `chat_sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chronicle_comment_likes`
--

DROP TABLE IF EXISTS `chronicle_comment_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chronicle_comment_likes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `comment_id` bigint(20) unsigned NOT NULL,
  `voter_hash` char(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chronicle_comment_like_voter` (`comment_id`,`voter_hash`),
  KEY `idx_chronicle_comment_likes_comment` (`comment_id`),
  CONSTRAINT `fk_chronicle_comment_likes_comment` FOREIGN KEY (`comment_id`) REFERENCES `chronicle_comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chronicle_comment_likes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chronicle_comment_likes` WRITE;
/*!40000 ALTER TABLE `chronicle_comment_likes` DISABLE KEYS */;
/*!40000 ALTER TABLE `chronicle_comment_likes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chronicle_comments`
--

DROP TABLE IF EXISTS `chronicle_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chronicle_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `author_name` varchar(80) NOT NULL,
  `content` text NOT NULL,
  `commenter_hash` char(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_chronicle_comments_post_created` (`post_id`,`created_at`,`id`),
  KEY `idx_chronicle_comments_author_rate` (`commenter_hash`,`created_at`),
  CONSTRAINT `fk_chronicle_comments_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chronicle_comments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chronicle_comments` WRITE;
/*!40000 ALTER TABLE `chronicle_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `chronicle_comments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `chronicle_post_votes`
--

DROP TABLE IF EXISTS `chronicle_post_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chronicle_post_votes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `voter_hash` char(64) NOT NULL,
  `vote` tinyint(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_chronicle_post_vote_voter` (`post_id`,`voter_hash`),
  KEY `idx_chronicle_post_votes_post_vote` (`post_id`,`vote`),
  CONSTRAINT `fk_chronicle_post_votes_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chronicle_post_votes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `chronicle_post_votes` WRITE;
/*!40000 ALTER TABLE `chronicle_post_votes` DISABLE KEYS */;
INSERT INTO `chronicle_post_votes` VALUES
(1,4,'647a8618ad731c575cff473885d7d0dba044fc6117b478288f492da9d2c832d0',1,'2026-09-20 21:55:56'),
(2,6,'647a8618ad731c575cff473885d7d0dba044fc6117b478288f492da9d2c832d0',1,'2026-09-24 18:40:41'),
(3,7,'647a8618ad731c575cff473885d7d0dba044fc6117b478288f492da9d2c832d0',1,'2026-09-24 19:38:07'),
(4,7,'9846c588c5a85e5d12d5f0d7183205ad080bf024554f4ed4bbdd191ca7d2fec9',1,'2026-09-24 20:16:59'),
(5,6,'6f0ccc409feb3394c873873183b5fb71c7ad564323bf895d91ec9662eb14ace5',1,'2026-09-25 01:59:58'),
(6,7,'95c1a70d41f6905f012e9771b1377909f5026d56c3353fa96e6da043bc7bc0b9',1,'2026-09-25 02:41:20'),
(7,7,'70d5c0aa3953ab06d68f4f9b85ef9c5b56c22aa3c7ff522f3f42fcfe168572ca',1,'2026-09-25 03:55:32'),
(8,6,'7699612b6f2564bc0b7dcff9a05609528bd5e71bfea819dfb50d52c37b1e112e',1,'2026-09-25 15:49:26'),
(9,4,'7699612b6f2564bc0b7dcff9a05609528bd5e71bfea819dfb50d52c37b1e112e',1,'2026-09-25 15:49:36'),
(10,7,'34f76e5a20b3bde7bc6ee0c7ae5fbadc05d3fbb01eb63f4aa78423b10e16209f',1,'2026-09-25 15:50:28'),
(11,5,'34f76e5a20b3bde7bc6ee0c7ae5fbadc05d3fbb01eb63f4aa78423b10e16209f',1,'2026-09-25 15:50:46'),
(12,6,'34f76e5a20b3bde7bc6ee0c7ae5fbadc05d3fbb01eb63f4aa78423b10e16209f',1,'2026-09-25 15:51:07'),
(13,6,'9846c588c5a85e5d12d5f0d7183205ad080bf024554f4ed4bbdd191ca7d2fec9',1,'2026-09-25 15:51:48'),
(14,4,'9846c588c5a85e5d12d5f0d7183205ad080bf024554f4ed4bbdd191ca7d2fec9',1,'2026-09-25 15:51:54'),
(15,5,'9846c588c5a85e5d12d5f0d7183205ad080bf024554f4ed4bbdd191ca7d2fec9',1,'2026-09-25 15:52:08'),
(16,7,'14b0f2abd52d2473bee1741ab54a08123f701022bdf91dae5f813b740288a2ec',1,'2026-09-25 15:52:50'),
(17,6,'14b0f2abd52d2473bee1741ab54a08123f701022bdf91dae5f813b740288a2ec',1,'2026-09-25 15:53:00'),
(18,4,'14b0f2abd52d2473bee1741ab54a08123f701022bdf91dae5f813b740288a2ec',1,'2026-09-25 15:53:08'),
(19,7,'2d76d498d7774da718a3f9cdf71522d266776bb803e7ac128a0d1f9ff5e7ef97',1,'2026-09-25 18:16:44');
/*!40000 ALTER TABLE `chronicle_post_votes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `poll_options`
--

DROP TABLE IF EXISTS `poll_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `poll_options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` int(10) unsigned NOT NULL,
  `option_text` varchar(80) NOT NULL,
  `display_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_poll_options_order` (`poll_id`,`display_order`),
  CONSTRAINT `fk_poll_options_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poll_options`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `poll_options` WRITE;
/*!40000 ALTER TABLE `poll_options` DISABLE KEYS */;
INSERT INTO `poll_options` VALUES
(6,1,'Jeszcze nie mam zdania',1,'2026-09-24 21:52:26'),
(7,1,'⭐⭐',2,'2026-09-24 21:52:26'),
(8,1,'⭐⭐⭐',3,'2026-09-24 21:52:26'),
(9,1,'⭐⭐⭐⭐',4,'2026-09-24 21:52:26'),
(10,1,'⭐⭐⭐⭐⭐',5,'2026-09-24 21:52:26');
/*!40000 ALTER TABLE `poll_options` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `poll_votes`
--

DROP TABLE IF EXISTS `poll_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `poll_votes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `poll_id` int(10) unsigned NOT NULL,
  `poll_option_id` int(10) unsigned NOT NULL,
  `voter_hash` char(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_poll_votes_voter` (`poll_id`,`voter_hash`),
  KEY `idx_poll_votes_option` (`poll_option_id`),
  CONSTRAINT `fk_poll_votes_option` FOREIGN KEY (`poll_option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_poll_votes_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `poll_votes`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `poll_votes` WRITE;
/*!40000 ALTER TABLE `poll_votes` DISABLE KEYS */;
INSERT INTO `poll_votes` VALUES
(1,1,10,'11321c7b0329f379f024ee1477d8cb057d694950a236589242f51f43045e11b2','2026-09-24 21:53:24'),
(2,1,10,'338ecc6288b2def1d4dacf7e8875e1fc404572e8b784bef53533adf24a2acf01','2026-09-25 01:58:48'),
(3,1,10,'00a894021772aee35e25a1d9bb5b3665a2304ced9a2e94b562fed2211b498a10','2026-09-25 15:49:16'),
(4,1,10,'da75567b273679167d024e0d91d640fa94dc19fa0772de278989227021e69ae3','2026-09-25 15:50:22'),
(5,1,10,'c63ab78b3dc706ed1b16c97954de8c5b72698506e31061467ac94385b19cd00a','2026-09-25 15:51:37'),
(6,1,10,'f6cee9347ea727ca3b0e7354622ce42f244de5fa07cc4379f6714286959abd2b','2026-09-25 15:52:43');
/*!40000 ALTER TABLE `poll_votes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `polls`
--

DROP TABLE IF EXISTS `polls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `polls` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(160) NOT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `show_results_after_vote` tinyint(1) NOT NULL DEFAULT 1,
  `archived_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_polls_public` (`is_active`,`starts_at`,`ends_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `polls`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `polls` WRITE;
/*!40000 ALTER TABLE `polls` DISABLE KEYS */;
INSERT INTO `polls` VALUES
(1,'Jakie są Twoje wrażenia po wejściu na tą stronę?',NULL,NULL,1,1,NULL,'2026-09-24 21:52:00','2026-09-24 21:52:26');
/*!40000 ALTER TABLE `polls` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `public_menu_items`
--

DROP TABLE IF EXISTS `public_menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `public_menu_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_key` varchar(50) NOT NULL,
  `label` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu_key` (`menu_key`)
) ENGINE=InnoDB AUTO_INCREMENT=4369 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `public_menu_items`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `public_menu_items` WRITE;
/*!40000 ALTER TABLE `public_menu_items` DISABLE KEYS */;
INSERT INTO `public_menu_items` VALUES
(1,'ads','Lokalne Ogłoszenia',1,10,'2026-09-10 08:54:02','2026-09-16 16:59:20'),
(2,'blog','Kronika Miasta',1,20,'2026-09-10 08:54:02','2026-09-10 08:54:02'),
(3,'chatroom','Anonimowy Chatroom',1,30,'2026-09-10 08:54:02','2026-09-16 16:59:20'),
(4,'about','O, co tu chodzi?',1,40,'2026-09-10 08:54:02','2026-09-10 08:54:02'),
(5,'cooperation','Współpraca z 66600.pl',1,50,'2026-09-10 08:54:02','2026-09-10 11:35:24'),
(6,'report','Zgłoś Nadużycie!',1,60,'2026-09-10 08:54:02','2026-09-16 16:59:20'),
(10,'pulse','Puls miasta',1,35,'2026-09-10 09:16:42','2026-09-10 09:16:42'),
(4368,'poker','Zagraj w pokera',1,70,'2026-09-25 13:39:09','2026-09-25 13:39:09');
/*!40000 ALTER TABLE `public_menu_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `pulse_notices`
--

DROP TABLE IF EXISTS `pulse_notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pulse_notices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message` varchar(160) NOT NULL,
  `signature` varchar(80) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `author_source` varchar(16) NOT NULL DEFAULT 'community',
  `status` varchar(16) NOT NULL DEFAULT 'published',
  `ip_hash` char(64) DEFAULT NULL,
  `published_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pulse_public` (`status`,`expires_at`,`published_at`),
  KEY `idx_pulse_rate` (`ip_hash`,`published_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pulse_notices`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `pulse_notices` WRITE;
/*!40000 ALTER TABLE `pulse_notices` DISABLE KEYS */;
INSERT INTO `pulse_notices` VALUES
(1,'Test',NULL,NULL,'community','published','cf2ba22c3a2a8ba5550c9d7990fc98f0f4625aad36bc9bcddc59ce5df8b907eb','2026-09-10 11:45:41','2026-09-10 23:45:41','2026-09-10 13:45:41'),
(2,'Krkdm',NULL,NULL,'community','published','7eced59f11f75acf1a773ee8a1a9076de7d50cd741a9a95cae2b0fd54c71438c','2026-09-22 20:37:33','2026-09-23 08:37:33','2026-09-22 22:37:33'),
(3,'Pełno żandarmerii w kominiarkach w Krośnie. Wałęsają się chyba wszędzie. Czy ktoś wie o co chodzi!?','Administrator',NULL,'official','published','9bdd29bd63a77027594498421b7de3869752f35638eebf4d36977e93c44c62ad','2026-09-24 00:05:43','2026-09-24 12:05:43','2026-09-24 02:05:43'),
(4,'Zapraszam do korzystania. Wszystko za darmo bez żadnych formalności',NULL,NULL,'community','published','9bdd29bd63a77027594498421b7de3869752f35638eebf4d36977e93c44c62ad','2026-09-25 13:13:31','2026-09-26 01:13:31','2026-09-25 15:13:31');
/*!40000 ALTER TABLE `pulse_notices` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `site_visit_stats`
--

DROP TABLE IF EXISTS `site_visit_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_visit_stats` (
  `id` tinyint(3) unsigned NOT NULL,
  `total_visits` bigint(20) unsigned NOT NULL DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_visit_stats`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `site_visit_stats` WRITE;
/*!40000 ALTER TABLE `site_visit_stats` DISABLE KEYS */;
INSERT INTO `site_visit_stats` VALUES
(1,4789,'2026-09-26 00:05:08');
/*!40000 ALTER TABLE `site_visit_stats` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','editor','user') DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Karol','$2y$10$Gf6IAnSsq8ZTypgnTbVT1OnUpSnbZ4uAfxFJ9wZgf9Shhn9.9Lvqy','admin@66600.pl','Administrator','admin',1,NULL,'2026-08-11 21:53:10');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Dumping events for database 'server629599_site66main'
--

--
-- Dumping routines for database 'server629599_site66main'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-26  0:35:55
