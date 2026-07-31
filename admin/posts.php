<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$db = getDB();

// Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT featured_image FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists(UPLOAD_DIR . $img)) {
        @unlink(UPLOAD_DIR . $img);
    }
    $db->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
    header('Location: posts.php?msg=deleted');
    exit;
}

$posts = $db->query("
    SELECT p.*, c.name as category_name, u.display_name as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    ORDER BY p.created_at DESC
")->fetchAll();

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bài viết | <?= e(SITE_NAME) ?></title>
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
            <a href="index.php">Dashboard</a>
            <a href="posts.php" class="active">Bài viết</a>
            <a href="post-edit.php">Viết bài mới</a>
            <a href="categories.php">Danh mục</a>
            <a href="tags.php">Tags</a>
            <a href="comments.php">Bình luận</a>
            <a href="../" target="_blank">Xem website</a>
            <a href="logout.php">Đăng xuất</a>
        </nav>
    </aside>
    <div class="admin-content">
        <div class="admin-header">
            <h1>Quản lý bài viết</h1>
            <a href="post-edit.php" class="btn">+ Viết bài mới</a>
        </div>

        <?php if ($msg === 'deleted'): ?>
        <div class="alert alert-success">Đã xóa bài viết.</div>
        <?php elseif ($msg === 'saved'): ?>
        <div class="alert alert-success">Đã lưu bài viết.</div>
        <?php endif; ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Danh mục</th>
                        <th>Trạng thái</th>
                        <th>Lượt xem</th>
                        <th>Ngày</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $p): ?>
                    <tr>
                        <td>
                            <a href="post-edit.php?id=<?= $p['id'] ?>"><?= e($p['title']) ?></a>
                            <?php if ($p['status'] === 'published'): ?>
                            <br><a href="../post.php?slug=<?= e($p['slug']) ?>" target="_blank" style="font-size:0.8rem;color:var(--text-muted)">Xem →</a>
                            <?php endif; ?>
                        </td>
                        <td><?= e($p['category_name'] ?? '—') ?></td>
                        <td><span class="badge badge-<?= $p['status'] ?>"><?= $p['status'] === 'published' ? 'Xuất bản' : 'Nháp' ?></span></td>
                        <td><?= (int)$p['views'] ?></td>
                        <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                        <td class="actions">
                            <a href="post-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">Sửa</a>
                            <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Xóa bài viết này?">Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($posts)): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">Chưa có bài viết nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
