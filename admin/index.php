<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$user = currentUser();
$db = getDB();

$stats = [
    'posts' => (int)$db->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'published' => (int)$db->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'comments' => (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
    'pending' => (int)$db->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn(),
    'categories' => (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'tags' => (int)$db->query("SELECT COUNT(*) FROM tags")->fetchColumn(),
    'users' => (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
];

$recentPosts = $db->query("SELECT id, title, status, created_at FROM posts ORDER BY created_at DESC LIMIT 5")->fetchAll();
$pendingComments = $db->query("SELECT c.id, c.author_name, c.content, c.created_at, p.title as post_title FROM comments c JOIN posts p ON c.post_id = p.id WHERE c.status = 'pending' ORDER BY c.created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="logo">
            <span class="logo-icon">⚡</span>
            <span class="logo-text"><?= e(SITE_NAME) ?></span>
        </div>
        <nav class="admin-nav">
            <a href="index.php" class="active">Dashboard</a>
            <a href="posts.php">Bài viết</a>
            <a href="post-edit.php">Viết bài mới</a>
            <a href="categories.php">Danh mục</a>
            <a href="tags.php">Tags</a>
            <a href="comments.php">Bình luận <?= $stats['pending'] ? "({$stats['pending']})" : '' ?></a>
            <a href="users.php">Người dùng</a>
            <a href="../" target="_blank">Xem website</a>
            <a href="logout.php">Đăng xuất</a>
        </nav>
    </aside>
    <div class="admin-content">
        <div class="admin-header">
            <h1>Dashboard</h1>
            <span style="color:var(--text-muted)">Xin chào, <?= e($user['display_name']) ?></span>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['posts'] ?></div>
                <div class="stat-label">Tổng bài viết</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['published'] ?></div>
                <div class="stat-label">Đã xuất bản</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['comments'] ?></div>
                <div class="stat-label">Bình luận</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['pending'] ?></div>
                <div class="stat-label">Chờ duyệt</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['users'] ?></div>
                <div class="stat-label">Người dùng</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['categories'] ?></div>
                <div class="stat-label">Danh mục</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
            <div>
                <h2 style="font-size:1.1rem;margin-bottom:1rem;">Bài viết gần đây</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Tiêu đề</th><th>Trạng thái</th><th>Ngày</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPosts as $p): ?>
                            <tr>
                                <td><a href="post-edit.php?id=<?= $p['id'] ?>"><?= e(mb_substr($p['title'], 0, 40)) ?></a></td>
                                <td><span class="badge badge-<?= $p['status'] ?>"><?= $p['status'] === 'published' ? 'Xuất bản' : 'Nháp' ?></span></td>
                                <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentPosts)): ?>
                            <tr><td colspan="3" style="text-align:center;color:var(--text-muted)">Chưa có bài viết</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <h2 style="font-size:1.1rem;margin-bottom:1rem;">Bình luận chờ duyệt</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Người gửi</th><th>Bài viết</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingComments as $c): ?>
                            <tr>
                                <td><?= e($c['author_name']) ?></td>
                                <td><?= e(mb_substr($c['post_title'], 0, 30)) ?></td>
                                <td><a href="comments.php" class="btn btn-sm">Duyệt</a></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($pendingComments)): ?>
                            <tr><td colspan="3" style="text-align:center;color:var(--text-muted)">Không có bình luận chờ</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
