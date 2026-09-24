<?php
declare(strict_types=1);

/**
 * Centralna polityka uploadu i usuwania obrazów.
 *
 * Zasady: UPLOAD_ERR_OK, limit bajtów, rzeczywisty MIME z zawartości, wymiary,
 * whitelist rozszerzeń ze zgodnością rozszerzenia i MIME, odrzucanie nazw
 * wielokropkowych (podwójne rozszerzenia), losowa nazwa docelowa, zapis
 * atomowy przez plik tymczasowy, miniatury wyłącznie po zapisie oryginału.
 */
final class ImageUpload
{
    /** @var array<string, string> MIME => rozszerzenie docelowe */
    public const MIME_EXTENSIONS = [
        'image/avif' => 'avif',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /** @var array<string, list<string>> rozszerzenie deklarowane => akceptowane MIME */
    private const DECLARED_EXTENSION_MIMES = [
        'avif' => ['image/avif'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
    ];

    public const DEFAULT_MAX_BYTES = 5242880;
    public const DEFAULT_MAX_DIMENSION = 12000;

    /** Rozszerzenia i człony nazw, które nigdy nie mogą znaleźć się w nazwie uploadu. */
    private const DANGEROUS_NAME_PATTERN = '/(^|[.\\/\\\\])(php|phtml|php[0-9]|phar|pl|py|cgi|sh|bash|shtml|htaccess|htpasswd|htgroup|js|mjs|html?|xhtml|svg|swf|exe|dll|so|app|command|cmd|bat|lnk|ini|conf)\b/i';

    /**
     * Bezpiecznie zapisuje przesłany obraz.
     *
     * @param array<string, mixed> $file wpis z $_FILES
     * @param array<string, mixed> $options maxBytes, maxDimension, targetDir, thumbnailCallback, requireUploadedFile
     * @return array<string, mixed> wynik zgodny z dotychczasowym uploadFile()
     */
    public static function store(array $file, array $options = []): array
    {
        $maxBytes = (int) ($options['maxBytes'] ?? (defined('MAX_FILE_SIZE') ? MAX_FILE_SIZE : self::DEFAULT_MAX_BYTES));
        $maxDimension = (int) ($options['maxDimension'] ?? self::DEFAULT_MAX_DIMENSION);
        $targetDir = (string) ($options['targetDir'] ?? (defined('UPLOAD_PATH') ? UPLOAD_PATH : ''));
        $requireUploadedFile = (bool) ($options['requireUploadedFile'] ?? true);

        if ($targetDir === '') {
            return ['success' => false, 'message' => 'Serwis nie jest poprawnie skonfigurowany do zapisu obrazów.'];
        }

        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'message' => 'Błąd podczas przesyłania pliku.'];
        }
        if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
            return ['success' => false, 'message' => 'Plik jest za duży. Maksymalny rozmiar: ' . self::formatBytes($maxBytes)];
        }
        if ($errorCode !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Błąd podczas przesyłania pliku.'];
        }

        $temporaryName = (string) ($file['tmp_name'] ?? '');
        if ($temporaryName === '' || !is_file($temporaryName)) {
            return ['success' => false, 'message' => 'Błąd podczas przesyłania pliku.'];
        }
        if ($requireUploadedFile && !is_uploaded_file($temporaryName)) {
            return ['success' => false, 'message' => 'Błąd podczas przesyłania pliku.'];
        }

        $declaredSize = (int) ($file['size'] ?? 0);
        $actualSize = (int) @filesize($temporaryName);
        if (max($declaredSize, $actualSize) > $maxBytes) {
            return ['success' => false, 'message' => 'Plik jest za duży. Maksymalny rozmiar: ' . self::formatBytes($maxBytes)];
        }

        $declaredName = basename((string) ($file['name'] ?? ''));
        $declaredExtension = self::safeExtension($declaredName);
        if ($declaredExtension === null) {
            return ['success' => false, 'message' => 'Dozwolone są tylko pliki obrazków (AVIF, JPG, PNG, GIF, WebP).'];
        }

        $mimeType = self::detectMimeType($temporaryName);
        if ($mimeType === null || !isset(self::MIME_EXTENSIONS[$mimeType])) {
            return ['success' => false, 'message' => 'Przesłany plik nie jest obsługiwanym obrazem.'];
        }
        if (!in_array($mimeType, self::DECLARED_EXTENSION_MIMES[$declaredExtension] ?? [], true)) {
            return ['success' => false, 'message' => 'Przesłany plik nie jest obsługiwanym obrazem.'];
        }

        $dimensions = @getimagesize($temporaryName);
        if (!is_array($dimensions) || (int) ($dimensions[0] ?? 0) < 1 || (int) ($dimensions[1] ?? 0) < 1) {
            return ['success' => false, 'message' => 'Przesłany plik nie jest obsługiwanym obrazem.'];
        }
        if ((int) $dimensions[0] > $maxDimension || (int) $dimensions[1] > $maxDimension) {
            return ['success' => false, 'message' => 'Obraz ma zbyt duże wymiary.'];
        }

        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            return ['success' => false, 'message' => 'Nie udało się przygotować katalogu obrazów.'];
        }

        $baseFilename = bin2hex(random_bytes(12));
        $stagingPath = $targetDir . '.' . bin2hex(random_bytes(8)) . '.part';

        if (!move_uploaded_file($temporaryName, $stagingPath) && !@rename($temporaryName, $stagingPath)) {
            self::removeIfExists($stagingPath);
            return ['success' => false, 'message' => 'Nie udało się zapisać pliku.'];
        }
        @chmod($stagingPath, 0644);

        $extension = self::MIME_EXTENSIONS[$mimeType];
        $finalFilename = $baseFilename . '.' . $extension;
        $finalPath = $targetDir . $finalFilename;

        try {
            if ($mimeType !== 'image/avif'
                && function_exists('convertImageToAvif')
                && convertImageToAvif($stagingPath, $targetDir . $baseFilename . '.avif', $mimeType)
            ) {
                $finalFilename = $baseFilename . '.avif';
                $finalPath = $targetDir . $finalFilename;
                self::removeIfExists($stagingPath);
                $optimized = true;
            } else {
                if (!rename($stagingPath, $finalPath)) {
                    return ['success' => false, 'message' => 'Nie udało się zapisać pliku.'];
                }
                @chmod($finalPath, 0644);
                $optimized = $mimeType === 'image/avif';
            }
        } catch (Throwable $exception) {
            self::removeIfExists($stagingPath);
            self::removeIfExists($targetDir . $baseFilename . '.avif');
            error_log('Upload obrazu nie powiódł się podczas konwersji.');
            return ['success' => false, 'message' => 'Nie udało się zapisać pliku.'];
        }

        // Miniatury generowane wyłącznie po pomyślnym zapisaniu oryginału.
        $thumbnailCallback = $options['thumbnailCallback'] ?? null;
        if (is_callable($thumbnailCallback)) {
            try {
                $thumbnailCallback($finalPath, $finalFilename, $targetDir);
            } catch (Throwable $exception) {
                error_log('Generowanie miniatur uploadu nie powiodło się.');
            }
        } elseif (function_exists('generateUploadThumbnailVariants')) {
            try {
                generateUploadThumbnailVariants($finalPath, $finalFilename, $targetDir);
            } catch (Throwable $exception) {
                error_log('Generowanie miniatur uploadu nie powiodło się.');
            }
        }

        return [
            'success' => true,
            'filename' => $finalFilename,
            'path' => $finalPath,
            'optimized' => $optimized,
        ];
    }

    /**
     * Usuwa obraz wraz z powiązanymi miniaturami. Nazwa pliku musi być
     * poprawną, bezpieczną nazwą pliku bez elementów ścieżki.
     */
    public static function delete(string $filename, string $targetDir): bool
    {
        if (!self::isSafeStoredName($filename)) {
            return false;
        }

        $filePath = rtrim($targetDir, '/') . '/' . $filename;
        $deleted = false;
        if (is_file($filePath)) {
            $deleted = @unlink($filePath);
        }

        $thumbsDir = rtrim($targetDir, '/') . '/thumbs/';
        foreach (self::thumbnailNames($filename) as $thumbnail) {
            $thumbnailPath = $thumbsDir . $thumbnail;
            if (is_file($thumbnailPath)) {
                @unlink($thumbnailPath);
            }
        }

        return $deleted;
    }

    /**
     * Sprawdza, czy nazwa zapisanego obrazu jest bezpieczna (brak ścieżek,
     * poprawny kształt nazwy, dozwolone rozszerzenie).
     */
    public static function isSafeStoredName(string $filename): bool
    {
        if ($filename === '' || strlen($filename) > 255) {
            return false;
        }
        if (basename($filename) !== $filename || str_contains($filename, '..') || $filename[0] === '.') {
            return false;
        }
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.(avif|jpg|jpeg|png|gif|webp)$/i', $filename)) {
            return false;
        }
        return !self::hasDangerousNameSegments($filename);
    }

    /**
     * @return list<string>
     */
    public static function thumbnailNames(string $filename): array
    {
        $basename = basename($filename);
        $stem = pathinfo($basename, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        if ($stem === '') {
            return [];
        }
        if (function_exists('imageavif')) {
            $extension = 'avif';
        } elseif ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        $names = [];
        foreach ([480, 768] as $width) {
            $names[] = $stem . '-w' . $width . '.' . $extension;
        }
        return $names;
    }

    private static function safeExtension(string $declaredName): ?string
    {
        if ($declaredName === '' || strlen($declaredName) > 255) {
            return null;
        }
        if (preg_match('/[\\x00-\\x1f\\x7f]/', $declaredName)) {
            return null;
        }
        // Nazwy wielokropkowe (np. shell.php.jpg) są odrzucane w całości.
        if (substr_count($declaredName, '.') !== 1) {
            return null;
        }
        if (self::hasDangerousNameSegments($declaredName)) {
            return null;
        }
        $extension = strtolower(pathinfo($declaredName, PATHINFO_EXTENSION));
        return isset(self::DECLARED_EXTENSION_MIMES[$extension]) ? $extension : null;
    }

    private static function hasDangerousNameSegments(string $name): bool
    {
        return preg_match(self::DANGEROUS_NAME_PATTERN, $name) === 1;
    }

    private static function detectMimeType(string $path): ?string
    {
        if (class_exists('finfo')) {
            $info = new finfo(FILEINFO_MIME_TYPE);
            $mime = $info->file($path);
            return is_string($mime) ? strtolower($mime) : null;
        }
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($path);
            return is_string($mime) ? strtolower($mime) : null;
        }
        return null;
    }

    private static function removeIfExists(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function formatBytes(int $bytes): string
    {
        if (function_exists('formatFileSize')) {
            return formatFileSize($bytes);
        }
        return round($bytes / 1048576, 1) . ' MB';
    }
}
