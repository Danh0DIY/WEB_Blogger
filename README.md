# WEB_Blogger

Blog kỹ thuật & DIY – web động PHP + SQLite.

## Tính năng

- Đăng bài viết (hỗ trợ HTML, ảnh đại diện)
- Phân loại Category & Tag
- Bình luận (duyệt trước khi hiển thị)
- Đăng ký / Đăng nhập người dùng
- **Chat đầy đủ**: tin nhắn 1-1, tạo nhóm, chat nhóm riêng tư
- Đăng nhập / Quản trị (admin)
- Responsive (mobile)
- Tìm kiếm
- Giao diện tối hiện đại

## Chat

- **Tin nhắn riêng (DM)**: chọn người dùng → chat 1-1
- **Tạo nhóm**: đặt tên, thêm thành viên → nhóm chat riêng tư
- Polling real-time ~2.5s, đánh dấu đã đọc, badge chưa đọc
- Chỉ thành viên nhóm mới xem/gửi tin được

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
   (Nếu DB cũ, bảng chat sẽ được migrate tự động.)

## Tài khoản mặc định

- **Username:** `admin`
- **Password:** `admin123`

> Người dùng thường đăng ký tại `/register.php`. Admin dùng tài khoản trên để quản trị.

## Cấu trúc thư mục

```
WEB_Blogger/
├── index.php          # Trang chủ
├── post.php           # Xem bài + bình luận
├── category.php       # Lọc theo danh mục
├── tag.php            # Lọc theo tag
├── search.php         # Tìm kiếm
├── login.php          # Đăng nhập
├── register.php       # Đăng ký
├── logout.php
├── chat/              # Hệ thống chat
│   ├── index.php      # Giao diện chat
│   └── api.php        # API JSON (list, send, group, dm...)
├── admin/             # Khu vực quản trị
├── includes/          # Config, DB, Auth, Header, Footer
├── assets/            # CSS, JS (chat.js)
├── uploads/           # Ảnh bài viết
└── database/          # SQLite
```

## License

MIT – tự do sử dụng và chỉnh sửa.
