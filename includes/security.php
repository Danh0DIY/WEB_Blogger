<?php
/**
 * HTTPS redirect + security headers
 * Web Push yêu cầu HTTPS (trừ localhost)
 */

require_once __DIR__ . '/config.php';

function isHttpsRequest(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') return true;
    if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;
    return false;
}

function isLocalhost(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    $host = strtolower(explode(':', $host)[0]);
    return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

function enforceHttps(): void {
    if (!FORCE_HTTPS) return;
    if (isLocalhost()) return;
    if (isHttpsRequest()) return;
    if (php_sapi_name() === 'cli') return;

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: https://' . $host . $uri, true, 301);
    exit;
}

function sendSecurityHeaders(): void {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if (isHttpsRequest() && !isLocalhost()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function canUseWebPush(): bool {
    if (isLocalhost()) return true;
    return isHttpsRequest();
}

enforceHttps();
sendSecurityHeaders();
