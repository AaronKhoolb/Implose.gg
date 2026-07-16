/*
Program Name: /assets/js/user/room_lobby.js
Description : Lobby behavior: poll room_status, refresh participant
              list, update countdown, redirect to start_quiz.php
              when the room flips to in_progress. Host's Start
              button POSTs to start_quiz_room.php.
First Written on: Tuesday, 30-Jun-2026
*/

(function () {
    const ctx = window.ROOM_CONTEXT || {};
    if (!ctx.roomCode) return;

    const STATUS_URL = '/Implose.gg-src/api/game/quiz_room/room_status.php?room_code=' + encodeURIComponent(ctx.roomCode);
    const START_URL  = '/Implose.gg-src/actions/user/quiz_room/start_quiz_room.php';
    const POLL_MS    = 2000;

    const $countdown    = document.getElementById('lobby-countdown');
    const $timerSub     = document.getElementById('lobby-timer-sub');
    const $list         = document.getElementById('lobby-participant-list');
    const $count        = document.getElementById('lobby-count');
    const $startBtn     = document.getElementById('lobby-start-btn');
    const $copyBtn      = document.getElementById('lobby-copy-btn');
    const $codeValue    = document.getElementById('lobby-code-value');

    let redirected = false;

    if ($copyBtn && $codeValue) {
        $copyBtn.addEventListener('click', function () {
            const code = $codeValue.textContent.trim();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(function () {
                    $copyBtn.textContent = 'Copied!';
                    setTimeout(function () { $copyBtn.textContent = 'Copy Code'; }, 1500);
                });
            } else {
                const ta = document.createElement('textarea');
                ta.value = code;
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); } catch (e) { }
                document.body.removeChild(ta);
                $copyBtn.textContent = 'Copied!';
                setTimeout(function () { $copyBtn.textContent = 'Copy Code'; }, 1500);
            }
        });
    }

    if ($startBtn) {
        $startBtn.addEventListener('click', function () {
            $startBtn.disabled = true;
            $startBtn.textContent = 'Starting...';

            const fd = new FormData();
            fd.append('room_code', ctx.roomCode);

            fetch(START_URL, { method: 'POST', body: fd })
                .then(function (res) { return res.text(); })
                .then(function (txt) {
                    if (txt.trim() !== 'ok') {
                        alert(txt || 'Failed to start the room.');
                        $startBtn.disabled = false;
                        $startBtn.textContent = 'Start Quiz Now';
                    }
                    // Next poll will redirect everyone.
                })
                .catch(function () {
                    alert('Network error. Try again.');
                    $startBtn.disabled = false;
                    $startBtn.textContent = 'Start Quiz Now';
                });
        });
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderParticipants(list) {
        if (!Array.isArray(list) || list.length === 0) {
            $list.innerHTML = '<li class="lobby-empty">No players yet — share the code!</li>';
            $count.textContent = '(0)';
            return;
        }

        $count.textContent = '(' + list.length + ')';
        let html = '';
        list.forEach(function (p) {
            const cls = p.is_host ? 'is-host' : '';
            const tag = p.is_host ? '<span class="lobby-host-tag">HOST</span>' : '';
            html += '<li class="' + cls + '">'
                  +     '<span>' + escapeHtml(p.display_name) + '</span>'
                  +     tag
                  +  '</li>';
        });
        $list.innerHTML = html;
    }

    function renderCountdown(seconds, status) {
        if (status !== 'waiting') {
            $countdown.textContent = '0';
            $timerSub.textContent = 'Launching...';
            $countdown.classList.add('is-warning');
            return;
        }

        $countdown.textContent = String(seconds);
        $timerSub.textContent = seconds === 1 ? 'second' : 'seconds';

        if (seconds <= 10) {
            $countdown.classList.add('is-warning');
        } else {
            $countdown.classList.remove('is-warning');
        }
    }

    function poll() {
        fetch(STATUS_URL, { credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) throw new Error('bad_status');
                return res.json();
            })
            .then(function (data) {
                if (!data || data.error) return;

                renderParticipants(data.participants);
                renderCountdown(data.seconds_remaining, data.status);

                if (!redirected && (data.status === 'in_progress' || data.status === 'finished')) {
                    redirected = true;
                    window.location.href =
                        '/Implose.gg-src/pages/user/game/live_quiz/quiz.php'
                        + '?quiz_id='   + encodeURIComponent(data.quiz_id)
                        + '&room_code=' + encodeURIComponent(ctx.roomCode);
                }
            })
            .catch(function () { /* swallow; next poll will retry */ });
    }

    poll();
    setInterval(poll, POLL_MS);
})();
