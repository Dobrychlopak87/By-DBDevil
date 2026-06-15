import { useCms } from '../cms/CmsContext';

export function Produkty() {
  const { data } = useCms();

  return (
    <div className="container mx-auto max-w-7xl px-4 py-24 flex-1">
      <div className="max-w-2xl mx-auto text-center mb-16">
        <h1 className="text-4xl font-bold tracking-tight mb-4">Nasze Autorskie Produkty</h1>
        <p className="text-lg text-foreground/70">W pełni zintegrowane rozwiązania gotowe by zrewolucjonizować działanie Twojego biznesu z wykorzystaniem AI i automatyzacji.</p>
      </div>

      <div className="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
        {data.products.map(p => (
          <div key={p.id} className="group perspective-1000 h-[450px]">
            <div className="relative w-full h-full transform-style-3d transition-transform duration-700 ease-in-out group-hover:rotate-y-180">
              
              {/* Front side */}
              <div className="absolute w-full h-full backface-hidden rounded-3xl overflow-hidden shadow-lg bg-background border border-border flex flex-col">
                <img src={p.image} alt={p.name} className="w-full h-2/3 object-cover" />
                <div className="p-6 flex-1 flex flex-col justify-center text-center">
                  <h3 className="text-2xl font-bold">{p.name}</h3>
                  <p className="text-sm uppercase tracking-wide text-accent mt-2 font-semibold">{p.subtitle}</p>
                </div>
              </div>

              {/* Back side */}
              <div className="absolute w-full h-full backface-hidden rounded-3xl shadow-xl bg-primary text-primary-foreground border border-primary overflow-hidden rotate-y-180 p-10 flex flex-col items-center justify-center text-center">
                <h3 className="text-3xl font-bold mb-6">{p.name}</h3>
                <p className="text-lg leading-relaxed font-light opacity-90">{p.description}</p>
              </div>

            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
