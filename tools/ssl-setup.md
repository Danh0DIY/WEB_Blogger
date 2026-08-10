# Cài HTTPS (chứng chỉ SSL) cho WEB_Blogger

Web Push **bắt buộc HTTPS** (trừ `localhost`).

## Cloudflare (dễ)

1. Trỏ domain về Cloudflare
2. SSL/TLS: **Full** hoặc **Full (strict)**
3. Bật Always Use HTTPS
4. Trong `includes/config.php`:
```php
define('FORCE_HTTPS', true);
define('SITE_URL', 'https://your-domain.com');
```

## Let's Encrypt (VPS)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Chứng chỉ: `/etc/letsencrypt/live/your-domain.com/`

## Shared hosting

cPanel → Security → SSL/TLS → bật Let's Encrypt + Force HTTPS.

## Sau khi có HTTPS

1. `FORCE_HTTPS = true` và `SITE_URL = https://...`
2. Mở site bằng https
3. Chat → cho phép thông báo trình duyệt
4. Tuỳ chọn: `php tools/generate_vapid.php`
