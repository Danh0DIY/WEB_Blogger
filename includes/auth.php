<?php
/**
 * Authentication helpers
 */

require_once __DIR__ . '/db.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    $user = currentUser();
    return $user && ($user['role'] ?? '') === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $redirect = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode(ltrim($redirect, '/')));
        exit;
    }
}

function requireAdmin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/');
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $stmt = getDB()->prepare('SELECT id, username, display_name, email, avatar, role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        if (!$user) {
            logout();
            return null;
        }
    }
    return $user;
}

/** Xóa cache currentUser sau khi cập nhật hồ sơ */
function refreshCurrentUser(): void {
    // Force reload on next currentUser() call by clearing static via session touch
    // PHP static in currentUser is per-request, so just don't cache across updates in same request:
    // callers should re-fetch after update
}

function login(string $username, string $password): bool {
    $stmt = getDB()->prepare('SELECT id, password FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if ($row && password_verify($password, $row['password'])) {
        startSession();
        $_SESSION['user_id'] = (int)$row['id'];
        return true;
    }
    return false;
}

function register(string $username, string $password, string $displayName, string $email = ''): ?string {
    $username = trim($username);
    $displayName = trim($displayName);
    $email = trim($email);

    if ($username === '' || $password === '' || $displayName === '') {
        return 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        return 'Tên đăng nhập chỉ gồm chữ, số, gạch dưới (3–30 ký tự).';
    }
    if (str_len($password) < 6) {
        return 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    $dnLen = str_len($displayName);
    if ($dnLen < 2 || $dnLen > 50) {
        return 'Tên hiển thị phải từ 2–50 ký tự.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Email không hợp lệ.';
    }

    $db = getDB();
    $check = $db->prepare('SELECT id FROM users WHERE username = ?');
    $check->execute([$username]);
    if ($check->fetch()) {
        return 'Tên đăng nhập đã được sử dụng.';
    }

    if ($email !== '') {
        $check = $db->prepare('SELECT id FROM users WHERE email = ? AND email IS NOT NULL AND email != ""');
        $check->execute([$email]);
        if ($check->fetch()) {
            return 'Email đã được sử dụng.';
        }
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->prepare('INSERT INTO users (username, password, display_name, email, role) VALUES (?, ?, ?, ?, ?)')
        ->execute([$username, $hash, $displayName, $email ?: null, 'user']);

    $id = (int)$db->lastInsertId();
    startSession();
    $_SESSION['user_id'] = $id;
    return null;
}

/** Đổi mật khẩu. null = OK, string = lỗi */
function changePassword(int $userId, string $current, string $new, string $confirm): ?string {
    if ($new === '' || $current === '') {
        return 'Vui lòng nhập đầy đủ mật khẩu.';
    }
    if (str_len($new) < 6) {
        return 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    }
    if ($new !== $confirm) {
        return 'Mật khẩu xác nhận không khớp.';
    }
    $stmt = getDB()->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($current, $hash)) {
        return 'Mật khẩu hiện tại không đúng.';
    }
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    getDB()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $userId]);
    return null;
}

function logout(): void {
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
