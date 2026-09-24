<?php
declare(strict_types=1);

function html_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function attribute_escape(string $value): string
{
    return html_escape($value);
}

function url_escape(string $value): string
{
    return rawurlencode($value);
}
