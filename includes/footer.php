</main>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-about">
                    <h3><?= e(SITE_NAME) ?></h3>
                    <p><?= e(SITE_DESCRIPTION) ?></p>
                </div>
                <div class="footer-links">
                    <h4>Danh mục</h4>
                    <ul>
                        <?php
                        $cats = getDB()->query("SELECT name, slug FROM categories ORDER BY name")->fetchAll();
                        foreach ($cats as $c):
                        ?>
                        <li><a href="<?= SITE_URL ?>/category.php?slug=<?= e($c['slug']) ?>"><?= e($c['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="footer-tags">
                    <h4>Tags phổ biến</h4>
                    <div class="tag-cloud">
                        <?php
                        $tags = getDB()->query("SELECT t.name, t.slug, COUNT(pt.post_id) as cnt FROM tags t LEFT JOIN post_tags pt ON t.id = pt.tag_id GROUP BY t.id ORDER BY cnt DESC LIMIT 10")->fetchAll();
                        foreach ($tags as $t):
                        ?>
                        <a href="<?= SITE_URL ?>/tag.php?slug=<?= e($t['slug']) ?>" class="tag"><?= e($t['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. Blog kỹ thuật & DIY.</p>
            </div>
        </div>
    </footer>
    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
