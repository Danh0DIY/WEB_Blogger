<?php
require_once __DIR__ . '/includes/auth.php';
startSession();

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $displayName = trim($_POST['display_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($password !== $password2) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        $err = register($username, $password, $displayName, $email);
        if ($err === null) {
            header('Location: index.php');
            exit;
        }
        $error = $err;
    }
}

$pageTitle = 'Đăng ký';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-page">
        <div class="login-box">
            <h1>Đăng ký tài khoản</h1>
            <p class="subtitle">Tạo tài khoản để bình luận và tham gia cộng đồng</p>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="username">Tên đăng nhập *</label>
                    <input type="text" id="username" name="username" required autofocus
                           pattern="[a-zA-Z0-9_]{3,30}" title="Chữ, số, gạch dưới (3–30 ký tự)"
                           value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="display_name">Tên hiển thị *</label>
                    <input type="text" id="display_name" name="display_name" required maxlength="50"
                           value="<?= e($_POST['display_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email (không bắt buộc)</label>
                    <input type="email" id="email" name="email" maxlength="150"
                           value="<?= e($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Mật khẩu *</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="password2">Xác nhận mật khẩu *</label>
                    <input type="password" id="password2" name="password2" required minlength="6">
                </div>
                <button type="submit" class="btn">Đăng ký</button>
            </form>

            <p class="auth-footer">
                Đã có tài khoản? <a href="login.php">Đăng nhập</a>
            </p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
