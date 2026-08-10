<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/time.php';
require_once __DIR__ . '/../includes/push.php';
require_once __DIR__ . '/../includes/security.php';
startSession();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Chưa đăng nhập']);
    exit;
}

$user = currentUser();
$userId = (int) $user['id'];
$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function jsonOut(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function isoRow(array $row, array $fields = ['created_at', 'last_msg_at', 'joined_at']): array {
    foreach ($fields as $f) {
        if (isset($row[$f]) && $row[$f] !== null && $row[$f] !== '') {
            $row[$f] = toIso($row[$f]) ?? $row[$f];
        }
    }
    return $row;
}

function isMember(PDO $db, int $convId, int $userId): bool {
    $stmt = $db->prepare('SELECT 1 FROM conversation_members WHERE conversation_id = ? AND user_id = ?');
    $stmt->execute([$convId, $userId]);
    return (bool) $stmt->fetch();
}

if ($action === 'list') {
    $stmt = $db->prepare("
        SELECT c.id, c.type, c.name, c.created_at,
               (SELECT content FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_msg,
               (SELECT created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_msg_at,
               (SELECT COUNT(*) FROM messages m
                WHERE m.conversation_id = c.id
                  AND m.created_at > COALESCE(cm.last_read_at, '1970-01-01')
                  AND m.user_id != ?) AS unread
        FROM conversations c
        JOIN conversation_members cm ON cm.conversation_id = c.id AND cm.user_id = ?
        ORDER BY COALESCE(last_msg_at, c.created_at) DESC
    ");
    $stmt->execute([$userId, $userId]);
    $convs = $stmt->fetchAll();
    foreach ($convs as &$c) {
        if ($c['type'] === 'dm') {
            $s = $db->prepare("SELECT u.id, u.display_name, u.username FROM conversation_members cm JOIN users u ON u.id = cm.user_id WHERE cm.conversation_id = ? AND cm.user_id != ?");
            $s->execute([$c['id'], $userId]);
            $other = $s->fetch();
            $c['name'] = $other ? $other['display_name'] : 'Người dùng';
            $c['other_user'] = $other ?: null;
        }
        $c['unread'] = (int) ($c['unread'] ?? 0);
        $c = isoRow($c);
    }
    unset($c);
    jsonOut(['ok' => true, 'conversations' => $convs]);
}

if ($action === 'messages') {
    $convId = (int) ($_GET['id'] ?? 0);
    $afterId = (int) ($_GET['after'] ?? 0);
    if (!$convId || !isMember($db, $convId, $userId)) jsonOut(['ok' => false, 'error' => 'Không có quyền']);
    if ($afterId > 0) {
        $stmt = $db->prepare("SELECT m.id, m.content, m.created_at, m.user_id, u.display_name, u.username FROM messages m JOIN users u ON u.id = m.user_id WHERE m.conversation_id = ? AND m.id > ? ORDER BY m.id ASC LIMIT 100");
        $stmt->execute([$convId, $afterId]);
        $msgs = array_map('isoRow', $stmt->fetchAll());
        if ($msgs) $db->prepare('UPDATE conversation_members SET last_read_at = CURRENT_TIMESTAMP WHERE conversation_id = ? AND user_id = ?')->execute([$convId, $userId]);
        jsonOut(['ok' => true, 'messages' => $msgs]);
    }
    $stmt = $db->prepare("SELECT m.id, m.content, m.created_at, m.user_id, u.display_name, u.username FROM messages m JOIN users u ON u.id = m.user_id WHERE m.conversation_id = ? ORDER BY m.id DESC LIMIT 80");
    $stmt->execute([$convId]);
    $msgs = array_map('isoRow', array_reverse($stmt->fetchAll()));
    $db->prepare('UPDATE conversation_members SET last_read_at = CURRENT_TIMESTAMP WHERE conversation_id = ? AND user_id = ?')->execute([$convId, $userId]);
    jsonOut(['ok' => true, 'messages' => $msgs]);
}

if ($action === 'send') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $convId = (int) ($input['conversation_id'] ?? 0);
    $content = trim($input['content'] ?? '');
    if (!$convId || $content === '') jsonOut(['ok' => false, 'error' => 'Thiếu dữ liệu']);
    if (mb_strlen($content) > 4000) jsonOut(['ok' => false, 'error' => 'Tin nhắn quá dài']);
    if (!isMember($db, $convId, $userId)) jsonOut(['ok' => false, 'error' => 'Không có quyền']);
    $db->prepare('INSERT INTO messages (conversation_id, user_id, content) VALUES (?, ?, ?)')->execute([$convId, $userId, $content]);
    $msgId = (int) $db->lastInsertId();
    $stmt = $db->prepare("SELECT m.id, m.content, m.created_at, m.user_id, u.display_name, u.username FROM messages m JOIN users u ON u.id = m.user_id WHERE m.id = ?");
    $stmt->execute([$msgId]);
    $msg = isoRow($stmt->fetch() ?: []);
    $preview = mb_strlen($content) > 80 ? mb_substr($content, 0, 80) . '…' : $content;
    notifyConversationMembers($convId, $userId, $user['display_name'] ?? $user['username'], $preview);
    jsonOut(['ok' => true, 'message' => $msg]);
}

if ($action === 'create_group') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $name = trim($input['name'] ?? '');
    $memberIds = $input['members'] ?? [];
    $description = trim($input['description'] ?? '');
    if ($name === '' || mb_strlen($name) > 80) jsonOut(['ok' => false, 'error' => 'Tên nhóm không hợp lệ']);
    if (!is_array($memberIds)) $memberIds = [];
    $memberIds = array_filter(array_unique(array_map('intval', $memberIds)), fn($id) => $id > 0 && $id !== $userId);
    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO conversations (type, name, description, created_by, is_private) VALUES (?, ?, ?, ?, 1)')->execute(['group', $name, $description ?: null, $userId]);
        $convId = (int) $db->lastInsertId();
        $db->prepare('INSERT INTO conversation_members (conversation_id, user_id, role) VALUES (?, ?, ?)')->execute([$convId, $userId, 'admin']);
        $ins = $db->prepare('INSERT OR IGNORE INTO conversation_members (conversation_id, user_id, role) VALUES (?, ?, ?)');
        foreach ($memberIds as $mid) {
            $check = $db->prepare('SELECT id FROM users WHERE id = ?'); $check->execute([$mid]);
            if ($check->fetch()) $ins->execute([$convId, $mid, 'member']);
        }
        $db->commit();
        jsonOut(['ok' => true, 'conversation_id' => $convId]);
    } catch (Exception $e) { $db->rollBack(); jsonOut(['ok' => false, 'error' => 'Không tạo được nhóm']); }
}

if ($action === 'start_dm') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $otherId = (int) ($input['user_id'] ?? 0);
    if ($otherId <= 0 || $otherId === $userId) jsonOut(['ok' => false, 'error' => 'User không hợp lệ']);
    $check = $db->prepare('SELECT id FROM users WHERE id = ?'); $check->execute([$otherId]);
    if (!$check->fetch()) jsonOut(['ok' => false, 'error' => 'Không tìm thấy người dùng']);
    $stmt = $db->prepare("SELECT c.id FROM conversations c WHERE c.type = 'dm' AND EXISTS (SELECT 1 FROM conversation_members WHERE conversation_id = c.id AND user_id = ?) AND EXISTS (SELECT 1 FROM conversation_members WHERE conversation_id = c.id AND user_id = ?) LIMIT 1");
    $stmt->execute([$userId, $otherId]);
    $existing = $stmt->fetch();
    if ($existing) jsonOut(['ok' => true, 'conversation_id' => (int) $existing['id']]);
    $db->beginTransaction();
    try {
        $db->prepare('INSERT INTO conversations (type, name, created_by, is_private) VALUES (?, NULL, ?, 1)')->execute(['dm', $userId]);
        $convId = (int) $db->lastInsertId();
        $db->prepare('INSERT INTO conversation_members (conversation_id, user_id, role) VALUES (?, ?, ?)')->execute([$convId, $userId, 'member']);
        $db->prepare('INSERT INTO conversation_members (conversation_id, user_id, role) VALUES (?, ?, ?)')->execute([$convId, $otherId, 'member']);
        $db->commit();
        jsonOut(['ok' => true, 'conversation_id' => $convId]);
    } catch (Exception $e) { $db->rollBack(); jsonOut(['ok' => false, 'error' => 'Không tạo được cuộc trò chuyện']); }
}

if ($action === 'users') {
    $q = trim($_GET['q'] ?? '');
    if ($q !== '') {
        $stmt = $db->prepare('SELECT id, username, display_name FROM users WHERE id != ? AND (username LIKE ? OR display_name LIKE ?) ORDER BY display_name LIMIT 20');
        $like = '%' . $q . '%'; $stmt->execute([$userId, $like, $like]);
    } else {
        $stmt = $db->prepare('SELECT id, username, display_name FROM users WHERE id != ? ORDER BY display_name LIMIT 50');
        $stmt->execute([$userId]);
    }
    jsonOut(['ok' => true, 'users' => $stmt->fetchAll()]);
}

if ($action === 'info') {
    $convId = (int) ($_GET['id'] ?? 0);
    if (!$convId || !isMember($db, $convId, $userId)) jsonOut(['ok' => false, 'error' => 'Không có quyền']);
    $stmt = $db->prepare('SELECT id, type, name, description, created_by, created_at FROM conversations WHERE id = ?');
    $stmt->execute([$convId]);
    $conv = $stmt->fetch();
    if (!$conv) jsonOut(['ok' => false, 'error' => 'Không tìm thấy']);
    $members = $db->prepare('SELECT u.id, u.username, u.display_name, cm.role FROM conversation_members cm JOIN users u ON u.id = cm.user_id WHERE cm.conversation_id = ? ORDER BY cm.role DESC, u.display_name');
    $members->execute([$convId]);
    $conv['members'] = $members->fetchAll();
    if ($conv['type'] === 'dm') {
        foreach ($conv['members'] as $m) {
            if ((int)$m['id'] !== $userId) { $conv['name'] = $m['display_name']; break; }
        }
    }
    jsonOut(['ok' => true, 'conversation' => isoRow($conv)]);
}

if ($action === 'add_members') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $convId = (int) ($input['conversation_id'] ?? 0);
    $memberIds = $input['members'] ?? [];
    if (!$convId || !is_array($memberIds) || !isMember($db, $convId, $userId)) jsonOut(['ok' => false, 'error' => 'Không có quyền']);
    $ins = $db->prepare('INSERT OR IGNORE INTO conversation_members (conversation_id, user_id, role) VALUES (?, ?, ?)');
    $added = 0;
    foreach ($memberIds as $mid) {
        $mid = (int) $mid; if ($mid <= 0 || $mid === $userId) continue;
        $check = $db->prepare('SELECT id FROM users WHERE id = ?'); $check->execute([$mid]);
        if ($check->fetch()) { $ins->execute([$convId, $mid, 'member']); if ($ins->rowCount()) $added++; }
    }
    jsonOut(['ok' => true, 'added' => $added]);
}

if ($action === 'notifications') {
    jsonOut(['ok' => true, 'notifications' => fetchUnreadNotifications($userId, 30), 'can_push' => canUseWebPush()]);
}
if ($action === 'notifications_read') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    markNotificationsRead($userId, is_array($input['ids'] ?? null) ? $input['ids'] : null);
    jsonOut(['ok' => true]);
}
if ($action === 'push_subscribe') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    jsonOut(savePushSubscription($userId, $input) ? ['ok' => true] : ['ok' => false, 'error' => 'Subscription không hợp lệ']);
}
if ($action === 'push_unsubscribe') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    if (!empty($input['endpoint'])) removePushSubscription($input['endpoint']);
    jsonOut(['ok' => true]);
}
if ($action === 'vapid_public') {
    jsonOut(['ok' => true, 'publicKey' => vapidConfigured() ? VAPID_PUBLIC_KEY : '', 'https' => canUseWebPush()]);
}

jsonOut(['ok' => false, 'error' => 'Action không hợp lệ']);
