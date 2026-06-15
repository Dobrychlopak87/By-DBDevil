import { useCms } from '../cms/CmsContext';
import { motion } from 'motion/react';
import { ArrowRight } from 'lucide-react';
import { Link } from 'react-router-dom';

export function Home() {
  const { data } = useCms();
  const { about, services } = data;

  return (
    <div>
      {/* Hero */}
      <section className="relative min-h-[80vh] flex items-center justify-center overflow-hidden">
        <div className="absolute inset-0 bg-primary/95 z-10" />
        <img src={about.image} alt="Hero" className="absolute inset-0 w-full h-full object-cover grayscale opacity-50" />
        
        <div className="container mx-auto px-4 z-20 text-center text-primary-foreground max-w-4xl pt-20">
          <motion.h1 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            className="text-5xl md:text-7xl font-bold tracking-tight mb-6"
          >
            {about.heroTitle}
          </motion.h1>
          <motion.p 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2 }}
            className="text-xl md:text-2xl opacity-90 max-w-2xl mx-auto mb-10 leading-relaxed font-light"
          >
            {about.heroSubtitle}
          </motion.p>
          <motion.div 
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.4 }}
            className="flex flex-col sm:flex-row gap-4 justify-center"
          >
            <Link to="/produkty" className="bg-accent text-white px-8 py-4 rounded-md font-medium hover:bg-accent/90 transition flex items-center justify-center gap-2">
              Nasze Produkty <ArrowRight className="w-4 h-4" />
            </Link>
            <Link to="/kontakt" className="bg-transparent border border-primary-foreground/30 px-8 py-4 rounded-md font-medium hover:bg-primary-foreground/10 transition block">
              Skontaktuj się
            </Link>
          </motion.div>
        </div>
      </section>

      {/* Mission / Vision */}
      <section className="py-24 bg-background">
        <div className="container mx-auto max-w-7xl px-4 grid md:grid-cols-2 gap-16">
          <div className="p-10 border border-border rounded-3xl bg-secondary/20">
            <h2 className="text-sm uppercase tracking-widest font-semibold text-accent mb-4">Nasza Misja</h2>
            <p className="text-2xl md:text-3xl font-light leading-relaxed">{about.mission}</p>
          </div>
          <div className="p-10 border border-border rounded-3xl bg-secondary/20">
            <h2 className="text-sm uppercase tracking-widest font-semibold text-foreground/50 mb-4">Nasza Wizja</h2>
            <p className="text-2xl md:text-3xl font-light leading-relaxed">{about.vision}</p>
          </div>
        </div>
      </section>

      {/* Quick Links Showcase */}
      <section className="py-24 border-t border-border">
        <div className="container mx-auto max-w-7xl px-4 text-center">
          <h2 className="text-3xl font-bold mb-16">Eksploruj DBDevStudio</h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <Link to="/aktualnosci" className="group p-8 rounded-2xl bg-secondary hover:bg-primary hover:text-primary-foreground transition-colors text-left flex flex-col h-full">
              <h3 className="text-xl font-bold mb-2">Aktualności</h3>
              <p className="opacity-80 flex-1">Najnowsze wpisy z branży IT, analizy rynkowe.</p>
              <ArrowRight className="w-5 h-5 mt-4 opacity-50 group-hover:opacity-100 group-hover:translate-x-2 transition-all" />
            </Link>
            <Link to="/uslugi" className="group p-8 rounded-2xl bg-secondary hover:bg-primary hover:text-primary-foreground transition-colors text-left flex flex-col h-full">
              <h3 className="text-xl font-bold mb-2">Usługi</h3>
              <p className="opacity-80 flex-1">Strony www, aplikacje mobilne i webowe, sztuczna inteligencja.</p>
              <ArrowRight className="w-5 h-5 mt-4 opacity-50 group-hover:opacity-100 group-hover:translate-x-2 transition-all" />
            </Link>
            <Link to="/promocje" className="group p-8 rounded-2xl bg-secondary hover:bg-primary hover:text-primary-foreground transition-colors text-left flex flex-col h-full">
              <h3 className="text-xl font-bold mb-2">Promocje</h3>
              <p className="opacity-80 flex-1">Bieżące oferty wsparcia i wdrożeń.</p>
              <ArrowRight className="w-5 h-5 mt-4 opacity-50 group-hover:opacity-100 group-hover:translate-x-2 transition-all" />
            </Link>
            <Link to="/by-dbdevstudio" className="group p-8 rounded-2xl bg-secondary hover:bg-primary hover:text-primary-foreground transition-colors text-left flex flex-col h-full">
              <h3 className="text-xl font-bold mb-2">by DBDevStudio</h3>
              <p className="opacity-80 flex-1">Szkolenia, audyty i wysoce specjalistyczne usługi.</p>
              <ArrowRight className="w-5 h-5 mt-4 opacity-50 group-hover:opacity-100 group-hover:translate-x-2 transition-all" />
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}
