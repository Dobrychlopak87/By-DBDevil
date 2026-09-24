<?php
require_once __DIR__ . '/config.php';

// ============================================
// FUNKCJE OGÓLNE
// ============================================

function ensureAdCategoryHierarchySchema() {
    static $checked = null;
    if ($checked !== null) return $checked;
    global $pdo;
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM ad_categories")->fetchAll(PDO::FETCH_COLUMN);
        $required = ['parent_id'];
        $checked = count(array_diff($required, $columns)) === 0;
    } catch (PDOException $e) { $checked = false; }
    return $checked;
}

/**
 * Pobierz wszystkie aktywne kategorie ogłoszeń
 */
function getAdCategories() {
    static $categories = null;
    if ($categories !== null) {
        return $categories;
    }
    global $pdo;
    ensureAdCategoryHierarchySchema();
    ensureCategoryIconSchema();
    $stmt = $pdo->query("SELECT c.*, p.slug AS parent_slug, p.name AS parent_name
        FROM ad_categories c
        LEFT JOIN ad_categories p ON c.parent_id = p.id
        WHERE c.is_active = 1
        ORDER BY COALESCE(p.display_order, c.display_order) ASC, c.parent_id IS NOT NULL ASC, c.display_order ASC");
    $categories = $stmt->fetchAll();
    return $categories;
}

function getAdCategoryTree() {
    $categories = getAdCategories();
    $roots = [];

    foreach ($categories as $category) {
        $category['children'] = [];
        if (empty($category['parent_id'])) {
            $roots[(int) $category['id']] = $category;
        }
    }

    foreach ($categories as $category) {
        $parentId = (int) ($category['parent_id'] ?? 0);
        if ($parentId > 0 && isset($roots[$parentId])) {
            $roots[$parentId]['children'][] = $category;
        }
    }

    return array_values($roots);
}

function getAdSelectableCategories() {
    $categories = [];
    foreach (getAdCategoryTree() as $category) {
        if (!empty($category['children'])) {
            foreach ($category['children'] as $child) {
                $categories[] = $child;
            }
            continue;
        }
        $categories[] = $category;
    }
    return $categories;
}

/**
 * Pobierz kategorię ogłoszeń po slug
 */
function getAdCategoryBySlug($slug) {
    global $pdo;
    $slug = strtolower(trim((string) $slug));
    ensureAdCategoryHierarchySchema();
    ensureCategoryIconSchema();
    $stmt = $pdo->prepare("SELECT * FROM ad_categories WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

/**
 * Pobierz wszystkie aktywne kategorie bloga
 */
function getBlogCategories() {
    static $categories = null;
    if ($categories !== null) {
        return $categories;
    }
    global $pdo;
    ensureCategoryIconSchema();
    $stmt = $pdo->query("SELECT * FROM blog_categories WHERE is_active = 1 ORDER BY display_order ASC");
    $categories = $stmt->fetchAll();
    return $categories;
}

/**
 * Pobierz kategorię bloga po slug
 */
function getBlogCategoryBySlug($slug) {
    global $pdo;
    ensureCategoryIconSchema();
    $stmt = $pdo->prepare("SELECT * FROM blog_categories WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

/**
 * Zapewnia konfigurację pozycji publicznego menu bez utrwalania kategorii,
 * które są zawsze pobierane dynamicznie z bieżących danych serwisu.
 */
function ensurePublicMenuSchema() {
    static $checked = null;
    if ($checked !== null) return $checked;
    global $pdo;
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'public_menu_items'");
        $stmt->execute();
        $checked = (bool) $stmt->fetchColumn();
    } catch (PDOException $e) { $checked = false; }
    return $checked;
}

/**
 * Zwraca aktywne pozycje menu w kolejności ustawionej w panelu.
 */
function getPublicMenuItems() {
    global $pdo;
    if (!ensurePublicMenuSchema()) {
        return [];
    }
    return $pdo->query("SELECT * FROM public_menu_items WHERE is_active = 1 ORDER BY display_order ASC, id ASC")->fetchAll();
}

// ============================================
// IKONY KATEGORII
// ============================================

/**
 * Dostępne, bezpieczne identyfikatory ikon SVG wybierane w panelu administratora.
 */
function getCategoryIconOptions() {
    return [
        'all' => 'Globus',
        'taxi' => 'Samochód / taxi',
        'fachowcy' => 'Narzędzia',
        'beauty' => 'Beauty',
        'gastronomia' => 'Gastronomia',
        'rozrywka' => 'Rozrywka',
        'handel' => 'Handel',
        'kupie-sprzedam' => 'Kupię - Sprzedam',
        'kupie' => 'Kupię',
        'sprzedam' => 'Sprzedam',
        'oddam-za-darmo' => 'Oddam za darmo',
        'zagubione-znalezione' => 'Zagubione - Znalezione',
        'na-biezaco' => 'Zegar',
        'zwracamy-uwage' => 'Uwaga',
        'rekreacja' => 'Rekreacja',
        'inicjatywy' => 'Inicjatywy',
        'ciekawostki' => 'Ciekawostki',
        'historia-miasta' => 'Historia miasta',
        'w-planach' => 'W planach',
        'location' => 'Lokalizacja',
        'phone' => 'Telefon',
        'email' => 'E-mail'
    ];
}

/**
 * Dodaje pole ikony do kategorii bloga oraz normalizuje stare wartości do kluczy SVG.
 */
function ensureCategoryIconSchema() {
    static $checked = null;
    if ($checked !== null) return $checked;
    if (!ensureAdCategoryHierarchySchema()) return $checked = false;
    global $pdo;
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM blog_categories")->fetchAll(PDO::FETCH_COLUMN);
        $checked = in_array('icon', $columns, true);
    } catch (PDOException $e) { $checked = false; }
    return $checked;
}

/**
 * Zwraca bezpieczny klucz ikony zapisany przy kategorii, z zachowaniem fallbacku do sluga.
 */
function getCategoryIconKey($category) {
    $options = getCategoryIconOptions();
    $icon = is_array($category) ? ($category['icon'] ?? '') : '';
    $slug = is_array($category) ? ($category['slug'] ?? 'all') : 'all';
    return array_key_exists($icon, $options) ? $icon : (array_key_exists($slug, $options) ? $slug : 'all');
}

/**
 * Własne ikony są przechowywane jako prefiks custom: oraz losowa nazwa SVG.
 */
function isCustomCategoryIcon($icon) {
    return is_string($icon) && preg_match('/^custom:([a-f0-9]{32}\\.svg)$/', $icon) === 1;
}

function getCustomCategoryIconUrl($icon) {
    if (!isCustomCategoryIcon($icon)) {
        return null;
    }
    $filename = substr($icon, strlen('custom:'));
    $file = __DIR__ . '/../assets/uploads/category-icons/' . $filename;
    if (!is_file($file)) {
        return null;
    }
    return SITE_URL . '/assets/uploads/category-icons/' . rawurlencode($filename);
}

/**
 * Zwraca bezpieczny znacznik ikony: własne SVG jako obraz albo ikonę wbudowaną.
 */
function getCategoryIconMarkup($category) {
    $icon = is_array($category) ? ($category['icon'] ?? '') : '';
    $customUrl = getCustomCategoryIconUrl($icon);
    if ($customUrl !== null) {
        return '<img class="category-custom-icon" src="' . sanitize($customUrl) . '" alt="" aria-hidden="true" loading="lazy" decoding="async">';
    }
    return getCategorySvg(getCategoryIconKey($category));
}

/**
 * Usuwa osieroconą własną ikonę po zastąpieniu jej inną ikoną kategorii.
 */
function deleteCustomCategoryIcon($icon) {
    if (!isCustomCategoryIcon($icon)) {
        return;
    }
    $filename = substr($icon, strlen('custom:'));
    $file = __DIR__ . '/../assets/uploads/category-icons/' . $filename;
    if (is_file($file)) {
        @unlink($file);
    }
}

/**
 * Wczytuje wyłącznie małe, statyczne SVG przeznaczone do użycia jako ikona kategorii.
 */
function uploadCategoryIconSvg($file) {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'empty' => true, 'message' => 'Nie wybrano pliku SVG.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'empty' => false, 'message' => 'Nie udało się przesłać ikony SVG.'];
    }
    if (($file['size'] ?? 0) < 1 || $file['size'] > 307200) {
        return ['success' => false, 'empty' => false, 'message' => 'Ikona SVG może mieć maksymalnie 300 KB.'];
    }
    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if ($extension !== 'svg') {
        return ['success' => false, 'empty' => false, 'message' => 'Dozwolony jest wyłącznie plik SVG.'];
    }

    $svg = @file_get_contents($file['tmp_name']);
    if ($svg === false || !preg_match('/<svg\\b[^>]*>/i', $svg)) {
        return ['success' => false, 'empty' => false, 'message' => 'Plik nie zawiera prawidłowego elementu SVG.'];
    }

    // Odrzucamy wyłącznie aktywne treści i zewnętrzne odwołania, zachowując zgodność ze standardowymi plikami SVG.
    $blockedPattern = '/<\\s*(script|foreignobject|iframe|object|embed|image|audio|video|animate|set)\\b|\\bon[a-z]+\\s*=|javascript\\s*:|\\bdata\\s*:|@\\s*import\\b/i';
    $externalReferencePattern = '/\\b(?:href|xlink:href)\\s*=\\s*(["\'])\\s*(?!#)[^"\']+\\1/i';
    $styleUrlPattern = '/url\\(\\s*(?:["\'])?\\s*(?!#)[^)]+\\)/i';
    if (preg_match($blockedPattern, $svg) || preg_match($externalReferencePattern, $svg) || preg_match($styleUrlPattern, $svg)) {
        return ['success' => false, 'empty' => false, 'message' => 'Plik SVG zawiera niedozwolone elementy lub odwołania zewnętrzne.'];
    }

    $directory = __DIR__ . '/../assets/uploads/category-icons';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        return ['success' => false, 'empty' => false, 'message' => 'Nie udało się przygotować katalogu ikon.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.svg';
    $target = $directory . '/' . $filename;
    $staging = $directory . '/' . bin2hex(random_bytes(8)) . '.part';
    if (@file_put_contents($staging, $svg, LOCK_EX) === false) {
        return ['success' => false, 'empty' => false, 'message' => 'Nie udało się zapisać ikony SVG.'];
    }
    @chmod($staging, 0644);
    if (!rename($staging, $target)) {
        @unlink($staging);
        return ['success' => false, 'empty' => false, 'message' => 'Nie udało się zapisać ikony SVG.'];
    }
    return ['success' => true, 'empty' => false, 'value' => 'custom:' . $filename, 'message' => 'Ikona SVG została dodana.'];
}

// ============================================
// POMOCNICZE FUNKCJE PODGLĄDÓW I UDOSTĘPNIANIA
// ============================================

/**
 * Zwraca publiczny adres obrazu z katalogu uploadów albo null, gdy obraz nie istnieje.
 */
function getPublicUploadUrl($filename) {
    if (empty($filename)) {
        return null;
    }
    return SITE_URL . '/assets/uploads/' . rawurlencode($filename);
}

/**
 * Zwraca nazwę wariantu miniatury bez zmiany oryginalnego uploadu.
 */
function getUploadThumbnailFilename($filename, int $maxWidth): ?string {
    $basename = basename((string) $filename);
    $stem = pathinfo($basename, PATHINFO_FILENAME);
    if ($basename === '' || $stem === '' || $maxWidth <= 0) {
        return null;
    }
    $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
    if (function_exists('imageavif')) {
        $extension = 'avif';
    } elseif ($extension === 'jpeg') {
        $extension = 'jpg';
    }
    return $stem . '-w' . $maxWidth . '.' . $extension;
}

/**
 * Zwraca publiczny URL istniejącej miniatury albo null. Brak wariantu nigdy
 * nie generuje adresu 404 — widok korzysta wtedy z istniejącego oryginału.
 */
function getPublicUploadThumbnailUrl($filename, int $maxWidth): ?string {
    $thumbnail = getUploadThumbnailFilename($filename, $maxWidth);
    if ($thumbnail === null) {
        return null;
    }
    $relativePath = 'thumbs/' . $thumbnail;
    if (!is_file(UPLOAD_PATH . $relativePath)) {
        return null;
    }
    return SITE_URL . '/assets/uploads/' . rawurlencode('thumbs') . '/' . rawurlencode($thumbnail);
}

/**
 * Przygotowuje bezpieczne źródło i srcset dla obrazu w widokach listowych.
 */
function getResponsiveUploadImage($filename, array $widths = [480, 768]): array {
    $source = getPublicUploadUrl($filename);
    $sources = [];
    foreach ($widths as $width) {
        $width = (int) $width;
        $thumbnail = getPublicUploadThumbnailUrl($filename, $width);
        if ($thumbnail !== null) {
            $sources[$width] = $thumbnail;
        }
    }
    ksort($sources, SORT_NUMERIC);

    return [
        'src' => $source,
        'srcset' => implode(', ', array_map(
            static fn(string $url, int $width): string => $url . ' ' . $width . 'w',
            $sources,
            array_keys($sources)
        )),
    ];
}

/**
 * Zwraca atrybut aria-current="page", gdy podany adres jest stroną, na której
 * użytkownik aktualnie jest (WCAG 2.4.8 — lokalizacja w obrębie zestawu stron).
 */
function currentPageAttribute($href) {
    $targetPath = parse_url((string) $href, PHP_URL_PATH);
    if (!$targetPath) {
        return '';
    }
    $targetQuery = parse_url((string) $href, PHP_URL_QUERY);
    $target = $targetPath . ($targetQuery !== null && $targetQuery !== '' ? '?' . $targetQuery : '');

    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $currentPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    $currentQuery = parse_url($requestUri, PHP_URL_QUERY);
    $current = $currentPath . ($currentQuery !== null && $currentQuery !== '' ? '?' . $currentQuery : '');

    return $target === $current ? ' aria-current="page"' : '';
}

function getChroniclePostUrl($slug) {
    return SITE_URL . '/kronika/' . rawurlencode((string) $slug);
}

function getOpenGraphImageUrl($filename) {
    if (empty($filename)) {
        return null;
    }

    $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
    if ($extension === 'avif') {
        $shareFilename = pathinfo((string) $filename, PATHINFO_FILENAME) . '-share.jpg';
        if (is_file(UPLOAD_PATH . $shareFilename)) {
            return getPublicUploadUrl($shareFilename);
        }
    }

    return getPublicUploadUrl($filename);
}

/**
 * Normalizuje treść do pojedynczego, czytelnego opisu meta i udostępniania.
 */
function createMetaDescription($text, $limit = 160) {
    $text = trim(preg_replace('/\\s+/u', ' ', strip_tags((string) $text)));
    $length = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    if ($length <= $limit) {
        return $text;
    }
    $short = function_exists('mb_substr') ? mb_substr($text, 0, $limit - 1) : substr($text, 0, $limit - 1);
    return rtrim($short) . '…';
}

// ============================================
// LICZNIK CAŁKOWITEJ LICZBY ODSŁON
// ============================================

/**
 * Zapewnia istnienie tabeli z pojedynczym, trwałym licznikiem odsłon.
 */
function ensureSiteVisitCounterSchema() {
    static $checked = null;
    if ($checked !== null) return $checked;
    global $pdo;
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'site_visit_stats'");
        $stmt->execute();
        $checked = (bool) $stmt->fetchColumn();
    } catch (PDOException $e) { $checked = false; }
    return $checked;
}

/**
 * Zwiększa całkowitą liczbę odsłon o jedną i zwraca aktualny stan licznika.
 * Inicjalizacja tabeli następuje wyłącznie awaryjnie, gdy tabela nie została jeszcze utworzona.
 */
function recordAndGetSiteVisitCount() {
    global $pdo;

    try {
        $pdo->exec("UPDATE site_visit_stats SET total_visits = LAST_INSERT_ID(total_visits + 1) WHERE id = 1");
        return (int) $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
    } catch (PDOException $e) {
        if (!ensureSiteVisitCounterSchema()) {
            return 0;
        }

        try {
            $pdo->exec("UPDATE site_visit_stats SET total_visits = LAST_INSERT_ID(total_visits + 1) WHERE id = 1");
            return (int) $pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
        } catch (PDOException $retryError) {
            return 0;
        }
    }
}

// ============================================
// FUNKCJE DLA OGŁOSZEŃ
// ============================================

/**
 * Dodaje obsługę moderacji zgłoszeń w istniejącej bazie bez naruszania opublikowanych ogłoszeń.
 */
function ensureAdModerationSchema() {
    static $checked = null;
    if ($checked !== null) return $checked;
    global $pdo;
    try { $checked = in_array('submission_status', $pdo->query("SHOW COLUMNS FROM ads")->fetchAll(PDO::FETCH_COLUMN), true); }
    catch (PDOException $e) { $checked = false; }
    return $checked;
}

/**
 * Dodaje opcjonalne dane kontaktowe i precyzyjny adres do istniejących ogłoszeń.
 */
function ensureAdContactSchema() {
    static $checked = null;
    if ($checked !== null) return $checked;
    global $pdo;
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM ads")->fetchAll(PDO::FETCH_COLUMN);
        $checked = count(array_diff(['phone', 'email', 'address'], $columns)) === 0;
    } catch (PDOException $e) { $checked = false; }
    return $checked;
}

/**
 * Dodaje pola nowego profilu ogłoszenia bez ingerencji w dotychczasowe wpisy.
 */
function ensureAdProfileSchema() {
    static $checked = null;
    if ($checked !== null) return $checked;
    global $pdo;
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM ads")->fetchAll(PDO::FETCH_COLUMN);
        $checked = count(array_diff(['detail_layout', 'profile_subtitle', 'profile_tags', 'contact_url', 'is_city_pride'], $columns)) === 0;
    } catch (PDOException $e) { $checked = false; }
    return $checked;
}

/**
 * Normalizuje krótkie, unikatowe tagi profilu ogłoszenia.
 */
function normalizeAdProfileTags($value) {
    $rawTags = is_array($value) ? $value : preg_split('/[,;\\n]+/u', (string) $value);
    $tags = [];
    $seen = [];
    foreach ($rawTags as $rawTag) {
        $tag = preg_replace('/\\s+/u', ' ', trim(strip_tags((string) $rawTag)));
        if ($tag === '') {
            continue;
        }
        $tag = function_exists('mb_substr') ? mb_substr($tag, 0, 40) : substr($tag, 0, 40);
        $key = function_exists('mb_strtolower') ? mb_strtolower($tag) : strtolower($tag);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $tags[] = $tag;
        if (count($tags) === 8) {
            break;
        }
    }
    return $tags;
}

/**
 * Odczytuje zapisane tagi profilu w bezpiecznej postaci tablicy.
 */
function getAdProfileTags($ad) {
    $decoded = json_decode((string) ($ad['profile_tags'] ?? ''), true);
    return is_array($decoded) ? normalizeAdProfileTags($decoded) : [];
}

/**
 * Dopuszcza wyłącznie bezpieczne kanały kontaktu dla przycisku profilu.
 */
function normalizeAdContactUrl($value) {
    $url = trim((string) $value);
    if ($url === '') {
        return '';
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (in_array($scheme, ['http', 'https'], true)) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
    }
    if ($scheme === 'tel') {
        $number = substr($url, 4);
        return preg_match('/^[0-9+().\\s-]{6,40}$/', $number) ? 'tel:' . preg_replace('/[^0-9+]/', '', $number) : '';
    }
    if ($scheme === 'mailto') {
        $email = substr($url, 7);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? 'mailto:' . $email : '';
    }
    return '';
}

/**
 * Zwraca docelowy, działający kontakt nowego profilu.
 */
function getAdProfileContactUrl($ad) {
    $contactUrl = normalizeAdContactUrl($ad['contact_url'] ?? '');
    if ($contactUrl !== '') {
        return $contactUrl;
    }
    $phone = preg_replace('/[^0-9+]/', '', (string) ($ad['phone'] ?? ''));
    if ($phone !== '') {
        return 'tel:' . $phone;
    }
    if (!empty($ad['email']) && filter_var($ad['email'], FILTER_VALIDATE_EMAIL)) {
        return 'mailto:' . $ad['email'];
    }
    return '';
}

/**
 * Liczy zdjęcie główne i wszystkie zdjęcia galerii ogłoszenia.
 */
function getAdImageCount($adId, $mainImage = null) {
    global $pdo;
    if ($mainImage === null) {
        $stmt = $pdo->prepare("SELECT image FROM ads WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $adId]);
        $mainImage = $stmt->fetchColumn();
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ad_gallery WHERE ad_id = ?");
    $stmt->execute([(int) $adId]);
    return (!empty($mainImage) ? 1 : 0) + (int) $stmt->fetchColumn();
}

function getSubmissionStatusLabel($status) {
    $labels = [
        'pending' => 'Oczekuje na akceptację',
        'approved' => 'Zaakceptowane',
        'rejected' => 'Odrzucone'
    ];
    return $labels[$status] ?? $labels['approved'];
}

/**
 * Normalizuje podpis do porównań nazw zastrzeżonych, bez zmiany tekstu wyświetlanego użytkownikowi.
 */
function normalizeOfficialLabel(string $value): string {
    $normalized = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
    return function_exists('mb_strtolower') ? mb_strtolower($normalized, 'UTF-8') : strtolower($normalized);
}

function isReservedOfficialLabel(string $value): bool {
    return in_array(normalizeOfficialLabel($value), ['66600.pl', 'administracja', 'administrator'], true);
}

function isOfficialAdminSession(): bool {
    return function_exists('isAdminLoggedIn') && isAdminLoggedIn();
}

/**
 * Dodaje pola moderacji i podpisu autora do bloga bez naruszania obecnych wpisów.
 */
function ensureBlogModerationSchema() {
    static $checked = null;
    if ($checked !== null) return $checked;
    global $pdo;
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM blog_posts")->fetchAll(PDO::FETCH_COLUMN);
        $checked = count(array_diff(['author_signature', 'author_source', 'submission_status'], $columns)) === 0;
    } catch (PDOException $e) { $checked = false; }
    return $checked;
}

/**
 * Zwraca publiczne kategorie, do których mieszkańcy mogą przesyłać wpisy.
 */
function ensureChronicleInteractionSchema(): bool
{
    static $checked = null;
    if ($checked !== null) return $checked;
    global $pdo;
    try {
        $required = ['chronicle_post_votes', 'chronicle_comments', 'chronicle_comment_likes'];
        $checked = true;
        foreach ($required as $table) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetchColumn()) { $checked = false; break; }
        }
    } catch (PDOException $e) { $checked = false; }
    return $checked;
}

function getChronicleVoterHash(): string
{
    $token = $_COOKIE['66_600_chronicle_voter'] ?? '';
    if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        $token = bin2hex(random_bytes(32));
        setcookie('66_600_chronicle_voter', $token, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        $_COOKIE['66_600_chronicle_voter'] = $token;
    }

    return hash_hmac('sha256', $token, DB_PASS);
}

function getChroniclePostInteractionSummary(int $postId): array
{
    global $pdo;
    if (!ensureChronicleInteractionSchema()) {
        return ['positive_votes' => 0, 'negative_votes' => 0, 'comments_count' => 0];
    }

    $votesStatement = $pdo->prepare('SELECT
        COALESCE(SUM(vote = 1), 0) AS positive_votes,
        COALESCE(SUM(vote = -1), 0) AS negative_votes
        FROM chronicle_post_votes WHERE post_id = ?');
    $votesStatement->execute([$postId]);
    $votes = $votesStatement->fetch() ?: [];

    $commentsStatement = $pdo->prepare('SELECT COUNT(*) FROM chronicle_comments WHERE post_id = ?');
    $commentsStatement->execute([$postId]);

    return [
        'positive_votes' => (int) ($votes['positive_votes'] ?? 0),
        'negative_votes' => (int) ($votes['negative_votes'] ?? 0),
        'comments_count' => (int) $commentsStatement->fetchColumn()
    ];
}

function attachChronicleInteractionSummaries(array $posts): array
{
    if ($posts === [] || !ensureChronicleInteractionSchema()) {
        foreach ($posts as &$post) {
            $post['positive_votes'] = 0;
            $post['negative_votes'] = 0;
            $post['comments_count'] = 0;
        }
        unset($post);
        return $posts;
    }

    global $pdo;
    $postIds = array_values(array_unique(array_filter(array_map(static fn(array $post): int => (int) ($post['id'] ?? 0), $posts))));
    if ($postIds === []) {
        return $posts;
    }

    $placeholders = implode(',', array_fill(0, count($postIds), '?'));
    $summaries = array_fill_keys($postIds, ['positive_votes' => 0, 'negative_votes' => 0, 'comments_count' => 0]);

    $votesStatement = $pdo->prepare("SELECT post_id, COALESCE(SUM(vote = 1), 0) AS positive_votes, COALESCE(SUM(vote = -1), 0) AS negative_votes
        FROM chronicle_post_votes WHERE post_id IN ({$placeholders}) GROUP BY post_id");
    $votesStatement->execute($postIds);
    foreach ($votesStatement->fetchAll() as $row) {
        $summaries[(int) $row['post_id']]['positive_votes'] = (int) $row['positive_votes'];
        $summaries[(int) $row['post_id']]['negative_votes'] = (int) $row['negative_votes'];
    }

    $commentsStatement = $pdo->prepare("SELECT post_id, COUNT(*) AS comments_count
        FROM chronicle_comments WHERE post_id IN ({$placeholders}) GROUP BY post_id");
    $commentsStatement->execute($postIds);
    foreach ($commentsStatement->fetchAll() as $row) {
        $summaries[(int) $row['post_id']]['comments_count'] = (int) $row['comments_count'];
    }

    foreach ($posts as &$post) {
        $summary = $summaries[(int) $post['id']] ?? ['positive_votes' => 0, 'negative_votes' => 0, 'comments_count' => 0];
        $post = array_merge($post, $summary);
    }
    unset($post);

    return $posts;
}

function getChroniclePostInteractionState(int $postId): array
{
    global $pdo;
    $summary = getChroniclePostInteractionSummary($postId);
    $viewerVote = null;
    if (ensureChronicleInteractionSchema()) {
        $statement = $pdo->prepare('SELECT vote FROM chronicle_post_votes WHERE post_id = ? AND voter_hash = ? LIMIT 1');
        $statement->execute([$postId, getChronicleVoterHash()]);
        $vote = $statement->fetchColumn();
        $viewerVote = $vote === false ? null : (int) $vote;
    }

    return array_merge($summary, ['viewer_vote' => $viewerVote]);
}

function normalizeChronicleCommentAuthor($value): string
{
    $author = preg_replace('/\s+/u', ' ', trim(strip_tags((string) $value)));
    $length = function_exists('mb_strlen') ? mb_strlen($author, 'UTF-8') : strlen($author);
    if ($length < 2 || $length > 80) {
        throw new InvalidArgumentException('Podpis autora musi mieć od 2 do 80 znaków.');
    }
    return $author;
}

function normalizeChronicleCommentContent($value): string
{
    $content = trim((string) $value);
    $content = preg_replace('/\r\n?|\n/u', "\n", $content);
    $content = preg_replace('/[\t ]*\n[\t ]*/u', "\n", $content);
    $content = trim(strip_tags($content));
    $length = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
    if ($length < 1 || $length > 1500) {
        throw new InvalidArgumentException('Komentarz musi mieć od 1 do 1500 znaków.');
    }
    return $content;
}

function getChronicleCommentForViewer(int $commentId): ?array
{
    global $pdo;
    if (!ensureChronicleInteractionSchema()) {
        return null;
    }

    $statement = $pdo->prepare('SELECT c.id, c.post_id, c.author_name, c.content, c.created_at,
        COUNT(cl.id) AS likes_count,
        EXISTS(SELECT 1 FROM chronicle_comment_likes own_like WHERE own_like.comment_id = c.id AND own_like.voter_hash = ?) AS viewer_has_liked
        FROM chronicle_comments c
        LEFT JOIN chronicle_comment_likes cl ON cl.comment_id = c.id
        WHERE c.id = ?
        GROUP BY c.id, c.post_id, c.author_name, c.content, c.created_at
        LIMIT 1');
    $statement->execute([getChronicleVoterHash(), $commentId]);
    $comment = $statement->fetch();
    if (!$comment) {
        return null;
    }

    $comment['id'] = (int) $comment['id'];
    $comment['post_id'] = (int) $comment['post_id'];
    $comment['likes_count'] = (int) $comment['likes_count'];
    $comment['viewer_has_liked'] = (bool) $comment['viewer_has_liked'];
    $comment['created_label'] = formatDate($comment['created_at'], 'd.m.Y H:i');
    return $comment;
}

function getChronicleComments(int $postId): array
{
    global $pdo;
    if (!ensureChronicleInteractionSchema()) {
        return [];
    }

    $statement = $pdo->prepare('SELECT c.id, c.post_id, c.author_name, c.content, c.created_at,
        COUNT(cl.id) AS likes_count,
        EXISTS(SELECT 1 FROM chronicle_comment_likes own_like WHERE own_like.comment_id = c.id AND own_like.voter_hash = ?) AS viewer_has_liked
        FROM chronicle_comments c
        LEFT JOIN chronicle_comment_likes cl ON cl.comment_id = c.id
        WHERE c.post_id = ?
        GROUP BY c.id, c.post_id, c.author_name, c.content, c.created_at
        ORDER BY c.created_at ASC, c.id ASC');
    $statement->execute([getChronicleVoterHash(), $postId]);
    $comments = $statement->fetchAll();
    foreach ($comments as &$comment) {
        $comment['id'] = (int) $comment['id'];
        $comment['post_id'] = (int) $comment['post_id'];
        $comment['likes_count'] = (int) $comment['likes_count'];
        $comment['viewer_has_liked'] = (bool) $comment['viewer_has_liked'];
        $comment['created_label'] = formatDate($comment['created_at'], 'd.m.Y H:i');
    }
    unset($comment);
    return $comments;
}

function submitChroniclePostVote(int $postId, int $vote): array
{
    global $pdo;
    if (!in_array($vote, [-1, 1], true)) {
        throw new InvalidArgumentException('Wybierz prawidłową ocenę wpisu.');
    }
    if (!ensureChronicleInteractionSchema()) {
        throw new RuntimeException('Nie udało się przygotować ocen Kroniki.');
    }

    $postStatement = $pdo->prepare("SELECT id FROM blog_posts WHERE id = ? AND is_active = 1 AND submission_status = 'approved' LIMIT 1");
    $postStatement->execute([$postId]);
    if (!$postStatement->fetch()) {
        throw new RuntimeException('Ten wpis nie jest dostępny do oceny.');
    }

    $voterHash = getChronicleVoterHash();
    $insert = $pdo->prepare('INSERT IGNORE INTO chronicle_post_votes (post_id, voter_hash, vote) VALUES (?, ?, ?)');
    $insert->execute([$postId, $voterHash, $vote]);
    $recorded = $insert->rowCount() === 1;

    $existingStatement = $pdo->prepare('SELECT vote FROM chronicle_post_votes WHERE post_id = ? AND voter_hash = ? LIMIT 1');
    $existingStatement->execute([$postId, $voterHash]);
    $selectedVote = (int) $existingStatement->fetchColumn();

    return array_merge(getChroniclePostInteractionSummary($postId), [
        'ok' => true,
        'recorded' => $recorded,
        'viewer_vote' => $selectedVote
    ]);
}

function submitChronicleComment(int $postId, $authorName, $content): array
{
    global $pdo;
    if (!ensureChronicleInteractionSchema()) {
        throw new RuntimeException('Nie udało się przygotować komentarzy Kroniki.');
    }

    $postStatement = $pdo->prepare("SELECT id FROM blog_posts WHERE id = ? AND is_active = 1 AND submission_status = 'approved' LIMIT 1");
    $postStatement->execute([$postId]);
    if (!$postStatement->fetch()) {
        throw new RuntimeException('Ten wpis nie jest dostępny do komentowania.');
    }

    $authorName = normalizeChronicleCommentAuthor($authorName);
    $content = normalizeChronicleCommentContent($content);
    $voterHash = getChronicleVoterHash();
    $rateStatement = $pdo->prepare('SELECT COUNT(*) FROM chronicle_comments WHERE commenter_hash = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)');
    $rateStatement->execute([$voterHash]);
    if ((int) $rateStatement->fetchColumn() >= 3) {
        throw new RuntimeException('Odczekaj chwilę przed dodaniem kolejnego komentarza.');
    }

    $insert = $pdo->prepare('INSERT INTO chronicle_comments (post_id, author_name, content, commenter_hash) VALUES (?, ?, ?, ?)');
    $insert->execute([$postId, $authorName, $content, $voterHash]);
    $comment = getChronicleCommentForViewer((int) $pdo->lastInsertId());
    if ($comment === null) {
        throw new RuntimeException('Nie udało się odczytać zapisanego komentarza.');
    }

    return [
        'ok' => true,
        'comment' => $comment,
        'comments_count' => getChroniclePostInteractionSummary($postId)['comments_count']
    ];
}

function submitChronicleCommentLike(int $commentId): array
{
    global $pdo;
    if (!ensureChronicleInteractionSchema()) {
        throw new RuntimeException('Nie udało się przygotować polubień komentarzy.');
    }

    $commentStatement = $pdo->prepare("SELECT c.id FROM chronicle_comments c
        JOIN blog_posts bp ON bp.id = c.post_id
        WHERE c.id = ? AND bp.is_active = 1 AND bp.submission_status = 'approved' LIMIT 1");
    $commentStatement->execute([$commentId]);
    if (!$commentStatement->fetch()) {
        throw new RuntimeException('Ten komentarz nie jest już dostępny.');
    }

    $insert = $pdo->prepare('INSERT IGNORE INTO chronicle_comment_likes (comment_id, voter_hash) VALUES (?, ?)');
    $insert->execute([$commentId, getChronicleVoterHash()]);
    $comment = getChronicleCommentForViewer($commentId);
    if ($comment === null) {
        throw new RuntimeException('Nie udało się odczytać polubienia komentarza.');
    }

    return ['ok' => true, 'recorded' => $insert->rowCount() === 1, 'comment' => $comment];
}

function getAdminChronicleComments(): array
{
    global $pdo;
    if (!ensureChronicleInteractionSchema()) {
        return [];
    }

    $statement = $pdo->query('SELECT c.*, bp.title AS post_title, bp.slug AS post_slug, COUNT(cl.id) AS likes_count
        FROM chronicle_comments c
        JOIN blog_posts bp ON bp.id = c.post_id
        LEFT JOIN chronicle_comment_likes cl ON cl.comment_id = c.id
        GROUP BY c.id, c.post_id, c.author_name, c.content, c.commenter_hash, c.created_at, bp.title, bp.slug
        ORDER BY c.created_at DESC, c.id DESC
        LIMIT 300');
    return $statement->fetchAll();
}

function deleteChronicleComment(int $commentId): bool
{
    global $pdo;
    if (!ensureChronicleInteractionSchema()) {
        return false;
    }

    $statement = $pdo->prepare('DELETE FROM chronicle_comments WHERE id = ?');
    $statement->execute([$commentId]);
    return $statement->rowCount() === 1;
}

function getCommunityBlogCategories() {
    global $pdo;
    $slugs = ['na-biezaco', 'zwracamy-uwage', 'rekreacja', 'inicjatywy', 'ciekawostki', 'historia-miasta'];
    $placeholders = implode(',', array_fill(0, count($slugs), '?'));
    $stmt = $pdo->prepare("SELECT * FROM blog_categories WHERE is_active = 1 AND slug IN ($placeholders) ORDER BY display_order ASC");
    $stmt->execute($slugs);
    return $stmt->fetchAll();
}

/**
 * Tworzy niepowtarzalny, techniczny slug bez wpływu na podpis autora.
 */
function generateUniqueBlogSlug($title) {
    global $pdo;
    $base = generateSlug($title);
    if ($base === '') {
        $base = 'wpis-mieszkanca';
    }

    $candidate = $base;
    $number = 2;
    $exists = $pdo->prepare("SELECT COUNT(*) FROM blog_posts WHERE slug = ?");
    while (true) {
        $exists->execute([$candidate]);
        if ((int) $exists->fetchColumn() === 0) {
            return $candidate;
        }
        $suffix = '-' . $number;
        $candidate = substr($base, 0, 255 - strlen($suffix)) . $suffix;
        $number++;
    }
}

/**
 * Zapisuje zgłoszenie mieszkańca jako nieaktywne i oczekujące na akceptację.
 */
function createCommunityBlogSubmission($data) {
    global $pdo;
    ensureBlogModerationSchema();

    $paragraphs = preg_split('/(?:\r\n|\r|\n){2,}/u', trim((string) ($data['content'] ?? '')));
    $contentParts = [];
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim((string) $paragraph);
        if ($paragraph === '') {
            continue;
        }
        $contentParts[] = '<p>' . nl2br(sanitize($paragraph), false) . '</p>';
    }
    $content = implode("\n", $contentParts);
    if ($content === '') {
        return 0;
    }
    $excerpt = createMetaDescription($data['content']);
    $slug = generateUniqueBlogSlug($data['title']);
    $stmt = $pdo->prepare("INSERT INTO blog_posts
        (owner_id, title, content, excerpt, image, category_id, slug, author_signature, author_source, is_featured, is_active, submission_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'pending')");
    $saved = $stmt->execute([
        !empty($data['owner_id']) ? (int) $data['owner_id'] : null,
        $data['title'],
        $content,
        $excerpt,
        $data['image'] ?? null,
        $data['category_id'],
        $slug,
        $data['author_signature'] ?? null,
        $data['author_source'] ?? 'community'
    ]);

    return $saved ? (int) $pdo->lastInsertId() : 0;
}

/**
 * Pobierz wszystkie aktywne ogłoszenia
 */
function getAllAds($limit = null) {
    global $pdo;
    ensureAdModerationSchema();
    ensureAdContactSchema();
    $sql = "SELECT a.*, ac.name as category_name, ac.slug as category_slug, ac.icon as category_icon 
            FROM ads a 
            JOIN ad_categories ac ON a.category_id = ac.id 
            WHERE a.is_active = 1 AND a.submission_status = 'approved'
            ORDER BY a.is_featured DESC, a.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

/**
 * Pobierz ogłoszenia z danej kategorii
 */
function getAdsByCategory($categorySlug, $limit = null) {
    global $pdo;
    $categorySlug = strtolower(trim((string) $categorySlug));
    ensureAdModerationSchema();
    ensureAdContactSchema();
    ensureAdCategoryHierarchySchema();
    $sql = "SELECT a.*, ac.name as category_name, ac.slug as category_slug, ac.icon as category_icon
            FROM ads a
            JOIN ad_categories ac ON a.category_id = ac.id
            LEFT JOIN ad_categories parent_category ON ac.parent_id = parent_category.id
            WHERE (ac.slug = ? OR parent_category.slug = ?) AND a.is_active = 1 AND a.submission_status = 'approved'
            ORDER BY a.is_featured DESC, a.created_at DESC";

    if ($limit) {
        $sql .= " LIMIT $limit";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categorySlug, $categorySlug]);
    return $stmt->fetchAll();
}

/**
 * Pobierz pojedyncze ogłoszenie
 */
function getAdById($id, $includeUnpublished = false) {
    global $pdo;
    ensureAdModerationSchema();
    ensureAdContactSchema();
    ensureAdProfileSchema();
    $sql = "SELECT a.*, ac.name as category_name, ac.slug as category_slug 
            FROM ads a 
            JOIN ad_categories ac ON a.category_id = ac.id 
            WHERE a.id = ?";
    if (!$includeUnpublished) {
        $sql .= " AND a.is_active = 1 AND a.submission_status = 'approved'";
    }
    $sql .= " LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Dodaj nowe ogłoszenie
 */
function addAd($data) {
    global $pdo;
    ensureAdContactSchema();
    ensureAdProfileSchema();
    $stmt = $pdo->prepare("INSERT INTO ads 
            (title, description, image, category_id, location, phone, email, address, rating, link, detail_layout, profile_subtitle, profile_tags, contact_url, is_city_pride, is_featured, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['title'],
        $data['description'],
        $data['image'] ?? null,
        $data['category_id'],
        $data['location'] ?? null,
        $data['phone'] ?? null,
        $data['email'] ?? null,
        $data['address'] ?? null,
        $data['rating'] ?? 4.5,
        $data['link'] ?? null,
        $data['detail_layout'] ?? 'profile',
        $data['profile_subtitle'] ?? null,
        json_encode(normalizeAdProfileTags($data['profile_tags'] ?? []), JSON_UNESCAPED_UNICODE),
        normalizeAdContactUrl($data['contact_url'] ?? ''),
        $data['is_city_pride'] ?? 0,
        $data['is_featured'] ?? 0,
        $data['is_active'] ?? 1
    ]);
}

/**
 * Edytuj ogłoszenie
 */
function updateAd($id, $data) {
    global $pdo;
    
    ensureAdContactSchema();
    ensureAdProfileSchema();
    $stmt = $pdo->prepare("UPDATE ads SET 
            title = ?,
            description = ?,
            image = ?,
            category_id = ?,
            location = ?,
            phone = ?,
            email = ?,
            address = ?,
            rating = ?,
            link = ?,
            detail_layout = ?,
            profile_subtitle = ?,
            profile_tags = ?,
            contact_url = ?,
            is_city_pride = ?,
            is_featured = ?,
            is_active = ?
            WHERE id = ?");
    
    return $stmt->execute([
        $data['title'],
        $data['description'],
        $data['image'] ?? null,
        $data['category_id'],
        $data['location'] ?? null,
        $data['phone'] ?? null,
        $data['email'] ?? null,
        $data['address'] ?? null,
        $data['rating'] ?? 4.5,
        $data['link'] ?? null,
        $data['detail_layout'] ?? 'legacy',
        $data['profile_subtitle'] ?? null,
        json_encode(normalizeAdProfileTags($data['profile_tags'] ?? []), JSON_UNESCAPED_UNICODE),
        normalizeAdContactUrl($data['contact_url'] ?? ''),
        $data['is_city_pride'] ?? 0,
        $data['is_featured'] ?? 0,
        $data['is_active'] ?? 1,
        $id
    ]);
}

/**
 * Usuń ogłoszenie
 */
function deleteAd($id) {
    global $pdo;
    
    // Pobierz zdjęcia z galerii przed usunięciem
    $gallery = getAdGallery($id);
    foreach ($gallery as $img) {
        deleteFile($img['image']);
    }
    
    // Pobierz główne zdjęcie
    $stmt = $pdo->prepare("SELECT image FROM ads WHERE id = ?");
    $stmt->execute([$id]);
    $ad = $stmt->fetch();
    if ($ad && !empty($ad['image'])) {
        deleteFile($ad['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ?");
    return $stmt->execute([$id]);
}

// ============================================
// FUNKCJE DLA BLOGA
// ============================================

/**
 * Pobierz wszystkie aktywne artykuły blogowe
 */
function getAllBlogPosts($limit = null) {
    global $pdo;
    ensureBlogModerationSchema();
    $sql = "SELECT bp.*, bc.name as category_name, bc.slug as category_slug 
            FROM blog_posts bp 
            JOIN blog_categories bc ON bp.category_id = bc.id 
            WHERE bp.is_active = 1 AND bp.submission_status = 'approved' 
            ORDER BY bp.is_featured DESC, bp.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    
    $stmt = $pdo->query($sql);
    return attachChronicleInteractionSummaries($stmt->fetchAll());
}

/**
 * Pobierz artykuły z danej kategorii bloga
 */
function getBlogPostsByCategory($categorySlug, $limit = null) {
    global $pdo;
    ensureBlogModerationSchema();
    $sql = "SELECT bp.*, bc.name as category_name, bc.slug as category_slug 
            FROM blog_posts bp 
            JOIN blog_categories bc ON bp.category_id = bc.id 
            WHERE bc.slug = ? AND bp.is_active = 1 AND bp.submission_status = 'approved' 
            ORDER BY bp.is_featured DESC, bp.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categorySlug]);
    return attachChronicleInteractionSummaries($stmt->fetchAll());
}

/**
 * Pobierz pojedynczy artykuł blogowy
 */
function getBlogPostBySlug($slug) {
    global $pdo;
    ensureBlogModerationSchema();
    $stmt = $pdo->prepare("SELECT bp.*, bc.name as category_name, bc.slug as category_slug 
            FROM blog_posts bp 
            JOIN blog_categories bc ON bp.category_id = bc.id 
            WHERE bp.slug = ? AND bp.is_active = 1 AND bp.submission_status = 'approved' LIMIT 1");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    if (!$post) {
        return false;
    }
    $posts = attachChronicleInteractionSummaries([$post]);
    return $posts[0] ?? false;
}

/**
 * Pobierz artykuł blogowy po ID
 */
function getBlogPostById($id, $includeUnpublished = false) {
    global $pdo;
    ensureBlogModerationSchema();
    $sql = "SELECT bp.*, bc.name as category_name, bc.slug as category_slug 
            FROM blog_posts bp 
            JOIN blog_categories bc ON bp.category_id = bc.id 
            WHERE bp.id = ?";
    if (!$includeUnpublished) {
        $sql .= " AND bp.is_active = 1 AND bp.submission_status = 'approved'";
    }
    $sql .= " LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Dodaj nowy artykuł blogowy
 */
function addBlogPost($data) {
    global $pdo;
    
    $slug = generateSlug($data['title']);
    
    $stmt = $pdo->prepare("INSERT INTO blog_posts 
            (title, content, excerpt, image, category_id, slug, is_featured, is_active, meta_title, meta_description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    return $stmt->execute([
        $data['title'],
        $data['content'],
        $data['excerpt'] ?? null,
        $data['image'] ?? null,
        $data['category_id'],
        $slug,
        $data['is_featured'] ?? 0,
        $data['is_active'] ?? 1,
        $data['meta_title'] ?? null,
        $data['meta_description'] ?? null
    ]);
}

/**
 * Edytuj artykuł blogowy
 */
function updateBlogPost($id, $data) {
    global $pdo;
    
    $slug = !empty($data['slug']) ? $data['slug'] : generateSlug($data['title']);
    
    $stmt = $pdo->prepare("UPDATE blog_posts SET 
            title = ?,
            content = ?,
            excerpt = ?,
            image = ?,
            category_id = ?,
            slug = ?,
            author_signature = ?,
            author_source = ?,
            is_featured = ?,
            is_active = ?,
            meta_title = ?,
            meta_description = ?
            WHERE id = ?");
    
    return $stmt->execute([
        $data['title'],
        $data['content'],
        $data['excerpt'] ?? null,
        $data['image'] ?? null,
        $data['category_id'],
        $slug,
        $data['author_signature'] ?? 'Administracja',
        $data['author_source'] ?? 'official',
        $data['is_featured'] ?? 0,
        $data['is_active'] ?? 1,
        $data['meta_title'] ?? null,
        $data['meta_description'] ?? null,
        $id
    ]);
}

/**
 * Usuń artykuł blogowy
 */
function deleteBlogPost($id) {
    global $pdo;
    
    // Pobierz zdjęcia z galerii przed usunięciem
    $gallery = getBlogPostGallery($id);
    foreach ($gallery as $img) {
        deleteFile($img['image']);
    }
    
    // Pobierz główne zdjęcie
    $stmt = $pdo->prepare("SELECT image FROM blog_posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if ($post && !empty($post['image'])) {
        deleteFile($post['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = ?");
    return $stmt->execute([$id]);
}

// ============================================
// FUNKCJE POMOCNICZE
// ============================================

/**
 * Generuj slug z tekstu
 */
function generateSlug($text) {
    // Usunięcie polskich znaków
    $text = strtolower($text);
    $polishChars = ['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż', 'ł', ' ', '-', '_'];
    $replaceChars = ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z', 'l', '-', '-', '-'];
    $text = str_replace($polishChars, $replaceChars, $text);
    
    // Usunięcie innych niepożądanych znaków
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    
    // Usunięcie podwójnych myślników
    $text = preg_replace('/-+/', '-', $text);
    
    // Usunięcie myślnika na początku i końcu
    $text = trim($text, '-');
    
    return $text;
}

/**
 * Skróć tekst
 */
function truncateText($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Formatuj datę
 */
function formatDate($date, $format = 'd.m.Y') {
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

function formatPolishChronicleDate($date) {
    $dateObj = new DateTime($date, new DateTimeZone('Europe/Warsaw'));
    $days = ['PONIEDZIAŁEK', 'WTOREK', 'ŚRODA', 'CZWARTEK', 'PIĄTEK', 'SOBOTA', 'NIEDZIELA'];
    $months = [1 => 'STYCZNIA', 2 => 'LUTEGO', 3 => 'MARCA', 4 => 'KWIETNIA', 5 => 'MAJA', 6 => 'CZERWCA', 7 => 'LIPCA', 8 => 'SIERPNIA', 9 => 'WRZEŚNIA', 10 => 'PAŹDZIERNIKA', 11 => 'LISTOPADA', 12 => 'GRUDNIA'];

    return $days[(int) $dateObj->format('N') - 1] . ', ' . $dateObj->format('j') . ' ' . $months[(int) $dateObj->format('n')] . ' ' . $dateObj->format('Y');
}

/**
 * Pobierz rozmiar pliku w czytelnej formie
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

/**
 * Sprawdź, czy plik jest obrazkiem
 */
function isImageFile($filename) {
    $allowedExtensions = ['avif', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowedExtensions);
}

/**
 * Próbuje zapisać obraz wejściowy jako AVIF. Zwraca false, gdy serwer nie
 * udostępnia kodeka AVIF w rozszerzeniu GD albo format nie ma loadera GD.
 */
/**
 * Generuje proporcjonalny wariant AVIF dla list i kart. Nie skaluje obrazu w górę.
 */
function generateUploadThumbnailVariant(string $sourcePath, string $filename, int $maxWidth, string $targetDir = UPLOAD_PATH): bool {
    $thumbnail = getUploadThumbnailFilename($filename, $maxWidth);
    if ($thumbnail === null || !is_file($sourcePath)) {
        return false;
    }

    $details = @getimagesize($sourcePath);
    $mimeType = $details['mime'] ?? null;
    $loaders = [
        'image/avif' => 'imagecreatefromavif',
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png' => 'imagecreatefrompng',
        'image/gif' => 'imagecreatefromgif',
        'image/webp' => 'imagecreatefromwebp',
    ];
    $loader = $loaders[$mimeType] ?? null;
    if ($loader === null || !function_exists($loader)) {
        return false;
    }

    $source = @$loader($sourcePath);
    if (!$source) {
        return false;
    }

    $sourceWidth = imagesx($source);
    $sourceHeight = imagesy($source);
    if ($sourceWidth <= 0 || $sourceHeight <= 0) {
        imagedestroy($source);
        return false;
    }

    $targetWidth = min($sourceWidth, $maxWidth);
    $targetHeight = max(1, (int) round($sourceHeight * $targetWidth / $sourceWidth));
    $target = imagecreatetruecolor($targetWidth, $targetHeight);
    if (!$target) {
        imagedestroy($source);
        return false;
    }
    imagealphablending($target, false);
    imagesavealpha($target, true);
    $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
    imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
    imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

    $directory = rtrim($targetDir, '/') . '/thumbs/';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        imagedestroy($target);
        imagedestroy($source);
        return false;
    }
    $targetPath = $directory . $thumbnail;
    $thumbnailExtension = strtolower(pathinfo($thumbnail, PATHINFO_EXTENSION));
    if ($thumbnailExtension === 'avif' && function_exists('imageavif')) {
        $saved = @imageavif($target, $targetPath, 50);
    } elseif ($thumbnailExtension === 'jpg' && function_exists('imagejpeg')) {
        $saved = @imagejpeg($target, $targetPath, 82);
    } elseif ($thumbnailExtension === 'png' && function_exists('imagepng')) {
        $saved = @imagepng($target, $targetPath, 6);
    } elseif ($thumbnailExtension === 'webp' && function_exists('imagewebp')) {
        $saved = @imagewebp($target, $targetPath, 82);
    } elseif ($thumbnailExtension === 'gif' && function_exists('imagegif')) {
        $saved = @imagegif($target, $targetPath);
    } else {
        $saved = false;
    }
    imagedestroy($target);
    imagedestroy($source);
    if ($saved && is_file($targetPath)) {
        @chmod($targetPath, 0644);
    }
    return $saved && is_file($targetPath) && filesize($targetPath) > 0;
}

/**
 * Próbuje utworzyć warianty dla nowych uploadów, bez blokowania udanego uploadu.
 */
function generateUploadThumbnailVariants(string $sourcePath, string $filename, string $targetDir = UPLOAD_PATH): void {
    $details = @getimagesize($sourcePath);
    $sourceWidth = (int) ($details[0] ?? 0);
    if ($sourceWidth <= 0) {
        return;
    }

    foreach ([480, 768] as $maxWidth) {
        if ($sourceWidth > $maxWidth) {
            generateUploadThumbnailVariant($sourcePath, $filename, $maxWidth, $targetDir);
        }
    }
}

function convertImageToAvif($sourcePath, $targetPath, $mimeType) {
    if (!function_exists('imageavif')) {
        return false;
    }

    $loaders = [
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png' => 'imagecreatefrompng',
        'image/gif' => 'imagecreatefromgif',
        'image/webp' => 'imagecreatefromwebp'
    ];
    $loader = $loaders[$mimeType] ?? null;
    if (!$loader || !function_exists($loader)) {
        return false;
    }

    $image = @$loader($sourcePath);
    if (!$image) {
        return false;
    }

    imagepalettetotruecolor($image);
    imagealphablending($image, true);
    imagesavealpha($image, true);
    $saved = @imageavif($image, $targetPath, 50);
    imagedestroy($image);

    return $saved && file_exists($targetPath) && filesize($targetPath) > 0;
}

/**
 * Przesyła obraz. Cienki wrapper nad ImageUpload — zachowuje dotychczasową
 * sygnaturę i format wyniku do czasu zakończenia testów regresji.
 * Nowe JPG, PNG, GIF i WebP są automatycznie zamieniane na AVIF, jeżeli
 * hosting ma GD z imageavif(); w przeciwnym razie upload działa nadal
 * w oryginalnym formacie, bez blokowania redakcji treści.
 *
 * @param array $file wpis z $_FILES
 * @return array wynik zgodny z poprzednią implementacją
 */
function uploadFile($file, $targetDir = UPLOAD_PATH) {
    if (!is_array($file)) {
        return ['success' => false, 'message' => 'Błąd podczas przesyłania pliku.'];
    }
    return ImageUpload::store($file, [
        'maxBytes' => MAX_FILE_SIZE,
        'targetDir' => $targetDir,
    ]);
}

/**
 * Usuń plik. Cienki wrapper nad ImageUpload — usuwa wyłącznie nazwy
 * bezpieczne (bez elementów ścieżki), razem z powiązanymi miniaturami.
 */
function deleteFile($filename, $targetDir = UPLOAD_PATH) {
    if (!is_string($filename) || $filename === '') {
        return false;
    }
    return ImageUpload::delete($filename, $targetDir);
}

/**
 * Pobierz ilość ogłoszeń w kategorii
 */
function getAdCountByCategory($categoryId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM ads WHERE category_id = ? AND is_active = 1");
    $stmt->execute([$categoryId]);
    $result = $stmt->fetch();
    return $result['count'];
}

/**
 * Pobierz ilość artykułów w kategorii bloga
 */
function getBlogPostCountByCategory($categoryId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM blog_posts WHERE category_id = ? AND is_active = 1");
    $stmt->execute([$categoryId]);
    $result = $stmt->fetch();
    return $result['count'];
}

/**
 * Zwraca ikonę SVG dla danej kategorii
 */
function getCategorySvg($slug) {
    $svgs = [
        // Ogłoszenia
        'taxi' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-4.28a2 2 0 0 0-.84-1.61l-2.93-2.11A2 2 0 0 0 17.07 9h-2.07"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>',
        'fachowcy' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 8-2 2-5-5 2-2a7 7 0 0 1 10 10Z"/><path d="m11 13 3 3-9 9-2-2Z"/><path d="m16 9 5 5"/></svg>',
        'beauty' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a15 15 0 0 0-4.5 9c0 5 3 8 4.5 11 1.5-3 4.5-6 4.5-11A15 15 0 0 0 12 2Z"/><path d="M12 13V7"/><path d="M12 22v-3"/><path d="M16 8l-2.5 2"/><path d="M8 8l2.5 2"/></svg>',
        'gastronomia' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h8"/><path d="M10 19v-3"/><path d="M14 19v-3"/><path d="M18 16v3"/><path d="M22 6v13"/><path d="M2 10h18"/><path d="M2 14h18"/><path d="M3 6v4"/><path d="M7 6v4"/><path d="M11 6v4"/><path d="M15 6v4"/><path d="M19 6v4"/></svg>',
        'rozrywka' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>',
        'handel' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 3h16l2 7H2z"/><path d="M4 10v10h16V10"/><path d="M9 20v-6h6v6"/></svg>',
        'kupie-sprzedam' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h13"/><path d="m17 3 4 4-4 4"/><path d="M17 17H4"/><path d="m7 13-4 4 4 4"/></svg>',
        'kupie' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v12H4z"/><path d="m9 11 3 3 3-3"/><path d="M12 7v7"/></svg>',
        'sprzedam' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16v12H4z"/><path d="m9 13 3-3 3 3"/><path d="M12 17v-7"/></svg>',
        'oddam-za-darmo' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8v13"/><path d="M3 12h18"/><path d="M12 8H7.5a2.5 2.5 0 1 1 2.5-2.5V8"/><path d="M12 8h4.5A2.5 2.5 0 1 0 14 5.5V8"/></svg>',
        'zagubione-znalezione' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="6"/><path d="m20 20-4.2-4.2"/><path d="M11 8v6"/><path d="M8 11h6"/></svg>',
        
        // Blog
        'na-biezaco' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        'zwracamy-uwage' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'rekreacja' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3v3a2 2 0 0 1-2 2H3"/><path d="M21 8h-3a2 2 0 0 1-2-2V3"/><path d="M3 16h3a2 2 0 0 1 2 2v3"/><path d="M16 21v-3a2 2 0 0 1 2-2h3"/></svg>',
        'inicjatywy' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>',
        'ciekawostki' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'historia-miasta' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'w-planach' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',

        // Inne
        'all' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>',
        'location' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
        'external' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
        'back' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>',
        'share' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>',
        'plus' => '<svg aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
        'email' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
        'phone' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.62a2 2 0 0 1-.45 2.11L8.01 9.72a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.84.29 1.72.5 2.62.62A2 2 0 0 1 22 16.92z"/></svg>'
    ];
    return $svgs[$slug] ?? $svgs['all'];
}

function getChronicleInteractionSvg(string $type): string
{
    $icons = [
        'up' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.5 10.5H5.75A1.75 1.75 0 0 0 4 12.25v1.5c0 .97.78 1.75 1.75 1.75H7.5m0-5v5m0-5V8.75c0-1.1.9-2 2-2h.7c.44 0 .84.26 1.02.66l.6 1.34c.4.88 1.05 1.6 1.86 2.07l1.2.7c.7.4 1.12 1.14 1.12 1.95v.28c0 1.24-1.01 2.25-2.25 2.25H7.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'down' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><g transform="rotate(180 12 12)"><path d="M7.5 10.5H5.75A1.75 1.75 0 0 0 4 12.25v1.5c0 .97.78 1.75 1.75 1.75H7.5m0-5v5m0-5V8.75c0-1.1.9-2 2-2h.7c.44 0 .84.26 1.02.66l.6 1.34c.4.88 1.05 1.6 1.86 2.07l1.2.7c.7.4 1.12 1.14 1.12 1.95v.28c0 1.24-1.01 2.25-2.25 2.25H7.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></g></svg>',
        'comment' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v7.25A2.25 2.25 0 0 1 18.75 17H10l-4.75 3v-3H5.25A2.25 2.25 0 0 1 3 14.75V7.5a2.25 2.25 0 0 1 2.25-2.25Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'like' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.5 10.5H5.75A1.75 1.75 0 0 0 4 12.25v1.5c0 .97.78 1.75 1.75 1.75H7.5m0-5v5m0-5V8.75c0-1.1.9-2 2-2h.7c.44 0 .84.26 1.02.66l.6 1.34c.4.88 1.05 1.6 1.86 2.07l1.2.7c.7.4 1.12 1.14 1.12 1.95v.28c0 1.24-1.01 2.25-2.25 2.25H7.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'
    ];

    return $icons[$type] ?? $icons['comment'];
}

/**
 * Pobierz losowe ogłoszenia
 */
function getRandomAds($limit = 6) {
    global $pdo;
    ensureAdModerationSchema();
    ensureAdContactSchema();
    $stmt = $pdo->query("SELECT a.*, ac.name as category_name, ac.slug as category_slug, ac.icon as category_icon 
            FROM ads a 
            JOIN ad_categories ac ON a.category_id = ac.id 
            WHERE a.is_active = 1 AND a.submission_status = 'approved'
            ORDER BY RAND() 
            LIMIT $limit");
    return $stmt->fetchAll();
}

/**
 * Pobierz najnowsze artykuły blogowe
 */
function getLatestBlogPosts($limit = 3) {
    global $pdo;
    $stmt = $pdo->query("SELECT bp.*, bc.name as category_name, bc.slug as category_slug 
            FROM blog_posts bp 
            JOIN blog_categories bc ON bp.category_id = bc.id 
            WHERE bp.is_active = 1 AND bp.submission_status = 'approved'
            ORDER BY bp.created_at DESC 
            LIMIT $limit");
    return attachChronicleInteractionSummaries($stmt->fetchAll());
}

/**
 * Wyloguj administratora
 */
function adminLogout() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, [
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_destroy();
    redirect(SITE_URL . '/admin/login.php');
}

/**
 * Sprawdź, czy użytkownik jest zalogowany (dla frontendu)
 */
function isLoggedIn() {
    session_start();
    return isset($_SESSION['user_id']);
}

/**
 * Pobierz informacje o zalogowanym użytkowniku
 */
function getCurrentUser() {
    session_start();
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

/**
 * Sprawdź, czy użytkownik ma uprawnienia admina
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

/**
 * Pobierz ustawienia strony
 */
function getSiteSettings() {
    return [
        'name' => SITE_NAME,
        'url' => SITE_URL,
        'description' => 'Lokalne ogłoszenia, usługi i informacje z Krosna Odrzańskiego. Poznaj aktualności oraz oferty dla mieszkańców miasta i okolic.'
    ];
}

/**
 * Zwraca URL do istniejącego obrazu w bieżącej bibliotece uploadów.
 */
function getImageUrl($filename) {
    if (!empty($filename) && file_exists(UPLOAD_PATH . $filename)) {
        return getPublicUploadUrl($filename);
    }
    return SITE_URL . '/assets/images/herb-most.avif';
}

/**
 * Pobierz galerię dla ogłoszenia
 */
function getAdGallery($adId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM ad_gallery WHERE ad_id = ? ORDER BY display_order ASC, created_at ASC, id ASC");
    $stmt->execute([$adId]);
    return $stmt->fetchAll();
}

/**
 * Dodaj zdjęcie do galerii ogłoszenia
 */
function addAdGalleryImage($adId, $image, $description = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO ad_gallery (ad_id, image, description) VALUES (?, ?, ?)");
    return $stmt->execute([$adId, $image, $description]);
}

/**
 * Usuń zdjęcie z galerii ogłoszenia
 */
function deleteAdGalleryImage($imageId) {
    global $pdo;
    
    // Pobierz nazwę pliku przed usunięciem
    $stmt = $pdo->prepare("SELECT image FROM ad_gallery WHERE id = ?");
    $stmt->execute([$imageId]);
    $img = $stmt->fetch();
    
    if ($img) {
        deleteFile($img['image']);
        $stmt = $pdo->prepare("DELETE FROM ad_gallery WHERE id = ?");
        return $stmt->execute([$imageId]);
    }
    return false;
}

/**
 * Pobierz galerię dla artykułu blogowego
 */
function getBlogPostGallery($postId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM blog_post_gallery WHERE post_id = ? ORDER BY display_order ASC, created_at ASC, id ASC");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

/**
 * Dodaj zdjęcie do galerii artykułu blogowego
 */
function addBlogPostGalleryImage($postId, $image, $description = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO blog_post_gallery (post_id, image, description) VALUES (?, ?, ?)");
    return $stmt->execute([$postId, $image, $description]);
}

/**
 * Usuń zdjęcie z galerii artykułu blogowego
 */
function deleteBlogPostGalleryImage($imageId) {
    global $pdo;
    
    // Pobierz nazwę pliku przed usunięciem
    $stmt = $pdo->prepare("SELECT image FROM blog_post_gallery WHERE id = ?");
    $stmt->execute([$imageId]);
    $img = $stmt->fetch();
    
    if ($img) {
        deleteFile($img['image']);
        $stmt = $pdo->prepare("DELETE FROM blog_post_gallery WHERE id = ?");
        return $stmt->execute([$imageId]);
    }
    return false;
}

function ensureHomepageAdPositionSchema(): bool
{
    static $checked = null;
    if ($checked !== null) return $checked;
    global $pdo;
    try { $checked = in_array('homepage_position', $pdo->query("SHOW COLUMNS FROM ads")->fetchAll(PDO::FETCH_COLUMN), true); }
    catch (PDOException $e) { $checked = false; }
    return $checked;
}

function ensurePollSchema(): bool
{
    static $checked = null;
    if ($checked !== null) return $checked;
    global $pdo;
    try {
        $required = ['polls', 'poll_options', 'poll_votes'];
        $checked = true;
        foreach ($required as $table) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if (!$stmt->fetchColumn()) { $checked = false; break; }
        }
    } catch (PDOException $e) { $checked = false; }
    return $checked;
}

function getHomepageAds(int $limit = 6): array
{
    global $pdo;
    $limit = max(1, min($limit, 12));
    ensureAdModerationSchema();
    ensureAdContactSchema();
    if (!ensureHomepageAdPositionSchema()) {
        return getRandomAds($limit);
    }

    $fields = 'a.*, ac.name AS category_name, ac.slug AS category_slug, ac.icon AS category_icon';
    $base = ' FROM ads a JOIN ad_categories ac ON a.category_id = ac.id
        WHERE a.is_active = 1 AND a.submission_status = \'approved\'';

    $pinnedStatement = $pdo->prepare("SELECT {$fields}{$base} AND a.homepage_position BETWEEN 1 AND ? ORDER BY a.homepage_position ASC");
    $pinnedStatement->execute([$limit]);
    $pinned = $pinnedStatement->fetchAll();

    $slots = [];
    $pinnedIds = [];
    foreach ($pinned as $ad) {
        $position = (int) $ad['homepage_position'];
        if ($position >= 1 && $position <= $limit) {
            $slots[$position] = $ad;
            $pinnedIds[] = (int) $ad['id'];
        }
    }

    $remaining = $limit - count($slots);
    if ($remaining <= 0) {
        ksort($slots);
        return array_values($slots);
    }

    $fallbackSql = "SELECT {$fields}{$base} AND (a.homepage_position IS NULL OR a.homepage_position > ?)";
    $fallbackParams = [$limit];
    if ($pinnedIds !== []) {
        $fallbackSql .= ' AND a.id NOT IN (' . implode(',', array_fill(0, count($pinnedIds), '?')) . ')';
        $fallbackParams = array_merge($fallbackParams, $pinnedIds);
    }
    $fallbackSql .= ' ORDER BY a.created_at DESC, a.id DESC LIMIT ' . $remaining;
    $fallbackStatement = $pdo->prepare($fallbackSql);
    $fallbackStatement->execute($fallbackParams);
    $fallback = $fallbackStatement->fetchAll();

    $fallbackIndex = 0;
    for ($position = 1; $position <= $limit; $position++) {
        if (isset($slots[$position])) {
            continue;
        }
        if (isset($fallback[$fallbackIndex])) {
            $slots[$position] = $fallback[$fallbackIndex];
            $fallbackIndex++;
        }
    }

    ksort($slots);
    return array_values($slots);
}

function getPollNow(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Europe/Warsaw')))->format('Y-m-d H:i:s');
}

function getActivePoll(): ?array
{
    global $pdo;
    if (!ensurePollSchema()) {
        return null;
    }

    $now = getPollNow();
    $statement = $pdo->prepare('SELECT * FROM polls
        WHERE is_active = 1
          AND (starts_at IS NULL OR starts_at <= ?)
          AND (ends_at IS NULL OR ends_at >= ?)
        ORDER BY COALESCE(starts_at, created_at) DESC, id DESC
        LIMIT 1');
    $statement->execute([$now, $now]);
    $poll = $statement->fetch();
    return $poll ?: null;
}

function getPollVoterHash(): string
{
    $token = $_COOKIE['66_600_poll_voter'] ?? '';
    if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        $token = bin2hex(random_bytes(32));
        setcookie('66_600_poll_voter', $token, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        $_COOKIE['66_600_poll_voter'] = $token;
    }

    return hash_hmac('sha256', $token, DB_PASS);
}

function getPollResults(int $pollId): array
{
    global $pdo;
    $statement = $pdo->prepare('SELECT po.id, po.option_text, po.display_order, COUNT(pv.id) AS votes
        FROM poll_options po
        LEFT JOIN poll_votes pv ON pv.poll_option_id = po.id
        WHERE po.poll_id = ?
        GROUP BY po.id, po.option_text, po.display_order
        ORDER BY po.display_order ASC, po.id ASC');
    $statement->execute([$pollId]);
    $options = $statement->fetchAll();
    $total = array_sum(array_map(static fn(array $option): int => (int) $option['votes'], $options));

    foreach ($options as &$option) {
        $option['votes'] = (int) $option['votes'];
        $option['percentage'] = $total > 0 ? (int) round(((int) $option['votes'] * 100) / $total) : 0;
    }
    unset($option);

    return ['options' => $options, 'total_votes' => $total];
}

function getPublicPoll(): ?array
{
    $poll = getActivePoll();
    if ($poll === null) {
        return null;
    }

    global $pdo;
    $voterHash = getPollVoterHash();
    $voteStatement = $pdo->prepare('SELECT poll_option_id FROM poll_votes WHERE poll_id = ? AND voter_hash = ? LIMIT 1');
    $voteStatement->execute([(int) $poll['id'], $voterHash]);
    $selectedOptionId = $voteStatement->fetchColumn();
    $results = getPollResults((int) $poll['id']);

    $poll['options'] = $results['options'];
    $poll['total_votes'] = $results['total_votes'];
    $poll['selected_option_id'] = $selectedOptionId === false ? null : (int) $selectedOptionId;
    $poll['has_voted'] = $selectedOptionId !== false;
    return $poll;
}

function getAdminPolls(): array
{
    global $pdo;
    if (!ensurePollSchema()) {
        return [];
    }

    archiveExpiredPolls();
    $statement = $pdo->query('SELECT p.*, COUNT(pv.id) AS votes_count
        FROM polls p
        LEFT JOIN poll_votes pv ON pv.poll_id = p.id
        WHERE p.archived_at IS NULL
        GROUP BY p.id
        ORDER BY p.created_at DESC, p.id DESC');
    return $statement->fetchAll();
}

function getAdminPollById(int $pollId): ?array
{
    global $pdo;
    if (!ensurePollSchema()) {
        return null;
    }

    archiveExpiredPolls();
    $statement = $pdo->prepare('SELECT p.*, COUNT(pv.id) AS votes_count
        FROM polls p
        LEFT JOIN poll_votes pv ON pv.poll_id = p.id
        WHERE p.id = ?
        GROUP BY p.id
        LIMIT 1');
    $statement->execute([$pollId]);
    $poll = $statement->fetch();
    if (!$poll) {
        return null;
    }
    $poll['options'] = getPollResults($pollId)['options'];
    return $poll;
}

function getPollOptionValues(array $options): array
{
    $values = [];
    foreach ($options as $option) {
        $text = trim((string) $option);
        if ($text === '') {
            continue;
        }
        $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        if ($length > 80) {
            throw new InvalidArgumentException('Odpowiedź ankiety może mieć maksymalnie 80 znaków.');
        }
        $values[] = $text;
    }

    if (count($values) < 2 || count($values) > 5) {
        throw new InvalidArgumentException('Ankieta musi mieć od 2 do 5 odpowiedzi.');
    }

    return $values;
}

function normalizePollDateTime(?string $value): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $value, new DateTimeZone('Europe/Warsaw'));
    if ($date === false) {
        throw new InvalidArgumentException('Podaj prawidłową datę i godzinę ankiety.');
    }
    return $date->format('Y-m-d H:i:s');
}

function savePoll(int $pollId, string $question, array $options, ?string $startsAt, ?string $endsAt, bool $isActive): int
{
    global $pdo;
    if (!ensurePollSchema()) {
        throw new RuntimeException('Nie udało się przygotować danych ankiet.');
    }

    $question = trim($question);
    $questionLength = function_exists('mb_strlen') ? mb_strlen($question, 'UTF-8') : strlen($question);
    if ($questionLength < 1 || $questionLength > 160) {
        throw new InvalidArgumentException('Pytanie ankiety musi mieć od 1 do 160 znaków.');
    }

    $options = getPollOptionValues($options);
    $startsAt = normalizePollDateTime($startsAt);
    $endsAt = normalizePollDateTime($endsAt);
    if ($startsAt !== null && $endsAt !== null && strtotime($startsAt) > strtotime($endsAt)) {
        throw new InvalidArgumentException('Data zakończenia nie może być wcześniejsza niż data rozpoczęcia.');
    }

    $pdo->beginTransaction();
    try {
        if ($isActive) {
            $pdo->exec('UPDATE polls SET is_active = 0 WHERE is_active = 1');
        }

        if ($pollId > 0) {
            $archiveStatement = $pdo->prepare('SELECT archived_at FROM polls WHERE id = ? LIMIT 1');
            $archiveStatement->execute([$pollId]);
            $existingPoll = $archiveStatement->fetch();
            if (!$existingPoll || !empty($existingPoll['archived_at'])) {
                throw new RuntimeException('Ankiety z archiwum nie można edytować.');
            }
            $votesStatement = $pdo->prepare('SELECT COUNT(*) FROM poll_votes WHERE poll_id = ?');
            $votesStatement->execute([$pollId]);
            if ((int) $votesStatement->fetchColumn() > 0) {
                throw new RuntimeException('Nie można zmienić pytania ani odpowiedzi ankiety, w której oddano już głosy.');
            }
            $update = $pdo->prepare('UPDATE polls SET question = ?, starts_at = ?, ends_at = ?, is_active = ? WHERE id = ?');
            $update->execute([$question, $startsAt, $endsAt, $isActive ? 1 : 0, $pollId]);
            $pdo->prepare('DELETE FROM poll_options WHERE poll_id = ?')->execute([$pollId]);
        } else {
            $insert = $pdo->prepare('INSERT INTO polls (question, starts_at, ends_at, is_active) VALUES (?, ?, ?, ?)');
            $insert->execute([$question, $startsAt, $endsAt, $isActive ? 1 : 0]);
            $pollId = (int) $pdo->lastInsertId();
        }

        $optionInsert = $pdo->prepare('INSERT INTO poll_options (poll_id, option_text, display_order) VALUES (?, ?, ?)');
        foreach ($options as $order => $option) {
            $optionInsert->execute([$pollId, $option, $order + 1]);
        }

        $pdo->commit();
        return $pollId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function setPollActive(int $pollId, bool $isActive): void
{
    global $pdo;
    if (!ensurePollSchema()) {
        throw new RuntimeException('Nie udało się przygotować danych ankiet.');
    }

    $pdo->beginTransaction();
    try {
        $pollStatement = $pdo->prepare('SELECT archived_at FROM polls WHERE id = ? LIMIT 1');
        $pollStatement->execute([$pollId]);
        $poll = $pollStatement->fetch();
        if (!$poll) {
            throw new RuntimeException('Nie znaleziono wskazanej ankiety.');
        }
        if (!empty($poll['archived_at'])) {
            throw new RuntimeException('Ankiety z archiwum nie można ponownie aktywować.');
        }
        if ($isActive) {
            $pdo->exec('UPDATE polls SET is_active = 0 WHERE is_active = 1');
        }
        $statement = $pdo->prepare('UPDATE polls SET is_active = ? WHERE id = ?');
        $statement->execute([$isActive ? 1 : 0, $pollId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function archiveExpiredPolls(): void
{
    global $pdo;
    if (!ensurePollSchema()) {
        return;
    }

    $now = getPollNow();
    $statement = $pdo->prepare('UPDATE polls SET is_active = 0, archived_at = ?
        WHERE archived_at IS NULL AND is_active = 1 AND ends_at IS NOT NULL AND ends_at < ?');
    $statement->execute([$now, $now]);
}

function archivePoll(int $pollId): void
{
    global $pdo;
    if (!ensurePollSchema()) {
        throw new RuntimeException('Nie udało się przygotować danych ankiet.');
    }

    $statement = $pdo->prepare('UPDATE polls SET is_active = 0, archived_at = ?
        WHERE id = ? AND archived_at IS NULL');
    $statement->execute([getPollNow(), $pollId]);
    if ($statement->rowCount() !== 1) {
        throw new RuntimeException('Nie można zakończyć wskazanej ankiety.');
    }
}

function getAdminPollArchive(string $query = ''): array
{
    global $pdo;
    if (!ensurePollSchema()) {
        return [];
    }

    archiveExpiredPolls();
    $query = trim($query);
    $sql = 'SELECT p.*, COUNT(pv.id) AS votes_count
        FROM polls p
        LEFT JOIN poll_votes pv ON pv.poll_id = p.id
        WHERE p.archived_at IS NOT NULL';
    $params = [];
    if ($query !== '') {
        $sql .= ' AND p.question LIKE ?';
        $params[] = '%' . $query . '%';
    }
    $sql .= ' GROUP BY p.id ORDER BY p.archived_at DESC, p.id DESC';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

function getAdminArchivedPollById(int $pollId): ?array
{
    $poll = getAdminPollById($pollId);
    return $poll !== null && !empty($poll['archived_at']) ? $poll : null;
}

function deleteArchivedPoll(int $pollId): void
{
    global $pdo;
    if (!ensurePollSchema()) {
        throw new RuntimeException('Nie udało się przygotować danych ankiet.');
    }

    $statement = $pdo->prepare('DELETE FROM polls WHERE id = ? AND archived_at IS NOT NULL');
    $statement->execute([$pollId]);
    if ($statement->rowCount() !== 1) {
        throw new RuntimeException('Można usunąć wyłącznie ankietę znajdującą się w archiwum.');
    }
}

function getAdminCsrfToken(): string
{
    $token = $_SESSION['admin_csrf_token'] ?? '';
    if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['admin_csrf_token'] = $token;
    }
    return $token;
}

function verifyAdminCsrfToken(?string $token): void
{
    $expected = getAdminCsrfToken();
    if (!is_string($token) || !hash_equals($expected, $token)) {
        throw new RuntimeException('Formularz wygasł. Odśwież stronę i spróbuj ponownie.');
    }
}

function submitPollVote(int $pollId, int $optionId): array
{
    global $pdo;
    $poll = getActivePoll();
    if ($poll === null || (int) $poll['id'] !== $pollId) {
        throw new RuntimeException('Ta ankieta nie jest już aktywna.');
    }

    $voterHash = getPollVoterHash();
    $pdo->beginTransaction();
    try {
        $optionStatement = $pdo->prepare('SELECT id FROM poll_options WHERE id = ? AND poll_id = ? LIMIT 1');
        $optionStatement->execute([$optionId, $pollId]);
        if (!$optionStatement->fetch()) {
            throw new InvalidArgumentException('Wybrana odpowiedź nie należy do tej ankiety.');
        }

        $existingStatement = $pdo->prepare('SELECT poll_option_id FROM poll_votes WHERE poll_id = ? AND voter_hash = ? LIMIT 1');
        $existingStatement->execute([$pollId, $voterHash]);
        $existingOption = $existingStatement->fetchColumn();
        if ($existingOption === false) {
            $insert = $pdo->prepare('INSERT INTO poll_votes (poll_id, poll_option_id, voter_hash) VALUES (?, ?, ?)');
            $insert->execute([$pollId, $optionId, $voterHash]);
            $selectedOptionId = $optionId;
            $recorded = true;
        } else {
            $selectedOptionId = (int) $existingOption;
            $recorded = false;
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    $results = getPollResults($pollId);
    return [
        'ok' => true,
        'recorded' => $recorded,
        'selected_option_id' => $selectedOptionId,
        'total_votes' => $results['total_votes'],
        'options' => $results['options']
    ];
}

function getHomepageManualPositions(int $limit = 6): array
{
    global $pdo;
    $limit = max(1, min($limit, 12));
    if (!ensureHomepageAdPositionSchema()) {
        return [];
    }

    $statement = $pdo->prepare('SELECT a.*, ac.name AS category_name, ac.slug AS category_slug, ac.icon AS category_icon
        FROM ads a
        JOIN ad_categories ac ON a.category_id = ac.id
        WHERE a.is_active = 1
          AND a.submission_status = \'approved\'
          AND a.homepage_position BETWEEN 1 AND ?
        ORDER BY a.homepage_position ASC');
    $statement->execute([$limit]);
    $positions = [];
    foreach ($statement->fetchAll() as $ad) {
        $positions[(int) $ad['homepage_position']] = $ad;
    }
    return $positions;
}

function saveHomepageAdPositions(array $positions, int $limit = 6): void
{
    global $pdo;
    $limit = max(1, min($limit, 12));
    if (!ensureHomepageAdPositionSchema()) {
        throw new RuntimeException('Nie udało się przygotować ręcznej kolejności ogłoszeń.');
    }

    $normalized = [];
    $usedIds = [];
    foreach ($positions as $position => $adId) {
        $position = (int) $position;
        $adId = (int) $adId;
        if ($position < 1 || $position > $limit || $adId < 1) {
            continue;
        }
        if (isset($usedIds[$adId])) {
            throw new InvalidArgumentException('To samo ogłoszenie można ustawić tylko na jednej pozycji.');
        }
        $normalized[$position] = $adId;
        $usedIds[$adId] = true;
    }

    if ($normalized !== []) {
        $ids = array_values($normalized);
        $statement = $pdo->prepare('SELECT id FROM ads WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') AND is_active = 1 AND submission_status = \'approved\'');
        $statement->execute($ids);
        $availableIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
        sort($availableIds);
        $requestedIds = $ids;
        sort($requestedIds);
        if ($availableIds !== $requestedIds) {
            throw new InvalidArgumentException('Można ustawiać wyłącznie aktywne i zaakceptowane ogłoszenia.');
        }
    }

    $pdo->beginTransaction();
    try {
        $pdo->exec('UPDATE ads SET homepage_position = NULL WHERE homepage_position IS NOT NULL');
        $update = $pdo->prepare('UPDATE ads SET homepage_position = ? WHERE id = ?');
        foreach ($normalized as $position => $adId) {
            $update->execute([$position, $adId]);
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
