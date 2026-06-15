import { useCms } from '../cms/CmsContext';
import { ArrowRightIcon } from 'lucide-react';
import { Link } from 'react-router-dom';

export function ByDb() {
  const { data } = useCms();

  return (
    <div className="container mx-auto max-w-7xl px-4 py-24 flex-1">
      <div className="max-w-2xl mb-16">
        <h1 className="text-5xl font-bold tracking-tight mb-6">by DBDevStudio</h1>
        <p className="text-xl font-light text-foreground/70">Wiedza, doświadczenie i najwyższej klasy ekspertyza spakowane w elastyczne modele współpracy.</p>
      </div>

      <div className="flex flex-col gap-6">
        {data.byDb.map((item, index) => (
          <div key={item.id} className="flex flex-col md:flex-row bg-background border border-border shadow-sm hover:shadow-md transition-shadow rounded-2xl overflow-hidden group">
            <div className="md:w-32 bg-secondary flex items-center justify-center p-6 text-2xl font-bold text-foreground/20 border-r border-border md:border-b-0 border-b">
              0{index + 1}
            </div>
            <div className="flex-1 p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
              <div>
                <h3 className="text-2xl font-bold mb-2 group-hover:text-accent transition-colors">{item.title}</h3>
                <p className="text-foreground/70 text-lg">{item.description}</p>
              </div>
              <Link to="/kontakt" className="flex items-center justify-center w-12 h-12 rounded-full border border-border hover:bg-accent hover:text-white hover:border-accent transition-colors flex-shrink-0">
                <ArrowRightIcon className="w-5 h-5" />
              </Link>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
