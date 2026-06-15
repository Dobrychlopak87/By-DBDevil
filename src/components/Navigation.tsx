import { Link, useLocation } from 'react-router-dom';
import { useCms } from '../cms/CmsContext';
import { useTheme } from '../theme/ThemeContext';
import { Menu, X, Moon, Sun, Monitor } from 'lucide-react';
import { useState } from 'react';
import { cn } from '../lib/utils';

export function Navigation() {
  const { data } = useCms();
  const { theme, setTheme } = useTheme();
  const [mobileOpen, setMobileOpen] = useState(false);
  const location = useLocation();

  const isActive = (path: string) => location.pathname === path || location.pathname.startsWith(path + '/');

  const activeClasses = "bg-primary text-primary-foreground dark:bg-white dark:text-black";
  const hoverClasses = "hover:bg-primary hover:text-primary-foreground dark:hover:bg-white dark:hover:text-black transition-colors";

  return (
    <header className="sticky top-0 z-50 w-full border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
      <div className="container mx-auto max-w-7xl px-4 h-16 flex items-center justify-between">
        {/* Logo */}
        <Link to="/" className="text-xl font-bold tracking-tight">
          {data.settings.logoText}
        </Link>

        {/* Desktop Nav */}
        <nav className="hidden md:flex items-center gap-1">
          <Link to="/" className={cn("px-4 py-2 rounded-md font-medium", hoverClasses, location.pathname === '/' ? activeClasses : "")}>
            Kim jesteśmy
          </Link>
          <Link to="/aktualnosci" className={cn("px-4 py-2 rounded-md font-medium", hoverClasses, isActive('/aktualnosci') ? activeClasses : "")}>
            Aktualności
          </Link>
          
          {/* Megamenu Trigger */}
          <div className="group relative">
            <button className={cn("px-4 py-2 rounded-md font-medium focus:outline-none flex items-center gap-1", hoverClasses, 
               (isActive('/produkty') || isActive('/uslugi') || isActive('/by-dbdevstudio')) ? activeClasses : "")}>
              Oferta
            </button>
            <div className="absolute top-full left-1/2 -translate-x-1/2 pt-4 w-[600px] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
              <div className="bg-background border border-border shadow-xl rounded-xl p-6 grid grid-cols-3 gap-6">
                <div>
                  <h3 className="font-semibold text-lg mb-3 border-b pb-2">Produkty</h3>
                  <ul className="space-y-2">
                    {data.products.slice(0, 3).map(p => (
                      <li key={p.id}>
                        <Link to="/produkty" className={cn("text-sm block px-2 py-1 rounded-md transition-colors", isActive('/produkty') ? activeClasses : hoverClasses, "opacity-90")}>{p.name}</Link>
                      </li>
                    ))}
                    <li><Link to="/produkty" className="text-sm font-medium hover:underline text-accent block mt-2 px-2">Wszystkie produkty →</Link></li>
                  </ul>
                </div>
                <div>
                  <h3 className="font-semibold text-lg mb-3 border-b pb-2">Usługi</h3>
                  <ul className="space-y-2">
                    {data.services.slice(0, 4).map(s => (
                      <li key={s.id}>
                        <Link to="/uslugi" className={cn("text-sm block px-2 py-1 rounded-md transition-colors", isActive('/uslugi') ? activeClasses : hoverClasses, "opacity-90")}>{s.title}</Link>
                      </li>
                    ))}
                    <li><Link to="/uslugi" className="text-sm font-medium hover:underline text-accent block mt-2 px-2">Cała oferta →</Link></li>
                  </ul>
                </div>
                <div>
                  <h3 className="font-semibold text-lg mb-3 border-b pb-2">by DBDev</h3>
                  <ul className="space-y-2">
                    {data.byDb.slice(0, 3).map(b => (
                      <li key={b.id}>
                        <Link to="/by-dbdevstudio" className={cn("text-sm block px-2 py-1 rounded-md transition-colors", isActive('/by-dbdevstudio') ? activeClasses : hoverClasses, "opacity-90")}>{b.title}</Link>
                      </li>
                    ))}
                    <li><Link to="/by-dbdevstudio" className="text-sm font-medium hover:underline text-accent block mt-2 px-2">Więcej →</Link></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <Link to="/promocje" className={cn("px-4 py-2 rounded-md font-medium", hoverClasses, isActive('/promocje') ? activeClasses : "")}>
            Promocje
          </Link>
          <Link to="/faq" className={cn("px-4 py-2 rounded-md font-medium", hoverClasses, isActive('/faq') ? activeClasses : "")}>
            FAQ
          </Link>
          <Link to="/kontakt" className={cn("px-4 py-2 rounded-md font-medium", hoverClasses, isActive('/kontakt') ? activeClasses : "")}>
            Kontakt
          </Link>
        </nav>

        {/* Tools */}
        <div className="flex items-center gap-2">
          {/* Theme Switcher */}
          <div className="hidden sm:flex items-center bg-secondary/50 rounded-full p-1 border">
            <button onClick={() => setTheme('light')} className={cn("p-1.5 rounded-full", theme === 'light' ? "bg-background shadow-sm" : "opacity-50")} aria-label="Light theme">
              <Sun className="w-4 h-4" />
            </button>
            <button onClick={() => setTheme('dark')} className={cn("p-1.5 rounded-full", theme === 'dark' ? "bg-background shadow-sm" : "opacity-50")} aria-label="Dark theme">
              <Moon className="w-4 h-4" />
            </button>
            <button onClick={() => setTheme('auto')} className={cn("p-1.5 rounded-full", theme === 'auto' ? "bg-background shadow-sm" : "opacity-50")} aria-label="Auto theme">
              <Monitor className="w-4 h-4" />
            </button>
          </div>

          <button onClick={() => setMobileOpen(!mobileOpen)} className="md:hidden p-2 rounded-md hover:bg-secondary">
            {mobileOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>
      </div>

      {/* Mobile Nav */}
      {mobileOpen && (
        <div className="md:hidden border-t p-4 bg-background px-4">
          <nav className="flex flex-col gap-2">
            <Link onClick={() => setMobileOpen(false)} to="/" className="p-3 rounded-lg font-medium bg-secondary">Kim jesteśmy</Link>
            <Link onClick={() => setMobileOpen(false)} to="/aktualnosci" className="p-3 rounded-lg font-medium bg-secondary">Aktualności</Link>
            <Link onClick={() => setMobileOpen(false)} to="/produkty" className="p-3 rounded-lg font-medium bg-secondary ml-4">↳ Produkty</Link>
            <Link onClick={() => setMobileOpen(false)} to="/uslugi" className="p-3 rounded-lg font-medium bg-secondary ml-4">↳ Usługi</Link>
            <Link onClick={() => setMobileOpen(false)} to="/by-dbdevstudio" className="p-3 rounded-lg font-medium bg-secondary ml-4">↳ by DBDevStudio</Link>
            <Link onClick={() => setMobileOpen(false)} to="/promocje" className="p-3 rounded-lg font-medium bg-secondary">Promocje</Link>
            <Link onClick={() => setMobileOpen(false)} to="/faq" className="p-3 rounded-lg font-medium bg-secondary">FAQ</Link>
            <Link onClick={() => setMobileOpen(false)} to="/kontakt" className="p-3 rounded-lg font-medium bg-secondary">Kontakt</Link>
          </nav>
        </div>
      )}
    </header>
  );
}
