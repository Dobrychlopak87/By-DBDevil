import { useCms } from '../cms/CmsContext';
import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { Globe, LayoutTemplate, Smartphone, Cpu, Bot, ChevronDown } from 'lucide-react';

const iconMap: Record<string, any> = {
  'globe': Globe,
  'layout': LayoutTemplate,
  'smartphone': Smartphone,
  'cpu': Cpu,
  'bot': Bot
};

export function Uslugi() {
  const { data } = useCms();
  const [activeTab, setActiveTab] = useState<string | null>(data.services[0]?.id || null);

  return (
    <div className="container mx-auto max-w-5xl px-4 py-24 flex-1">
      <div className="text-center mb-16">
        <h1 className="text-4xl font-bold tracking-tight mb-4">Usługi</h1>
        <p className="text-lg text-foreground/70">Poznaj 5 głównych filarów naszej oferty</p>
      </div>

      <div className="flex flex-col gap-4">
        {data.services.map(service => {
          const Icon = iconMap[service.icon] || Globe;
          const isActive = activeTab === service.id;

          return (
            <div key={service.id} className="border border-border rounded-2xl overflow-hidden bg-background shadow-sm">
              <button 
                onClick={() => setActiveTab(isActive ? null : service.id)}
                className="w-full flex items-center justify-between p-6 md:p-8 hover:bg-secondary/50 transition-colors"
                aria-expanded={isActive}
              >
                <div className="flex items-center gap-6">
                  <div className={`p-4 rounded-xl ${isActive ? 'bg-accent text-white' : 'bg-secondary'}`}>
                    <Icon className="w-8 h-8" />
                  </div>
                  <h2 className="text-2xl font-semibold text-left">{service.title}</h2>
                </div>
                <ChevronDown className={`w-6 h-6 transition-transform duration-300 ${isActive ? 'rotate-180' : ''}`} />
              </button>

              <AnimatePresence>
                {isActive && (
                  <motion.div
                    initial={{ height: 0, opacity: 0 }}
                    animate={{ height: 'auto', opacity: 1 }}
                    exit={{ height: 0, opacity: 0 }}
                    transition={{ duration: 0.3 }}
                  >
                    <div className="px-6 pb-8 md:px-8 md:pb-10 pt-2 ml-24">
                      <p className="text-lg text-foreground/80 leading-relaxed max-w-3xl">
                        {service.description}
                      </p>
                      <button className="mt-8 px-6 py-2 bg-primary text-primary-foreground rounded-md font-medium hover:bg-primary/90 transition text-sm">
                        Zapytaj o wycenę
                      </button>
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>
            </div>
          );
        })}
      </div>
    </div>
  );
}
