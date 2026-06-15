import { useState } from 'react';
import { useOutletContext, useNavigate, Link } from 'react-router-dom';

export function Login() {
  const { setIsAuthenticated } = useOutletContext<any>() || {};
  const navigate = useNavigate();
  const [login, setLogin] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (login === 'admin' && password === 'admin123') {
      localStorage.setItem('admin_auth', 'true');
      if (setIsAuthenticated) setIsAuthenticated(true);
      navigate('/admin');
    } else {
      setError('Nieprawidłowy login lub hasło.');
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-background px-4">
      <div className="max-w-md w-full bg-secondary/50 border border-border p-8 rounded-3xl shadow-2xl">
        <div className="text-center mb-8">
           <h1 className="text-3xl font-bold mb-2">Panel Admina</h1>
           <p className="text-foreground/60">Zaloguj się aby zarządzać DBDevStudio</p>
        </div>
        {error && <div className="bg-red-500/10 text-red-500 p-3 rounded-lg text-sm mb-6 text-center font-medium">{error}</div>}
        <form onSubmit={handleSubmit} className="space-y-5">
          <div>
            <label className="block text-sm font-medium mb-1">Login</label>
            <input type="text" value={login} onChange={e=>setLogin(e.target.value)} className="w-full px-4 py-3 rounded-xl border border-border bg-background focus:ring-2 focus:ring-accent outline-none" placeholder="admin" />
          </div>
          <div>
            <label className="block text-sm font-medium mb-1">Hasło</label>
            <input type="password" value={password} onChange={e=>setPassword(e.target.value)} className="w-full px-4 py-3 rounded-xl border border-border bg-background focus:ring-2 focus:ring-accent outline-none" placeholder="••••••••" />
          </div>
          <button type="submit" className="w-full bg-primary text-primary-foreground font-semibold py-3 rounded-xl transition hover:bg-primary/90">
            Zaloguj się
          </button>
        </form>
        <div className="mt-8 text-center text-sm">
          <Link to="/" className="text-foreground/50 hover:text-foreground">Wróć do strony głównej</Link>
        </div>
      </div>
    </div>
  );
}
