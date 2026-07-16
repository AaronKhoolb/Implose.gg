/*
Programmer Name: Mr. Chong Ray Han
Program Name: /assets/js/user/game/boss_host.js
Description: Boss battle host stage logic (poll the room, animate hits and
             heart losses, live leaderboard, victory / defeat cinematics)
First Written on: Monday, 07-Jul-2026
Edited on: Monday, 07-Jul-2026
*/

var room_code = window.ROOM_DATA.room_code;
var quiz_id = window.ROOM_DATA.quiz_id;
var course_id = window.ROOM_DATA.course_id;
var status_url = '/Implose.gg-src/api/game/quiz_room/boss_status.php?room_code=' + encodeURIComponent(room_code);

var players = {};
var player_order = [];
var hearts_max = 3;
var hp_max = 1;
var shown_hp = null;
var seconds = 0;
var over = false;
var built = false;

var boss_wrap = document.getElementById('boss-wrap');

function avatar_src(p) {
    return p.avatar_path ? '/Implose.gg-src/' + p.avatar_path : '/Implose.gg-src/assets/images/avatar_test/avatar_robot.png';
}

/* retro synth sfx, webaudio so no sound files needed */

var audio_ctx = null;

function blip(freq_from, freq_to, dur, type, vol, when) {
    try {
        if (!audio_ctx) audio_ctx = new (window.AudioContext || window.webkitAudioContext)();
        if (audio_ctx.state === 'suspended') audio_ctx.resume();

        var t = audio_ctx.currentTime + (when || 0);
        var osc = audio_ctx.createOscillator();
        var gain = audio_ctx.createGain();

        osc.type = type || 'square';
        osc.frequency.setValueAtTime(freq_from, t);
        osc.frequency.exponentialRampToValueAtTime(Math.max(freq_to, 1), t + dur);
        gain.gain.setValueAtTime(vol || 0.08, t);
        gain.gain.exponentialRampToValueAtTime(0.001, t + dur);
        osc.connect(gain);
        gain.connect(audio_ctx.destination);
        osc.start(t);
        osc.stop(t + dur + 0.02);
    } catch (e) {
        // no sound is fine
    }
}

function sfx_hit() {
    blip(700, 180, 0.12, 'square', 0.08);
    blip(1100, 300, 0.09, 'square', 0.05, 0.02);
}

function sfx_hurt() {
    blip(300, 70, 0.3, 'sawtooth', 0.09);
}

function sfx_ko() {
    blip(220, 40, 0.55, 'sawtooth', 0.1);
    blip(160, 30, 0.6, 'square', 0.07, 0.06);
}

function sfx_final_blow() {
    blip(200, 40, 0.5, 'sawtooth', 0.11);
    blip(900, 120, 0.35, 'square', 0.08, 0.05);
}

function sfx_fanfare() {
    var notes = [523, 659, 784, 1047];
    for (var i = 0; i < notes.length; i++) {
        blip(notes[i], notes[i], 0.22, 'square', 0.09, i * 0.14);
    }
    blip(1319, 1319, 0.5, 'square', 0.09, 0.58);
}

function sfx_defeat() {
    var notes = [392, 330, 262, 196];
    for (var i = 0; i < notes.length; i++) {
        blip(notes[i], notes[i] * 0.96, 0.3, 'triangle', 0.1, i * 0.24);
    }
}

/* party cards */

function build_party(list) {
    var row = document.getElementById('party-row');
    row.innerHTML = '';
    players = {};
    player_order = [];

    for (var i = 0; i < list.length; i++) {
        var p = list[i];
        players[p.user_id] = p;
        player_order.push(p.user_id);

        var card = document.createElement('div');
        card.className = 'pcard';
        card.id = 'pcard-' + p.user_id;
        card.innerHTML = '<span class="flame-tag">ON FIRE!</span>'
            + '<span class="ko-tag">KO</span>'
            + '<span class="score-pop" id="spop-' + p.user_id + '"></span>'
            + '<img class="sprite" src="" alt="">'
            + '<span class="pname"></span>'
            + '<div class="phearts" id="ph-' + p.user_id + '"></div>'
            + '<span class="pscore" id="ps-' + p.user_id + '">0</span>';
        // textContent so usernames stay plain text
        card.querySelector('.sprite').src = avatar_src(p);
        card.querySelector('.pname').textContent = p.name;
        row.appendChild(card);

        paint_player(p);
    }
}

function paint_player(p) {
    var card = document.getElementById('pcard-' + p.user_id);
    if (!card) return;

    var hearts = document.getElementById('ph-' + p.user_id);
    hearts.innerHTML = '';
    for (var n = 0; n < hearts_max; n++) {
        var h = document.createElement('span');
        h.textContent = '♥';
        if (n >= p.hearts) h.className = 'lost';
        hearts.appendChild(h);
    }

    document.getElementById('ps-' + p.user_id).textContent = p.score;
    card.classList.toggle('ko', p.ko);
    card.classList.toggle('streaking', !p.ko && p.streak >= 3);
}

/* boss */

function paint_boss() {
    document.getElementById('boss-hp-fill').style.width = (shown_hp / hp_max * 100) + '%';
    document.getElementById('boss-hp-num').textContent = 'HP ' + shown_hp + '/' + hp_max;
}

/* leaderboard, rows slide when the order changes */

function paint_leaderboard() {
    var list = document.getElementById('lb-list');

    var before = {};
    var rows = list.children;
    for (var i = 0; i < rows.length; i++) {
        before[rows[i].getAttribute('data-pid')] = rows[i].getBoundingClientRect().top;
    }

    var sorted = [];
    for (var j = 0; j < player_order.length; j++) {
        sorted.push(players[player_order[j]]);
    }
    sorted.sort(function (a, b) { return b.score - a.score; });

    list.innerHTML = '';
    var alive = 0;

    for (var k = 0; k < sorted.length; k++) {
        var p = sorted[k];
        if (!p.ko) alive++;

        var row = document.createElement('div');
        row.className = 'lb-row' + (k === 0 && p.score > 0 ? ' first' : '') + (p.ko ? ' ko-row' : '');
        row.setAttribute('data-pid', p.user_id);
        row.innerHTML = '<span class="rank">' + (k + 1) + '</span>'
            + '<img src="" alt="">'
            + '<span class="n"></span>'
            + '<span class="s"></span>';
        row.querySelector('img').src = avatar_src(p);
        row.querySelector('.n').textContent = p.name;
        row.querySelector('.s').textContent = p.score;
        list.appendChild(row);
    }

    rows = list.children;
    for (var m = 0; m < rows.length; m++) {
        var prev = before[rows[m].getAttribute('data-pid')];
        if (prev === undefined) continue;
        var d = prev - rows[m].getBoundingClientRect().top;
        if (d) {
            slide_row(rows[m], d);
        }
    }

    document.getElementById('sb-alive').textContent = alive + ' fighting';
}

function slide_row(row, d) {
    row.style.transition = 'none';
    row.style.transform = 'translateY(' + d + 'px)';
    requestAnimationFrame(function () {
        row.style.transition = '';
        row.style.transform = '';
    });
}

/* battle effects */

function shake_screen() {
    document.body.classList.remove('shaking');
    void document.body.offsetWidth;
    document.body.classList.add('shaking');
    setTimeout(function () { document.body.classList.remove('shaking'); }, 450);
}

function flash_vignette() {
    var v = document.getElementById('fx-vignette');
    v.classList.remove('hit');
    void v.offsetWidth;
    v.classList.add('hit');
}

// damage number floating off the boss
function float_dmg(text) {
    var box = boss_wrap.getBoundingClientRect();
    var el = document.createElement('span');
    el.className = 'float-dmg';
    el.textContent = text;
    el.style.left = (box.left + box.width * (0.25 + Math.random() * 0.5)) + 'px';
    el.style.top = (box.top + box.height * 0.15) + 'px';
    document.body.appendChild(el);
    setTimeout(function () { el.remove(); }, 950);
}

// avatar clone flies from the card to the boss
function launch_projectile(p, on_arrive) {
    var card = document.getElementById('pcard-' + p.user_id);
    var img = card ? card.querySelector('.sprite') : null;
    if (!img) {
        on_arrive();
        return;
    }

    var from = img.getBoundingClientRect();
    var to = boss_wrap.getBoundingClientRect();

    var clone = document.createElement('img');
    clone.src = avatar_src(p);
    clone.className = 'projectile';
    clone.style.left = from.left + 'px';
    clone.style.top = from.top + 'px';
    clone.style.width = from.width + 'px';
    clone.style.height = from.height + 'px';
    document.body.appendChild(clone);
    card.classList.add('attacking');

    var dx = (to.left + to.width / 2) - (from.left + from.width / 2);
    var dy = (to.top + to.height * 0.62) - (from.top + from.height / 2);
    requestAnimationFrame(function () {
        clone.style.transform = 'translate(' + dx + 'px,' + dy + 'px) scale(1.25) rotate(-8deg)';
    });

    setTimeout(function () {
        clone.remove();
        card.classList.remove('attacking');
        on_arrive();
    }, 390);
}

function boss_lunge_at(p) {
    var card = document.getElementById('pcard-' + p.user_id);
    if (card) {
        var to = card.getBoundingClientRect();
        var from = boss_wrap.getBoundingClientRect();
        var dx = ((to.left + to.width / 2) - (from.left + from.width / 2)) * 0.22;
        var dy = ((to.top) - (from.top + from.height)) * 0.3 + 30;
        boss_wrap.style.setProperty('--dx', dx + 'px');
        boss_wrap.style.setProperty('--dy', dy + 'px');
    }
    boss_wrap.classList.remove('lunge');
    void boss_wrap.offsetWidth;
    boss_wrap.classList.add('lunge');
}

function score_pop(p, pts) {
    var el = document.getElementById('spop-' + p.user_id);
    if (!el) return;
    el.textContent = '+' + pts;
    el.classList.remove('pop');
    void el.offsetWidth;
    el.classList.add('pop');
}

/* pixel particles for the endings */

function burst_particles(x, y, colors, count, gravity) {
    var parts = [];
    for (var i = 0; i < count; i++) {
        var el = document.createElement('div');
        el.className = 'particle';
        el.style.background = colors[i % colors.length];
        el.style.left = x + 'px';
        el.style.top = y + 'px';
        var ang = Math.random() * Math.PI * 2;
        var speed = 3 + Math.random() * 7;
        parts.push({ el: el, vx: Math.cos(ang) * speed, vy: Math.sin(ang) * speed - 4, x: x, y: y, life: 1 });
        document.body.appendChild(el);
    }

    var g = gravity === undefined ? 0.28 : gravity;

    function step() {
        var alive = false;
        for (var i = 0; i < parts.length; i++) {
            var pt = parts[i];
            if (pt.life <= 0) continue;
            pt.vy += g;
            pt.x += pt.vx;
            pt.y += pt.vy;
            pt.life -= 0.016;
            pt.el.style.transform = 'translate(' + (pt.x - x) + 'px,' + (pt.y - y) + 'px)';
            pt.el.style.opacity = Math.max(pt.life, 0);
            if (pt.life > 0) {
                alive = true;
            } else {
                pt.el.remove();
            }
        }
        if (alive) requestAnimationFrame(step);
    }
    step();
}

function confetti_rain() {
    var colors = ['#f3b51f', '#00d4ff', '#00f5c8', '#ef3e12', '#2a6fdb'];
    for (var w = 0; w < 5; w++) {
        setTimeout(function () {
            for (var i = 0; i < 14; i++) {
                var c = colors[Math.floor(Math.random() * colors.length)];
                burst_particles(Math.random() * window.innerWidth, -10, [c], 1, 0.12);
            }
        }, w * 350);
    }
}

/* session clock, resynced from the server every poll */

setInterval(function () {
    if (over || !built) return;
    seconds++;
    var m = String(Math.floor(seconds / 60)).padStart(2, '0');
    var s = String(seconds % 60).padStart(2, '0');
    document.getElementById('session-time').textContent = m + ':' + s;
}, 1000);

/* endings */

function best_by(field) {
    var best = null;
    for (var i = 0; i < player_order.length; i++) {
        var p = players[player_order[i]];
        if (!best || p[field] > best[field] || (p[field] === best[field] && p.score > best.score)) {
            best = p;
        }
    }
    return best;
}

function show_victory() {
    over = true;
    var box = boss_wrap.getBoundingClientRect();
    boss_wrap.classList.add('dying');
    burst_particles(box.left + box.width / 2, box.top + box.height / 2, ['#ef3e12', '#f3b51f', '#fff', '#303847'], 46);
    sfx_fanfare();

    var mvp = best_by('score');
    if (mvp) {
        document.getElementById('mvp-img').src = avatar_src(mvp);
        document.getElementById('mvp-name').textContent = mvp.name;
        document.getElementById('mvp-score').textContent = mvp.score;
        document.getElementById('mvp-dmg').textContent = mvp.dmg;
    }

    setTimeout(function () {
        document.getElementById('victory-cine').classList.add('show');
        confetti_rain();
    }, 1300);
}

function show_defeat(boss_hp) {
    over = true;
    boss_wrap.classList.add('gloat');
    sfx_defeat();

    document.getElementById('d-boss-hp').textContent = boss_hp;

    var top = best_by('dmg');
    if (top) {
        document.getElementById('dmvp-img').src = avatar_src(top);
        document.getElementById('dmvp-name').textContent = top.name;
        document.getElementById('dmvp-dmg').textContent = top.dmg;
        document.getElementById('dmvp-score').textContent = top.score;
    }

    setTimeout(function () {
        document.getElementById('defeat-cine').classList.add('show');
    }, 1200);
}

/* poll loop */

function apply_status(data) {
    if (over) return;

    hearts_max = data.hearts_max;
    hp_max = data.hp_max;
    seconds = data.elapsed;

    if (!built) {
        built = true;
        shown_hp = data.boss_hp;
        build_party(data.players);
        paint_boss();
        paint_leaderboard();
    } else {
        var delay = 0;

        for (var i = 0; i < data.players.length; i++) {
            var fresh = data.players[i];
            var old = players[fresh.user_id];

            if (!old) {
                // somebody new somehow, rebuild everything
                build_party(data.players);
                break;
            }

            var dmg_delta = fresh.dmg - old.dmg;
            var pts_delta = fresh.score - old.score;
            var hearts_delta = old.hearts - fresh.hearts;
            var got_ko = fresh.ko && !old.ko;

            players[fresh.user_id] = fresh;

            if (dmg_delta > 0) {
                animate_hit(fresh, dmg_delta, pts_delta, delay);
                delay += 200;
            }
            if (hearts_delta > 0) {
                animate_hurt(fresh, got_ko, delay);
                delay += 200;
            }

            paint_player(fresh);
        }

        // settle on the server hp after the hits have flown in
        setTimeout(function () {
            shown_hp = data.boss_hp;
            paint_boss();
        }, delay + 400);

        paint_leaderboard();
    }

    if (data.outcome === 'victory') {
        shown_hp = 0;
        paint_boss();
        show_victory();
    } else if (data.outcome === 'defeat') {
        show_defeat(data.boss_hp);
    }
}

function animate_hit(p, dmg, pts, delay) {
    setTimeout(function () {
        launch_projectile(p, function () {
            boss_wrap.classList.remove('hit');
            void boss_wrap.offsetWidth;
            boss_wrap.classList.add('hit');
            float_dmg('-' + dmg);
            if (pts > 0) score_pop(p, pts);
            shown_hp = Math.max(shown_hp - dmg, 0);
            paint_boss();
            if (shown_hp <= 0) {
                sfx_final_blow();
            } else {
                sfx_hit();
            }
        });
    }, delay);
}

function animate_hurt(p, got_ko, delay) {
    setTimeout(function () {
        boss_lunge_at(p);
        var card = document.getElementById('pcard-' + p.user_id);
        if (card) {
            card.classList.remove('hurt');
            void card.offsetWidth;
            card.classList.add('hurt');
        }
        shake_screen();
        flash_vignette();
        if (got_ko) {
            sfx_ko();
        } else {
            sfx_hurt();
        }
    }, delay + 220);
}

function poll() {
    fetch(status_url)
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data || data.error) return;
            apply_status(data);
        })
        .catch(function () {});
}

/* buttons */

function end_room(then) {
    var fd = new FormData();
    fd.append('room_code', room_code);

    fetch('/Implose.gg-src/actions/user/quiz_room/end_quiz_room.php', { method: 'POST', body: fd })
        .then(function (res) { return res.text(); })
        .then(function (txt) {
            if (txt.trim() === 'ok') {
                then();
            } else {
                alert(txt || 'Failed to end the session.');
            }
        })
        .catch(function () {
            alert('Network error. Try again.');
        });
}

document.getElementById('end-btn').addEventListener('click', function () {
    end_room(function () {
        window.location.href = '/Implose.gg-src/pages/user/game/view_course.php?course_id=' + course_id;
    });
});

document.getElementById('v-standings-btn').addEventListener('click', function () {
    window.location.href = '/Implose.gg-src/pages/user/game/live_quiz/leaderboard.php?room_code=' + encodeURIComponent(room_code);
});

document.getElementById('d-standings-btn').addEventListener('click', function () {
    window.location.href = '/Implose.gg-src/pages/user/game/live_quiz/leaderboard.php?room_code=' + encodeURIComponent(room_code);
});

// a rematch is just a fresh lobby for the same quiz
document.getElementById('v-reset-btn').addEventListener('click', function () {
    end_room(function () {
        window.location.href = '/Implose.gg-src/pages/user/game/live_quiz/host.php?quiz_id=' + quiz_id;
    });
});

document.getElementById('d-reset-btn').addEventListener('click', function () {
    end_room(function () {
        window.location.href = '/Implose.gg-src/pages/user/game/live_quiz/host.php?quiz_id=' + quiz_id;
    });
});

poll();
setInterval(poll, 2000);
