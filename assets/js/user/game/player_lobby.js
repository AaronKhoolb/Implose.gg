/*
Programmer Name: Mr. Chong Ray Han
Program Name: /assets/js/user/game/player_lobby.js
Description: Player lobby logic (poll players + countdown, jump into the quiz on start)
First Written on: Thursday, 02-Jul-2026
Edited on: Thursday, 02-Jul-2026
*/

var room_code = window.ROOM_DATA.room_code;
var quiz_id = window.ROOM_DATA.quiz_id;
var status_url = '/Implose.gg-src/api/game/quiz_room/room_status.php?room_code=' + encodeURIComponent(room_code);
var redirected = false;

function render_players(list) {
    var wrap = document.getElementById('pl-players');

    var players = [];
    for (var i = 0; i < list.length; i++) {
        if (!list[i].is_host) {
            players.push(list[i]);
        }
    }

    document.getElementById('pl-count').textContent = players.length;

    wrap.innerHTML = '';
    for (var j = 0; j < players.length; j++) {
        var p = players[j];
        var card = document.createElement('div');
        card.className = 'pixel-panel pl-player';
        card.innerHTML = '<img class="pl-avatar" src="" alt=""><span class="pl-name"></span>';
        card.querySelector('.pl-avatar').src = p.avatar_path ? '/Implose.gg-src/' + p.avatar_path : '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';
        card.querySelector('.pl-name').textContent = p.display_name;
        wrap.appendChild(card);
    }
}

// seconds -> m:ss
function format_time(secs) {
    var m = Math.floor(secs / 60);
    var s = secs % 60;
    return m + ':' + (s < 10 ? '0' : '') + s;
}

function poll() {
    fetch(status_url)
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data || data.error) return;

            render_players(data.participants);
            document.getElementById('pl-timer').textContent = format_time(data.seconds_remaining);

            if (!redirected && data.status === 'in_progress') {
                redirected = true;
                document.getElementById('pl-status').textContent = 'Starting...';
                window.location.href = '/Implose.gg-src/pages/user/game/live_quiz/quiz.php'
                    + '?quiz_id=' + encodeURIComponent(quiz_id)
                    + '&room_code=' + encodeURIComponent(room_code);
            }
        })
        .catch(function () {});
}

poll();
setInterval(poll, 2000);
