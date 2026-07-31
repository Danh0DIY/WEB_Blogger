<?php
require_once __DIR__ . '/includes/db.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * POSTS_PER_PAGE;

$db = getDB();
$total = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
$totalPages = max(1, (int)ceil($total / POSTS_PER_PAGE));

$stmt = $db->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug, u.display_name as author_name
    FROM posts p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.author_id = u.id
    WHERE p.status = 'published'
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, POSTS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$pageTitle = 'Trang chủ';
$pageDesc = SITE_DESCRIPTION;
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <section class="hero">
        <h1>Blog kỹ thuật & DIY</h1>
        <p>Chia sẻ dự án ESP32, điện tử, lập trình và các mẹo chế tạo hữu ích.</p>
        <form class="search-bar" action="search.php" method="get">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="search" name="q" placeholder="Tìm kiếm bài viết..." value="<?= e($_GET['q'] ?? '') ?>">
        </form>
    </section>

    <?php if (empty($posts)): ?>
        <div class="empty-state">
            <h3>Chưa có bài viết nào</h3>
            <p>Hãy đăng nhập quản trị để viết bài đầu tiên.</p>
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
                        <?php if ($post['category_name']): ?>
                        <a href="category.php?slug=<?= e($post['category_slug']) ?>" class="category"><?= e($post['category_name']) ?></a>
                        <?php endif; ?>
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
            <a href="?page=<?= $page - 1 ?>">&laquo;</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                <span class="active"><?= $i ?></span>
                <?php else: ?>
                <a href="?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">&raquo;</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
