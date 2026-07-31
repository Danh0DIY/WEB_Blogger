<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$db = getDB();

if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $db->prepare("UPDATE comments SET status = 'approved' WHERE id = ?")->execute([(int)$_GET['approve']]);
    header('Location: comments.php?msg=approved');
    exit;
}
if (isset($_GET['spam']) && is_numeric($_GET['spam'])) {
    $db->prepare("UPDATE comments SET status = 'spam' WHERE id = ?")->execute([(int)$_GET['spam']]);
    header('Location: comments.php?msg=spam');
    exit;
}
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $db->prepare("DELETE FROM comments WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: comments.php?msg=deleted');
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$sql = "
    SELECT c.*, p.title as post_title, p.slug as post_slug
    FROM comments c
    JOIN posts p ON c.post_id = p.id
";
if ($filter === 'pending') $sql .= " WHERE c.status = 'pending'";
elseif ($filter === 'approved') $sql .= " WHERE c.status = 'approved'";
elseif ($filter === 'spam') $sql .= " WHERE c.status = 'spam'";
$sql .= " ORDER BY c.created_at DESC";

$comments = $db->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bình luận | <?= e(SITE_NAME) ?></title>
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
            <a href="posts.php">Bài viết</a>
            <a href="post-edit.php">Viết bài mới</a>
            <a href="categories.php">Danh mục</a>
            <a href="tags.php">Tags</a>
            <a href="comments.php" class="active">Bình luận</a>
            <a href="../" target="_blank">Xem website</a>
            <a href="logout.php">Đăng xuất</a>
        </nav>
    </aside>
    <div class="admin-content">
        <div class="admin-header">
            <h1>Quản lý bình luận</h1>
            <div style="display:flex;gap:0.5rem">
                <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? '' : 'btn-secondary' ?>">Tất cả</a>
                <a href="?filter=pending" class="btn btn-sm <?= $filter === 'pending' ? '' : 'btn-secondary' ?>">Chờ duyệt</a>
                <a href="?filter=approved" class="btn btn-sm <?= $filter === 'approved' ? '' : 'btn-secondary' ?>">Đã duyệt</a>
                <a href="?filter=spam" class="btn btn-sm <?= $filter === 'spam' ? '' : 'btn-secondary' ?>">Spam</a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?php
            echo match($_GET['msg']) {
                'approved' => 'Đã duyệt bình luận.',
                'spam' => 'Đã đánh dấu spam.',
                'deleted' => 'Đã xóa bình luận.',
                default => 'Đã cập nhật.'
            };
            ?>
        </div>
        <?php endif; ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Người gửi</th>
                        <th>Nội dung</th>
                        <th>Bài viết</th>
                        <th>Trạng thái</th>
                        <th>Ngày</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $c): ?>
                    <tr>
                        <td>
                            <strong><?= e($c['author_name']) ?></strong>
                            <?php if ($c['author_email']): ?>
                            <br><span style="font-size:0.8rem;color:var(--text-muted)"><?= e($c['author_email']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:280px"><?= e(mb_substr($c['content'], 0, 120)) ?><?= mb_strlen($c['content']) > 120 ? '…' : '' ?></td>
                        <td><a href="../post.php?slug=<?= e($c['post_slug']) ?>" target="_blank"><?= e(mb_substr($c['post_title'], 0, 30)) ?></a></td>
                        <td><span class="badge badge-<?= $c['status'] === 'approved' ? 'approved' : ($c['status'] === 'pending' ? 'pending' : 'draft') ?>"><?= e($c['status']) ?></span></td>
                        <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                        <td class="actions">
                            <?php if ($c['status'] !== 'approved'): ?>
                            <a href="?approve=<?= $c['id'] ?>" class="btn btn-sm">Duyệt</a>
                            <?php endif; ?>
                            <?php if ($c['status'] !== 'spam'): ?>
                            <a href="?spam=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">Spam</a>
                            <?php endif; ?>
                            <a href="?delete=<?= $c['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Xóa bình luận này?">Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($comments)): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2rem">Không có bình luận.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
