<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$user = currentUser();
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$post = null;
$postTagIds = [];

if ($id) {
    $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if (!$post) {
        header('Location: posts.php');
        exit;
    }
    $tags = $db->prepare("SELECT tag_id FROM post_tags WHERE post_id = ?");
    $tags->execute([$id]);
    $postTagIds = array_column($tags->fetchAll(), 'tag_id');
}

$categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$allTags = $db->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $status = ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
    $selectedTags = array_map('intval', $_POST['tags'] ?? []);
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') $slug = slugify($title);

    if ($title === '' || $content === '') {
        $error = 'Tiêu đề và nội dung không được để trống.';
    } else {
        $featuredImage = $post['featured_image'] ?? null;
        if (!empty($_FILES['featured_image']['name']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['featured_image'];
            if ($file['size'] > MAX_UPLOAD_SIZE) {
                $error = 'Ảnh quá lớn (tối đa 5MB).';
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $error = 'Chỉ chấp nhận ảnh JPG, PNG, GIF, WebP.';
                } else {
                    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
                    $filename = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
                        if ($featuredImage && file_exists(UPLOAD_DIR . $featuredImage)) {
                            @unlink(UPLOAD_DIR . $featuredImage);
                        }
                        $featuredImage = $filename;
                    }
                }
            }
        }

        if (isset($_POST['remove_image']) && $featuredImage) {
            if (file_exists(UPLOAD_DIR . $featuredImage)) @unlink(UPLOAD_DIR . $featuredImage);
            $featuredImage = null;
        }

        if ($error === '') {
            $check = $db->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
            $check->execute([$slug, $id]);
            if ($check->fetch()) {
                $slug .= '-' . time();
            }

            if ($id) {
                $db->prepare("UPDATE posts SET title=?, slug=?, content=?, excerpt=?, featured_image=?, category_id=?, status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")
                    ->execute([$title, $slug, $content, $excerpt ?: null, $featuredImage, $categoryId, $status, $id]);
            } else {
                $db->prepare("INSERT INTO posts (title, slug, content, excerpt, featured_image, category_id, author_id, status) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$title, $slug, $content, $excerpt ?: null, $featuredImage, $categoryId, $user['id'], $status]);
                $id = (int)$db->lastInsertId();
            }

            $db->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$id]);
            $ins = $db->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
            foreach ($selectedTags as $tid) {
                if ($tid > 0) $ins->execute([$id, $tid]);
            }

            header('Location: posts.php?msg=saved');
            exit;
        }
    }

    $post = [
        'id' => $id,
        'title' => $title,
        'slug' => $slug,
        'content' => $content,
        'excerpt' => $excerpt,
        'category_id' => $categoryId,
        'status' => $status,
        'featured_image' => $featuredImage ?? ($post['featured_image'] ?? null),
    ];
    $postTagIds = $selectedTags;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'Sửa bài' : 'Viết bài mới' ?> | <?= e(SITE_NAME) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
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
            <a href="post-edit.php" class="active">Viết bài mới</a>
            <a href="categories.php">Danh mục</a>
            <a href="tags.php">Tags</a>
            <a href="comments.php">Bình luận</a>
            <a href="../" target="_blank">Xem website</a>
            <a href="logout.php">Đăng xuất</a>
        </nav>
    </aside>
    <div class="admin-content">
        <div class="admin-header">
            <h1><?= $id ? 'Sửa bài viết' : 'Viết bài mới' ?></h1>
            <a href="posts.php" class="btn btn-secondary">← Danh sách</a>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" style="max-width:900px">
            <div class="form-group">
                <label for="title">Tiêu đề *</label>
                <input type="text" id="title" name="title" required value="<?= e($post['title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="slug">Slug (URL)</label>
                <input type="text" id="slug" name="slug" value="<?= e($post['slug'] ?? '') ?>" placeholder="Tự động tạo từ tiêu đề">
            </div>

            <div class="form-group">
                <label for="excerpt">Tóm tắt (excerpt)</label>
                <textarea id="excerpt" name="excerpt" rows="2"><?= e($post['excerpt'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="content">Nội dung * (hỗ trợ HTML)</label>
                <div class="editor-toolbar">
                    <button type="button" onclick="wrapTag('b')"><b>B</b></button>
                    <button type="button" onclick="wrapTag('i')"><i>I</i></button>
                    <button type="button" onclick="wrapTag('h2')">H2</button>
                    <button type="button" onclick="wrapTag('h3')">H3</button>
                    <button type="button" onclick="wrapTag('code')">Code</button>
                    <button type="button" onclick="wrapTag('pre')">Pre</button>
                    <button type="button" onclick="insertList()">List</button>
                    <button type="button" onclick="insertLink()">Link</button>
                </div>
                <textarea id="content" name="content" required rows="18" style="font-family:var(--mono);font-size:0.9rem"><?= e($post['content'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Danh mục</label>
                    <select id="category_id" name="category_id">
                        <option value="">— Không chọn —</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($post['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status">Trạng thái</label>
                    <select id="status" name="status">
                        <option value="published" <?= ($post['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Xuất bản</option>
                        <option value="draft" <?= ($post['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Nháp</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Tags</label>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-top:0.35rem">
                    <?php foreach ($allTags as $t): ?>
                    <label style="display:flex;align-items:center;gap:0.3rem;cursor:pointer;font-size:0.9rem">
                        <input type="checkbox" name="tags[]" value="<?= $t['id'] ?>" <?= in_array($t['id'], $postTagIds) ? 'checked' : '' ?>>
                        <?= e($t['name']) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="featured_image">Ảnh đại diện</label>
                <?php if (!empty($post['featured_image'])): ?>
                <div style="margin-bottom:0.75rem">
                    <img src="../<?= e(UPLOAD_URL . $post['featured_image']) ?>" alt="" style="max-width:280px;border-radius:8px">
                    <label style="display:block;margin-top:0.5rem;font-size:0.85rem">
                        <input type="checkbox" name="remove_image" value="1"> Xóa ảnh hiện tại
                    </label>
                </div>
                <?php endif; ?>
                <input type="file" id="featured_image" name="featured_image" accept="image/*">
            </div>

            <div style="display:flex;gap:0.75rem;margin-top:1.5rem">
                <button type="submit" class="btn">Lưu bài viết</button>
                <a href="posts.php" class="btn btn-secondary">Hủy</a>
            </div>
        </form>
    </div>
</div>
<script>
function wrapTag(tag) {
    const ta = document.getElementById('content');
    const start = ta.selectionStart, end = ta.selectionEnd;
    const selected = ta.value.substring(start, end);
    const open = '<' + tag + '>', close = '</' + tag + '>';
    ta.value = ta.value.substring(0, start) + open + selected + close + ta.value.substring(end);
    ta.focus();
    ta.setSelectionRange(start + open.length, start + open.length + selected.length);
}
function insertList() {
    const ta = document.getElementById('content');
    const start = ta.selectionStart;
    ta.value = ta.value.substring(0, start) + '<ul>\n  <li></li>\n</ul>' + ta.value.substring(ta.selectionEnd);
    ta.focus();
}
function insertLink() {
    const url = prompt('URL:');
    if (!url) return;
    const ta = document.getElementById('content');
    const start = ta.selectionStart, end = ta.selectionEnd;
    const selected = ta.value.substring(start, end) || 'link';
    ta.value = ta.value.substring(0, start) + '<a href="' + url + '">' + selected + '</a>' + ta.value.substring(end);
    ta.focus();
}
</script>
<script src="../assets/js/main.js"></script>
</body>
</html>
