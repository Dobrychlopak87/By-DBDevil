import { CmsData } from '../types';

export const initialCmsData: CmsData = {
  settings: {
    logoText: 'DBDevStudio',
    email: 'kontakt@dbdevstudio.pl',
    phone: '+48 000 000 000',
    facebook: 'https://facebook.com/dbdevstudio',
    linkedin: 'https://linkedin.com/company/dbdevstudio',
    footerText: '© 2026 DBDevStudio. Wszelkie prawa zastrzeżone.',
  },
  about: {
    heroTitle: 'DBDevStudio - Tworzymy Oprogramowanie Przyszłości',
    heroSubtitle: 'Innowacyjne rozwiązania IT dla biznesu: aplikacje mobilne, webowe, automatyzacja i sztuczna inteligencja.',
    mission: 'Naszą misją jest dostarczanie najwyższej jakości oprogramowania, które wspiera rozwój i transformację cyfrową naszych klientów na całym świecie.',
    vision: 'Dążymy do bycia liderem innowacji, wyznaczając nowe standardy w inżynierii oprogramowania i automatyzacji.',
    image: 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&q=80&w=1200&h=600',
  },
  products: [
    {
      id: 'p1',
      name: 'KurierAI',
      subtitle: 'Inteligentna logistyka',
      description: 'System zarządzania flotą i optymalizacji tras z wykorzystaniem sztucznej inteligencji, minimalizujący koszty operacyjne.',
      image: 'https://images.unsplash.com/photo-1580674285054-bed31e145f59?auto=format&fit=crop&q=80&w=400&h=600'
    },
    {
      id: 'p2',
      name: 'TikTok Reel Studio Pro',
      subtitle: 'Narzędzie dla twórców',
      description: 'Zautomatyzowana platforma do montażu wideo, generowania napisów i optymalizacji materiałów pod platformę TikTok.',
      image: 'https://images.unsplash.com/photo-1616469829581-73993eb86b02?auto=format&fit=crop&q=80&w=400&h=600'
    },
    {
      id: 'p3',
      name: 'nano4HR',
      subtitle: 'Zarządzanie zasobami ludzkimi',
      description: 'Kompaktowe narzędzie do rekrutacji, onboardingu i zarządzania zespołami w myśl nowoczesnego HR.',
      image: 'https://images.unsplash.com/photo-1600880292203-757bb62b4baf?auto=format&fit=crop&q=80&w=400&h=600'
    }
  ],
  services: [
    { id: 's1', title: 'Strony www', description: 'Nowoczesne, szybkie i użyteczne strony internetowe.', icon: 'globe' },
    { id: 's2', title: 'Aplikacje webowe', description: 'Dedykowane systemy CRM, ERP oraz portale B2B.', icon: 'layout' },
    { id: 's3', title: 'Mobilne', description: 'Aplikacje iOS i Android tworzone w oparciu o React Native i Flutter.', icon: 'smartphone' },
    { id: 's4', title: 'Automatyzacja', description: 'Optymalizacja i automatyzacja procesów za pomocą RPA.', icon: 'cpu' },
    { id: 's5', title: 'AI', description: 'Wdrażanie rozwiązań z obszaru sztucznej inteligencji i ML.', icon: 'bot' }
  ],
  byDb: [
    { id: 'bd1', title: 'Szkolenia IT', description: 'Zaawansowane szkolenia z programowania.', url: '/szkolenia' },
    { id: 'bd2', title: 'Audyty bezpieczeństwa', description: 'Kompleksowe sprawdzanie infrastruktury.', url: '/audyty' },
    { id: 'bd3', title: 'Konsulting architektoniczny', description: 'Projektowanie skalowalnych układów.', url: '/konsulting' },
    { id: 'bd4', title: 'Wsparcie techniczne', description: 'SLA i codzienne wsparcie zespołów.', url: '/wsparcie' },
    { id: 'bd5', title: 'Rozwój MVP', description: 'Szybkie prototypowanie startupów.', url: '/mvp' }
  ],
  promotions: [
    { id: 'pr1', title: 'Start w Web 3.0', content: 'Zbuduj swoją pierwszą stronę w technologii przyszłości z 20% rabatem.', image: 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=600&h=400', btnText: 'Chcę to!' },
    { id: 'pr2', title: 'Aplikacja w 30 dni', content: 'Skorzystaj z naszego szybkiego środowiska tworzenia i zrealizuj MVP.', image: 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&q=80&w=600&h=400', btnText: 'Chcę to!' },
    { id: 'pr3', title: 'Bezpłatny Audyt UX', content: 'Zapisz się do końca miesiąca aby otrzymać pełny raport.', image: 'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?auto=format&fit=crop&q=80&w=600&h=400', btnText: 'Chcę to!' },
    { id: 'pr4', title: 'Modelowanie AI', content: 'Zintegruj lokalne modele LLM za połowę ceny wdrożenia.', image: 'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&q=80&w=600&h=400', btnText: 'Chcę to!' },
    { id: 'pr5', title: 'Pakiety Wsparcia', content: 'Zakup pakiet godzinny i otrzymaj 10 godzin gratis.', image: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&q=80&w=600&h=400', btnText: 'Chcę to!' }
  ],
  faq: [
    { id: 'f1', question: 'Czym zajmuje się DBDevStudio?', answer: 'Dostarczamy sprzęt IT, tworzymy oprogramowanie na zamówienie oraz oferujemy integrację i automatyzację.' },
    { id: 'f2', question: 'Ile trwa stworzenie standardowej strony?', answer: 'Zazwyczaj stworzenie dedykowanej strony internetowej zajmuje od 4 do 8 tygodni pracy w zależności od wymagań.' },
    { id: 'f3', question: 'Jakie technologie wykorzystujecie?', answer: 'Pracujemy głównie w stacku JavaScript/TypeScript: React, Node.js, tRPC a także w mobile: Flutter.' },
    { id: 'f4', question: 'Czy oferujecie wsparcie po wdrożeniu?', answer: 'Tak, każdy projekt objęty jest gwarancją oraz opcjonalnymi formatami Service Level Agreement (SLA).' },
    { id: 'f5', question: 'Czy mogę zintegrować aplikację z systemem zewnętrznym?', answer: 'Oczywiście. Specjalizujemy się w wdrażaniu i integracji platform poprzez REST lub GraphQL API.' },
    { id: 'f6', question: 'Na jakim serwerze mogę to zainstalować?', answer: 'Nasze rozwiązania wdrażamy z wykorzystaniem chmury, np. AWS, Azure, lub tradycyjnych współdzielonych hostingów.' },
    { id: 'f7', question: 'Ile kosztuje zaprojektowanie aplikacji mobilnej?', answer: 'Każdy projekt jest wyceniany indywidualnie, zazwyczaj po przeprowadzeniu 2 darmowych dni analitycznych.' },
    { id: 'f8', question: 'Jak wygląda dbanie o SEO?', answer: 'Opieramy się na najlepszych praktykach. Wykorzystujemy semantyczny HTML, SSR oraz metaty i optymalizacje Core Web Vitals.' },
    { id: 'f9', question: 'Gdzie znajdujecie się fizycznie?', answer: 'Jesteśmy firmą dostarczającą usługi w pełni zdalnie dla klientów z całego świata w 100% cyfrowo.' },
    { id: 'f10', question: 'Jak nawiązać współpracę?', answer: 'Wyślij zapytanie przez formularz kontaktowy. Odpowiemy w 24 godziny celem omówienia detali.' }
  ],
  news: [
    { id: 'n1', title: 'Nowe trendy w web designie 2026', excerpt: 'Sprawdzamy co najbardziej porusza branżę.', content: 'Pełny artykuł o najnowszych trendach...', date: '2026-06-15', image: 'https://images.unsplash.com/photo-1547658719-da2b51169166?auto=format&fit=crop&q=80&w=800&h=400'},
    { id: 'n2', title: 'Czy AI zastąpi programistów?', excerpt: 'Analiza rynku i nowych narzędzi generatywnych.', content: 'Jak sztuczna inteligencja pomaga a nie szkodzi w pracy...', date: '2026-06-10', image: 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&q=80&w=800&h=400'},
    { id: 'n3', title: 'Automatyzacja HR z nano4HR', excerpt: 'Jak zwiększyć wydajność rekrutacyjną.', content: 'Dowiedz się jak nasz produkt optymalizuje czas...', date: '2026-06-05', image: 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&q=80&w=800&h=400'},
    { id: 'n4', title: 'Bezpieczeństwo aplikacji mobilnych', excerpt: 'Jak zabezpieczyć dane użytkowników.', content: 'Lista najlepszych praktyk bezpieczeństwa w 2026 roku.', date: '2026-05-28', image: 'https://images.unsplash.com/photo-1614064641913-6b71a2ea7fa3?auto=format&fit=crop&q=80&w=800&h=400'}
  ]
};
