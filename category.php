<?php
require_once __DIR__ . '/includes/db.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    header('Location: index.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty-state"><h3>Danh mục không tồn tại</h3><p><a href="index.php">Về trang chủ</a></p></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * POSTS_PER_PAGE;

$stmt = $db->prepare("SELECT COUNT(*) FROM posts WHERE category_id = ? AND status = 'published'");
$stmt->execute([$category['id']]);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / POSTS_PER_PAGE));

$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug, u.display_name as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.category_id = ? AND p.status = 'published'
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $category['id'], PDO::PARAM_INT);
$stmt->bindValue(2, POSTS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$pageTitle = $category['name'];
$pageDesc = $category['description'] ?? 'Bài viết thuộc danh mục ' . $category['name'];
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="page-title">
        <h1><?= e($category['name']) ?></h1>
        <?php if ($category['description']): ?>
        <p><?= e($category['description']) ?></p>
        <?php endif; ?>
    </div>

    <?php if (empty($posts)): ?>
        <div class="empty-state">
            <h3>Chưa có bài viết trong danh mục này</h3>
            <p><a href="index.php">Xem tất cả bài viết</a></p>
        </div>
    <?php else: ?>
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <?php if ($post['featured_image']): ?>
                <div class="post-card-image">
                    <a href="post.php?slug=<?= e($post['slug']) ?>">
                        <img src="<?= e(UPLOAD_URL . $post['featured_image']) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
                    </a>
                </div>
                <?php endif; ?>
                <div class="post-card-body">
                    <div class="post-card-meta">
                        <span class="category"><?= e($post['category_name']) ?></span>
                        <span><?= timeAgo($post['created_at']) ?></span>
                    </div>
                    <h2><a href="post.php?slug=<?= e($post['slug']) ?>"><?= e($post['title']) ?></a></h2>
                    <?php if ($post['excerpt']): ?>
                    <p class="excerpt"><?= e($post['excerpt']) ?></p>
                    <?php endif; ?>
                    <div class="post-card-footer">
                        <span><?= e($post['author_name'] ?? 'Admin') ?></span>
                        <span><?= (int)$post['views'] ?> lượt xem</span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="pagination">
            <?php if ($page > 1): ?>
            <a href="?slug=<?= e($slug) ?>&page=<?= $page - 1 ?>">&laquo;</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                <span class="active"><?= $i ?></span>
                <?php else: ?>
                <a href="?slug=<?= e($slug) ?>&page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?slug=<?= e($slug) ?>&page=<?= $page + 1 ?>">&raquo;</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
