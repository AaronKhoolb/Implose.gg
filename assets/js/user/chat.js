/*
Programmer Name: Max
Program Name: /assets/js/user/chat.js
Description: Chat drawer - open/close with chat button and < button, poll for new messages every 3s, send on form submit.
First Written on: Wednesday, 1-Jul-2026
Edited on: Wednesday, 1-Jul-2026
*/


const currentUserId = window.CHAT_CONTEXT.currentUserId;

const getUrl = '/Implose.gg-src/api/game/chat/get_messages.php';
const sendUrl = '/Implose.gg-src/actions/user/chat/send_message.php';
const pollMs = 3000;

const openBtn = document.getElementById('chat-open-btn');
const closeBtn = document.getElementById('chat-close-btn');
const backdrop = document.getElementById('chat-backdrop');
const drawer = document.getElementById('chat-drawer');
const messagesBox = document.getElementById('chat-messages');
const form = document.getElementById('chat-form');
const input = document.getElementById('chat-input');

let pollTimer = null;


// open the drawer
function openChat() {
    drawer.classList.add('is-open');
    backdrop.classList.add('is-open');
    loadMessages();
    pollTimer = setInterval(loadMessages, pollMs);
    input.focus();
}


// close the drawer
function closeChat() {
    drawer.classList.remove('is-open');
    backdrop.classList.remove('is-open');
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}


openBtn.addEventListener('click', openChat);
closeBtn.addEventListener('click', closeChat);
backdrop.addEventListener('click', closeChat);

// close with the ESC key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
        closeChat();
    }
});


function escapeHtml(text) {
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}


// format a datetime string into a short time like "2:15 PM"
function formatTime(dateStr) {
    const d = new Date(dateStr.replace(' ', 'T'));
    let h = d.getHours();
    const m = d.getMinutes();
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12;
    if (h === 0) h = 12;
    const mm = m < 10 ? '0' + m : m;
    return h + ':' + mm + ' ' + ampm;
}


// fetch messages from the server and render them
function loadMessages() {
    fetch(getUrl).then(function (res) {
        return res.json();
    }).then(function (data) {

        if (!data || data.error) return;
        if (!data.messages || data.messages.length === 0) {
            messagesBox.innerHTML = '<p class="chat-empty">No messages yet - be the first to say hi!</p>';
            return;
        }

        // check if scrolled near the bottom before we re-render
        const nearBottom = (messagesBox.scrollHeight - messagesBox.scrollTop - messagesBox.clientHeight) < 60;

        let html = '';
        data.messages.forEach(function (m) {
            let cls = 'chat-message';
            if (m.sender_id == currentUserId) cls += ' is-own';
            if (m.is_deleted == 1) cls += ' is-deleted';

            let text = m.message_text;
            if (m.is_deleted == 1) text = '[Message deleted]';

            html += '<div class="' + cls + '">';
            html += '<div class="chat-message-top" style="align-items: flex-start;">';
            html += '<span class="chat-message-name">' + escapeHtml(m.display_name) + '</span>';
            if (m.sender_id != currentUserId && m.is_deleted == 0) {
                html += '<div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">';
                html += '<span class="chat-message-time" style="line-height: 1;">' + formatTime(m.created_at) + '</span>';
                html += '<button type="button" class="chat-report-btn" data-id="' + m.message_id + '" style="background: none; border: none; color: #ff4c4c; font-size: 10px; cursor: pointer; text-decoration: underline; padding: 0; line-height: 1;">Report</button>';
                html += '</div>';
            } else {
                html += '<span class="chat-message-time">' + formatTime(m.created_at) + '</span>';
            }
            html += '</div>';
            html += '<p class="chat-message-text">' + escapeHtml(text) + '</p>';
            html += '</div>';
        });
        messagesBox.innerHTML = html;

        // keep the user pinned to the bottom if they were already there
        if (nearBottom) {
            messagesBox.scrollTop = messagesBox.scrollHeight;
        }
    }).catch(function () {});
}


// send a new message
form.addEventListener('submit', function (e) {
    e.preventDefault();

    const text = input.value.trim();
    if (text === '') return;

    const fd = new FormData();
    fd.append('message_text', text);

    fetch(sendUrl, {
        method: 'POST',
        body: fd
    }).then(function (res) {
        return res.text();
    }).then(function (reply) {
        if (reply.trim() === 'ok') {
            input.value = '';
            loadMessages();
            // scroll to the newest message
            setTimeout(function () {
                messagesBox.scrollTop = messagesBox.scrollHeight;
            }, 100);
        } else {
            alert(reply);
        }
    }).catch(function () {
        alert('Could not send. Please try again.');
    });
});

// --- Report Message Logic ---
const reportModal = document.getElementById('chat-report-modal');
const reportForm = document.getElementById('chat-report-form');
const reportInputId = document.getElementById('report-message-id');
const reportCancel = document.getElementById('chat-report-cancel');

// listen for clicks on report buttons
messagesBox.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('chat-report-btn')) {
        const msgId = e.target.getAttribute('data-id');
        reportInputId.value = msgId;
        reportModal.style.display = 'flex';
        // Need to add is-open because it uses the chat-backdrop class which has opacity 0 by default
        reportModal.classList.add('is-open');
    }
});

// close report modal
reportCancel.addEventListener('click', function () {
    reportModal.style.display = 'none';
    reportModal.classList.remove('is-open');
    reportForm.reset();
});

// submit report form
reportForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(reportForm);
    
    fetch('/Implose.gg-src/actions/user/chat/report_message.php', {
        method: 'POST',
        body: fd
    }).then(function (res) {
        return res.text();
    }).then(function (reply) {
        if (reply.trim() === 'ok') {
            alert('Message reported successfully.');
            reportModal.style.display = 'none';
            reportModal.classList.remove('is-open');
            reportForm.reset();
        } else {
            alert(reply);
        }
    }).catch(function () {
        alert('Could not report. Please try again.');
    });
});
