<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];

// Luôn lấy user mới nhất từ DB
$stmt = $db->prepare('SELECT id, username, display_name, email, avatar, role, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    logout();
    header('Location: login.php');
    exit;
}

$msg = '';
$msgType = '';
$tab = $_GET['tab'] ?? 'profile';

// Cập nhật hồ sơ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
    $displayName = trim($_POST['display_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dnLen = str_len($displayName);

    if ($dnLen < 2 || $dnLen > 50) {
        $msg = 'Tên hiển thị phải từ 2–50 ký tự.';
        $msgType = 'error';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Email không hợp lệ.';
        $msgType = 'error';
    } else {
        if ($email !== '') {
            $check = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ? AND email IS NOT NULL AND email != ""');
            $check->execute([$email, $userId]);
            if ($check->fetch()) {
                $msg = 'Email đã được sử dụng.';
                $msgType = 'error';
            }
        }
        if ($msg === '') {
            $avatar = $user['avatar'];
            $uploadErr = null;
            if (!empty($_FILES['avatar']['name'])) {
                $newAvatar = uploadImage($_FILES['avatar'], AVATAR_DIR, $uploadErr);
                if ($uploadErr) {
                    $msg = $uploadErr;
                    $msgType = 'error';
                } elseif ($newAvatar) {
                    if ($avatar && file_exists(AVATAR_DIR . $avatar)) {
                        @unlink(AVATAR_DIR . $avatar);
                    }
                    $avatar = $newAvatar;
                }
            }
            if (isset($_POST['remove_avatar']) && $avatar) {
                if (file_exists(AVATAR_DIR . $avatar)) @unlink(AVATAR_DIR . $avatar);
                $avatar = null;
            }
            if ($msg === '') {
                $db->prepare('UPDATE users SET display_name = ?, email = ?, avatar = ? WHERE id = ?')
                    ->execute([$displayName, $email ?: null, $avatar, $userId]);
                $msg = 'Đã cập nhật hồ sơ.';
                $msgType = 'success';
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            }
        }
    }
    $tab = 'profile';
}

// Đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    $err = changePassword(
        $userId,
        $_POST['current_password'] ?? '',
        $_POST['new_password'] ?? '',
        $_POST['confirm_password'] ?? ''
    );
    if ($err === null) {
        $msg = 'Đã đổi mật khẩu thành công.';
        $msgType = 'success';
    } else {
        $msg = $err;
        $msgType = 'error';
    }
    $tab = 'password';
}

// Thống kê bài viết của user
$postCount = (int)$db->prepare('SELECT COUNT(*) FROM posts WHERE author_id = ?')->execute([$userId]) ?: 0;
$cStmt = $db->prepare('SELECT COUNT(*) FROM posts WHERE author_id = ?');
$cStmt->execute([$userId]);
$postCount = (int)$cStmt->fetchColumn();

$pageTitle = 'Hồ sơ';
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="profile-layout">
        <aside class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-avatar-wrap">
                    <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= e(avatarUrl($user['avatar'])) ?>" alt="" class="profile-avatar">
                    <?php else: ?>
                    <span class="profile-avatar profile-avatar-initial"><?= e(avatarInitial($user['display_name'])) ?></span>
                    <?php endif; ?>
                </div>
                <h2><?= e($user['display_name']) ?></h2>
                <p class="profile-username">@<?= e($user['username']) ?></p>
                <p class="profile-meta">Tham gia <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
                <p class="profile-meta"><?= $postCount ?> bài viết</p>
                <?php if ($user['role'] === 'admin'): ?>
                <span class="badge badge-published">Admin</span>
                <?php endif; ?>
            </div>
            <nav class="profile-nav">
                <a href="?tab=profile" class="<?= $tab === 'profile' ? 'active' : '' ?>">Hồ sơ & Avatar</a>
                <a href="?tab=password" class="<?= $tab === 'password' ? 'active' : '' ?>">Đổi mật khẩu</a>
                <a href="my-posts.php">Bài viết của tôi</a>
                <a href="write.php">Viết bài mới</a>
            </nav>
        </aside>

        <div class="profile-main">
            <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?>"><?= e($msg) ?></div>
            <?php endif; ?>

            <?php if ($tab === 'password'): ?>
            <div class="profile-section">
                <h1>Đổi mật khẩu</h1>
                <form method="post" class="profile-form">
                    <input type="hidden" name="action" value="password">
                    <div class="form-group">
                        <label for="current_password">Mật khẩu hiện tại *</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Mật khẩu mới *</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Xác nhận mật khẩu mới *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    <button type="submit" class="btn">Đổi mật khẩu</button>
                </form>
            </div>
            <?php else: ?>
            <div class="profile-section">
                <h1>Hồ sơ của tôi</h1>
                <form method="post" enctype="multipart/form-data" class="profile-form">
                    <input type="hidden" name="action" value="profile">

                    <div class="form-group">
                        <label>Avatar</label>
                        <div class="avatar-upload">
                            <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= e(avatarUrl($user['avatar'])) ?>" alt="" class="avatar-preview">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remove_avatar" value="1"> Xóa avatar hiện tại
                            </label>
                            <?php else: ?>
                            <span class="avatar-preview avatar-preview-initial"><?= e(avatarInitial($user['display_name'])) ?></span>
                            <?php endif; ?>
                            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
                            <p class="form-hint">JPG, PNG, GIF, WebP · tối đa 5MB</p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tên đăng nhập</label>
                        <input type="text" value="<?= e($user['username']) ?>" disabled>
                        <p class="form-hint">Không thể đổi tên đăng nhập</p>
                    </div>

                    <div class="form-group">
                        <label for="display_name">Tên hiển thị *</label>
                        <input type="text" id="display_name" name="display_name" required maxlength="50"
                               value="<?= e($user['display_name']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="150"
                               value="<?= e($user['email'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn">Lưu hồ sơ</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
