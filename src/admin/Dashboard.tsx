import { useCms } from '../cms/CmsContext';
import { Users, FileText, View, Settings, Activity } from 'lucide-react';

export function Dashboard() {
  const { data } = useCms();

  return (
    <div>
      <h1 className="text-3xl font-bold mb-8">Panel zarządzania</h1>
      
      <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div className="bg-background border border-border p-6 rounded-2xl shadow-sm flex items-center justify-between">
          <div>
            <p className="text-foreground/50 text-sm font-semibold mb-1">Wpisy</p>
            <h3 className="text-3xl font-bold">{data.news.length}</h3>
          </div>
          <div className="p-4 bg-blue-500/10 text-blue-500 rounded-xl"><FileText className="w-8 h-8" /></div>
        </div>
        <div className="bg-background border border-border p-6 rounded-2xl shadow-sm flex items-center justify-between">
          <div>
            <p className="text-foreground/50 text-sm font-semibold mb-1">Produkty</p>
            <h3 className="text-3xl font-bold">{data.products.length}</h3>
          </div>
          <div className="p-4 bg-green-500/10 text-green-500 rounded-xl"><View className="w-8 h-8" /></div>
        </div>
        <div className="bg-background border border-border p-6 rounded-2xl shadow-sm flex items-center justify-between">
          <div>
            <p className="text-foreground/50 text-sm font-semibold mb-1">Usługi</p>
            <h3 className="text-3xl font-bold">{data.services.length}</h3>
          </div>
          <div className="p-4 bg-purple-500/10 text-purple-500 rounded-xl"><Settings className="w-8 h-8" /></div>
        </div>
        <div className="bg-background border border-border p-6 rounded-2xl shadow-sm flex items-center justify-between">
          <div>
            <p className="text-foreground/50 text-sm font-semibold mb-1">Promocje</p>
            <h3 className="text-3xl font-bold">{data.promotions.length}</h3>
          </div>
          <div className="p-4 bg-orange-500/10 text-orange-500 rounded-xl"><Activity className="w-8 h-8" /></div>
        </div>
      </div>

      <div className="bg-background border border-border rounded-2xl shadow-sm p-8">
        <h2 className="text-xl font-bold mb-6">Witaj w panelu CMS Systemu DBDevStudio</h2>
        <p className="text-foreground/70 mb-4">
          Ten panel służy do zarządzania wszystkimi treściami widocznymi na stronie głównej. Wszelkie zmiany wprowadzone w odpowiednich modułach (w lewym menu) natychmiast znajdują odzwierciedlenie na głównej stronie, działając na zasadzie Headless.
        </p>
        <p className="text-foreground/70 mb-4">
          Uwaga (Hosting współdzielony): Ze względu na bezinstalacyjny charakter, aplikacja po stronie administratora zapisuje wszystkie zmiany do wewnętrznego stanu lub pliku JSON w katalogu instalacji, o ile PHP ma prawa zapisu.
        </p>
        <div className="bg-accent/10 border border-accent/20 p-4 rounded-xl inline-block mt-4">
          <span className="font-semibold">Aktualny motyw:</span> Kolorystyka i design strony są definiowane automatycznie przez ThemeProvider. Możesz skonfigurować logotyp w module "Ustawienia".
        </div>
      </div>
    </div>
  );
}
