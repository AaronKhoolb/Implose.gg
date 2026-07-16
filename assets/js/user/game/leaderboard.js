/*
Programmer Name: Mr. Chong Ray Han
Program Name: /assets/js/user/game/leaderboard.js
Description: Live leaderboard logic (poll ranked scores, host can end the session)
First Written on: Thursday, 02-Jul-2026
Edited on: Thursday, 02-Jul-2026
*/

var room_code = window.ROOM_DATA.room_code;
var is_host = window.ROOM_DATA.is_host;
var course_id = window.ROOM_DATA.course_id;
var board_url = '/Implose.gg-src/api/game/quiz_room/room_leaderboard.php?room_code=' + encodeURIComponent(room_code);

function render_board(data) {
    var list = document.getElementById('lb-list');

    if (data.players.length === 0) {
        list.innerHTML = '<div class="pixel-panel lb-empty">No players in this room.</div>';
        return;
    }

    list.innerHTML = '';
    for (var i = 0; i < data.players.length; i++) {
        var p = data.players[i];
        var rank = i + 1;
        var row = document.createElement('div');
        row.className = 'pixel-panel lb-row' + (rank <= 3 ? ' rank-' + rank : '');
        row.innerHTML = '<span class="lb-rank">' + rank + '</span>'
            + '<img class="lb-avatar" src="" alt="">'
            + '<span class="lb-name"></span>'
            + '<span class="lb-progress"></span>'
            + '<span class="lb-pts"></span>';
        row.querySelector('.lb-avatar').src = p.avatar_path ? '/Implose.gg-src/' + p.avatar_path : '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';
        row.querySelector('.lb-name').textContent = p.name;
        row.querySelector('.lb-progress').textContent = p.correct + ' correct · ' + p.answered + '/' + data.total_questions + ' answered';
        row.querySelector('.lb-pts').textContent = p.points + ' pts';
        list.appendChild(row);
    }
}

function poll() {
    fetch(board_url)
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data || data.error) return;

            render_board(data);

            if (data.status === 'finished') {
                document.getElementById('lb-status').textContent = 'Session ended — final standings.';
            }
        })
        .catch(function () {});
}

if (is_host) {
    document.getElementById('end-btn').addEventListener('click', function () {
        var btn = document.getElementById('end-btn');
        btn.disabled = true;
        btn.textContent = 'Ending...';

        var fd = new FormData();
        fd.append('room_code', room_code);

        fetch('/Implose.gg-src/actions/user/quiz_room/end_quiz_room.php', { method: 'POST', body: fd })
            .then(function (res) { return res.text(); })
            .then(function (txt) {
                if (txt.trim() === 'ok') {
                    window.location.href = '/Implose.gg-src/pages/user/game/view_course.php?course_id=' + course_id;
                } else {
                    alert(txt || 'Failed to end the session.');
                    btn.disabled = false;
                    btn.textContent = 'End Session';
                }
            })
            .catch(function () {
                alert('Network error. Try again.');
                btn.disabled = false;
                btn.textContent = 'End Session';
            });
    });
}

poll();
setInterval(poll, 2000);
