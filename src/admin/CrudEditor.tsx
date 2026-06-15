import { useCms } from '../cms/CmsContext';
import { useState, useEffect } from 'react';

export function CrudEditor({ section, title }: { section: keyof typeof initialCmsData, title: string }) {
  const { data, updateData } = useCms();
  const [formState, setFormState] = useState<any>(data[section]);

  useEffect(() => {
    setFormState(data[section]);
  }, [data, section]);

  const handleSave = () => {
    updateData({ ...data, [section]: formState });
    alert("Zapisano pomyślnie!");
  };

  const isArray = Array.isArray(formState);

  // Helper for single object edit
  const renderObjectEditor = (obj: any, onChange: (newObj: any) => void) => {
    return (
      <div className="gap-4 grid md:grid-cols-2">
        {Object.entries(obj).map(([key, val]) => {
          if (key === 'id') return null; // skip id
          return (
            <div key={key} className={key === 'description' || key === 'content' || key === 'answer' || key === 'mission' || key === 'vision' ? 'md:col-span-2' : ''}>
              <label className="block text-sm font-medium mb-1 capitalize text-foreground/70">{key}</label>
              {typeof val === 'string' && val.length > 80 ? (
                <textarea 
                  className="w-full p-2 border border-border rounded-lg bg-background" 
                  rows={4} 
                  value={val as string} 
                  onChange={e => onChange({ ...obj, [key]: e.target.value })} 
                />
              ) : (
                <input 
                  type="text" 
                  className="w-full p-2 border border-border rounded-lg bg-background" 
                  value={val as string} 
                  onChange={e => onChange({ ...obj, [key]: e.target.value })} 
                />
              )}
            </div>
          )
        })}
      </div>
    );
  }

  return (
    <div className="pb-24">
      <div className="flex justify-between items-center mb-8">
         <h1 className="text-3xl font-bold">{title}</h1>
         <button onClick={handleSave} className="bg-accent text-white px-6 py-2 rounded-lg font-medium hover:bg-accent/90">Zapisz Zmiany</button>
      </div>

      <div className="bg-background border border-border p-6 rounded-2xl shadow-sm">
        {!isArray ? (
          renderObjectEditor(formState, setFormState)
        ) : (
          <div className="space-y-8">
             {formState.map((item: any, idx: number) => (
               <div key={item.id} className="p-6 border border-border rounded-xl bg-secondary/20 relative">
                 <div className="absolute top-4 right-4 bg-primary text-primary-foreground w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">{idx + 1}</div>
                 {renderObjectEditor(item, (newObj) => {
                   const arr = [...formState];
                   arr[idx] = newObj;
                   setFormState(arr);
                 })}
               </div>
             ))}
             {/* Fake add button for visual completeness */}
             <button onClick={() => alert("W wersji bez db (localStorage) tworzenie ID obsługujemy z góry, edytuj istniejące elementy.")} className="w-full py-4 border-2 border-dashed border-border rounded-xl hover:bg-secondary transition text-foreground/50 font-medium">
               + Dodaj nowy element
             </button>
          </div>
        )}
      </div>
    </div>
  );
}

// Importing for type reference in props
import { initialCmsData } from '../cms/initialData';
