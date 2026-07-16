<!--
Programmer Name: Mr. Chong Ray Han
Program Name: /pages/user/game/quiz/edit_quiz.php
Description: User edit quiz page (level meta + questions list)
First Written on: Thursday, 2-Jul-2026
Edited on: Friday, 3-Jul-2026
-->
<?php
session_start();

ob_start();
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');
ob_end_clean();

$success_msg = '';
$error_msg   = '';

if (isset($_SESSION['edit_quiz_success'])) {
    $success_msg = $_SESSION['edit_quiz_success'];
    unset($_SESSION['edit_quiz_success']);
}

if (isset($_SESSION['edit_quiz_error'])) {
    $error_msg = $_SESSION['edit_quiz_error'];
    unset($_SESSION['edit_quiz_error']);
}

$quiz_id = (int)($_GET['quiz_id'] ?? 0);

$quiz_sql = "SELECT q.*, c.title AS course_title, c.course_id
             FROM QUIZ_T q
             JOIN COURSE_T c ON q.course_id = c.course_id
             WHERE q.quiz_id = '$quiz_id'
               AND c.creator_id = '$_SESSION[user_id]'";
$quiz_result = mysqli_query($conn, $quiz_sql);

if (!$quiz_result || mysqli_num_rows($quiz_result) !== 1) {
    header('Location: /Implose.gg-src/pages/user/game/view_course.php');
    exit();
}

$quiz = mysqli_fetch_assoc($quiz_result);
$course_id = $quiz['course_id'];

$questions_sql = "SELECT * FROM QUESTION_T WHERE quiz_id = '$quiz_id' ORDER BY question_id ASC";
$questions_result = mysqli_query($conn, $questions_sql);

$questions = [];
while ($row = mysqli_fetch_assoc($questions_result)) {
    $questions[] = $row;
}
$question_count = count($questions);

// highest level in the course = boss battle
$last_sql = "SELECT quiz_id FROM QUIZ_T WHERE course_id = '$course_id' ORDER BY level_number DESC LIMIT 1";
$last_result = mysqli_query($conn, $last_sql);
$last_quiz = mysqli_fetch_assoc($last_result);
$is_boss = $last_quiz['quiz_id'] == $quiz_id;

$edit_qid = (int)($_GET['edit'] ?? 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_create_course.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_view_course.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_manage_quiz.css">
    <title>Edit Quiz — Implose.gg User</title>
    <meta name="description" content="Edit quiz level details and questions.">
</head>
<body>
    <?php
        $current_page = 'course';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">
        <div class="mq-container">
            <nav class="cc-breadcrumb">
                <a href="/Implose.gg-src/pages/user/game/view_course.php">My Courses</a>
                <span class="sep">></span>
                <a href="/Implose.gg-src/pages/user/game/manage_course.php?course_id=<?php echo $course_id; ?>"><?php echo htmlspecialchars($quiz['course_title']); ?></a>
                <span class="sep">></span>
                <span class="current"><?php echo htmlspecialchars($quiz['title']); ?></span>
            </nav>

            <?php if ($error_msg): ?>
                <p class="cc-error"><?php echo htmlspecialchars($error_msg); ?></p>
            <?php endif; ?>

            <?php if ($success_msg): ?>
                <p class="cc-success"><?php echo htmlspecialchars($success_msg); ?></p>
            <?php endif; ?>

            <form class="pixel-panel mq-meta" action="/Implose.gg-src/actions/user/game/quiz/edit_quiz.php" method="post">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz['quiz_id']; ?>">

                <div class="mq-meta-top">
                    <div class="mq-level<?php echo $is_boss ? ' boss' : ''; ?>">
                        <?php echo $is_boss ? 'Boss Battle' : 'Level ' . (int)$quiz['level_number']; ?>
                    </div>

                    <span class="mq-meta-count"><strong><?php echo $question_count; ?></strong> questions</span>
                </div>

                <div class="mq-meta-form">
                    <div class="mq-field">
                        <label for="lvl-title">Level Title</label>
                        <div class="txt-container">
                            <input id="lvl-title" type="text" name="title" value="<?php echo htmlspecialchars($quiz['title']); ?>">
                        </div>
                    </div>

                    <div class="mq-field">
                        <label for="lvl-desc">Description</label>
                        <div class="txt-container">
                            <textarea id="lvl-desc" name="description" rows="2"><?php echo htmlspecialchars($quiz['description']); ?></textarea>
                        </div>
                    </div>

                    <div class="mq-meta-row">
                        <div class="mq-field mq-field-sm">
                            <label for="lvl-num">Level Number</label>
                            <div class="txt-container">
                                <input id="lvl-num" type="number" name="level_number" value="<?php echo (int)$quiz['level_number']; ?>" min="1">
                            </div>
                        </div>
                        <button class="btn-red" type="submit">Save Details</button>
                    </div>
                </div>
            </form>

            <div class="mq-q-head">
                <h2 class="pixel-title vc-section-title">Questions</h2>
                <span class="vc-count-pill"><?php echo $question_count; ?> total</span>
                <div class="mq-q-head-actions">
                    <a class="btn-pixel" href="/Implose.gg-src/pages/user/game/live_quiz/quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>">
                        <span>Preview How It Plays</span>
                    </a>
                    <form action="/Implose.gg-src/actions/user/game/quiz/create_question.php" method="post">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz['quiz_id']; ?>">
                        <button class="btn-pixel btn-pixel-red" type="submit">Add Question</button>
                    </form>
                    <form action="/Implose.gg-src/actions/user/game/quiz/delete_quiz.php" method="post" onsubmit="return confirm('Delete this quiz and all its questions? This cannot be undone.');">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz['quiz_id']; ?>">
                        <button class="btn-pixel" type="submit">
                            <span><img src="/Implose.gg-src/assets/images/icons/trash.svg" alt=""></span>
                            <span>Delete Quiz</span>
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($question_count === 0): ?>
                <div class="pixel-panel vc-empty">
                    <div class="vc-empty-title">No questions yet</div>
                    <div class="vc-empty-sub">Add the first question to get this level going.</div>
                    <form action="/Implose.gg-src/actions/user/game/quiz/create_question.php" method="post">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz['quiz_id']; ?>">
                        <button class="btn-pixel btn-pixel-red" type="submit">Add Question</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="mq-list">
                    <?php foreach ($questions as $i => $qn):
                        $qid = (int)$qn['question_id'];
                        $is_mcq = $qn['question_type'] === 'single_choice';
                        $is_editing = $edit_qid === $qid;
                        $type_label = $is_mcq ? 'MULTIPLE CHOICE' : 'FILL IN THE BLANK';
                        $type_class = $is_mcq ? 'mq-type-mcq' : 'mq-type-fitb';
                    ?>
                        <div class="pixel-panel mq-q<?php echo $is_editing ? ' is-open' : ''; ?>" id="q-<?php echo $qid; ?>">
                            <div class="mq-q-row">
                                <div class="mq-q-num"><?php echo $i + 1; ?></div>

                                <div class="mq-q-main">
                                    <?php if ($is_editing): ?>
                                        <div class="mq-q-tags">
                                            <span class="mq-type <?php echo $type_class; ?>"><?php echo $type_label; ?></span>
                                            <span class="mq-chip">Editing…</span>
                                        </div>
                                    <?php endif; ?>
                                    <span class="mq-q-text"><?php echo htmlspecialchars($qn['question_text']); ?></span>
                                </div>

                                <div class="mq-q-actions">
                                    <?php if (!$is_editing): ?>
                                        <a class="btn-pixel mq-icon-btn" href="/Implose.gg-src/pages/user/game/quiz/edit_quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>&edit=<?php echo $qid; ?>#q-<?php echo $qid; ?>"><span>Edit</span></a>
                                    <?php endif; ?>
                                    <form action="/Implose.gg-src/actions/user/game/quiz/delete_question.php" method="post" onsubmit="return confirm('Delete this question? This cannot be undone.');">
                                        <input type="hidden" name="quiz_id" value="<?php echo $quiz['quiz_id']; ?>">
                                        <input type="hidden" name="question_id" value="<?php echo $qid; ?>">
                                        <button class="btn-pixel mq-icon-btn mq-del" type="submit"><span>Delete</span></button>
                                    </form>
                                </div>
                            </div>

                            <?php if (!$is_editing): ?>
                                <div class="mq-q-preview">
                                    <?php if ($is_mcq): ?>
                                        <div class="mq-view-opts">
                                            <?php foreach (['a', 'b', 'c', 'd'] as $letter):
                                                $opt_val = $qn['option_' . $letter] ?? '';

                                                // skip empty options
                                                if ($opt_val === '') continue;

                                                $opt_correct = ($qn['correct_option'] ?? '') === $letter;
                                            ?>
                                                <div class="mq-view-opt<?php echo $opt_correct ? ' is-correct' : ''; ?>">
                                                    <span class="opt-letter"><?php echo strtoupper($letter); ?></span>
                                                    <span><?php echo htmlspecialchars($opt_val); ?></span>
                                                    <?php if ($opt_correct): ?>
                                                        <span class="opt-correct-tag">CORRECT</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="mq-view-answer">
                                            <span class="ans-label">ANSWER:</span>
                                            <span class="ans-text"><?php echo htmlspecialchars($qn['correct_text_answer'] ?? ''); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mq-q-meta">
                                        <span class="mq-type <?php echo $type_class; ?>"><?php echo $type_label; ?></span>
                                        <div class="mq-q-meta-right">
                                            <span class="mq-q-stat"><?php echo (int)$qn['marks']; ?> marks</span>
                                            <span class="mq-q-stat"><?php echo (int)$qn['time_limit']; ?>s</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($is_editing): ?>
                                <form class="mq-editor <?php echo $is_mcq ? 'mq-mode-mcq' : 'mq-mode-fitb'; ?>" action="/Implose.gg-src/actions/user/game/quiz/edit_question.php" method="post">
                                    <input type="hidden" name="quiz_id" value="<?php echo $quiz['quiz_id']; ?>">
                                    <input type="hidden" name="question_id" value="<?php echo $qid; ?>">

                                    <div class="mq-type-toggle">
                                        <label class="mq-tt<?php echo $is_mcq ? ' is-active' : ''; ?>">
                                            <input type="radio" name="question_type" value="single_choice"<?php echo $is_mcq ? ' checked' : ''; ?>>
                                            <span>Multiple Choice</span>
                                        </label>
                                        <label class="mq-tt<?php echo !$is_mcq ? ' is-active' : ''; ?>">
                                            <input type="radio" name="question_type" value="text_input"<?php echo !$is_mcq ? ' checked' : ''; ?>>
                                            <span>Fill in the Blank</span>
                                        </label>
                                    </div>

                                    <div class="mq-field">
                                        <label for="q<?php echo $qid; ?>-text">Question</label>
                                        <div class="txt-container">
                                            <textarea id="q<?php echo $qid; ?>-text" name="question_text" rows="2"><?php echo htmlspecialchars($qn['question_text']); ?></textarea>
                                        </div>
                                    </div>

                                    <div class="mq-mcq-block">
                                        <span class="mq-sub">ANSWER OPTIONS — TICK THE CORRECT ONE</span>
                                        <div class="mq-options">
                                            <?php foreach (['a', 'b', 'c', 'd'] as $letter):
                                                $is_correct = ($qn['correct_option'] ?? '') === $letter;
                                            ?>
                                                <div class="mq-opt tile-<?php echo $letter; ?><?php echo $is_correct ? ' is-correct' : ''; ?>">
                                                    <input type="radio" name="correct_option" value="<?php echo $letter; ?>" id="q<?php echo $qid; ?>-opt-<?php echo $letter; ?>"<?php echo $is_correct ? ' checked' : ''; ?>>
                                                    <label class="mq-opt-letter" for="q<?php echo $qid; ?>-opt-<?php echo $letter; ?>" title="Mark as correct answer"><?php echo strtoupper($letter); ?></label>
                                                    <div class="txt-container">
                                                        <input type="text" name="option_<?php echo $letter; ?>" value="<?php echo htmlspecialchars($qn['option_' . $letter] ?? ''); ?>" placeholder="Option <?php echo strtoupper($letter); ?>">
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="mq-fitb-block">
                                        <span class="mq-sub">ACCEPTED ANSWER — CASE-INSENSITIVE</span>
                                        <div class="txt-container">
                                            <input type="text" name="correct_text_answer" value="<?php echo htmlspecialchars($qn['correct_text_answer'] ?? ''); ?>" placeholder="Type the answer that scores">
                                        </div>
                                    </div>

                                    <div class="mq-editor-foot">
                                        <div class="mq-foot-field">
                                            <label for="q<?php echo $qid; ?>-pts">POINTS</label>
                                            <div class="txt-container">
                                                <input id="q<?php echo $qid; ?>-pts" type="number" name="marks" value="<?php echo (int)$qn['marks']; ?>" min="1">
                                            </div>
                                        </div>
                                        <div class="mq-foot-field">
                                            <label for="q<?php echo $qid; ?>-time">TIME (SEC)</label>
                                            <div class="txt-container">
                                                <input id="q<?php echo $qid; ?>-time" type="number" name="time_limit" value="<?php echo (int)$qn['time_limit']; ?>" min="1">
                                            </div>
                                        </div>
                                        <div class="mq-foot-actions">
                                            <a class="btn-pixel" href="/Implose.gg-src/pages/user/game/quiz/edit_quiz.php?quiz_id=<?php echo $quiz['quiz_id']; ?>"><span>Cancel</span></a>
                                            <button class="btn-red" type="submit">Save Question</button>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // show mcq or fitb block based on the type toggle
        var type_radios = document.querySelectorAll('.mq-editor input[name="question_type"]');
        for (var i = 0; i < type_radios.length; i++) {
            type_radios[i].addEventListener('change', function () {
                var form = this.closest('.mq-editor');
                if (this.value == 'single_choice') {
                    form.classList.add('mq-mode-mcq');
                    form.classList.remove('mq-mode-fitb');
                } else {
                    form.classList.add('mq-mode-fitb');
                    form.classList.remove('mq-mode-mcq');
                }

                var tabs = form.querySelectorAll('.mq-tt');
                for (var j = 0; j < tabs.length; j++) {
                    if (tabs[j].querySelector('input').checked) {
                        tabs[j].classList.add('is-active');
                    } else {
                        tabs[j].classList.remove('is-active');
                    }
                }
            });
        }

        // highlight whichever option is ticked correct
        var opt_groups = document.querySelectorAll('.mq-editor .mq-options');
        for (var g = 0; g < opt_groups.length; g++) {
            opt_groups[g].addEventListener('change', function () {
                var opts = this.querySelectorAll('.mq-opt');
                for (var k = 0; k < opts.length; k++) {
                    var radio = opts[k].querySelector('input[type="radio"]');
                    if (radio.checked) {
                        opts[k].classList.add('is-correct');
                    } else {
                        opts[k].classList.remove('is-correct');
                    }
                }
            });
        }
    </script>
</body>
</html>
