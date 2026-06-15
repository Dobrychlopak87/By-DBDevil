export interface BlogPost {
  id: string;
  title: string;
  excerpt: string;
  content: string;
  date: string;
  image: string;
}

export interface Product {
  id: string;
  name: string;
  subtitle: string;
  description: string;
  image: string;
}

export interface ServiceItem {
  id: string;
  title: string;
  description: string;
  icon: string;
}

export interface DBLink {
  id: string;
  title: string;
  description: string;
  url: string;
}

export interface Promotion {
  id: string;
  title: string;
  content: string;
  image: string;
  btnText: string;
}

export interface FAQ {
  id: string;
  question: string;
  answer: string;
}

export interface CmsData {
  settings: {
    logoText: string;
    email: string;
    phone: string;
    facebook: string;
    linkedin: string;
    footerText: string;
  };
  about: {
    heroTitle: string;
    heroSubtitle: string;
    mission: string;
    vision: string;
    image: string;
  };
  products: Product[];
  services: ServiceItem[];
  byDb: DBLink[];
  promotions: Promotion[];
  faq: FAQ[];
  news: BlogPost[];
}
