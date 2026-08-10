<?php
require_once __DIR__ . '/includes/auth.php';
startSession();

if (isLoggedIn()) {
    header('Location: ' . SITE_URL . '/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif (login($username, $password)) {
        $redirect = $_GET['redirect'] ?? '/';
        if (strpos($redirect, 'http') === 0) $redirect = '/';
        header('Location: ' . SITE_URL . $redirect);
        exit;
    } else {
        $error = 'Sai tên đăng nhập hoặc mật khẩu.';
    }
}

$pageTitle = 'Đăng nhập';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="max-width:420px;margin:3rem auto;">
    <div class="login-box" style="padding:2rem;">
        <h1 style="text-align:center;margin-bottom:0.5rem;">Đăng nhập</h1>
        <p class="subtitle" style="text-align:center;color:var(--text-muted);margin-bottom:1.5rem;">Đăng nhập để chat và tương tác</p>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn" style="width:100%;">Đăng nhập</button>
        </form>
        <p style="text-align:center;margin-top:1.25rem;font-size:0.9rem;color:var(--text-muted)">
            Chưa có tài khoản? <a href="<?= SITE_URL ?>/register.php">Đăng ký</a>
        </p>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
