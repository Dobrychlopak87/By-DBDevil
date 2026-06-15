import { useCms } from '../cms/CmsContext';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'motion/react';
import { Calendar } from 'lucide-react';

const POSTS_PER_PAGE = 6;

export function Aktualnosci() {
  const { data } = useCms();
  const [currentPage, setCurrentPage] = useState(1);

  const posts = data.news;
  const totalPages = Math.ceil(posts.length / POSTS_PER_PAGE);
  const startIndex = (currentPage - 1) * POSTS_PER_PAGE;
  const currentPosts = posts.slice(startIndex, startIndex + POSTS_PER_PAGE);

  return (
    <div className="container mx-auto max-w-7xl px-4 py-24 flex-1">
      <div className="text-center mb-16">
        <h1 className="text-5xl font-bold tracking-tight mb-4">Aktualności</h1>
        <p className="text-xl text-foreground/70">Najnowsze wpisy technologiczne, raporty i nowości z życia DBDevStudio.</p>
      </div>

      <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        {currentPosts.map(post => (
          <Link key={post.id} to={`/aktualnosci/${post.id}`} className="group block h-full">
            <motion.div 
              whileHover={{ y: -5 }}
              className="bg-background border border-border rounded-2xl overflow-hidden h-full flex flex-col hover:shadow-xl transition-all"
            >
              <div className="h-56 overflow-hidden">
                <img src={post.image} alt={post.title} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              </div>
              <div className="p-6 flex-1 flex flex-col">
                <div className="flex items-center gap-2 text-xs text-foreground/50 mb-3 font-semibold tracking-wider">
                  <Calendar className="w-4 h-4" /> {post.date}
                </div>
                <h2 className="text-xl font-bold mb-3 group-hover:text-accent transition-colors">{post.title}</h2>
                <p className="text-foreground/70 line-clamp-3">{post.excerpt}</p>
                <div className="mt-auto pt-6 text-sm font-semibold text-accent flex items-center gap-1 group-hover:gap-2 transition-all">
                  Czytaj dalej <span className="text-lg">→</span>
                </div>
              </div>
            </motion.div>
          </Link>
        ))}
      </div>

      {totalPages > 1 && (
        <div className="flex justify-center items-center gap-2 mt-16">
          <button 
            disabled={currentPage === 1}
            onClick={() => setCurrentPage(p => p - 1)}
            className="px-4 py-2 rounded-lg border border-border disabled:opacity-50 hover:bg-secondary transition"
          >
            Wstecz
          </button>
          
          {[...Array(totalPages)].map((_, i) => (
            <button
              key={i}
              onClick={() => setCurrentPage(i + 1)}
              className={`w-10 h-10 rounded-lg font-medium transition ${currentPage === i + 1 ? 'bg-primary text-primary-foreground' : 'hover:bg-secondary'}`}
            >
              {i + 1}
            </button>
          ))}

          <button 
            disabled={currentPage === totalPages}
            onClick={() => setCurrentPage(p => p + 1)}
            className="px-4 py-2 rounded-lg border border-border disabled:opacity-50 hover:bg-secondary transition"
          >
            Dalej
          </button>
        </div>
      )}
    </div>
  );
}
