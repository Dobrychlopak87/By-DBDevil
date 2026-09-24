<?php
// Panel administracyjny - Dashboard
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!isAdminLoggedIn()) {
    redirect(SITE_URL . '/admin/login.php');
    exit();
}

// Pobierz statystyki
global $pdo;
ensureAdModerationSchema();

// Ilość ogłoszeń
$adsCount = $pdo->query("SELECT COUNT(*) as count FROM ads WHERE is_active = 1 AND submission_status = 'approved'")->fetch()['count'];
$adsInactiveCount = $pdo->query("SELECT COUNT(*) as count FROM ads WHERE is_active = 0")->fetch()['count'];
$pendingAdsCount = $pdo->query("SELECT COUNT(*) as count FROM ads WHERE submission_status = 'pending'")->fetch()['count'];

// Ilość artykułów blogowych
$blogPostsCount = $pdo->query("SELECT COUNT(*) as count FROM blog_posts WHERE is_active = 1")->fetch()['count'];
$blogPostsInactiveCount = $pdo->query("SELECT COUNT(*) as count FROM blog_posts WHERE is_active = 0")->fetch()['count'];

// Ilość kategorii ogłoszeń
$adCategoriesCount = $pdo->query("SELECT COUNT(*) as count FROM ad_categories WHERE is_active = 1")->fetch()['count'];

// Ilość kategorii bloga
$blogCategoriesCount = $pdo->query("SELECT COUNT(*) as count FROM blog_categories WHERE is_active = 1")->fetch()['count'];

// Ostatnie ogłoszenia
$latestAds = $pdo->query("SELECT a.*, ac.name as category_name FROM ads a JOIN ad_categories ac ON a.category_id = ac.id WHERE a.is_active = 1 AND a.submission_status = 'approved' ORDER BY a.created_at DESC LIMIT 5")->fetchAll();

// Ostatnie artykuły
$latestBlogPosts = $pdo->query("SELECT bp.*, bc.name as category_name FROM blog_posts bp JOIN blog_categories bc ON bp.category_id = bc.id WHERE bp.is_active = 1 ORDER BY bp.created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard - Panel administracyjny';
$csrfToken = getAdminCsrfToken();
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

    <div class="admin-container">
        <!-- Admin Header -->
        <?php require_once __DIR__ . '/includes/admin-header.php'; ?>
        
        <!-- Dashboard Content -->
        <div class="admin-content">
            <!-- Page Header -->
            <div class="admin-page-header">
                <div class="admin-page-title">
                    <h1>Dashboard</h1>
                    <p>Witamy w panelu administracyjnym 66600.PL</p>
                </div>
                <div class="admin-page-actions">
                    <a href="<?= SITE_URL ?>/admin/ads/add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Dodaj ogłoszenie
                    </a>
                    <a href="<?= SITE_URL ?>/admin/blog/add.php" class="btn btn-secondary">
                        <i class="fas fa-pen"></i> Dodaj artykuł
                    </a>
                    <a href="<?= SITE_URL ?>/" class="btn btn-secondary" target="_blank" rel="noopener">
                        <i class="fas fa-external-link-alt"></i> Zobacz stronę
                    </a>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Ogłoszenia</h3>
                        <p class="stat-number"><?= $adsCount ?></p>
                        <p class="stat-subtitle"><?= $pendingAdsCount ? $pendingAdsCount . ' oczekuje na moderację' : ($adsInactiveCount ? $adsInactiveCount . ' ukrytych do sprawdzenia' : 'Wszystkie opublikowane') ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Artykuły</h3>
                        <p class="stat-number"><?= $blogPostsCount ?></p>
                        <p class="stat-subtitle"><?= $blogPostsInactiveCount ? $blogPostsInactiveCount . ' szkiców / ukrytych' : 'Wszystkie opublikowane' ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Kategorie ogłoszeń</h3>
                        <p class="stat-number"><?= $adCategoriesCount ?></p>
                        <p class="stat-subtitle">Aktywne kategorie</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Kategorie bloga</h3>
                        <p class="stat-number"><?= $blogCategoriesCount ?></p>
                        <p class="stat-subtitle">Aktywne kategorie</p>
                    </div>
                </div>
            </div>
            
            <section class="quick-actions-grid" aria-label="Szybkie działania">
                <a href="<?= SITE_URL ?>/admin/ads/add.php" class="quick-action-card">
                    <span class="quick-action-icon"><i class="fas fa-bullhorn"></i></span>
                    <span><h2>Nowe ogłoszenie</h2><p>Dodaj lokalną ofertę lub usługę.</p></span>
                </a>
                <a href="<?= SITE_URL ?>/admin/blog/add.php" class="quick-action-card">
                    <span class="quick-action-icon"><i class="fas fa-newspaper"></i></span>
                    <span><h2>Nowy artykuł</h2><p>Opublikuj aktualność lub materiał redakcyjny.</p></span>
                </a>
                <a href="<?= SITE_URL ?>/admin/ads/index.php?moderation=pending" class="quick-action-card">
                    <span class="quick-action-icon"><i class="fas fa-hourglass-half"></i></span>
                    <span><h2>Ogłoszenia do moderacji</h2><p>Przejrzyj, edytuj i zatwierdź zgłoszenia użytkowników.</p></span>
                </a>
                <a href="<?= SITE_URL ?>/admin/blog/index.php?status=inactive" class="quick-action-card">
                    <span class="quick-action-icon"><i class="fas fa-file-alt"></i></span>
                    <span><h2>Nieopublikowane artykuły</h2><p>Wróć do materiałów wymagających decyzji.</p></span>
                </a>
                <a href="<?= SITE_URL ?>/admin/calendar.php" class="quick-action-card">
                    <span class="quick-action-icon"><i class="fas fa-calendar-alt"></i></span>
                    <span><h2>Kalendarz</h2><p>Przeglądaj i usuwaj publiczne wydarzenia.</p></span>
                </a>
                <a href="<?= SITE_URL ?>/admin/pulse.php" class="quick-action-card">
                    <span class="quick-action-icon"><i class="fas fa-bolt"></i></span>
                    <span><h2>Moderacja Pulsu</h2><p>Ukryj, przywróć albo usuń aktywny komunikat.</p></span>
                </a>
                <a href="<?= SITE_URL ?>/chatroom/" class="quick-action-card">
                    <span class="quick-action-icon"><i class="fas fa-comments"></i></span>
                    <span><h2>Moderacja chatroomu</h2><p>Zarządzaj osobami online, rolami i blokadami.</p></span>
                </a>
            </section>

            <!-- Latest Items -->
            <div class="dashboard-sections">
                <!-- Latest Ads -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Ostatnie ogłoszenia</h2>
                        <a href="ads/index.php" class="see-all-link">Zobacz wszystkie</a>
                    </div>
                    
                    <?php if (!empty($latestAds)): ?>
                        <div class="latest-items-list">
                            <?php foreach ($latestAds as $ad): ?>
                                <div class="latest-item">
                                    <div class="latest-item-image">
                                        <img src="<?= getImageUrl($ad['image']) ?>" alt="<?= sanitize($ad['title']) ?>">
                                    </div>
                                    <div class="latest-item-info">
                                        <h4><?= truncateText(sanitize($ad['title']), 40) ?></h4>
                                        <p class="category-tag"><?= sanitize($ad['category_name']) ?></p>
                                        <p class="item-date"><?= formatDate($ad['created_at']) ?></p>
                                    </div>
                                    <div class="latest-item-actions">
                                        <a href="ads/edit.php?id=<?= $ad['id'] ?>" class="action-btn edit-btn" title="Edytuj">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="submit" form="dashboard-ad-delete-<?= (int) $ad['id'] ?>" class="action-btn delete-btn" title="Usuń" onclick="return confirm('Czy na pewno chcesz usunąć to ogłoszenie?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-items">Brak ogłoszeń.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Latest Blog Posts -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Ostatnie artykuły</h2>
                        <a href="blog/index.php" class="see-all-link">Zobacz wszystkie</a>
                    </div>
                    
                    <?php if (!empty($latestBlogPosts)): ?>
                        <div class="latest-items-list">
                            <?php foreach ($latestBlogPosts as $post): ?>
                                <div class="latest-item">
                                    <div class="latest-item-image">
                                        <img src="<?= getImageUrl($post['image']) ?>" alt="<?= sanitize($post['title']) ?>">
                                    </div>
                                    <div class="latest-item-info">
                                        <h4><?= truncateText(sanitize($post['title']), 40) ?></h4>
                                        <p class="category-tag"><?= sanitize($post['category_name']) ?></p>
                                        <p class="item-date"><?= formatDate($post['created_at']) ?></p>
                                    </div>
                                    <div class="latest-item-actions">
                                        <a href="blog/edit.php?id=<?= $post['id'] ?>" class="action-btn edit-btn" title="Edytuj">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="submit" form="dashboard-blog-delete-<?= (int) $post['id'] ?>" class="action-btn delete-btn" title="Usuń" onclick="return confirm('Czy na pewno chcesz usunąć ten artykuł?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-items">Brak artykułów.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php foreach ($latestAds as $ad): ?>
        <form id="dashboard-ad-delete-<?= (int) $ad['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/ads/delete.php" hidden>
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= (int) $ad['id'] ?>">
        </form>
    <?php endforeach; ?>
    <?php foreach ($latestBlogPosts as $post): ?>
        <form id="dashboard-blog-delete-<?= (int) $post['id'] ?>" method="post" action="<?= SITE_URL ?>/admin/blog/delete.php" hidden>
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
        </form>
    <?php endforeach; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
