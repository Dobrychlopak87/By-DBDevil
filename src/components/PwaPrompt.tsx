import { useEffect, useState } from 'react';
import { Download, X } from 'lucide-react';

export function PwaPrompt() {
  const [deferredPrompt, setDeferredPrompt] = useState<any>(null);
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const handler = (e: any) => {
      e.preventDefault();
      setDeferredPrompt(e);
      if (!localStorage.getItem('pwa_prompt_dismissed')) {
        setIsVisible(true);
      }
    };

    window.addEventListener('beforeinstallprompt', handler);
    return () => window.removeEventListener('beforeinstallprompt', handler);
  }, []);

  const handleInstall = () => {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then((choiceResult: any) => {
        if (choiceResult.outcome === 'accepted') {
          console.log('User accepted the install prompt');
        }
        setDeferredPrompt(null);
        setIsVisible(false);
      });
    }
  };

  const handleDismiss = () => {
    localStorage.setItem('pwa_prompt_dismissed', '1');
    setIsVisible(false);
  };

  if (!isVisible) return null;

  return (
    <div className="fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-foreground text-background p-4 rounded-xl shadow-2xl z-50 flex items-start gap-4">
      <div className="bg-background/20 p-2 rounded-lg">
        <Download className="w-6 h-6" />
      </div>
      <div className="flex-1">
        <h3 className="font-semibold text-sm">Zainstaluj DBDevStudio</h3>
        <p className="text-xs opacity-80 mt-1">Dodaj do ekranu z głównego dla szybkiego dostępu (PWA).</p>
        <div className="mt-3 flex gap-2">
          <button onClick={handleInstall} className="bg-background text-foreground text-xs px-4 py-1.5 rounded-md font-medium">Zainstaluj</button>
          <button onClick={handleDismiss} className="text-background/80 text-xs px-4 py-1.5 font-medium">Odrzuć</button>
        </div>
      </div>
      <button onClick={handleDismiss} className="p-1 opacity-50 hover:opacity-100">
        <X className="w-4 h-4" />
      </button>
    </div>
  );
}
