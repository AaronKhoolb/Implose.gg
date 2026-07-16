/*
Programmer Name: Mr. Chong Ray Han
Program Name: /assets/js/user/game/boss_battle.js
Description: Boss battle play logic (vs intro, battle with hearts and boss hp,
             victory / defeat endings, party mode polling in live rooms)
First Written on: Monday, 07-Jul-2026
Edited on: Monday, 07-Jul-2026
*/

(function () {
    const QUESTIONS = window.BOSS_DATA.questions;
    const QUIZ_ID = window.BOSS_DATA.quiz_id;
    const COURSE_ID = window.BOSS_DATA.course_id;
    const ROOM_CODE = window.BOSS_DATA.room_code;

    const KEYS = ['a', 'b', 'c', 'd'];
    const SHAPES = { a: 'tri', b: 'dia', c: 'cir', d: 'sq' };
    const $ = (id) => document.getElementById(id);

    const HEARTS_MAX = 3;
    const CLEAR_BONUS = 500;
    const FINAL_BLOW_BONUS = 150;

    const state = {
        index: 0,
        score: 0,
        streak: 0,
        bestStreak: 0,
        hearts: HEARTS_MAX,
        // solo: you can only afford 2 misses, so the boss needs the rest
        // live: placeholder until the first room poll brings the shared pool
        bossHpMax: ROOM_CODE ? QUESTIONS.length : Math.max(QUESTIONS.length - 2, 1),
        bossHp: ROOM_CODE ? QUESTIONS.length : Math.max(QUESTIONS.length - 2, 1),
        synced: false,
        locked: false,
        spectator: false,
        results: [],
        dmg: 0,
        over: false,
        waiting: false,
        timeLeft: 0,
        totalTime: 0,
        timerId: null,
        pollId: null,
    };

    $('q-total').textContent = QUESTIONS.length;

    /* painters */

    function paintHearts() {
        const spots = ['intro-hearts', 'hud-hearts', 'top-hearts'];
        for (let i = 0; i < spots.length; i++) {
            const el = $(spots[i]);
            el.innerHTML = '';
            for (let n = 0; n < HEARTS_MAX; n++) {
                const h = document.createElement('span');
                h.className = 'heart' + (n < state.hearts ? '' : ' lost');
                h.textContent = '♥';
                el.appendChild(h);
            }
        }
    }

    function breakHeart() {
        const spots = ['hud-hearts', 'top-hearts'];
        for (let i = 0; i < spots.length; i++) {
            // first lost heart sits right at the new count
            const h = $(spots[i]).children[state.hearts];
            if (h) h.classList.add('lost', 'breaking');
        }
    }

    function paintBossHp() {
        const bar = $('boss-hp');
        bar.innerHTML = '';
        for (let n = 0; n < state.bossHpMax; n++) {
            const seg = document.createElement('i');
            if (n >= state.bossHp) seg.className = 'empty';
            bar.appendChild(seg);
        }
        $('boss-hp-label').textContent = 'HP ' + state.bossHp + '/' + state.bossHpMax;

        const intro = $('intro-hp');
        intro.innerHTML = '';
        for (let n = 0; n < Math.min(state.bossHpMax, 12); n++) {
            intro.appendChild(document.createElement('i'));
        }
    }

    function buildSegs() {
        for (let i = 0; i < QUESTIONS.length; i++) {
            const seg = document.createElement('div');
            seg.className = 'seg';
            $('seg-track').appendChild(seg);
        }
    }

    function paintSegs() {
        const segs = $('seg-track').children;
        for (let i = 0; i < segs.length; i++) {
            segs[i].className = 'seg';
            if (i < state.index) {
                segs[i].classList.add('done');
            } else if (i === state.index) {
                segs[i].classList.add('current');
            }
        }
    }

    function paintStreak() {
        if (state.streak >= 2) {
            $('streak-n').textContent = state.streak;
            $('streak-tag').classList.add('show');
        } else {
            $('streak-tag').classList.remove('show');
        }
    }

    // quick count up animation for the score displays
    function countUp(el, target) {
        const from = parseInt(el.textContent, 10) || 0;
        const steps = 15;
        let step = 0;
        const timer = setInterval(() => {
            step++;
            el.textContent = Math.round(from + (target - from) * step / steps);
            if (step >= steps) clearInterval(timer);
        }, 30);
    }

    /* battle effects */

    function shakeScreen() {
        document.body.classList.remove('shaking');
        void document.body.offsetWidth;
        document.body.classList.add('shaking');
        setTimeout(() => document.body.classList.remove('shaking'), 450);
    }

    function flashVignette() {
        const v = $('fx-vignette');
        v.classList.remove('hit');
        void v.offsetWidth;
        v.classList.add('hit');
    }

    function bossTakesHit(finalBlow) {
        const sprite = $('boss-sprite');
        sprite.classList.remove('hit');
        void sprite.offsetWidth;
        sprite.classList.add('hit');

        const pop = $('dmg-pop');
        pop.textContent = finalBlow ? 'K.O.!' : 'HIT!';
        pop.classList.remove('pop');
        void pop.offsetWidth;
        pop.classList.add('pop');
    }

    function bossAttacks() {
        const sprite = $('boss-sprite');
        sprite.classList.remove('lunge');
        void sprite.offsetWidth;
        sprite.classList.add('lunge');
        shakeScreen();
        flashVignette();
    }

    /* questions */

    function renderQuestion() {
        const q = QUESTIONS[state.index];
        state.locked = false;

        $('q-now').textContent = state.index + 1;
        $('q-topic').textContent = q.topic;
        $('q-marks').textContent = q.marks * 2;
        $('q-text').textContent = q.text;
        $('q-kicker').textContent = q.type === 'single_choice' ? 'SINGLE CHOICE - PICK ONE' : 'TYPE YOUR ANSWER';
        paintSegs();

        // reset the action row
        $('fb').className = 'fb';
        $('act-spacer').style.display = 'block';
        $('next-btn').classList.add('is-disabled');
        if (q.type === 'text_input') {
            $('next-btn').textContent = 'Submit';
        } else {
            $('next-btn').textContent = state.index === QUESTIONS.length - 1 ? 'Finish' : 'Next';
        }

        const answers = $('answers');
        answers.className = 'answers';
        answers.innerHTML = '';

        if (q.type === 'single_choice') {
            answers.style.display = 'grid';

            KEYS.forEach((k) => {
                const tile = document.createElement('button');
                tile.type = 'button';
                tile.className = 'tile';
                tile.setAttribute('data-tile', k);
                tile.innerHTML = '<span class="glyph"><span class="shape ' + SHAPES[k] + '"></span></span>'
                    + '<span class="key">' + k.toUpperCase() + '</span>'
                    + '<span class="otext"></span>'
                    + '<span class="verdict"></span>';
                // textContent so option text stays plain text
                tile.querySelector('.otext').textContent = q.options[k];
                tile.addEventListener('click', () => chooseOption(k));
                answers.appendChild(tile);
            });
        } else {
            answers.style.display = 'flex';
            answers.innerHTML = '<div class="text-answer">'
                + '<div class="ta-box" id="ta-box"><input id="ta-input" type="text" autocomplete="off" spellcheck="false"></div>'
                + '<p class="ta-hint">Press <b>Enter</b> or hit Submit to lock it in.</p>'
                + '</div>';

            const input = $('ta-input');
            input.placeholder = q.placeholder;

            // submit only allowed once something is typed
            input.addEventListener('input', () => {
                if (input.value.trim() === '') {
                    $('next-btn').classList.add('is-disabled');
                } else {
                    $('next-btn').classList.remove('is-disabled');
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !state.locked && input.value.trim() !== '') {
                    submitText();
                }
            });

            input.focus();
        }

        startTimer(q.time);
    }

    /* timer */

    function startTimer(secs) {
        clearInterval(state.timerId);
        state.timeLeft = secs;
        state.totalTime = secs;

        $('timer-secs').textContent = secs;
        $('timer-num').classList.remove('warn');

        // snap the bar back to full without animating it backwards
        const fill = $('timer-fill');
        fill.classList.remove('warn');
        fill.style.transition = 'none';
        fill.style.width = '100%';
        setTimeout(() => { fill.style.transition = ''; }, 50);

        state.timerId = setInterval(() => {
            state.timeLeft--;
            $('timer-secs').textContent = Math.max(state.timeLeft, 0);
            fill.style.width = (state.timeLeft / state.totalTime * 100) + '%';

            if (state.timeLeft <= 4) {
                $('timer-num').classList.add('warn');
                fill.classList.add('warn');
            }
            if (state.timeLeft <= 0) {
                timeOut();
            }
        }, 1000);
    }

    /* answering logic */

    function lock() {
        state.locked = true;
        clearInterval(state.timerId);
    }

    // boss battles pay double the normal quiz points
    function ptsFor(marks) {
        const bonus = state.timeLeft / state.totalTime;
        return Math.round(marks * (1 + bonus) * 10);
    }

    // send each answer to the server the moment it locks in
    function recordAnswer(q, selected, text) {
        const record = {
            question_id: q.question_id,
            selected_option: selected,
            text_answer: text,
            response_time: state.totalTime - state.timeLeft,
        };

        const fd = new FormData();
        fd.append('quiz_id', QUIZ_ID);
        fd.append('answers', JSON.stringify([record]));
        if (ROOM_CODE) fd.append('room_code', ROOM_CODE);
        fetch('/Implose.gg-src/actions/user/game/quiz/save_attempt.php', { method: 'POST', body: fd });
    }

    function chooseOption(k) {
        if (state.locked) return;
        const q = QUESTIONS[state.index];
        lock();

        recordAnswer(q, k, null);
        const correct = k === q.correct;
        const answers = $('answers');
        answers.classList.add('locked');

        const tiles = answers.children;
        for (let i = 0; i < tiles.length; i++) {
            const tk = tiles[i].getAttribute('data-tile');
            if (tk === q.correct) {
                tiles[i].classList.add('correct');
                tiles[i].querySelector('.verdict').textContent = '✓';
            }
            if (tk === k) {
                tiles[i].classList.add('chosen');
                if (!correct) {
                    tiles[i].classList.add('wrong');
                    tiles[i].querySelector('.verdict').textContent = '✗';
                }
            }
        }

        finishAnswer(correct, q, 'The answer was ' + q.options[q.correct], false);
    }

    function submitText() {
        if (state.locked) return;
        const q = QUESTIONS[state.index];
        const input = $('ta-input');
        lock();

        recordAnswer(q, null, input.value.trim());
        input.disabled = true;
        const correct = input.value.trim().toLowerCase() === q.answer.trim().toLowerCase();
        $('ta-box').classList.add(correct ? 'correct' : 'wrong');

        finishAnswer(correct, q, 'The answer was "' + q.answer + '"', false);
    }

    function timeOut() {
        if (state.locked) return;
        const q = QUESTIONS[state.index];
        lock();
        recordAnswer(q, null, null);

        if (q.type === 'single_choice') {
            $('answers').classList.add('locked');
            const tiles = $('answers').children;
            for (let i = 0; i < tiles.length; i++) {
                if (tiles[i].getAttribute('data-tile') === q.correct) {
                    tiles[i].classList.add('correct');
                    tiles[i].querySelector('.verdict').textContent = '✓';
                }
            }
            finishAnswer(false, q, 'The answer was ' + q.options[q.correct], true);
        } else {
            $('ta-input').disabled = true;
            $('ta-box').classList.add('wrong');
            finishAnswer(false, q, 'The answer was "' + q.answer + '"', true);
        }
    }

    // shared wrap up after any answer: hp, hearts, score, feedback banner
    function finishAnswer(correct, q, answerLabel, timedOut) {
        state.results.push(correct);
        let pts = 0;
        let finalBlow = false;
        let knockedOut = false;

        if (correct) {
            state.streak++;
            state.bestStreak = Math.max(state.bestStreak, state.streak);

            // spectators still answer but deal no damage
            if (!state.spectator) {
                state.bossHp = Math.max(state.bossHp - 1, 0);
                state.dmg++;
                finalBlow = state.bossHp === 0;
                pts = ptsFor(q.marks) + (finalBlow ? FINAL_BLOW_BONUS : 0);
                state.score += pts;
                paintBossHp();
                bossTakesHit(finalBlow);
                countUp($('score-val'), state.score);
            }
        } else {
            state.streak = 0;
            if (!state.spectator) {
                state.hearts = Math.max(state.hearts - 1, 0);
                breakHeart();
                bossAttacks();
                if (state.hearts === 0) {
                    knockedOut = true;
                    state.spectator = true;
                    document.body.classList.add('spectator');
                }
            }
        }
        paintStreak();

        $('fb').className = 'fb show ' + (correct ? 'good' : 'bad');
        if (correct) {
            $('fb-icon').textContent = '✓';
            if (finalBlow) {
                $('fb-head').textContent = 'FINAL BLOW!';
                $('fb-sub').textContent = 'The Glitch King is down!';
            } else if (state.spectator) {
                $('fb-head').textContent = 'CORRECT!';
                $('fb-sub').textContent = 'Right - but spectators deal no damage.';
            } else {
                $('fb-head').textContent = state.streak >= 3 ? 'ON FIRE!' : 'DIRECT HIT!';
                $('fb-sub').textContent = 'The boss takes 1 damage. ' + state.bossHp + ' HP left.';
            }
            $('fb-pts').textContent = '+' + pts;
            $('fb-pts').style.color = pts ? '' : 'var(--text-muted)';
        } else {
            $('fb-icon').textContent = timedOut ? '⏱' : '✗';
            if (knockedOut) {
                $('fb-head').textContent = 'KNOCKED OUT!';
                $('fb-sub').textContent = 'Out of hearts - you finish this battle as a spectator. ' + answerLabel + '.';
            } else {
                $('fb-head').textContent = timedOut ? 'TIME\'S UP' : 'BOSS HITS YOU!';
                $('fb-sub').textContent = state.spectator && !knockedOut
                    ? answerLabel
                    : answerLabel + '. You lose 1 heart.';
            }
            $('fb-pts').textContent = '+0';
            $('fb-pts').style.color = 'var(--text-muted)';
        }
        $('act-spacer').style.display = 'none';

        if (finalBlow) {
            $('next-btn').textContent = 'Claim Victory';
        } else if (state.index === QUESTIONS.length - 1) {
            $('next-btn').textContent = 'See Results';
        } else {
            $('next-btn').textContent = 'Next';
        }
        $('next-btn').classList.remove('is-disabled');
        $('next-btn').focus();
    }

    /* advancing */

    $('next-btn').addEventListener('click', () => {
        if ($('next-btn').classList.contains('is-disabled')) return;
        if (state.waiting) {
            exitBattle();
            return;
        }
        const q = QUESTIONS[state.index];

        // for text questions the button submits first, then advances
        if (!state.locked && q.type === 'text_input') {
            submitText();
            return;
        }
        if (!state.locked) return;

        if (state.bossHp <= 0) {
            showVictory();
            return;
        }

        state.index++;
        if (state.index >= QUESTIONS.length) {
            if (ROOM_CODE) {
                // the rest of the party may still be fighting
                enterWaiting();
            } else {
                showDefeat();
            }
        } else {
            renderQuestion();
        }
    });

    // press 1-4 to pick a tile
    document.addEventListener('keydown', (e) => {
        if (state.locked || state.over) return;
        if ($('battle-screen').style.display === 'none') return;
        const q = QUESTIONS[state.index];
        const n = parseInt(e.key, 10);
        if (q && q.type === 'single_choice' && n >= 1 && n <= 4) {
            chooseOption(KEYS[n - 1]);
        }
    });

    /* party mode: poll the room so the shared boss stays in sync */

    function applyRoomStatus(data) {
        if (state.over) return;

        state.bossHpMax = data.hp_max;
        if (state.synced) {
            // hp never climbs back up, my own hits land before the server sees them
            state.bossHp = Math.min(state.bossHp, data.boss_hp);
        } else {
            state.synced = true;
            state.bossHp = data.boss_hp;
        }
        paintBossHp();

        if (data.outcome === 'victory') {
            showVictory();
        } else if (data.outcome === 'defeat') {
            showDefeat();
        }
    }

    function poll() {
        fetch('/Implose.gg-src/api/game/quiz_room/boss_status.php?room_code=' + encodeURIComponent(ROOM_CODE))
            .then((res) => res.json())
            .then((data) => {
                if (!data || data.error) return;
                applyRoomStatus(data);
            })
            .catch(() => {});
    }

    // answered everything but the party is still fighting, poll decides the ending
    function enterWaiting() {
        state.waiting = true;
        $('fb-sub').textContent = 'All questions done - waiting for the rest of the party.';
        $('next-btn').textContent = 'See Standings';

        if (typeof window.notifyQuizComplete === 'function') {
            window.notifyQuizComplete(QUIZ_ID);
        }
    }

    /* endings */

    function paintDots(el) {
        el.innerHTML = '';
        state.results.forEach((ok, i) => {
            const dot = document.createElement('div');
            dot.className = 'res-dot ' + (ok ? 'ok' : 'no');
            dot.textContent = i + 1;
            el.appendChild(dot);
        });
    }

    function endBattle() {
        state.over = true;
        clearInterval(state.timerId);
        clearInterval(state.pollId);
        $('battle-screen').style.display = 'none';
        $('intro-screen').style.display = 'none';

        if (typeof window.notifyQuizComplete === 'function') {
            window.notifyQuizComplete(QUIZ_ID);
        }
    }

    function showVictory() {
        if (state.over) return;
        endBattle();

        state.score += CLEAR_BONUS;
        $('v-dmg').textContent = ROOM_CODE ? state.dmg : state.dmg + '/' + state.bossHpMax;
        $('v-hearts').textContent = '♥ ' + state.hearts;
        $('v-best').textContent = 'x' + state.bestStreak;
        paintDots($('v-dots'));
        countUp($('v-score'), state.score);
        $('victory-screen').classList.add('show');
    }

    function showDefeat() {
        if (state.over) return;
        endBattle();

        if (state.spectator) {
            $('d-eyebrow').textContent = 'KNOCKED OUT';
            $('d-cap').textContent = 'Out of hearts - run it back and finish him.';
        } else {
            $('d-eyebrow').textContent = 'BOSS SURVIVED';
            $('d-cap').textContent = 'He held on with ' + state.bossHp + ' HP. One more push!';
        }
        $('d-dmg').textContent = ROOM_CODE ? state.dmg : state.dmg + '/' + state.bossHpMax;
        $('d-answered').textContent = state.results.length + '/' + QUESTIONS.length;
        $('d-best').textContent = 'x' + state.bestStreak;
        paintDots($('d-dots'));
        countUp($('d-score'), state.score);
        $('defeat-screen').classList.add('show');
    }

    /* screen switching */

    function startBattle() {
        $('intro-screen').style.display = 'none';
        $('victory-screen').classList.remove('show');
        $('defeat-screen').classList.remove('show');
        $('battle-screen').style.display = 'flex';
        renderQuestion();
    }

    function resetRun() {
        clearInterval(state.timerId);
        state.index = 0;
        state.score = 0;
        state.streak = 0;
        state.bestStreak = 0;
        state.hearts = HEARTS_MAX;
        state.bossHpMax = Math.max(QUESTIONS.length - 2, 1);
        state.bossHp = state.bossHpMax;
        state.locked = false;
        state.spectator = false;
        state.results = [];
        state.dmg = 0;
        state.over = false;
        document.body.classList.remove('spectator');
        $('score-val').textContent = '0';
        paintStreak();
        paintHearts();
        paintBossHp();
    }

    function exitBattle() {
        if (ROOM_CODE) {
            window.location.href = '/Implose.gg-src/pages/user/game/live_quiz/leaderboard.php?room_code=' + encodeURIComponent(ROOM_CODE);
        } else {
            window.location.href = '/Implose.gg-src/pages/user/game/manage_course.php?course_id=' + COURSE_ID;
        }
    }

    $('fight-btn').addEventListener('click', startBattle);
    $('intro-back-btn').addEventListener('click', exitBattle);
    $('v-exit-btn').addEventListener('click', exitBattle);
    $('d-exit-btn').addEventListener('click', exitBattle);

    $('v-again-btn').addEventListener('click', () => {
        resetRun();
        startBattle();
    });

    $('d-again-btn').addEventListener('click', () => {
        resetRun();
        startBattle();
    });

    // boss runs feed the learning analytics dashboard, jump there next
    $('v-analytics-btn').addEventListener('click', () => {
        window.location.href = '/Implose.gg-src/pages/user/learning_analytics.php';
    });
    $('d-analytics-btn').addEventListener('click', () => {
        window.location.href = '/Implose.gg-src/pages/user/learning_analytics.php';
    });

    /* boot */

    // live mode: replaying would double count answers on the leaderboard
    if (ROOM_CODE) {
        $('v-again-btn').style.display = 'none';
        $('d-again-btn').style.display = 'none';
        const exits = ['intro-back-btn', 'v-exit-btn', 'd-exit-btn'];
        for (let i = 0; i < exits.length; i++) {
            $(exits[i]).innerHTML = '<img src="/Implose.gg-src/assets/images/icons/arrow.clockwise.svg" alt="">See Standings';
        }
    }

    buildSegs();
    paintHearts();

    if (ROOM_CODE) {
        // shared boss pool, first poll fills in the real numbers
        poll();
        state.pollId = setInterval(poll, 2000);
    } else {
        paintBossHp();
    }
})();
