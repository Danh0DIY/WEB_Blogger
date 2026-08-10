/**
 * WEB_Blogger Service Worker
 */
self.addEventListener('install', (e) => {
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
  let data = { title: 'WEB_Blogger', body: 'Tin nhắn mới', url: '/chat/' };
  try {
    if (event.data) {
      const j = event.data.json();
      data = { ...data, ...j };
    }
  } catch (err) {
    try {
      data.body = event.data ? event.data.text() : data.body;
    } catch (e2) {}
  }
  event.waitUntil(
    self.registration.showNotification(data.title || 'WEB_Blogger', {
      body: data.body || '',
      data: { url: data.url || '/chat/', conversation_id: data.conversation_id },
      tag: 'chat-' + (data.conversation_id || 'general'),
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/chat/';
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
      for (const c of clients) {
        if (c.url.includes('/chat') && 'focus' in c) {
          c.focus();
          return;
        }
      }
      if (self.clients.openWindow) {
        return self.clients.openWindow(url);
      }
    })
  );
});
