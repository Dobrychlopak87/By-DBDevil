<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

function weatherEndpointData(string $file): ?array
{
    if (!is_file($file) || !is_readable($file)) {
        return null;
    }

    $json = file_get_contents($file);
    $data = is_string($json) ? json_decode($json, true) : null;
    $updatedAt = is_array($data) ? ($data['updatedAt'] ?? null) : null;
    $updatedAtTimestamp = is_string($updatedAt) ? strtotime($updatedAt) : false;
    $required = ['city', 'icon', 'condition', 'temperature', 'wind'];

    if (!is_array($data)
        || ($data['ok'] ?? false) !== true
        || $updatedAtTimestamp === false
        || (time() - $updatedAtTimestamp) > 5400
        || array_diff($required, array_keys($data)) !== []) {
        return null;
    }

    return $data;
}

function refreshWeatherCacheIfNeeded(string $file): void
{
    $attemptFile = $file . '.refresh';
    if (is_file($attemptFile) && (time() - (int) filemtime($attemptFile)) < 120) {
        return;
    }

    $lock = @fopen($file . '.lock', 'c');
    if ($lock === false || !@flock($lock, LOCK_EX | LOCK_NB)) {
        if (is_resource($lock)) {
            fclose($lock);
        }
        return;
    }

    try {
        if (weatherEndpointData($file) !== null) {
            return;
        }

        @touch($attemptFile);
        define('WEATHER_UPDATE_LIBRARY', true);
        require_once __DIR__ . '/cron/update_weather.php';
        update_weather_cache();
        @unlink($attemptFile);
    } catch (Throwable $exception) {
        error_log('66600 weather fallback: ' . $exception->getMessage());
    } finally {
        @flock($lock, LOCK_UN);
        fclose($lock);
    }
}

$file = __DIR__ . '/weather.json';
$data = weatherEndpointData($file);
if ($data === null) {
    refreshWeatherCacheIfNeeded($file);
    $data = weatherEndpointData($file);
}

if ($data === null) {
    http_response_code(503);
    echo '{"ok":false}';
    exit;
}

echo json_encode([
    'ok' => true,
    'city' => (string) $data['city'],
    'icon' => (string) $data['icon'],
    'condition' => (string) $data['condition'],
    'temperature' => (int) $data['temperature'],
    'wind' => (int) $data['wind']
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
