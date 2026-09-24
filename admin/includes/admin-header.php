<?php
// Sprawdź, czy użytkownik jest zalogowany
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

// Pobierz informacje o zalogowanym użytkowniku
session_start();
$currentUser = [
    'full_name' => $_SESSION['full_name'] ?? 'Administrator',
    'username' => $_SESSION['username'] ?? 'admin',
    'role' => $_SESSION['role'] ?? 'admin'
];
$adminRequestPath = $_SERVER['REQUEST_URI'] ?? '';
$isAdsSection = strpos($adminRequestPath, '/admin/ads/') !== false;
$isBlogSection = strpos($adminRequestPath, '/admin/blog/') !== false;
$isCalendarSection = strpos($adminRequestPath, '/admin/calendar.php') !== false;
$isMenuSection = strpos($adminRequestPath, '/admin/menu.php') !== false;
$isPollsSection = strpos($adminRequestPath, '/admin/polls.php') !== false;
$isPulseSection = strpos($adminRequestPath, '/admin/pulse.php') !== false;
?>

<div class="admin-header">
    <div class="admin-header-left">
        <div class="admin-logo">
            <a href="<?= SITE_URL ?>/admin/dashboard.php" style="color: inherit; text-decoration: none;">
                <span class="logo-icon">66600</span>
                <span class="logo-text">Panel administracyjny</span>
            </a>
        </div>
        <button type="button" class="admin-menu-toggle" id="admin-menu-toggle" aria-label="Otwórz menu panelu" aria-controls="admin-mobile-menu" aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="admin-header-right">
        <div class="admin-user-info">
            <span class="user-greeting">Witaj, <strong><?= sanitize($currentUser['full_name']) ?></strong>!</span>
            <span class="user-role"><?= ucfirst($currentUser['role']) ?></span>
        </div>
        <div class="admin-user-actions">
            <a href="<?= SITE_URL ?>/" class="header-btn" target="_blank" title="Przejdź do strony">
                <i class="fas fa-external-link-alt"></i>
            </a>
            <a href="<?= SITE_URL ?>/admin/logout.php" class="header-btn" title="Wyloguj się">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</div>

<div class="admin-sidebar" id="admin-sidebar">
    <nav class="admin-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/admin/dashboard.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item has-submenu <?= $isAdsSection ? 'is-current' : '' ?>">
                <a href="#" class="nav-link <?= $isAdsSection ? 'active' : '' ?>" aria-expanded="<?= $isAdsSection ? 'true' : 'false' ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Ogłoszenia</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li><a href="<?= SITE_URL ?>/admin/ads/index.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/ads/index.php') !== false ? 'active' : '' ?>">Wszystkie ogłoszenia</a></li>
                    <li><a href="<?= SITE_URL ?>/admin/ads/add.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/ads/add.php') !== false ? 'active' : '' ?>">Dodaj ogłoszenie</a></li>
                    <li><a href="<?= SITE_URL ?>/admin/ads/categories.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/ads/categories.php') !== false ? 'active' : '' ?>">Kategorie ogłoszeń</a></li>
                    <li><a href="<?= SITE_URL ?>/admin/ads/homepage-order.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/ads/homepage-order.php') !== false ? 'active' : '' ?>">Kolejność strony głównej</a></li>
                </ul>
            </li>
            
            <li class="nav-item has-submenu <?= $isBlogSection ? 'is-current' : '' ?>">
                <a href="#" class="nav-link <?= $isBlogSection ? 'active' : '' ?>" aria-expanded="<?= $isBlogSection ? 'true' : 'false' ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>Blog</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li><a href="<?= SITE_URL ?>/admin/blog/index.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/blog/index.php') !== false ? 'active' : '' ?>">Wszystkie artykuły</a></li>
                    <li><a href="<?= SITE_URL ?>/admin/blog/add.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/blog/add.php') !== false ? 'active' : '' ?>">Dodaj artykuł</a></li>
                    <li><a href="<?= SITE_URL ?>/admin/blog/categories.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/blog/categories.php') !== false ? 'active' : '' ?>">Kategorie bloga</a></li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/admin/calendar.php" class="nav-link <?= $isCalendarSection ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Kalendarz</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/admin/polls.php" class="nav-link <?= $isPollsSection ? 'active' : '' ?>">
                    <i class="fas fa-poll"></i>
                    <span>Ankiety</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/admin/pulse.php" class="nav-link <?= $isPulseSection ? 'active' : '' ?>">
                    <i class="fas fa-bolt"></i>
                    <span>Puls miasta</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/chatroom/" class="nav-link">
                    <i class="fas fa-comments"></i>
                    <span>Moderacja chatroomu</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/admin/menu.php" class="nav-link <?= $isMenuSection ? 'active' : '' ?>">
                    <i class="fas fa-bars"></i>
                    <span>Menu strony</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/admin/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Wyloguj się</span>
                </a>
            </li>
        </ul>
    </nav>
</div>

<!-- Mobile Menu Overlay -->
<div class="admin-menu-backdrop" id="admin-menu-backdrop" aria-hidden="true"></div>
<div class="admin-mobile-menu" id="admin-mobile-menu">
    <div class="mobile-menu-header">
        <button type="button" class="mobile-menu-close" id="mobile-menu-close" aria-label="Zamknij menu panelu">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <nav class="mobile-menu-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/admin/dashboard.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard.php') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item has-submenu <?= $isAdsSection ? 'is-current' : '' ?>">
                <a href="#" class="nav-link <?= $isAdsSection ? 'active' : '' ?>" aria-expanded="<?= $isAdsSection ? 'true' : 'false' ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Ogłoszenia</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li><a href="<?= SITE_URL ?>/admin/ads/index.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/ads/index.php') !== false ? 'active' : '' ?>">Wszystkie ogłoszenia</a></li>
                    <li><a href="<?= SITE_URL ?>/admin/ads/add.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/ads/add.php') !== false ? 'active' : '' ?>">Dodaj ogłoszenie</a></li>
                    <li><a href="<?= SITE_URL ?>/admin/ads/categories.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/ads/categories.php') !== false ? 'active' : '' ?>">Kategorie ogłoszeń</a></li>
                    <li><a href="<?= SITE_URL ?>/admin/ads/homepage-order.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/ads/homepage-order.php') !== false ? 'active' : '' ?>">Kolejność strony głównej</a></li>
                </ul>
            </li>
            
            <li class="nav-item has-submenu <?= $isBlogSection ? 'is-current' : '' ?>">
                <a href="#" class="nav-link <?= $isBlogSection ? 'active' : '' ?>" aria-expanded="<?= $isBlogSection ? 'true' : 'false' ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>Blog</span>
                    <i class="fas fa-chevron-right submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <li><a href="<?= SITE_URL ?>/admin/blog/index.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/blog/index.php') !== false ? 'active' : '' ?>">Wszystkie artykuły</a></li>
                    <li><a href="<?= SITE_URL ?>/admin/blog/add.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/blog/add.php') !== false ? 'active' : '' ?>">Dodaj artykuł</a></li>
                    <li><a href="<?= SITE_URL ?>/admin/blog/categories.php" class="submenu-link <?= strpos($adminRequestPath, '/admin/blog/categories.php') !== false ? 'active' : '' ?>">Kategorie bloga</a></li>
                </ul>
            </li>
            
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/admin/polls.php" class="nav-link <?= $isPollsSection ? 'active' : '' ?>">
                    <i class="fas fa-poll"></i>
                    <span>Ankiety</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/admin/pulse.php" class="nav-link <?= $isPulseSection ? 'active' : '' ?>">
                    <i class="fas fa-bolt"></i>
                    <span>Puls miasta</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/chatroom/" class="nav-link">
                    <i class="fas fa-comments"></i>
                    <span>Moderacja chatroomu</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/admin/menu.php" class="nav-link <?= $isMenuSection ? 'active' : '' ?>">
                    <i class="fas fa-bars"></i>
                    <span>Menu strony</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= SITE_URL ?>/admin/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Wyloguj się</span>
                </a>
            </li>
        </ul>
    </nav>
</div>

<script>
    // Toggle sidebar na mobile
    const menuToggle = document.getElementById('admin-menu-toggle');
    const sidebar = document.getElementById('admin-sidebar');
    const mobileMenu = document.getElementById('admin-mobile-menu');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    const mobileMenuBackdrop = document.getElementById('admin-menu-backdrop');

    function closeMobileMenu() {
        if (!mobileMenu) return;
        mobileMenu.classList.remove('active');
        mobileMenuBackdrop?.classList.remove('active');
        document.body.classList.remove('admin-menu-open');
        if (menuToggle) {
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.focus();
        }
    }

    function openMobileMenu() {
        if (!mobileMenu) return;
        mobileMenu.classList.add('active');
        mobileMenuBackdrop?.classList.add('active');
        document.body.classList.add('admin-menu-open');
        menuToggle?.setAttribute('aria-expanded', 'true');
        mobileMenu.querySelector('a, button')?.focus();
    }
    
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', openMobileMenu);
    }
    
    if (mobileMenuClose && mobileMenu) {
        mobileMenuClose.addEventListener('click', closeMobileMenu);
    }
    mobileMenuBackdrop?.addEventListener('click', closeMobileMenu);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileMenu?.classList.contains('active')) {
            closeMobileMenu();
        }
    });
    
    // Toggle submenu
    const submenuToggles = document.querySelectorAll('.has-submenu > .nav-link');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            const submenu = toggle.nextElementSibling;
            const arrow = toggle.querySelector('.submenu-arrow');
            const isExpanded = submenu.classList.toggle('active');
            arrow.classList.toggle('active', isExpanded);
            toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        });
    });

    // Podgląd ikon SVG w formularzach kategorii. Ostateczna walidacja jest wykonywana po stronie PHP.
    document.querySelectorAll('.icon-file-input[data-svg-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const preview = document.getElementById(input.dataset.svgPreview);
            const file = input.files && input.files[0];
            if (!preview) return;
            preview.innerHTML = '';
            preview.classList.remove('is-error');
            if (!file) return;
            if (!/\.svg$/i.test(file.name) || file.size > 307200) {
                preview.textContent = 'Wybierz plik SVG o rozmiarze nie większym niż 300 KB.';
                preview.classList.add('is-error');
                input.value = '';
                return;
            }
            const url = URL.createObjectURL(file);
            const image = document.createElement('img');
            image.src = url;
            image.alt = 'Podgląd przesłanej ikony';
            image.onload = () => URL.revokeObjectURL(url);
            preview.append(image, document.createTextNode(file.name));
        });
    });
</script>
