<?php
declare(strict_types=1);

const WEATHER_ENDPOINT = 'https://api.open-meteo.com/v1/forecast?latitude=52.055&longitude=15.099&current=temperature_2m,weather_code,wind_speed_10m,is_day&temperature_unit=celsius&wind_speed_unit=kmh&timezone=Europe%2FWarsaw';

function weather_request(string $url): string
{
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Nie można utworzyć połączenia pogodowego.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => '66600.pl-weather/1.0'
        ]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if (!is_string($response) || $status < 200 || $status >= 300) {
            throw new RuntimeException($error !== '' ? $error : 'Źródło pogody zwróciło nieprawidłową odpowiedź.');
        }

        return $response;
    }

    $context = stream_context_create([
        'http' => ['timeout' => 20, 'header' => "User-Agent: 66600.pl-weather/1.0\r\n"],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
    ]);
    $response = @file_get_contents($url, false, $context);
    if (!is_string($response) || $response === '') {
        throw new RuntimeException('Nie można pobrać danych pogodowych.');
    }

    return $response;
}

function weather_condition(int $code, bool $isDay): array
{
    if ($code === 0) {
        return [$isDay ? '☀️' : '🌙', $isDay ? 'Słonecznie' : 'Bezchmurnie'];
    }
    if ($code === 1) {
        return [$isDay ? '🌤️' : '🌙', 'Lekko zachmurzone'];
    }
    if ($code === 2) {
        return ['⛅', 'Częściowe zachmurzenie'];
    }
    if ($code === 3) {
        return ['☁️', 'Pochmurno'];
    }
    if (in_array($code, [45, 48], true)) {
        return ['🌫️', 'Mgła'];
    }
    if (in_array($code, [51, 53, 55, 56, 57], true)) {
        return ['🌦️', 'Mżawka'];
    }
    if (in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true)) {
        return ['🌧️', 'Deszcz'];
    }
    if (in_array($code, [71, 73, 75, 77, 85, 86], true)) {
        return ['🌨️', 'Śnieg'];
    }
    if (in_array($code, [95, 96, 99], true)) {
        return ['⛈️', 'Burza'];
    }

    return ['🌡️', 'Warunki pogodowe'];
}

function update_weather_cache(): void
{
    $payload = json_decode(weather_request(WEATHER_ENDPOINT), true, 512, JSON_THROW_ON_ERROR);
    $current = is_array($payload) ? ($payload['current'] ?? null) : null;
    if (!is_array($current)
        || !is_numeric($current['temperature_2m'] ?? null)
        || !is_numeric($current['weather_code'] ?? null)
        || !is_numeric($current['wind_speed_10m'] ?? null)) {
        throw new RuntimeException('Źródło pogody nie zawiera kompletnych danych bieżących.');
    }

    [$icon, $condition] = weather_condition((int) $current['weather_code'], (int) ($current['is_day'] ?? 1) === 1);
    $weather = [
        'ok' => true,
        'city' => 'Krosno Odrzańskie',
        'icon' => $icon,
        'condition' => $condition,
        'temperature' => (int) round((float) $current['temperature_2m']),
        'wind' => (int) round((float) $current['wind_speed_10m']),
        'updatedAt' => (new DateTimeImmutable('now', new DateTimeZone('Europe/Warsaw')))->format(DateTimeInterface::ATOM)
    ];

    $target = dirname(__DIR__) . '/weather.json';
    $temporary = $target . '.tmp';
    $json = json_encode($weather, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false || !rename($temporary, $target)) {
        @unlink($temporary);
        throw new RuntimeException('Nie można zapisać lokalnego cache pogody.');
    }
    @chmod($target, 0644);
}

if (!defined('WEATHER_UPDATE_LIBRARY')) {
    // Aktualizacja wyłącznie z harmonogramu hostingu (CLI). Dostęp przez
    // HTTP jest dodatkowo zablokowany w cron/.htaccess.
    if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
        http_response_code(404);
        exit;
    }
    try {
        update_weather_cache();
    } catch (Throwable $exception) {
        error_log('66600 weather update: ' . $exception->getMessage());
        exit(1);
    }
}
