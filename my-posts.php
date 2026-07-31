<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
$db = getDB();
$userId = (int)$user['id'];

// Xóa bài của chính mình
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $db->prepare('SELECT featured_image FROM posts WHERE id = ? AND author_id = ?');
    $stmt->execute([$delId, $userId]);
    $img = $stmt->fetchColumn();
    if ($img !== false) {
        if ($img && file_exists(UPLOAD_DIR . $img)) @unlink(UPLOAD_DIR . $img);
        $db->prepare('DELETE FROM posts WHERE id = ? AND author_id = ?')->execute([$delId, $userId]);
        header('Location: my-posts.php?msg=deleted');
        exit;
    }
    header('Location: my-posts.php');
    exit;
}

$posts = $db->prepare("
    SELECT p.*, c.name as category_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.author_id = ?
    ORDER BY p.created_at DESC
");
$posts->execute([$userId]);
$postList = $posts->fetchAll();

$msg = $_GET['msg'] ?? '';
$pageTitle = 'Bài viết của tôi';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-title" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem">
        <div>
            <h1>Bài viết của tôi</h1>
            <p><?= count($postList) ?> bài viết</p>
        </div>
        <a href="write.php" class="btn">+ Viết bài mới</a>
    </div>

    <?php if ($msg === 'saved'): ?>
    <div class="alert alert-success">Đã lưu bài viết.</div>
    <?php elseif ($msg === 'deleted'): ?>
    <div class="alert alert-success">Đã xóa bài viết.</div>
    <?php endif; ?>

    <?php if (empty($postList)): ?>
    <div class="empty-state">
        <h3>Chưa có bài viết nào</h3>
        <p><a href="write.php" class="btn">Viết bài đầu tiên</a></p>
    </div>
    <?php else: ?>
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
                <?php foreach ($postList as $p): ?>
                <tr>
                    <td>
                        <a href="write.php?id=<?= $p['id'] ?>"><?= e($p['title']) ?></a>
                        <?php if ($p['status'] === 'published'): ?>
                        <br><a href="post.php?slug=<?= e($p['slug']) ?>" target="_blank" style="font-size:0.8rem;color:var(--text-muted)">Xem →</a>
                        <?php endif; ?>
                    </td>
                    <td><?= e($p['category_name'] ?? '—') ?></td>
                    <td><span class="badge badge-<?= $p['status'] ?>"><?= $p['status'] === 'published' ? 'Xuất bản' : 'Nháp' ?></span></td>
                    <td><?= (int)$p['views'] ?></td>
                    <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                    <td class="actions">
                        <a href="write.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary">Sửa</a>
                        <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Xóa bài viết này?">Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
