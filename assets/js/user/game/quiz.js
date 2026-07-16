/*
Programmer Name: Mr. Chong Ray Han
Program Name: /assets/js/user/game/quiz.js
Description: Solo quiz play logic (render questions, timer, scoring, results)
First Written on: Wednesday, 02-Jul-2026
Edited on: Wednesday, 02-Jul-2026
*/

(function () {
    const QUESTIONS = window.QUIZ_DATA.questions;
    const QUIZ_ID = window.QUIZ_DATA.quiz_id;
    const COURSE_ID = window.QUIZ_DATA.course_id;
    const ROOM_CODE = window.QUIZ_DATA.room_code;
    const TITLE =window.QUIZ_DATA.title;

    const KEYS = ['a', 'b', 'c', 'd'];
    const SHAPES = { a: 'tri', b: 'dia', c: 'cir', d: 'sq' };
    const $ = (id) => document.getElementById(id);

    const state = {
        index: 0,
        score: 0,
        streak: 0,
        bestStreak: 0,
        locked: false,
        results: [],
        lastAnswer: '',
        timeLeft: 0,
        totalTime: 0,
        timerId: null,
    };

    // pick up saved state when coming back from the ai explanation page
    const SAVE_KEY = 'ai_quiz_state_' + QUIZ_ID;
    const saved = JSON.parse(sessionStorage.getItem(SAVE_KEY) || 'null');
    if (saved) {
        sessionStorage.removeItem(SAVE_KEY);
        state.index = saved.next_index;
        state.score = saved.score;
        state.streak = saved.streak;
        state.bestStreak = saved.bestStreak;
        state.results = saved.results;
        $('score-val').textContent = state.score;
    }

    $('q-total').textContent = QUESTIONS.length;

    /* progress */

    
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

    /* questions */

    function renderQuestion() {
        const q = QUESTIONS[state.index];
        state.locked = false;

        $('q-now').textContent = state.index + 1;
        $('q-topic').textContent = q.topic;
        $('q-marks').textContent = q.marks;
        $('q-text').textContent = q.text;
        $('q-kicker').textContent = q.type === 'single_choice' ? 'SINGLE CHOICE - PICK ONE' : 'TYPE YOUR ANSWER';
        paintSegs();

        // reset the action row
        $('fb').className = 'fb';
        $('act-spacer').style.display = 'block';
        $('next-btn').classList.add('is-disabled');
        $('next-btn').textContent = q.type === 'text_input' ? 'Submit' : 'Next';

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

            if (state.timeLeft <= 5) {
                $('timer-num').classList.add('warn');
                fill.classList.add('warn');
            }
            if (state.timeLeft <= 0) {
                timeOut();
            }
        }, 1000);
    }

    /* answring logic */

    function lock() {
        state.locked = true;
        clearInterval(state.timerId);
    }

    // marks plus a time bonus, up to double points when answered instantly
    function ptsFor(marks) {
        const bonus = state.timeLeft / state.totalTime;
        return Math.round(marks * (1 + bonus) * 5);
    }

    // send each answer to the server the moment it locks in
    function recordAnswer(q, selected, text) {
        state.lastAnswer = selected || text || '';

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

    // shared wrap up after any answer: score, streak, feedback banner
    function finishAnswer(correct, q, answerLabel, timedOut) {
        let pts = 0;

        if (correct) {
            pts = ptsFor(q.marks);
            state.score += pts;
            state.streak++;
            state.bestStreak = Math.max(state.bestStreak, state.streak);
        } else {
            state.streak = 0;
        }
        state.results.push(correct);

        countUp($('score-val'), state.score);
        paintStreak();

        $('fb').className = 'fb show ' + (correct ? 'good' : 'bad');
        $('fb-icon').textContent = correct ? '✓' : (timedOut ? '⏱' : '✗');
        $('fb-head').textContent = correct ? (state.streak >= 3 ? 'ON FIRE!' : 'CORRECT!') : (timedOut ? 'TIME\'S UP' : 'NOT QUITE');
        $('fb-sub').textContent = correct ? 'Locked in with ' + Math.max(state.timeLeft, 0) + 's to spare.' : answerLabel;
        $('fb-pts').textContent = '+' + pts;
        $('fb-pts').style.color = correct ? '' : 'var(--text-muted)';
        $('act-spacer').style.display = 'none';

        $('next-btn').textContent = state.index === QUESTIONS.length - 1 ? 'See Results' : 'Next';
        $('next-btn').classList.remove('is-disabled');
        $('next-btn').focus();
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

    /* advancing */

    $('next-btn').addEventListener('click', () => {
        if ($('next-btn').classList.contains('is-disabled')) return;
        const q = QUESTIONS[state.index];

        // for text questions the button submits first, then advances
        if (!state.locked && q.type === 'text_input') {
            submitText();
            return;
        }
        if (!state.locked) return;

        // live mode stays in page, no ai redirect between questions
        if (ROOM_CODE) {
            state.index++;
            if (state.index >= QUESTIONS.length) {
                showResults();
            } else {
                renderQuestion();
            }
            return;
        }

        // solo mode goes to the ai explanation page, sessionStorage brings us back
        sessionStorage.setItem(SAVE_KEY, JSON.stringify({
            next_index: state.index + 1,
            score: state.score,
            streak: state.streak,
            bestStreak: state.bestStreak,
            results: state.results,
        }));

        window.location.href = '/Implose.gg-src/pages/user/game/live_quiz/ai_explanation.php'
            + '?question_id=' + q.question_id
            + '&answer=' + encodeURIComponent(state.lastAnswer || '');
    });

    // press 1-4 to pick a tile
    document.addEventListener('keydown', (e) => {
        if (state.locked) return;
        const q = QUESTIONS[state.index];
        const n = parseInt(e.key, 10);
        if (q && q.type === 'single_choice' && n >= 1 && n <= 4) {
            chooseOption(KEYS[n - 1]);
        }
    });

    /* results */

    function gradeFor(acc) {
        if (acc >= 90) return ['S', 'Flawless run. Nothing got past you.'];
        if (acc >= 75) return ['A', 'Sharp work - you really know this.'];
        if (acc >= 55) return ['B', 'Solid. A little more practice and it\'s yours.'];
        if (acc >= 35) return ['C', 'Getting there - review and run it back.'];
        return ['D', 'Tough round. Replay to lock these in.'];
    }

    function showResults() {
        const total = QUESTIONS.length;
        let correct = 0;
        state.results.forEach((ok) => {
            if (ok) correct++;
        });
        const acc = Math.round(correct / total * 100);
        const grade = gradeFor(acc);

        $('grade-letter').textContent = grade[0];
        $('score-cap').textContent = grade[1];
        $('stat-correct').textContent = correct + '/' + total;
        $('stat-acc').textContent = acc + '%';
        $('stat-best').textContent = 'x' + state.bestStreak;
        $('res-title').textContent = TITLE + ' Cleared!';

        // one dot per question, green or red
        $('res-dots').innerHTML = '';
        state.results.forEach((ok, i) => {
            const dot = document.createElement('div');
            dot.className = 'res-dot ' + (ok ? 'ok' : 'no');
            dot.textContent = i + 1;
            $('res-dots').appendChild(dot);
        });

        countUp($('final-score'), state.score);

        $('quiz-screen').style.display = 'none';
        $('results-screen').classList.add('show');

        if (typeof window.notifyQuizComplete === 'function') {
            window.notifyQuizComplete(QUIZ_ID);
        }
    }

    $('again-btn').addEventListener('click', () => {
        state.index = 0;
        state.score = 0;
        state.streak = 0;
        state.bestStreak = 0;
        state.results = [];
        $('score-val').textContent = '0';
        $('final-score').textContent = '0';
        paintStreak();
        $('results-screen').classList.remove('show');
        $('quiz-screen').style.display = 'flex';
        renderQuestion();
    });

    $('exit-btn').addEventListener('click', () => {
        if (ROOM_CODE) {
            window.location.href = '/Implose.gg-src/pages/user/game/live_quiz/leaderboard.php?room_code=' + encodeURIComponent(ROOM_CODE);
        } else {
            window.location.href = '/Implose.gg-src/pages/user/game/manage_course.php?course_id=' + COURSE_ID;
        }
    });

    /* boot */

    // live mode: replaying would double count answers on the leaderboard
    if (ROOM_CODE) {
        $('again-btn').style.display = 'none';
        $('exit-btn').innerHTML = '<img src="/Implose.gg-src/assets/images/icons/arrow.clockwise.svg" alt="">See Standings';
    }

    buildSegs();

    if (state.index >= QUESTIONS.length) {
        // resumed after the last question, go straight to results
        showResults();
    } else {
        renderQuestion();
    }
})();
