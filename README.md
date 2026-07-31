# WEB_Blogger

Blog kỹ thuật & DIY – web động PHP + SQLite.

## Tính năng

- Đăng ký / Đăng nhập người dùng thường
- Admin riêng (quản trị bài viết, bình luận, user…)
- Đăng bài viết (hỗ trợ HTML, ảnh đại diện)
- Phân loại Category & Tag
- Bình luận (user đăng nhập → hiện ngay; khách → chờ duyệt)
- Tìm kiếm
- Responsive, giao diện tối hiện đại

## Yêu cầu

- PHP 8.0+ (extension `pdo_sqlite`, `mbstring`)
- Không cần MySQL

## Cài đặt

1. Upload toàn bộ thư mục lên hosting hoặc chạy local:

```bash
cd WEB_Blogger
php -S localhost:8000
```

2. Mở trình duyệt: `http://localhost:8000`

3. Database SQLite tự tạo ở `database/blog.db` lần đầu chạy.

## Tài khoản

### Admin (quản trị)
- **Username:** `admin`
- **Password:** `admin123`
- Đăng nhập tại `/admin/login.php` hoặc `/login.php`

> Nên đổi mật khẩu sau lần đầu.

### Người dùng thường
- Đăng ký tại `/register.php`
- Đăng nhập tại `/login.php`
- Có thể bình luận (hiển thị ngay, không cần duyệt)

## Phân quyền

| Vai trò | Quyền |
|---------|--------|
| **admin** | Toàn quyền quản trị (bài viết, category, tag, bình luận, user) |
| **user** | Đăng nhập, bình luận |
| **Khách** | Xem bài, bình luận ẩn danh (chờ duyệt) |

## Cấu trúc thư mục

```
WEB_Blogger/
├── index.php, post.php, category.php, tag.php, search.php
├── login.php, register.php, logout.php
├── admin/
├── includes/
├── assets/
├── uploads/
└── database/
```

## License

MIT
