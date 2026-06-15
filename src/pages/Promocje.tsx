import { useCms } from '../cms/CmsContext';
import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';

export function Promocje() {
  const { data } = useCms();
  const [activeId, setActiveId] = useState(data.promotions[0]?.id);

  const activePromo = data.promotions.find(p => p.id === activeId) || data.promotions[0];

  return (
    <div className="container mx-auto max-w-7xl px-4 py-24 flex-1 flex flex-col lg:flex-row gap-12">
      <div className="lg:w-1/3 flex flex-col gap-3">
        <h1 className="text-4xl font-bold tracking-tight mb-8">Nasze Promocje</h1>
        {data.promotions.map(promo => (
          <button
            key={promo.id}
            onClick={() => setActiveId(promo.id)}
            className={`w-full text-left p-6 rounded-2xl transition-all border ${activeId === promo.id ? 'bg-primary text-primary-foreground border-primary shadow-lg scale-105' : 'bg-background hover:bg-secondary border-border'}`}
          >
            <h3 className={`font-semibold text-lg ${activeId === promo.id ? '' : 'opacity-80'}`}>{promo.title}</h3>
          </button>
        ))}
      </div>

      <div className="lg:w-2/3">
        <AnimatePresence mode="wait">
          {activePromo && (
            <motion.div
              key={activePromo.id}
              initial={{ opacity: 0, x: 20 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: -20 }}
              transition={{ duration: 0.3 }}
              className="bg-secondary/30 rounded-3xl overflow-hidden border border-border h-full flex flex-col"
            >
              <div className="h-64 md:h-80 w-full relative">
                <img src={activePromo.image} alt={activePromo.title} className="absolute inset-0 w-full h-full object-cover" />
                <div className="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent" />
                <div className="absolute bottom-6 left-8 right-8">
                  <h2 className="text-3xl md:text-5xl font-bold text-white">{activePromo.title}</h2>
                </div>
              </div>
              
              <div className="p-8 md:p-12 flex-1 flex flex-col justify-between gap-8">
                <p className="text-xl font-light leading-relaxed text-foreground/80">
                  {activePromo.content}
                </p>
                <div>
                  <button className="bg-accent text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-accent/90 transition shadow-lg hover:shadow-xl hover:-translate-y-1 w-full sm:w-auto">
                    {activePromo.btnText}
                  </button>
                </div>
              </div>
            </motion.div>
          )}
        </AnimatePresence>
      </div>
    </div>
  );
}
