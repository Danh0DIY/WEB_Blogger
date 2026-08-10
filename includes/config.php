<?php
/**
 * WEB_Blogger - Configuration
 */

define('SITE_NAME', 'WEB_Blogger');
define('SITE_DESCRIPTION', 'Blog kỹ thuật & DIY - Chia sẻ kiến thức, dự án, mẹo hay');
define('SITE_URL', ''); // https://domain.com khi production

define('DB_PATH', __DIR__ . '/../database/blog.db');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', 'uploads/');
define('AVATAR_DIR', __DIR__ . '/../uploads/avatars/');
define('AVATAR_URL', 'uploads/avatars/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);

define('SESSION_NAME', 'web_blogger_session');
// Bật true khi đã có HTTPS — bắt buộc cho Web Push trên domain thật
define('FORCE_HTTPS', false);

define('VAPID_SUBJECT', 'mailto:admin@example.com');
define('VAPID_PUBLIC_KEY', '');
define('VAPID_PRIVATE_KEY', '');

define('POSTS_PER_PAGE', 9);

date_default_timezone_set('Asia/Ho_Chi_Minh');

error_reporting(E_ALL);
ini_set('display_errors', 1);
