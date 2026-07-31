<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db = getDB();
$me = currentUser();

// Xóa user (không xóa chính mình / admin khác)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id !== (int)$me['id']) {
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $role = $stmt->fetchColumn();
        if ($role === 'user') {
            $db->prepare("DELETE FROM users WHERE id = ? AND role = 'user'")->execute([$id]);
            header('Location: users.php?msg=deleted');
            exit;
        }
    }
    header('Location: users.php?msg=error');
    exit;
}

$users = $db->query("SELECT id, username, display_name, email, role, created_at FROM users ORDER BY role ASC, created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Người dùng | <?= e(SITE_NAME) ?></title>
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
            <a href="comments.php">Bình luận</a>
            <a href="users.php" class="active">Người dùng</a>
            <a href="../" target="_blank">Xem website</a>
            <a href="logout.php">Đăng xuất</a>
        </nav>
    </aside>
    <div class="admin-content">
        <div class="admin-header">
            <h1>Quản lý người dùng</h1>
        </div>

        <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-<?= $_GET['msg'] === 'deleted' ? 'success' : 'error' ?>">
            <?= $_GET['msg'] === 'deleted' ? 'Đã xóa người dùng.' : 'Không thể xóa tài khoản này.' ?>
        </div>
        <?php endif; ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Tên hiển thị</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><strong><?= e($u['username']) ?></strong></td>
                        <td><?= e($u['display_name']) ?></td>
                        <td><?= e($u['email'] ?? '—') ?></td>
                        <td>
                            <span class="badge badge-<?= $u['role'] === 'admin' ? 'published' : 'draft' ?>">
                                <?= $u['role'] === 'admin' ? 'Admin' : 'User' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td class="actions">
                            <?php if ($u['role'] === 'user'): ?>
                            <a href="?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Xóa người dùng này?">Xóa</a>
                            <?php else: ?>
                            <span style="color:var(--text-muted);font-size:0.85rem">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
