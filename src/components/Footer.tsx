import { useCms } from '../cms/CmsContext';
import { Facebook, Linkedin, Mail } from 'lucide-react';

export function Footer() {
  const { data } = useCms();

  return (
    <footer className="border-t border-border mt-auto py-12">
      <div className="container mx-auto max-w-7xl px-4 flex flex-col md:flex-row justify-between items-center gap-6">
        <div className="text-sm text-foreground/60">
          {data.settings.footerText}
        </div>
        <div className="flex items-center gap-6">
          <a href={`mailto:${data.settings.email}`} className="text-foreground/60 hover:text-foreground transition-colors">
            <Mail className="w-5 h-5" />
          </a>
          <a href={data.settings.facebook} target="_blank" rel="noreferrer" className="text-foreground/60 hover:text-foreground transition-colors">
            <Facebook className="w-5 h-5" />
          </a>
          <a href={data.settings.linkedin} target="_blank" rel="noreferrer" className="text-foreground/60 hover:text-foreground transition-colors">
            <Linkedin className="w-5 h-5" />
          </a>
        </div>
      </div>
    </footer>
  );
}
