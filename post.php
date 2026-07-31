<?php
require_once __DIR__ . '/includes/auth.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: index.php');
    exit;
}

$db = getDB();
$currentUser = currentUser();

$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug, u.display_name as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.slug = ? AND p.status = 'published'
");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty-state"><h3>Bài viết không tồn tại</h3><p><a href="index.php">Về trang chủ</a></p></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$db->prepare("UPDATE posts SET views = views + 1 WHERE id = ?")->execute([$post['id']]);
$post['views']++;

$tags = $db->prepare("SELECT t.name, t.slug FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
$tags->execute([$post['id']]);
$postTags = $tags->fetchAll();

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $content = trim($_POST['content'] ?? '');

    if ($currentUser) {
        $name = $currentUser['display_name'];
        $email = $currentUser['email'] ?? '';
        $userId = $currentUser['id'];
        // User đã đăng nhập → duyệt luôn
        $status = 'approved';
    } else {
        $name = trim($_POST['author_name'] ?? '');
        $email = trim($_POST['author_email'] ?? '');
        $userId = null;
        $status = 'pending';
    }

    if ($name === '' || $content === '') {
        $msg = 'Vui lòng nhập tên và nội dung bình luận.';
        $msgType = 'error';
    } elseif (mb_strlen($content) < 5) {
        $msg = 'Bình luận quá ngắn.';
        $msgType = 'error';
    } else {
        $db->prepare("INSERT INTO comments (post_id, user_id, author_name, author_email, content, status) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$post['id'], $userId, $name, $email ?: null, $content, $status]);
        if ($status === 'approved') {
            $msg = 'Bình luận của bạn đã được đăng.';
        } else {
            $msg = 'Bình luận của bạn đã được gửi và đang chờ duyệt.';
        }
        $msgType = 'success';
        $_POST = [];
    }
}

$comments = $db->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC");
$comments->execute([$post['id']]);
$commentList = $comments->fetchAll();

$pageTitle = $post['title'];
$pageDesc = $post['excerpt'] ?: mb_substr(strip_tags($post['content']), 0, 160);
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <article class="post-single">
        <header class="post-header">
            <div class="post-meta">
                <?php if ($post['category_name']): ?>
                <a href="category.php?slug=<?= e($post['category_slug']) ?>" class="category"><?= e($post['category_name']) ?></a>
                <?php endif; ?>
                <span><?= e($post['author_name'] ?? 'Admin') ?></span>
                <span><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></span>
                <span><?= (int)$post['views'] ?> lượt xem</span>
            </div>
            <h1><?= e($post['title']) ?></h1>
        </header>

        <?php if ($post['featured_image']): ?>
        <div class="post-featured">
            <img src="<?= e(UPLOAD_URL . $post['featured_image']) ?>" alt="<?= e($post['title']) ?>">
        </div>
        <?php endif; ?>

        <div class="post-content">
            <?= $post['content'] ?>
        </div>

        <?php if ($postTags): ?>
        <div class="post-tags">
            <strong>Tags:</strong>
            <?php foreach ($postTags as $t): ?>
            <a href="tag.php?slug=<?= e($t['slug']) ?>" class="tag"><?= e($t['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <section class="comments-section">
            <h2>Bình luận (<?= count($commentList) ?>)</h2>

            <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>"><?= e($msg) ?></div>
            <?php endif; ?>

            <form class="comment-form" method="post">
                <h3>Viết bình luận</h3>
                <?php if ($currentUser): ?>
                <p style="color:var(--text-muted);margin-bottom:1rem;font-size:0.9rem">
                    Đăng với tên <strong><?= e($currentUser['display_name']) ?></strong>
                    · Bình luận sẽ hiển thị ngay
                </p>
                <?php else: ?>
                <p style="color:var(--text-muted);margin-bottom:1rem;font-size:0.9rem">
                    <a href="login.php?redirect=post.php?slug=<?= e(urlencode($slug)) ?>">Đăng nhập</a> để bình luận nhanh hơn,
                    hoặc gửi ẩn danh (cần duyệt).
                </p>
                <div class="form-row">
                    <div class="form-group">
                        <label for="author_name">Tên *</label>
                        <input type="text" id="author_name" name="author_name" required maxlength="100" value="<?= e($_POST['author_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="author_email">Email (không bắt buộc)</label>
                        <input type="email" id="author_email" name="author_email" maxlength="150" value="<?= e($_POST['author_email'] ?? '') ?>">
                    </div>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label for="content">Nội dung *</label>
                    <textarea id="content" name="content" required maxlength="2000"><?= e($_POST['content'] ?? '') ?></textarea>
                </div>
                <button type="submit" name="comment" class="btn">Gửi bình luận</button>
            </form>

            <?php if (empty($commentList)): ?>
            <p style="color: var(--text-muted);">Chưa có bình luận nào. Hãy là người đầu tiên!</p>
            <?php else: ?>
                <?php foreach ($commentList as $c): ?>
                <div class="comment">
                    <div class="comment-header">
                        <span class="comment-author"><?= e($c['author_name']) ?></span>
                        <span class="comment-date"><?= timeAgo($c['created_at']) ?></span>
                    </div>
                    <div class="comment-body"><?= nl2br(e($c['content'])) ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </article>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
