<?php
declare(strict_types=1);

function base_path(string $path = ''): string
{
    $siteUrl = defined('SITE_URL') ? (string) SITE_URL : (string) (getenv('SITE_URL') ?: '');
    $parsedPath = parse_url($siteUrl, PHP_URL_PATH);
    $base = is_string($parsedPath) ? '/' . trim($parsedPath, '/') : '';
    $base = $base === '/' ? '' : rtrim($base, '/');
    $path = trim($path);
    if ($path === '' || $path === '/') {
        return $base === '' ? '/' : $base . '/';
    }
    return $base . '/' . ltrim($path, '/');
}

function site_url(string $path = ''): string
{
    $configured = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : rtrim((string) (getenv('SITE_URL') ?: ''), '/');
    if ($configured === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if (!preg_match('/^[a-z0-9.-]+(?::[0-9]{1,5})?$/i', $host)) {
            $host = 'localhost';
        }
        $configured = $scheme . '://' . $host;
    }
    if ($path === '') {
        return $configured;
    }
    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
        return $path;
    }
    return $configured . '/' . ltrim($path, '/');
}

function asset_url(string $path): string
{
    return site_url('assets/' . ltrim($path, '/'));
}

function redirect_to(string $path): never
{
    $target = preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) ? $path : site_url($path);
    header('Location: ' . $target, true, 302);
    exit;
}

/**
 * Kanonizacja hosta i wymuszenie HTTPS na poziomie PHP.
 *
 * Aktywna wyłącznie po skonfigurowaniu CANONICAL_HOST (np. przez SetEnv
 * w .htaccess). Nie można tego zrobić w mod_rewrite, ponieważ SetEnv
 * wykonuje się w fazie późniejszej niż reguły per-dir.
 */
function enforce_canonical_request(): void
{
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        return;
    }

    $canonical = getenv('CANONICAL_HOST');
    if (!is_string($canonical) || trim($canonical) === '') {
        return;
    }
    $canonical = strtolower(trim($canonical));

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $hostWithoutPort = preg_replace('/:[0-9]{1,5}$/', '', $host) ?? '';

    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    if ($uri === '' || $uri[0] !== '/') {
        $uri = '/';
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    if ($hostWithoutPort !== $canonical || !$isHttps) {
        header('Location: https://' . $canonical . $uri, true, 301);
        exit;
    }
}
