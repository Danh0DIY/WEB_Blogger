/**
 * WEB_Blogger Chat client
 */
(function () {
    const API = window.CHAT_API;
    const ME = window.CHAT_USER;
    let currentConvId = null;
    let lastMsgId = 0;
    let pollTimer = null;
    let selectedMembers = new Map();
    let convCache = [];

    const $ = (sel, el = document) => el.querySelector(sel);
    const $$ = (sel, el = document) => [...el.querySelectorAll(sel)];

    async function api(action, opts = {}) {
        const method = opts.method || (opts.body ? 'POST' : 'GET');
        let url = API + '?action=' + encodeURIComponent(action);
        if (opts.query) {
            for (const [k, v] of Object.entries(opts.query)) {
                url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(v);
            }
        }
        const init = { method, headers: {} };
        if (opts.body) {
            init.headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(opts.body);
        }
        const res = await fetch(url, init);
        return res.json();
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    function parseTime(dt) {
        if (!dt) return null;
        let s = String(dt).trim();
        if (/^\d{4}-\d{2}-\d{2} /.test(s) && !/[Zz]|[+-]\d{2}:?\d{2}$/.test(s)) {
            s = s.replace(' ', 'T') + 'Z';
        }
        const d = new Date(s);
        return isNaN(d.getTime()) ? null : d;
    }

    function timeFmt(dt) {
        const d = parseTime(dt);
        if (!d) return '';
        const now = new Date();
        let diff = (now - d) / 1000;
        if (diff < 0) diff = 0;
        if (diff < 60) return 'vừa xong';
        if (diff < 3600) return Math.floor(diff / 60) + 'p';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd';
        return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
    }

    async function loadConversations() {
        const data = await api('list');
        if (!data.ok) return;
        convCache = data.conversations || [];
        renderConvList(convCache);
    }

    function renderConvList(list) {
        const el = $('#convList');
        if (!list.length) {
            el.innerHTML = '<div class="chat-empty-list">Chưa có cuộc trò chuyện.<br>Tạo nhóm hoặc nhắn tin mới!</div>';
            return;
        }
        el.innerHTML = list.map(c => {
            const active = c.id == currentConvId ? ' active' : '';
            const unread = c.unread > 0 ? `<span class="badge-unread">${c.unread > 99 ? '99+' : c.unread}</span>` : '';
            const icon = c.type === 'dm' ? '👤' : '👥';
            const preview = c.last_msg ? esc(c.last_msg.slice(0, 60)) : '<em>Chưa có tin nhắn</em>';
            return `<button type="button" class="conv-item${active}" data-id="${c.id}">
                <span class="conv-icon">${icon}</span>
                <span class="conv-body">
                    <span class="conv-name">${esc(c.name || 'Nhóm')} ${unread}</span>
                    <span class="conv-preview">${preview}</span>
                </span>
                <span class="conv-time">${timeFmt(c.last_msg_at || c.created_at)}</span>
            </button>`;
        }).join('');
        $$('.conv-item', el).forEach(btn => {
            btn.addEventListener('click', () => openConversation(+btn.dataset.id));
        });
    }

    async function openConversation(id) {
        currentConvId = id;
        lastMsgId = 0;
        stopPoll();
        $('#chatEmpty').hidden = true;
        $('#chatActive').hidden = false;
        document.body.classList.add('chat-open');
        $$('.conv-item').forEach(b => b.classList.toggle('active', +b.dataset.id === id));
        const info = await api('info', { query: { id } });
        if (info.ok) {
            const c = info.conversation;
            $('#chatTitle').textContent = c.name || 'Chat';
            const n = (c.members || []).length;
            $('#chatSubtitle').textContent = c.type === 'dm' ? 'Tin nhắn riêng' : n + ' thành viên · Nhóm riêng tư';
        }
        const data = await api('messages', { query: { id } });
        const box = $('#chatMessages');
        box.innerHTML = '';
        if (data.ok && data.messages) {
            data.messages.forEach(m => appendMessage(m, false));
            if (data.messages.length) lastMsgId = data.messages[data.messages.length - 1].id;
        }
        box.scrollTop = box.scrollHeight;
        startPoll();
        loadConversations();
    }

    function appendMessage(m, scroll) {
        const box = $('#chatMessages');
        const mine = m.user_id == ME.id;
        const div = document.createElement('div');
        div.className = 'msg' + (mine ? ' msg-mine' : '');
        div.dataset.id = m.id;
        div.innerHTML = `
            ${!mine ? `<div class="msg-author">${esc(m.display_name)}</div>` : ''}
            <div class="msg-bubble">${esc(m.content).replace(/\n/g, '<br>')}</div>
            <div class="msg-time">${timeFmt(m.created_at)}</div>
        `;
        box.appendChild(div);
        if (scroll) box.scrollTop = box.scrollHeight;
    }

    function startPoll() { stopPoll(); pollTimer = setInterval(pollMessages, 2500); }
    function stopPoll() { if (pollTimer) clearInterval(pollTimer); pollTimer = null; }
    async function pollMessages() {
        if (!currentConvId) return;
        const data = await api('messages', { query: { id: currentConvId, after: lastMsgId } });
        if (!data.ok || !data.messages || !data.messages.length) return;
        data.messages.forEach(m => {
            if (m.id > lastMsgId) { appendMessage(m, true); lastMsgId = m.id; }
        });
        loadConversations();
    }

    $('#chatCompose').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = $('#msgInput');
        const content = input.value.trim();
        if (!content || !currentConvId) return;
        input.value = '';
        input.style.height = 'auto';
        const data = await api('send', { body: { conversation_id: currentConvId, content } });
        if (data.ok && data.message) {
            appendMessage(data.message, true);
            lastMsgId = Math.max(lastMsgId, data.message.id);
            loadConversations();
        }
    });

    $('#msgInput').addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    $('#msgInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $('#chatCompose').requestSubmit(); }
    });

    function openModal(id) { const m = document.getElementById(id); if (m) m.hidden = false; }
    function closeModal(el) { const m = el.closest('.modal'); if (m) m.hidden = true; }
    $$('[data-close]').forEach(el => el.addEventListener('click', () => closeModal(el)));

    async function loadUsers(q, listEl, onPick) {
        const data = await api('users', { query: { q: q || '' } });
        if (!data.ok) return;
        listEl.innerHTML = (data.users || []).map(u => `
            <button type="button" class="user-pick" data-id="${u.id}">
                <span class="user-avatar">${esc((u.display_name || '?')[0].toUpperCase())}</span>
                <span><strong>${esc(u.display_name)}</strong><small>@${esc(u.username)}</small></span>
            </button>
        `).join('') || '<p class="muted">Không tìm thấy</p>';
        $$('.user-pick', listEl).forEach(btn => {
            btn.addEventListener('click', () => onPick(+btn.dataset.id, data.users.find(u => u.id == btn.dataset.id)));
        });
    }

    function openDmModal() {
        $('#dmSearch').value = '';
        openModal('modalDm');
        loadUsers('', $('#dmUserList'), async (id) => {
            const data = await api('start_dm', { body: { user_id: id } });
            closeModal($('#modalDm'));
            if (data.ok) { await loadConversations(); openConversation(data.conversation_id); }
            else alert(data.error || 'Lỗi');
        });
    }
    $('#btnNewDm').addEventListener('click', openDmModal);
    $('#btnEmptyDm').addEventListener('click', openDmModal);
    let dmSearchTimer;
    $('#dmSearch').addEventListener('input', () => {
        clearTimeout(dmSearchTimer);
        dmSearchTimer = setTimeout(() => loadUsers($('#dmSearch').value, $('#dmUserList'), async (id) => {
            const data = await api('start_dm', { body: { user_id: id } });
            closeModal($('#modalDm'));
            if (data.ok) { await loadConversations(); openConversation(data.conversation_id); }
        }), 250);
    });

    function openGroupModal() {
        selectedMembers.clear();
        $('#groupName').value = '';
        $('#groupDesc').value = '';
        $('#groupSearch').value = '';
        $('#selectedMembers').innerHTML = '';
        openModal('modalGroup');
        loadUsers('', $('#groupUserList'), toggleMember);
    }
    function toggleMember(id, user) {
        if (selectedMembers.has(id)) selectedMembers.delete(id);
        else if (user) selectedMembers.set(id, user);
        renderSelected();
        $$('.user-pick', $('#groupUserList')).forEach(b => b.classList.toggle('selected', selectedMembers.has(+b.dataset.id)));
    }
    function renderSelected() {
        const el = $('#selectedMembers');
        if (!selectedMembers.size) { el.innerHTML = ''; return; }
        el.innerHTML = [...selectedMembers.values()].map(u =>
            `<span class="chip">${esc(u.display_name)} <button type="button" data-rm="${u.id}">&times;</button></span>`
        ).join('');
        $$('[data-rm]', el).forEach(b => b.addEventListener('click', () => { selectedMembers.delete(+b.dataset.rm); renderSelected(); }));
    }
    $('#btnNewGroup').addEventListener('click', openGroupModal);
    $('#btnEmptyGroup').addEventListener('click', openGroupModal);
    let groupSearchTimer;
    $('#groupSearch').addEventListener('input', () => {
        clearTimeout(groupSearchTimer);
        groupSearchTimer = setTimeout(() => {
            loadUsers($('#groupSearch').value, $('#groupUserList'), toggleMember).then(() => {
                $$('.user-pick', $('#groupUserList')).forEach(b => b.classList.toggle('selected', selectedMembers.has(+b.dataset.id)));
            });
        }, 250);
    });
    $('#btnCreateGroup').addEventListener('click', async () => {
        const name = $('#groupName').value.trim();
        if (!name) { alert('Nhập tên nhóm'); return; }
        const data = await api('create_group', { body: { name, description: $('#groupDesc').value.trim(), members: [...selectedMembers.keys()] } });
        if (data.ok) { closeModal($('#modalGroup')); await loadConversations(); openConversation(data.conversation_id); }
        else alert(data.error || 'Lỗi tạo nhóm');
    });

    $('#btnConvInfo').addEventListener('click', async () => {
        if (!currentConvId) return;
        const data = await api('info', { query: { id: currentConvId } });
        if (!data.ok) return;
        const c = data.conversation;
        $('#infoTitle').textContent = c.name || 'Thông tin';
        const membersHtml = (c.members || []).map(m => `
            <div class="info-member">
                <span class="user-avatar">${esc((m.display_name || '?')[0].toUpperCase())}</span>
                <span><strong>${esc(m.display_name)}</strong><small>@${esc(m.username)} ${m.role === 'admin' ? '· Admin' : ''}</small></span>
            </div>`).join('');
        $('#infoBody').innerHTML = `
            <p class="muted" style="margin-bottom:1rem;">${c.type === 'dm' ? 'Tin nhắn riêng tư' : 'Nhóm chat riêng tư'} · Tạo ${timeFmt(c.created_at)}</p>
            ${c.description ? `<p style="margin-bottom:1rem;">${esc(c.description)}</p>` : ''}
            <h4 style="margin-bottom:0.5rem;">Thành viên (${(c.members || []).length})</h4>
            <div class="info-members">${membersHtml}</div>`;
        openModal('modalInfo');
    });

    $('#chatBack').addEventListener('click', () => {
        currentConvId = null; stopPoll();
        $('#chatActive').hidden = true; $('#chatEmpty').hidden = false;
        document.body.classList.remove('chat-open');
        $$('.conv-item').forEach(b => b.classList.remove('active'));
    });

    $('#convSearch').addEventListener('input', () => {
        const q = $('#convSearch').value.trim().toLowerCase();
        if (!q) { renderConvList(convCache); return; }
        renderConvList(convCache.filter(c => (c.name || '').toLowerCase().includes(q)));
    });

    async function pollNotifications() {
        try {
            const data = await api('notifications');
            if (!data.ok || !data.notifications || !data.notifications.length) return;
            const ids = [];
            for (const n of data.notifications) {
                ids.push(n.id);
                if (document.hidden && typeof Notification !== 'undefined' && Notification.permission === 'granted') {
                    const note = new Notification(n.title || 'WEB_Blogger', {
                        body: n.body || '', tag: 'chat-' + (n.conversation_id || n.id)
                    });
                    note.onclick = () => { window.focus(); if (n.conversation_id) openConversation(n.conversation_id); note.close(); };
                }
            }
            if (ids.length) await api('notifications_read', { body: { ids } });
        } catch (e) {}
    }
    async function ensureNotifyPermission() {
        if (typeof Notification === 'undefined') return;
        if (Notification.permission === 'default') {
            try { await Notification.requestPermission(); } catch (e) {}
        }
    }
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(window.CHAT_SW || '/sw.js').catch(() => {});
    }
    ensureNotifyPermission();
    setInterval(pollNotifications, 5000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) pollNotifications(); });

    loadConversations();
    setInterval(loadConversations, 10000);
})();
