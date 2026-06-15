import { useCms } from '../cms/CmsContext';
import { useParams, Link } from 'react-router-dom';
import { ArrowLeft, Calendar } from 'lucide-react';

export function Post() {
  const { id } = useParams();
  const { data } = useCms();
  const post = data.news.find(n => n.id === id);

  if (!post) {
    return (
      <div className="container mx-auto max-w-3xl px-4 py-24 text-center">
        <h1 className="text-3xl font-bold">Wpis nie został znaleziony</h1>
        <Link to="/aktualnosci" className="text-accent underline mt-4 inline-block">Wróć do aktualności</Link>
      </div>
    );
  }

  return (
    <article className="container mx-auto max-w-4xl px-4 py-16 flex-1">
      <Link to="/aktualnosci" className="inline-flex items-center gap-2 text-foreground/60 hover:text-foreground mb-8 transition font-medium">
        <ArrowLeft className="w-5 h-5" /> Powrót
      </Link>

      <div className="mb-12">
        <div className="flex items-center gap-2 text-sm text-foreground/50 mb-4 font-semibold tracking-wider">
          <Calendar className="w-4 h-4" /> Opublikowano: {post.date}
        </div>
        <h1 className="text-4xl md:text-5xl font-bold tracking-tight leading-tight mb-6">{post.title}</h1>
        <p className="text-xl text-foreground/70 font-light leading-relaxed">{post.excerpt}</p>
      </div>

      <img src={post.image} alt={post.title} className="w-full h-[400px] md:h-[600px] object-cover rounded-3xl mb-12 shadow-md" />

      <div className="prose prose-lg dark:prose-invert max-w-none font-light leading-relaxed">
        {/* We emulate content. In a real app we might use react-markdown here */}
        {post.content.split('\n').map((paragraph, idx) => (
          <p key={idx} className="mb-6">{paragraph}</p>
        ))}
      </div>
    </article>
  );
}
