import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App';
import './index.css';
import { CmsProvider } from './cms/CmsContext';
import { ThemeProvider } from './theme/ThemeContext';

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').then(
      registration => console.log('ServiceWorker registered with scope:', registration.scope),
      err => console.log('ServiceWorker registration failed:', err)
    );
  });
}

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter>
      <ThemeProvider>
        <CmsProvider>
          <App />
        </CmsProvider>
      </ThemeProvider>
    </BrowserRouter>
  </StrictMode>,
);
