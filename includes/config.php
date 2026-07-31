<?php
/**
 * WEB_Blogger - Configuration
 * Blog kỹ thuật / DIY
 */

// Site settings
define('SITE_NAME', 'WEB_Blogger');
define('SITE_DESCRIPTION', 'Blog kỹ thuật & DIY - Chia sẻ kiến thức, dự án, mẹo hay');
define('SITE_URL', ''); // Để trống nếu chạy local, hoặc điền domain

// Database
define('DB_PATH', __DIR__ . '/../database/blog.db');

// Upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');
define('AVATAR_DIR', __DIR__ . '/../uploads/avatars/');
define('AVATAR_URL', 'uploads/avatars/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Security
define('SESSION_NAME', 'web_blogger_session');

// Pagination
define('POSTS_PER_PAGE', 9);

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error reporting (tắt khi production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
