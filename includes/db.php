<?php
/**
 * Database connection & initialization (SQLite)
 */

require_once __DIR__ . '/config.php';

/** Độ dài chuỗi (hỗ trợ UTF-8, fallback khi không có mbstring) */
function str_len(string $str): int {
    return function_exists('mb_strlen') ? mb_strlen($str, 'UTF-8') : strlen($str);
}

/** Cắt chuỗi (hỗ trợ UTF-8) */
function str_cut(string $str, int $start, ?int $length = null): string {
    if (function_exists('mb_substr')) {
        return $length === null ? mb_substr($str, $start, null, 'UTF-8') : mb_substr($str, $start, $length, 'UTF-8');
    }
    return $length === null ? substr($str, $start) : substr($str, $start, $length);
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $isNew = !file_exists(DB_PATH);
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');

        if ($isNew) {
            initDatabase($pdo);
        } else {
            migrateDatabase($pdo);
        }
    }
    return $pdo;
}

function migrateDatabase(PDO $pdo): void {
    // Thêm cột user_id vào comments nếu chưa có
    $cols = $pdo->query("PRAGMA table_info(comments)")->fetchAll();
    $names = array_column($cols, 'name');
    if (!in_array('user_id', $names, true)) {
        $pdo->exec("ALTER TABLE comments ADD COLUMN user_id INTEGER DEFAULT NULL");
    }
}

function initDatabase(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            display_name TEXT NOT NULL,
            email TEXT,
            role TEXT DEFAULT 'user',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            slug TEXT NOT NULL UNIQUE,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            slug TEXT NOT NULL UNIQUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            content TEXT NOT NULL,
            excerpt TEXT,
            featured_image TEXT,
            category_id INTEGER,
            author_id INTEGER NOT NULL,
            status TEXT DEFAULT 'published',
            views INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE post_tags (
            post_id INTEGER NOT NULL,
            tag_id INTEGER NOT NULL,
            PRIMARY KEY (post_id, tag_id),
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
        );

        CREATE TABLE comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL,
            parent_id INTEGER DEFAULT NULL,
            user_id INTEGER DEFAULT NULL,
            author_name TEXT NOT NULL,
            author_email TEXT,
            content TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        );

        CREATE INDEX idx_posts_slug ON posts(slug);
        CREATE INDEX idx_posts_status ON posts(status);
        CREATE INDEX idx_posts_category ON posts(category_id);
        CREATE INDEX idx_comments_post ON comments(post_id);
        CREATE INDEX idx_comments_status ON comments(status);
    ");

    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, password, display_name, email, role) VALUES (?, ?, ?, ?, ?)")
        ->execute(['admin', $hash, 'Administrator', 'admin@example.com', 'admin']);

    $cats = [
        ['ESP32 & IoT', 'esp32-iot', 'Dự án ESP32, Arduino, IoT'],
        ['Điện tử', 'dien-tu', 'Mạch điện, linh kiện, hàn mạch'],
        ['Lập trình', 'lap-trinh', 'Code, phần mềm, web, app'],
        ['DIY & Chế tạo', 'diy-che-tao', 'Dự án tự làm, sửa chữa'],
        ['Hướng dẫn', 'huong-dan', 'Tutorial, mẹo, tip'],
    ];
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
    foreach ($cats as $c) {
        $stmt->execute($c);
    }

    $tags = [
        ['ESP32', 'esp32'],
        ['Arduino', 'arduino'],
        ['PlatformIO', 'platformio'],
        ['C++', 'cpp'],
        ['Python', 'python'],
        ['PCB', 'pcb'],
        ['Sensor', 'sensor'],
        ['Web', 'web'],
        ['DIY', 'diy'],
        ['Tutorial', 'tutorial'],
    ];
    $stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
    foreach ($tags as $t) {
        $stmt->execute($t);
    }

    $pdo->prepare("INSERT INTO posts (title, slug, content, excerpt, category_id, author_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([
            'Chào mừng đến với WEB_Blogger',
            'chao-mung-den-voi-web-blogger',
            "<p>Đây là bài viết mẫu của <strong>WEB_Blogger</strong> – blog kỹ thuật & DIY.</p>\n            <p>Bạn có thể đăng bài, phân loại, gắn tag, nhận bình luận và quản trị dễ dàng.</p>\n            <h2>Tính năng chính</h2>\n            <ul>\n                <li>Đăng bài viết với ảnh đại diện</li>\n                <li>Category & Tag</li>\n                <li>Bình luận (duyệt trước khi hiện)</li>\n                <li>Đăng ký / Đăng nhập người dùng</li>\n                <li>Quản trị admin riêng</li>\n                <li>Giao diện responsive</li>\n            </ul>\n            <p>Admin: <code>admin</code> / <code>admin123</code></p>",
            'Bài viết chào mừng và hướng dẫn nhanh về blog kỹ thuật DIY.',
            1,
            1,
            'published'
        ]);

    $postId = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$postId, 9]);
    $pdo->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)")->execute([$postId, 10]);
}

function slugify(string $text): string {
    if (function_exists('mb_strtolower')) {
        $text = mb_strtolower($text, 'UTF-8');
    } else {
        $text = strtolower($text);
    }
    $text = preg_replace('/[àáạảãâầấậẩẫăằắặẳẵ]/u', 'a', $text);
    $text = preg_replace('/[èéẹẻẽêềếệểễ]/u', 'e', $text);
    $text = preg_replace('/[ìíịỉĩ]/u', 'i', $text);
    $text = preg_replace('/[òóọỏõôồốộổỗơờớợởỡ]/u', 'o', $text);
    $text = preg_replace('/[ùúụủũưừứựửữ]/u', 'u', $text);
    $text = preg_replace('/[ỳýỵỷỹ]/u', 'y', $text);
    $text = preg_replace('/[đ]/u', 'd', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return $text ?: 'post-' . time();
}

function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'vừa xong';
    if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if ($diff < 604800) return floor($diff / 86400) . ' ngày trước';
    return date('d/m/Y', $time);
}
