<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
$db = getDB();

$msg = '';
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $db->prepare("DELETE FROM tags WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: tags.php?msg=deleted');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $editId = (int)($_POST['edit_id'] ?? 0);
    $slug = slugify($name);

    if ($name === '') {
        $error = 'Tên tag không được để trống.';
    } else {
        if ($editId) {
            $db->prepare("UPDATE tags SET name=?, slug=? WHERE id=?")->execute([$name, $slug, $editId]);
            $msg = 'Đã cập nhật tag.';
        } else {
            try {
                $db->prepare("INSERT INTO tags (name, slug) VALUES (?,?)")->execute([$name, $slug]);
                $msg = 'Đã thêm tag.';
            } catch (PDOException $e) {
                $error = 'Tag đã tồn tại.';
            }
        }
    }
}

$tags = $db->query("
    SELECT t.*, COUNT(pt.post_id) as post_count
    FROM tags t
    LEFT JOIN post_tags pt ON t.id = pt.tag_id
    GROUP BY t.id
    ORDER BY t.name
")->fetchAll();

$edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM tags WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tags | <?= e(SITE_NAME) ?></title>
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
            <a href="tags.php" class="active">Tags</a>
            <a href="comments.php">Bình luận</a>
            <a href="users.php">Người dùng</a>
            <a href="../" target="_blank">Xem website</a>
            <a href="logout.php">Đăng xuất</a>
        </nav>
    </aside>
    <div class="admin-content">
        <div class="admin-header">
            <h1>Quản lý Tags</h1>
        </div>

        <?php if ($msg || isset($_GET['msg'])): ?>
        <div class="alert alert-success"><?= e($msg ?: 'Đã xóa tag.') ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:1.5rem;">
            <div>
                <h2 style="font-size:1.1rem;margin-bottom:1rem"><?= $edit ? 'Sửa tag' : 'Thêm tag' ?></h2>
                <form method="post" style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem">
                    <?php if ($edit): ?>
                    <input type="hidden" name="edit_id" value="<?= $edit['id'] ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Tên tag *</label>
                        <input type="text" name="name" required value="<?= e($edit['name'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn"><?= $edit ? 'Cập nhật' : 'Thêm' ?></button>
                    <?php if ($edit): ?>
                    <a href="tags.php" class="btn btn-secondary">Hủy</a>
                    <?php endif; ?>
                </form>
            </div>
            <div>
                <h2 style="font-size:1.1rem;margin-bottom:1rem">Danh sách</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Tên</th><th>Slug</th><th>Bài viết</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tags as $t): ?>
                            <tr>
                                <td><?= e($t['name']) ?></td>
                                <td style="color:var(--text-muted);font-size:0.85rem"><?= e($t['slug']) ?></td>
                                <td><?= (int)$t['post_count'] ?></td>
                                <td class="actions">
                                    <a href="?edit=<?= $t['id'] ?>" class="btn btn-sm btn-secondary">Sửa</a>
                                    <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Xóa tag này?">Xóa</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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
