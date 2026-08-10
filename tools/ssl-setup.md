# HTTPS với Let's Encrypt cho WEB_Blogger

Chứng chỉ **Let's Encrypt** miễn phí, tự gia hạn 90 ngày. Web Push và cookie Secure **bắt buộc HTTPS** (trừ `localhost`).

---

## 1. Điều kiện trước khi cài

- Domain đã trỏ DNS A/AAAA về IP server (VPS)
- Port **80** và **443** mở firewall
- Web server: **Nginx** hoặc **Apache**
- PHP-FPM (hoặc mod_php) đang chạy site

---

## 2. Cài Certbot + Let's Encrypt

### Ubuntu / Debian

```bash
sudo apt update
sudo apt install -y certbot

# Nginx
sudo apt install -y python3-certbot-nginx

# hoặc Apache
sudo apt install -y python3-certbot-apache
```

### CentOS / Rocky / Alma

```bash
sudo dnf install -y certbot python3-certbot-nginx
# hoặc: python3-certbot-apache
```

---

## 3A. Nginx + Let's Encrypt (khuyến nghị)

1. Tạo virtual host HTTP trước (xem mẫu `tools/nginx-letsencrypt.conf.example`):

```bash
sudo nano /etc/nginx/sites-available/web-blogger
sudo ln -s /etc/nginx/sites-available/web-blogger /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

2. Cấp chứng chỉ:

```bash
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

Certbot tự sửa config Nginx, trỏ SSL tới:

```
/etc/letsencrypt/live/your-domain.com/fullchain.pem
/etc/letsencrypt/live/your-domain.com/privkey.pem
```

3. Kiểm tra gia hạn tự động:

```bash
sudo certbot renew --dry-run
```

Timer systemd thường đã bật: `certbot.timer`.

---

## 3B. Apache + Let's Encrypt

```bash
sudo a2enmod ssl rewrite headers
sudo systemctl restart apache2

sudo certbot --apache -d your-domain.com -d www.your-domain.com
```

Mẫu config: `tools/apache-letsencrypt.conf.example`.

---

## 3C. Certbot standalone (không dùng plugin web server)

Dừng tạm Nginx/Apache, rồi:

```bash
sudo systemctl stop nginx   # hoặc apache2
sudo certbot certonly --standalone -d your-domain.com -d www.your-domain.com
sudo systemctl start nginx
```

Sau đó cấu hình SSL thủ công trỏ tới `/etc/letsencrypt/live/your-domain.com/`.

---

## 4. Cấu hình WEB_Blogger sau khi có HTTPS

Sửa `includes/config.php`:

```php
define('SITE_URL', 'https://your-domain.com');
define('FORCE_HTTPS', true);

define('VAPID_SUBJECT', 'mailto:admin@your-domain.com');
// php tools/generate_vapid.php  → dán key vào đây
```

`FORCE_HTTPS = true` sẽ:

- Redirect 301 HTTP → HTTPS
- Gửi header **HSTS**
- Session cookie **Secure + HttpOnly + SameSite=Lax**

---

## 5. Cloudflare (proxy SSL)

Nếu dùng Cloudflare phía trước:

1. SSL/TLS mode: **Full (strict)** (origin vẫn cần Let's Encrypt hoặc Cloudflare Origin Cert)
2. Bật **Always Use HTTPS**
3. Trong app: `FORCE_HTTPS = true`, `SITE_URL = https://...`

App đã đọc `X-Forwarded-Proto` / `CF-Visitor` để nhận biết HTTPS sau proxy.

---

## 6. Shared hosting (cPanel / DirectAdmin)

1. **cPanel** → Security → SSL/TLS Status → **Run AutoSSL** / Let's Encrypt
2. Bật **Force HTTPS Redirect**
3. Sửa `config.php` như mục 4

Không cần SSH certbot trên shared hosting.

---

## 7. Kiểm tra sau khi cài

```bash
# Chứng chỉ còn hạn
sudo certbot certificates

# SSL Labs (trên máy bạn)
# https://www.ssllabs.com/ssltest/analyze.html?d=your-domain.com

# Header HSTS / redirect
curl -I http://your-domain.com
curl -I https://your-domain.com
```

Trình duyệt: ổ khóa xanh, không cảnh báo "Not secure".

Web Push: mở site bằng `https://`, vào Chat → cho phép thông báo.

---

## 8. Gia hạn & sự cố thường gặp

| Vấn đề | Cách xử lý |
|--------|------------|
| Renew fail | Port 80 phải mở; không block `/.well-known/acme-challenge/` |
| Redirect loop | Cloudflare: dùng Full (strict), không Flexible nếu origin cũng redirect |
| Cookie không Secure | `FORCE_HTTPS=true` + truy cập bằng https |
| Web Push lỗi | Phải https (hoặc localhost); kiểm tra `canUseWebPush()` |

Gia hạn thủ công:

```bash
sudo certbot renew
sudo systemctl reload nginx   # hoặc apache2
```
