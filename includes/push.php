<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function vapidConfigured(): bool {
    return defined('VAPID_PUBLIC_KEY') && VAPID_PUBLIC_KEY !== ''
        && defined('VAPID_PRIVATE_KEY') && VAPID_PRIVATE_KEY !== '';
}

function savePushSubscription(int $userId, array $sub): bool {
    $endpoint = trim($sub['endpoint'] ?? '');
    $p256dh = $sub['keys']['p256dh'] ?? '';
    $auth = $sub['keys']['auth'] ?? '';
    if ($endpoint === '' || $p256dh === '' || $auth === '') return false;
    $db = getDB();
    $db->prepare('
        INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth_key, user_agent, updated_at)
        VALUES (?, ?, ?, ?, ?, datetime("now"))
        ON CONFLICT(endpoint) DO UPDATE SET
            user_id = excluded.user_id,
            p256dh = excluded.p256dh,
            auth_key = excluded.auth_key,
            user_agent = excluded.user_agent,
            updated_at = datetime("now")
    ')->execute([
        $userId, $endpoint, $p256dh, $auth,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);
    return true;
}

function removePushSubscription(string $endpoint): void {
    getDB()->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?')->execute([$endpoint]);
}

function createNotification(int $userId, string $title, string $body, string $url = '/chat/', ?int $conversationId = null): void {
    getDB()->prepare('
        INSERT INTO notifications (user_id, title, body, url, conversation_id, created_at)
        VALUES (?, ?, ?, ?, ?, datetime("now"))
    ')->execute([$userId, $title, $body, $url, $conversationId]);
}

function notifyConversationMembers(int $conversationId, int $fromUserId, string $title, string $body): void {
    $stmt = getDB()->prepare('SELECT user_id FROM conversation_members WHERE conversation_id = ? AND user_id != ?');
    $stmt->execute([$conversationId, $fromUserId]);
    foreach ($stmt->fetchAll() as $row) {
        createNotification((int)$row['user_id'], $title, $body, '/chat/', $conversationId);
    }
}

function fetchUnreadNotifications(int $userId, int $limit = 20): array {
    require_once __DIR__ . '/time.php';
    $stmt = getDB()->prepare('
        SELECT id, title, body, url, conversation_id, created_at, is_read
        FROM notifications WHERE user_id = ? AND is_read = 0
        ORDER BY id DESC LIMIT ?
    ');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['created_at'] = toIso($r['created_at']) ?? $r['created_at'];
        $r['id'] = (int)$r['id'];
        $r['conversation_id'] = $r['conversation_id'] !== null ? (int)$r['conversation_id'] : null;
        $r['is_read'] = (int)$r['is_read'];
    }
    unset($r);
    return $rows;
}

function markNotificationsRead(int $userId, ?array $ids = null): void {
    $db = getDB();
    if ($ids === null || $ids === []) {
        $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$userId]);
        return;
    }
    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = $ids;
    $params[] = $userId;
    $db->prepare("UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders) AND user_id = ?")->execute($params);
}
