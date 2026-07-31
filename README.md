# WEB_Blogger

Blog kỹ thuật & DIY – web động PHP + SQLite.

## Tính năng

- Đăng bài viết (hỗ trợ HTML, ảnh đại diện)
- Phân loại Category & Tag
- Bình luận (duyệt trước khi hiển thị)
- Đăng nhập / Quản trị
- Responsive (mobile)
- Tìm kiếm
- Giao diện tối hiện đại

## Yêu cầu

- PHP 8.0+ (với extension `pdo_sqlite`, `mbstring`)
- Không cần MySQL

## Cài đặt

1. Upload toàn bộ thư mục lên hosting hoặc chạy local với PHP built-in server:

```bash
cd WEB_Blogger
php -S localhost:8000
```

2. Mở trình duyệt: `http://localhost:8000`

3. Database SQLite sẽ tự tạo ở `database/blog.db` lần đầu chạy.

## Tài khoản mặc định

- **Username:** `admin`
- **Password:** `admin123`

> Nên đổi mật khẩu sau khi đăng nhập lần đầu.

## Cấu trúc thư mục

```
WEB_Blogger/
├── index.php
├── post.php
├── category.php
├── tag.php
├── search.php
├── admin/
├── includes/
├── assets/
├── uploads/
└── database/
```

## License

MIT
