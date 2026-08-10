# WEB_Blogger

Blog kỹ thuật & DIY – web động PHP + SQLite.

## Tính năng

- Đăng bài viết (hỗ trợ HTML, ảnh đại diện)
- Phân loại Category & Tag
- Bình luận (duyệt trước khi hiển thị)
- Đăng ký / Đăng nhập / Hồ sơ / Avatar
- User đăng bài kèm ảnh
- **Chat**: tin nhắn 1-1, tạo nhóm, chat nhóm riêng tư
- Quản trị admin
- Responsive, giao diện tối
- HTTPS / Let's Encrypt + security headers

## Yêu cầu

- PHP 8.0+ (`pdo_sqlite`, khuyến nghị `mbstring`, `openssl`)
- Không cần MySQL

## Cài đặt nhanh (local)

```bash
cd WEB_Blogger
php -S localhost:8000
```

Mở `http://localhost:8000` — SQLite tự tạo tại `database/blog.db`.

## HTTPS production (Let's Encrypt)

Chi tiết: **[tools/ssl-setup.md](tools/ssl-setup.md)**

Tóm tắt VPS + Nginx:

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Sau đó trong `includes/config.php`:

```php
define('SITE_URL', 'https://your-domain.com');
define('FORCE_HTTPS', true);
```

Mẫu config: `tools/nginx-letsencrypt.conf.example`, `tools/apache-letsencrypt.conf.example`.

## Tài khoản mặc định

- **Admin:** `admin` / `admin123`
- User thường: đăng ký tại `/register.php`

## Cấu trúc

```
WEB_Blogger/
├── index.php, post.php, write.php, account.php, ...
├── chat/              # Chat + API
├── admin/             # Quản trị
├── includes/          # config, security, auth, db, ...
├── tools/
│   ├── ssl-setup.md           # Hướng dẫn Let's Encrypt
│   ├── nginx-letsencrypt.conf.example
│   ├── apache-letsencrypt.conf.example
│   └── generate_vapid.php     # Web Push keys
├── assets/
├── uploads/
└── database/
```

## License

MIT
