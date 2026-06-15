import { createContext, useContext, useEffect, useState, ReactNode } from 'react';
import { CmsData } from '../types';
import { initialCmsData } from './initialData';

interface CmsContextType {
  data: CmsData;
  updateData: (newData: CmsData) => void;
  isLoading: boolean;
}

const CmsContext = createContext<CmsContextType | undefined>(undefined);

export function CmsProvider({ children }: { children: ReactNode }) {
  const [data, setData] = useState<CmsData>(initialCmsData);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    // Attempt to load from API (PHP) first, fallback to localStorage
    const loadData = async () => {
      try {
        const res = await fetch('/api.php', { method: 'GET' });
        if (res.ok) {
          const apiData = await res.json();
          if (apiData && !apiData.status) {
            setData(apiData as CmsData);
            setIsLoading(false);
            return;
          }
        }
      } catch (e) {
        console.log('API not found or not supported, falling back to localStorage');
      }

      const localData = localStorage.getItem('dbdevstudio_cms');
      if (localData) {
        try {
          setData(JSON.parse(localData));
        } catch (e) {
          console.error("Failed to parse local storage cms", e);
        }
      } else {
        localStorage.setItem('dbdevstudio_cms', JSON.stringify(initialCmsData));
      }
      setIsLoading(false);
    };

    loadData();
  }, []);

  const updateData = async (newData: CmsData) => {
    setData(newData);
    try {
      const res = await fetch('/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newData)
      });
      if (!res.ok) throw new Error('API save failed');
    } catch (e) {
      // Fallback
      localStorage.setItem('dbdevstudio_cms', JSON.stringify(newData));
    }
  };

  return (
    <CmsContext.Provider value={{ data, updateData, isLoading }}>
      {children}
    </CmsContext.Provider>
  );
}

export function useCms() {
  const ctx = useContext(CmsContext);
  if (!ctx) throw new Error('useCms must be used within CmsProvider');
  return ctx;
}
