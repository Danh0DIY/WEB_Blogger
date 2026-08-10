<?php
/**
 * Time helpers — SQLite CURRENT_TIMESTAMP = UTC
 * Chuyển sang ISO 8601 với offset timezone site (Asia/Ho_Chi_Minh)
 */

/**
 * Parse SQLite datetime (UTC, "Y-m-d H:i:s") → DateTimeImmutable theo timezone site
 */
function parseDbTime(?string $dt): ?DateTimeImmutable {
    if ($dt === null || $dt === '') return null;
    $dt = trim($dt);
    if (preg_match('/[Zz]|[+-]\d{2}:?\d{2}$/', $dt)) {
        try {
            return new DateTimeImmutable($dt);
        } catch (Exception $e) {
            return null;
        }
    }
    try {
        $utc = new DateTimeImmutable($dt, new DateTimeZone('UTC'));
        return $utc->setTimezone(new DateTimeZone(date_default_timezone_get()));
    } catch (Exception $e) {
        return null;
    }
}

/** ISO 8601 cho JSON/JS (có offset, parse đúng mọi browser) */
function toIso(?string $dt): ?string {
    $d = parseDbTime($dt);
    return $d ? $d->format('c') : null;
}

/** Unix timestamp */
function toUnix(?string $dt): int {
    $d = parseDbTime($dt);
    return $d ? $d->getTimestamp() : 0;
}

function timeAgoFixed(string $datetime): string {
    $d = parseDbTime($datetime);
    if (!$d) return $datetime;
    $diff = time() - $d->getTimestamp();
    if ($diff < 0) $diff = 0;
    if ($diff < 60) return 'vừa xong';
    if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if ($diff < 604800) return floor($diff / 86400) . ' ngày trước';
    return $d->format('d/m/Y');
}
