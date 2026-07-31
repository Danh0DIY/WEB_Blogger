<?php
require_once __DIR__ . '/includes/auth.php';
startSession();

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin/' : 'index.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif (login($username, $password)) {
        $redirect = $_GET['redirect'] ?? '';
        if ($redirect !== '' && str_starts_with($redirect, '/') === false && !preg_match('#^https?://#i', $redirect)) {
            header('Location: ' . $redirect);
        } elseif (isAdmin()) {
            header('Location: admin/');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $error = 'Sai tên đăng nhập hoặc mật khẩu.';
    }
}

$pageTitle = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-page">
        <div class="login-box">
            <h1>Đăng nhập</h1>
            <p class="subtitle">Đăng nhập để bình luận và sử dụng website</p>

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
                <button type="submit" class="btn">Đăng nhập</button>
            </form>

            <p class="auth-footer">
                Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
            </p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
