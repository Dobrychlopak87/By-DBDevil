import { useState, useEffect } from 'react';
import { Navigate, Outlet, Link, useLocation } from 'react-router-dom';
import { LayoutDashboard, FileText, Settings, Package, LayoutTemplate, Briefcase, Tag, MessageCircle, Info, FolderOpen, LogOut } from 'lucide-react';
import { useCms } from '../cms/CmsContext';

export function AdminLayout() {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [isChecking, setIsChecking] = useState(true);
  const location = useLocation();

  useEffect(() => {
    const auth = localStorage.getItem('admin_auth');
    if (auth === 'true') {
      setIsAuthenticated(true);
    }
    setIsChecking(false);
  }, []);

  const handleLogout = () => {
    localStorage.removeItem('admin_auth');
    setIsAuthenticated(false);
  };

  if (isChecking) return <div className="min-h-screen flex items-center justify-center">Ładowanie...</div>;

  if (!isAuthenticated) {
    if (location.pathname !== '/admin/login') {
      return <Navigate to="/admin/login" replace />;
    }
    return <Outlet context={{ setIsAuthenticated }} />;
  }

  const menu = [
    { name: 'Dashboard', path: '/admin', icon: LayoutDashboard },
    { name: 'O Nas', path: '/admin/about', icon: Info },
    { name: 'Aktualności', path: '/admin/news', icon: FileText },
    { name: 'Produkty', path: '/admin/products', icon: Package },
    { name: 'Usługi', path: '/admin/services', icon: LayoutTemplate },
    { name: 'by DBDev', path: '/admin/bydb', icon: Briefcase },
    { name: 'Promocje', path: '/admin/promotions', icon: Tag },
    { name: 'FAQ', path: '/admin/faq', icon: MessageCircle },
    { name: 'Ustawienia', path: '/admin/settings', icon: Settings },
    { name: 'Menadżer plików (Demo)', path: '/admin/files', icon: FolderOpen },
  ];

  return (
    <div className="min-h-screen bg-secondary/20 flex flex-col md:flex-row text-foreground font-sans">
      <aside className="w-full md:w-64 bg-primary text-primary-foreground flex flex-col overflow-y-auto">
        <div className="p-6 text-2xl font-bold tracking-tight border-b border-primary-foreground/10">DBAdmin</div>
        <nav className="flex-1 py-4 flex flex-col gap-1 px-3">
          {menu.map(m => {
            const Icon = m.icon;
            const isActive = location.pathname === m.path;
            return (
              <Link key={m.path} to={m.path} className={`flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors ${isActive ? 'bg-primary-foreground/10 font-semibold text-white' : 'hover:bg-primary-foreground/5 text-primary-foreground/70'}`}>
                <Icon className="w-5 h-5 flex-shrink-0" />
                {m.name}
              </Link>
            )
          })}
        </nav>
        <div className="p-4 border-t border-primary-foreground/10">
           <button onClick={handleLogout} className="flex items-center gap-2 w-full px-3 py-2 text-red-300 hover:bg-red-500/10 rounded-lg transition-colors">
              <LogOut className="w-5 h-5" /> Wyloguj
           </button>
        </div>
      </aside>
      <main className="flex-1 p-8 overflow-y-auto max-h-screen">
        <Outlet />
      </main>
    </div>
  );
}
