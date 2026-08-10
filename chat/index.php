<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
$currentUser = currentUser();
$pageTitle = 'Chat';
$pageDesc = 'Nhắn tin & chat nhóm riêng tư';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="chat-app">
    <aside class="chat-sidebar">
        <div class="chat-sidebar-header">
            <h2>💬 Chat</h2>
            <div class="chat-actions">
                <button type="button" class="btn-icon" id="btnNewDm" title="Tin nhắn mới">✉️</button>
                <button type="button" class="btn-icon" id="btnNewGroup" title="Tạo nhóm">👥</button>
            </div>
        </div>
        <div class="chat-search-wrap">
            <input type="search" id="convSearch" placeholder="Tìm cuộc trò chuyện..." autocomplete="off">
        </div>
        <div class="chat-conv-list" id="convList">
            <div class="chat-loading">Đang tải...</div>
        </div>
    </aside>

    <section class="chat-main" id="chatMain">
        <div class="chat-empty" id="chatEmpty">
            <div class="chat-empty-icon">💬</div>
            <p>Chọn cuộc trò chuyện hoặc tạo nhóm mới</p>
            <div class="chat-empty-btns">
                <button type="button" class="btn" id="btnEmptyDm">Tin nhắn mới</button>
                <button type="button" class="btn btn-outline" id="btnEmptyGroup">Tạo nhóm</button>
            </div>
        </div>

        <div class="chat-active" id="chatActive" hidden>
            <header class="chat-header">
                <button type="button" class="btn-icon chat-back" id="chatBack" aria-label="Quay lại">←</button>
                <div class="chat-header-info">
                    <h3 id="chatTitle">—</h3>
                    <span id="chatSubtitle" class="chat-subtitle"></span>
                </div>
                <button type="button" class="btn-icon" id="btnConvInfo" title="Thông tin">ℹ️</button>
            </header>
            <div class="chat-messages" id="chatMessages"></div>
            <form class="chat-compose" id="chatCompose">
                <textarea id="msgInput" rows="1" placeholder="Nhập tin nhắn..." maxlength="4000"></textarea>
                <button type="submit" class="btn" id="btnSend">Gửi</button>
            </form>
        </div>
    </section>
</div>

<!-- Modal: New DM -->
<div class="modal" id="modalDm" hidden>
    <div class="modal-backdrop" data-close></div>
    <div class="modal-box">
        <header class="modal-header">
            <h3>Tin nhắn mới</h3>
            <button type="button" class="btn-icon" data-close>&times;</button>
        </header>
        <div class="modal-body">
            <input type="search" id="dmSearch" placeholder="Tìm người dùng..." autocomplete="off">
            <div class="user-pick-list" id="dmUserList"></div>
        </div>
    </div>
</div>

<!-- Modal: New Group -->
<div class="modal" id="modalGroup" hidden>
    <div class="modal-backdrop" data-close></div>
    <div class="modal-box">
        <header class="modal-header">
            <h3>Tạo nhóm chat</h3>
            <button type="button" class="btn-icon" data-close>&times;</button>
        </header>
        <div class="modal-body">
            <div class="form-group">
                <label for="groupName">Tên nhóm</label>
                <input type="text" id="groupName" maxlength="80" placeholder="Ví dụ: Team DIY ESP32">
            </div>
            <div class="form-group">
                <label for="groupDesc">Mô tả (tuỳ chọn)</label>
                <input type="text" id="groupDesc" maxlength="200" placeholder="Mô tả ngắn">
            </div>
            <div class="form-group">
                <label>Thêm thành viên</label>
                <input type="search" id="groupSearch" placeholder="Tìm người dùng..." autocomplete="off">
                <div class="user-pick-list" id="groupUserList"></div>
                <div class="selected-members" id="selectedMembers"></div>
            </div>
            <button type="button" class="btn" id="btnCreateGroup" style="width:100%;">Tạo nhóm riêng tư</button>
        </div>
    </div>
</div>

<!-- Modal: Conv info -->
<div class="modal" id="modalInfo" hidden>
    <div class="modal-backdrop" data-close></div>
    <div class="modal-box">
        <header class="modal-header">
            <h3 id="infoTitle">Thông tin</h3>
            <button type="button" class="btn-icon" data-close>&times;</button>
        </header>
        <div class="modal-body" id="infoBody"></div>
    </div>
</div>

<script>
window.CHAT_USER = <?= json_encode([
    'id' => (int)$currentUser['id'],
    'username' => $currentUser['username'],
    'display_name' => $currentUser['display_name'],
], JSON_UNESCAPED_UNICODE) ?>;
window.CHAT_API = '<?= SITE_URL ?>/chat/api.php';
</script>
<script src="<?= SITE_URL ?>/assets/js/chat.js"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
