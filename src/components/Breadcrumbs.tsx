import { useLocation, Link } from 'react-router-dom';
import { ChevronRight, Home } from 'lucide-react';

const pathNames: Record<string, string> = {
  'aktualnosci': 'Aktualności',
  'produkty': 'Produkty',
  'uslugi': 'Usługi',
  'by-dbdevstudio': 'By DBDevStudio',
  'promocje': 'Promocje',
  'faq': 'FAQ',
  'kontakt': 'Kontakt',
  'admin': 'Panel Administracyjny'
};

export function Breadcrumbs() {
  const location = useLocation();
  const paths = location.pathname.split('/').filter(p => p !== '' && p !== 'home');

  if (paths.length === 0 || paths[0] === 'admin') return null;

  return (
    <div className="bg-accent/5 border-b border-border py-3">
      <div className="container mx-auto max-w-7xl px-4 flex items-center text-sm text-foreground/60 overflow-x-auto whitespace-nowrap">
        <Link to="/" className="hover:text-foreground flex items-center gap-1">
          <Home className="w-4 h-4" /> <span className="sr-only">Start</span>
        </Link>
        {paths.map((path, index) => {
          const routeTo = `/${paths.slice(0, index + 1).join('/')}`;
          const isLast = index === paths.length - 1;
          const name = pathNames[path] || path;

          return (
            <div key={path} className="flex items-center">
              <ChevronRight className="w-4 h-4 mx-2 opacity-50 flex-shrink-0" />
              {isLast ? (
                <span className="font-medium text-foreground">{name}</span>
              ) : (
                <Link to={routeTo} className="hover:text-foreground">
                  {name}
                </Link>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
