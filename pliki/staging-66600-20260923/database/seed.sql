-- Minimalne dane startowe nowej instalacji 66600.PL.
-- Tylko kategorie, menu i ustawienia konieczne do działania modułów.
-- Brak kont administratorów — konto tworzy instalator w osobnym kroku.
-- Ziarno jest idempotentne (slug jest unikalny).
SET NAMES utf8mb4;

-- Kategorie ogłoszeń (zgodne z zestawem ikon aplikacji).
INSERT INTO ad_categories (id, parent_id, slug, name, icon, display_order, is_active) VALUES
(1, NULL, 'taxi', 'Taxi', 'taxi', 1, 1),
(2, NULL, 'fachowcy', 'Fachowcy', 'fachowcy', 2, 1),
(3, NULL, 'beauty', 'Beauty', 'beauty', 3, 1),
(4, NULL, 'gastronomia', 'Gastronomia', 'gastronomia', 4, 1),
(5, NULL, 'rozrywka', 'Rozrywka', 'rozrywka', 5, 1),
(6, NULL, 'handel', 'Handel', 'handel', 6, 1),
(7, NULL, 'kupie-sprzedam', 'Kupię - Sprzedam', 'kupie-sprzedam', 7, 1),
(8, 7, 'sprzedam', 'Sprzedam', 'sprzedam', 1, 1),
(9, 7, 'kupie', 'Kupię', 'kupie', 2, 1),
(10, 7, 'oddam-za-darmo', 'Oddam za darmo', 'oddam-za-darmo', 3, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), display_order = VALUES(display_order);

-- Kategorie kroniki.
INSERT INTO blog_categories (id, slug, name, icon, display_order, is_active) VALUES
(1, 'na-biezaco', 'Na bieżąco', 'na-biezaco', 1, 1),
(2, 'zwracamy-uwage', 'Zwracamy uwagę', 'zwracamy-uwage', 2, 1),
(3, 'rekreacja', 'Rekreacja', 'rekreacja', 3, 1),
(4, 'inicjatywy', 'Inicjatywy', 'inicjatywy', 4, 1),
(5, 'ciekawostki', 'Ciekawostki', 'ciekawostki', 5, 1),
(6, 'historia-miasta', 'Historia miasta', 'historia-miasta', 6, 1),
(7, 'w-planach', 'W planach', 'w-planach', 7, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), display_order = VALUES(display_order);

-- Menu publiczne (klonowane przez header.php według menu_key).
INSERT INTO public_menu_items (menu_key, label, is_active, display_order) VALUES
('ads', 'Ogłoszenia', 1, 1),
('blog', 'Kronika', 1, 2),
('pulse', 'Puls miasta', 1, 3),
('chatroom', 'Chatroom', 1, 4),
('about', 'O nas', 1, 5),
('report', 'Zgłoś', 1, 6)
ON DUPLICATE KEY UPDATE label = VALUES(label), is_active = VALUES(is_active), display_order = VALUES(display_order);
