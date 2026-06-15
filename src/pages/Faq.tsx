import { useCms } from '../cms/CmsContext';
import { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { Plus, Minus } from 'lucide-react';

export function Faq() {
  const { data } = useCms();
  const [openId, setOpenId] = useState<string | null>(null);

  return (
    <div className="container mx-auto max-w-4xl px-4 py-24 flex-1">
      <div className="text-center mb-16">
        <h1 className="text-4xl font-bold tracking-tight mb-4">FAQ</h1>
        <p className="text-lg text-foreground/70">Najczęściej zadawane pytania</p>
      </div>

      <div className="space-y-4">
        {data.faq.map(item => {
          const isOpen = openId === item.id;
          return (
            <div key={item.id} className="border border-border rounded-xl bg-background overflow-hidden">
              <button 
                onClick={() => setOpenId(isOpen ? null : item.id)}
                className="w-full text-left p-6 flex items-center justify-between hover:bg-secondary/30 transition-colors"
                aria-expanded={isOpen}
              >
                <span className="font-semibold text-lg">{item.question}</span>
                {isOpen ? <Minus className="w-5 h-5 flex-shrink-0 ml-4 text-accent" /> : <Plus className="w-5 h-5 flex-shrink-0 ml-4 opacity-50" />}
              </button>
              <AnimatePresence>
                {isOpen && (
                  <motion.div
                    initial={{ height: 0, opacity: 0 }}
                    animate={{ height: 'auto', opacity: 1 }}
                    exit={{ height: 0, opacity: 0 }}
                    transition={{ duration: 0.2 }}
                  >
                    <div className="p-6 pt-0 text-foreground/80 leading-relaxed bg-secondary/5 border-t border-border/50">
                      {item.answer}
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
