<?php
/**
 * HTTPS redirect + security headers
 * Web Push yêu cầu HTTPS (trừ localhost)
 * Chứng chỉ khuyến nghị: Let's Encrypt (miễn phí, tự gia hạn)
 */

require_once __DIR__ . '/config.php';

function isHttpsRequest(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    // Cloudflare / reverse proxy
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return true;
    }
    if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
        $cf = json_decode($_SERVER['HTTP_CF_VISITOR'], true);
        if (!empty($cf['scheme']) && $cf['scheme'] === 'https') {
            return true;
        }
    }
    if (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    return false;
}

function isLocalhost(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    $host = strtolower(explode(':', $host)[0]);
    return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
}

/** Redirect HTTP → HTTPS khi FORCE_HTTPS = true (production + Let's Encrypt) */
function enforceHttps(): void {
    if (!defined('FORCE_HTTPS') || !FORCE_HTTPS) {
        return;
    }
    if (isLocalhost()) {
        return;
    }
    if (isHttpsRequest()) {
        return;
    }
    if (PHP_SAPI === 'cli') {
        return;
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: https://' . $host . $uri, true, 301);
    exit;
}

function sendSecurityHeaders(): void {
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('X-XSS-Protection: 0');

    // HSTS chỉ khi thực sự HTTPS (có chứng chỉ Let's Encrypt / Cloudflare)
    if (isHttpsRequest() && !isLocalhost()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    // CSP cơ bản — cho phép Google Fonts + self
    $csp = "default-src 'self'; "
         . "script-src 'self' 'unsafe-inline'; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "font-src 'self' https://fonts.gstatic.com data:; "
         . "img-src 'self' data: blob: https:; "
         . "connect-src 'self'; "
         . "frame-ancestors 'self'; "
         . "base-uri 'self'; "
         . "form-action 'self'";
    header('Content-Security-Policy: ' . $csp);
}

/** Web Push chỉ hoạt động trên HTTPS hoặc localhost */
function canUseWebPush(): bool {
    if (isLocalhost()) {
        return true;
    }
    return isHttpsRequest();
}

enforceHttps();
sendSecurityHeaders();
