import { useCms } from '../cms/CmsContext';
import { Mail, Phone, MapPin, Facebook, Linkedin } from 'lucide-react';

export function Kontakt() {
  const { data } = useCms();

  return (
    <div className="container mx-auto max-w-7xl px-4 py-24 flex-1">
      <h1 className="text-4xl font-bold tracking-tight mb-16 text-center">Skontaktuj się z nami</h1>

      <div className="grid md:grid-cols-2 gap-16 max-w-5xl mx-auto">
        <div className="space-y-12">
          <div>
            <h2 className="text-2xl font-bold mb-6">Dane kontaktowe</h2>
            <div className="space-y-6">
               <a href={`mailto:${data.settings.email}`} className="flex items-center gap-4 text-lg hover:text-accent transition">
                 <div className="p-3 bg-secondary rounded-full"><Mail className="w-6 h-6" /></div>
                 {data.settings.email}
               </a>
               <a href={`tel:${data.settings.phone}`} className="flex items-center gap-4 text-lg hover:text-accent transition">
                 <div className="p-3 bg-secondary rounded-full"><Phone className="w-6 h-6" /></div>
                 {data.settings.phone}
               </a>
               <div className="flex items-center gap-4 text-lg">
                 <div className="p-3 bg-secondary rounded-full"><MapPin className="w-6 h-6" /></div>
                 W pełni zdalnie (Globalnie)
               </div>
            </div>
          </div>

          <div>
            <h2 className="text-2xl font-bold mb-6">Social Media</h2>
            <div className="flex gap-4">
               <a href={data.settings.facebook} target="_blank" rel="noreferrer" className="p-4 bg-secondary rounded-xl hover:bg-accent hover:text-white transition">
                 <Facebook className="w-6 h-6" />
               </a>
               <a href={data.settings.linkedin} target="_blank" rel="noreferrer" className="p-4 bg-secondary rounded-xl hover:bg-accent hover:text-white transition">
                 <Linkedin className="w-6 h-6" />
               </a>
            </div>
          </div>
        </div>

        <div className="bg-secondary/30 p-8 rounded-3xl border border-border">
          <h2 className="text-2xl font-bold mb-8">Napisz wiadomość</h2>
          <form className="space-y-6" onSubmit={(e) => { e.preventDefault(); alert("Wiadomość wysłana!"); }}>
            <div>
              <label className="block text-sm font-medium mb-2">Imię i nazwisko</label>
              <input required type="text" className="w-full bg-background border border-border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-accent" />
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">Email</label>
              <input required type="email" className="w-full bg-background border border-border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-accent" />
            </div>
            <div>
              <label className="block text-sm font-medium mb-2">Wiadomość</label>
              <textarea required rows={5} className="w-full bg-background border border-border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-accent"></textarea>
            </div>
            <button type="submit" className="w-full bg-primary text-primary-foreground font-semibold py-4 rounded-xl hover:bg-primary/90 transition text-lg">
              Wyślij
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
