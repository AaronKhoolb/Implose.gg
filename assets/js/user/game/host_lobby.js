/*
Programmer Name: Mr. Chong Ray Han
Program Name: /assets/js/user/game/host_lobby.js
Description: Host lobby logic (poll joined players, start the room, redirect to leaderboard)
First Written on: Thursday, 02-Jul-2026
Edited on: Thursday, 02-Jul-2026
*/

var room_code = window.ROOM_DATA.room_code;
var is_boss = window.ROOM_DATA.is_boss;
var status_url = '/Implose.gg-src/api/game/quiz_room/room_status.php?room_code=' + encodeURIComponent(room_code);
var redirected = false;

function render_players(list) {
    var grid = document.getElementById('players-grid');

    var players = [];
    for (var i = 0; i < list.length; i++) {
        if (!list[i].is_host) {
            players.push(list[i]);
        }
    }

    document.getElementById('waiting-count').textContent = players.length;

    if (players.length === 0) {
        grid.innerHTML = '<div class="players-empty">'
            + '<p>No players yet.</p>'
            + '<p class="hint">Share the code or QR to bring them in.</p>'
            + '</div>';
        return;
    }

    grid.innerHTML = '';
    for (var j = 0; j < players.length; j++) {
        var p = players[j];
        var card = document.createElement('div');
        card.className = 'pixel-panel player-card';
        card.innerHTML = '<img class="player-avatar" src="" alt=""><span class="player-name"></span>';
        card.querySelector('.player-avatar').src = p.avatar_path ? '/Implose.gg-src/' + p.avatar_path : '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';
        // textContent so usernames stay plain text
        card.querySelector('.player-name').textContent = p.display_name;
        grid.appendChild(card);
    }
}

function poll() {
    fetch(status_url)
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data || data.error) return;

            render_players(data.participants);

            // room started (host pressed start or auto-start fired)
            // boss rooms get the battle stage, normal quizzes the leaderboard
            if (!redirected && data.status === 'in_progress') {
                redirected = true;
                var watch_page = is_boss ? 'boss_host.php' : 'leaderboard.php';
                window.location.href = '/Implose.gg-src/pages/user/game/live_quiz/' + watch_page + '?room_code=' + encodeURIComponent(room_code);
            }
        })
        .catch(function () {});
}

document.getElementById('start-btn').addEventListener('click', function () {
    var btn = document.getElementById('start-btn');
    btn.disabled = true;
    btn.textContent = 'Starting...';

    var fd = new FormData();
    fd.append('room_code', room_code);

    fetch('/Implose.gg-src/actions/user/quiz_room/start_quiz_room.php', { method: 'POST', body: fd })
        .then(function (res) { return res.text(); })
        .then(function (txt) {
            if (txt.trim() !== 'ok') {
                alert(txt || 'Failed to start the room.');
                btn.disabled = false;
                btn.textContent = 'Start Game';
            }
            // next poll sees in_progress and redirects
        })
        .catch(function () {
            alert('Network error. Try again.');
            btn.disabled = false;
            btn.textContent = 'Start Game';
        });
});

poll();
setInterval(poll, 2000);
