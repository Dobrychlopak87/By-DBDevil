import { Routes, Route } from 'react-router-dom';
import { Layout } from './components/Layout';
import { Home } from './pages/Home';
import { Aktualnosci } from './pages/Aktualnosci';
import { Post } from './pages/Post';
import { Produkty } from './pages/Produkty';
import { Uslugi } from './pages/Uslugi';
import { ByDb } from './pages/ByDb';
import { Promocje } from './pages/Promocje';
import { Faq } from './pages/Faq';
import { Kontakt } from './pages/Kontakt';

import { AdminLayout } from './admin/AdminLayout';
import { Login } from './admin/Login';
import { Dashboard } from './admin/Dashboard';
import { CrudEditor } from './admin/CrudEditor';

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Layout />}>
        <Route index element={<Home />} />
        <Route path="aktualnosci" element={<Aktualnosci />} />
        <Route path="aktualnosci/:id" element={<Post />} />
        <Route path="produkty" element={<Produkty />} />
        <Route path="uslugi" element={<Uslugi />} />
        <Route path="by-dbdevstudio" element={<ByDb />} />
        <Route path="promocje" element={<Promocje />} />
        <Route path="faq" element={<Faq />} />
        <Route path="kontakt" element={<Kontakt />} />
      </Route>

      <Route path="/admin" element={<AdminLayout />}>
        <Route index element={<Dashboard />} />
        <Route path="about" element={<CrudEditor section="about" title="Edycja: Kim jesteśmy" />} />
        <Route path="news" element={<CrudEditor section="news" title="Zarządzaj wpisami Bloga" />} />
        <Route path="products" element={<CrudEditor section="products" title="Edycja: Produkty" />} />
        <Route path="services" element={<CrudEditor section="services" title="Edycja: Usługi" />} />
        <Route path="bydb" element={<CrudEditor section="byDb" title="Edycja: by DBDevStudio" />} />
        <Route path="promotions" element={<CrudEditor section="promotions" title="Edycja: Promocje" />} />
        <Route path="faq" element={<CrudEditor section="faq" title="Zarządzaj FAQ" />} />
        <Route path="settings" element={<CrudEditor section="settings" title="Ustawienia ogólne i UI" />} />
        <Route path="files" element={<div className="p-8 border-2 border-dashed border-border rounded-xl text-center"><h2 className="font-bold text-2xl mb-2">Menadżer plików</h2><p>W wersji instalacyjnej (PHP/Node) tutaj znajduje się uploader Assetów.</p></div>} />
      </Route>
      
      <Route path="/admin/login" element={<Login />} />
    </Routes>
  );
}
