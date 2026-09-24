#!/bin/sh
set -eu

: "${DB_HOST:=db}"
: "${DB_PORT:=3306}"
: "${DB_NAME:=app}"
: "${DB_USER:=app}"
: "${DB_PASSWORD:?DB_PASSWORD must be set}"
: "${CHAT_DB_DSN:=mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME};charset=utf8mb4}"
: "${CHAT_DB_USER:=${DB_USER}}"
: "${CHAT_DB_PASSWORD:=${DB_PASSWORD}}"
: "${PULSE_RATE_HASH_SECRET:?PULSE_RATE_HASH_SECRET must be set}"

private_dir=/var/www/private
mkdir -p "$private_dir"
mkdir -p /var/lib/66600/session /var/lib/66600/cache \
    /var/www/html/assets/uploads /var/www/html/chatroom/uploads
chown -R www-data:www-data /var/lib/66600 \
    /var/www/html/assets/uploads /var/www/html/chatroom/uploads
cat > "$private_dir/66-600-security-config.php" <<PHP
<?php
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'db',
        'user' => getenv('DB_USER') ?: 'app',
        'pass' => getenv('DB_PASSWORD') ?: '',
        'name' => getenv('DB_NAME') ?: 'app',
    ],
    'chat_database' => [
        'dsn' => getenv('CHAT_DB_DSN') ?: '',
        'username' => getenv('CHAT_DB_USER') ?: '',
        'password' => getenv('CHAT_DB_PASSWORD') ?: '',
    ],
    'pulse_rate_hash_secret' => getenv('PULSE_RATE_HASH_SECRET') ?: '',
];
PHP
chown www-data:www-data "$private_dir/66-600-security-config.php"
chmod 0640 "$private_dir/66-600-security-config.php"

exec "$@"
