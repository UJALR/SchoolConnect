(function(){
    const socketServerUrl = 'http://localhost:45000'; // change if different; server will try 45000-45010
    const currentUser = window.SC_CURRENT_USER || null;
    let socket = null;
    let currentChatUser = null;

    function initSocket() {
        socket = io(socketServerUrl, { query: { userId: currentUser } });

        socket.on('connect', () => {
            console.log('connected to chat server', socket.id);
        });

        socket.on('connect_error', (err) => {
            console.error('socket connect_error', err);
        });

        socket.on('disconnect', (reason) => {
            console.warn('socket disconnected', reason);
        });

        socket.on('private_message', (data) => {
            // data: { id, sender_id, recipient_id, message_text, sent_at }
            const senderId = String(data.sender_id || data.senderId || data.sender);
            appendMessageToWindow(data, (senderId === String(currentUser)) ? 'me' : 'them');
            // Update unread badge if message is for current user and not currently viewing that conversation
            try {
                const recipientId = String(data.recipient_id || data.recipientId || data.recipient);
                const senderId = String(data.sender_id || data.senderId || data.sender);
                if (recipientId === String(currentUser)) {
                    const badge = document.querySelector('.chat-link .friend-badge');
                    if (badge) {
                        const v = parseInt(badge.textContent || '0') || 0;
                        // if currently viewing this chat, we may have appended it already
                        if (String(currentChatUser) !== senderId) {
                            badge.textContent = v + 1;
                        }
                    }
                }
            } catch (e) {
                // ignore
            }
        });
    }

    function setChatEnabled(enabled) {
        const input = document.getElementById('chatInput');
        const sendBtn = document.getElementById('chatSend');
        if (input) input.disabled = !enabled;
        if (sendBtn) sendBtn.disabled = !enabled;
        if (enabled) {
            if (input) input.placeholder = 'Write a message...';
        } else {
            if (input) input.placeholder = 'Select a friend to start typing...';
        }
    }

    function appendMessageToWindow(m, who) {
        const ch = document.getElementById('chatMessages');
        if (!ch) return;
        const row = document.createElement('div');
        row.className = 'sc-message-row';
        row.style.display = 'flex';
        row.style.marginBottom = '8px';
        row.style.justifyContent = (who === 'me') ? 'flex-end' : 'flex-start';

        const bubble = document.createElement('div');
        bubble.className = (who === 'me') ? 'sc-bubble me' : 'sc-bubble them';
        bubble.style.maxWidth = '70%';
        bubble.style.padding = '8px 10px';
        bubble.style.borderRadius = '12px';
        bubble.style.background = (who === 'me') ? '#dcf8c6' : '#ffffff';
        bubble.style.boxShadow = '0 1px 0 rgba(0,0,0,0.04)';
        bubble.style.wordBreak = 'break-word';
        bubble.innerHTML = `<div class="sc-message-text">${escapeHtml(m.message_text)}</div><div style="font-size:0.75rem;color:#666;margin-top:6px;text-align:${who==='me'?'right':'left'}">${m.sent_at}</div>`;

        row.appendChild(bubble);
        ch.appendChild(row);
        ch.scrollTop = ch.scrollHeight;
    }

    function escapeHtml(text) {
        return (text+'').replace(/[&<>"']/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]); });
    }

    function loadConversation(otherId) {
        currentChatUser = otherId;
        document.getElementById('chatMessages').innerHTML = '';
        const url = `api/get_messages.php?other_id=${otherId}`;
        console.log('loadConversation', { otherId, url });
        fetch(url, { credentials: 'same-origin' })
            .then(r => {
                if (!r.ok) throw new Error('Network response not OK: ' + r.status);
                return r.text().then(text => {
                    try {
                        const data = JSON.parse(text);
                        return data;
                    } catch (e) {
                        console.error('Failed to parse JSON from', url, 'raw response:', text);
                        throw e;
                    }
                });
            })
            .then(data => {
                console.log('get_messages response', data && data.messages ? data.messages.length : 'no messages', data);
                if (data && data.messages) {
                    data.messages.forEach(m => appendMessageToWindow(m, (m.sender_id == currentUser || m.sender_id == String(currentUser)) ? 'me' : 'them'));
                    // refresh unread badge count after loading
                    updateUnreadBadge();
                }
            })
            .catch(err => {
                console.error('Failed to load conversation', err);
            });
    }

    function updateUnreadBadge() {
        fetch('api/get_unread_count.php')
            .then(r => r.json())
            .then(data => {
                const badge = document.querySelector('.chat-link .friend-badge');
                if (!badge) return;
                const cnt = (data && data.unread) ? parseInt(data.unread, 10) : 0;
                badge.textContent = cnt > 0 ? String(cnt) : '';
            }).catch(() => {});
    }

    function sendMessage(toId, text) {
        const payload = { to: toId, message: text };
        console.log('sendMessage()', { toId, text, socketConnected: !!(socket && socket.connected) });
        if (socket && socket.connected) {
            // send via socket; server will echo back the saved message
            console.log('emitting private_message via socket', payload);
            socket.emit('private_message', payload);
            return;
        }

        // fallback: optimistic append for sender when socket is unavailable
        appendMessageToWindow({ message_text: text, sent_at: new Date().toISOString() }, 'me');

        // fallback: send via PHP API to store message when socket not available
        fetch('api/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ recipient_id: toId, message_text: text })
        })
        .then(r => r.json())
        .then(data => {
            console.log('send_message.php response', data);
            if (!data || data.error) console.error('Send fallback error', data && data.error);
        }).catch(err => console.error('Send fallback network error', err));
    }

    function setupUI() {
        document.querySelectorAll('.chat-friend').forEach(el => {
            el.addEventListener('click', () => {
                const uid = el.getAttribute('data-user-id');
                const name = el.textContent.trim();
                console.log('chat-friend clicked', { uid, name });
                const header = document.getElementById('chatHeader');
                if (header) header.textContent = name;
                const toEl = document.getElementById('chatToId');
                if (toEl) toEl.value = uid;
                // set currentChatUser for fallback when sending
                currentChatUser = uid;
                // persist last selected friend so conversation can be restored after reload
                try { localStorage.setItem('sc_last_chat_user', String(uid)); } catch (e) {}
                // add selected class for visual feedback
                document.querySelectorAll('.chat-friend').forEach(x => x.classList && x.classList.remove('selected'));
                el.classList && el.classList.add('selected');
                // enable input and send button now that a friend is selected
                setChatEnabled(true);
                loadConversation(uid);
            });
        });

        const form = document.getElementById('chatForm');
        if (form) {
            form.addEventListener('submit', function(e){
                e.preventDefault();
                const toEl = document.getElementById('chatToId');
                const input = document.getElementById('chatInput');
                const to = toEl ? toEl.value : null;
                const text = input ? input.value.trim() : '';
                console.log('chatForm submit', { to, text });
                if (!to || !text) return;
                sendMessage(to, text);
                if (input) input.value = '';
            });
        }

        // bind send button click in all cases to be defensive
        const sendBtn = document.getElementById('chatSend');
        if (sendBtn) {
            sendBtn.addEventListener('click', function(e){
                e.preventDefault();
                const toEl = document.getElementById('chatToId');
                const input = document.getElementById('chatInput');
                // prefer explicit field, otherwise fall back to last selected friend
                let to = toEl ? toEl.value : null;
                if ((!to || to === '') && currentChatUser) {
                    to = currentChatUser;
                    if (toEl) toEl.value = to;
                }
                const text = input ? input.value.trim() : '';
                console.log('chatSend click', { to, text, currentChatUser });
                if (!to) {
                    console.warn('No recipient selected. Click a friend first.');
                    alert('Please select a friend from the left list before sending.');
                    return;
                }
                if (!text) return;
                sendMessage(to, text);
                if (input) input.value = '';
            });
        }

        // ensure unread badge is up-to-date
        updateUnreadBadge();
        // restore last conversation if present
        try {
            const last = localStorage.getItem('sc_last_chat_user');
            if (last) {
                // find friend element and simulate click
                const el = document.querySelector(`.chat-friend[data-user-id="${last}"]`);
                if (el) {
                    el.click();
                } else {
                    // if friend not visible (edge case), set hidden field and load
                    const toEl = document.getElementById('chatToId');
                    if (toEl) toEl.value = last;
                    currentChatUser = last;
                    setChatEnabled(true);
                    loadConversation(last);
                }
            }
        } catch (e) {}
    }

    // init
    if (!currentUser) {
        console.warn('No current user; chat disabled');
    } else {
        initSocket();
        setupUI();
    }
})();
