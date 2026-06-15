import { Outlet } from 'react-router-dom';
import { Navigation } from './Navigation';
import { Footer } from './Footer';
import { Breadcrumbs } from './Breadcrumbs';
import { PwaPrompt } from './PwaPrompt';

export function Layout() {
  return (
    <div className="min-h-screen flex flex-col font-sans bg-background text-foreground relative selection:bg-accent/30">
      <Navigation />
      <Breadcrumbs />
      <main className="flex-1 flex flex-col">
        <Outlet />
      </main>
      <Footer />
      <PwaPrompt />
    </div>
  );
}
