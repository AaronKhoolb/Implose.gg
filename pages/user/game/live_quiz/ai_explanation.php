<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/game/live_quiz/ai_explanation.php
Description: AI explanation page shown after answering a quiz question
First Written on: Thursday, 02-Jul-2026
Edited on: Sunday, 05-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php');
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/ai_engine.php');

        $question_id = $_GET['question_id'];
        $user_answer = $_GET['answer'];

        // load the question, quiz, course from the DB
        $question_sql = "SELECT * FROM QUESTION_T WHERE question_id = '$question_id'";
        $question_result = mysqli_query($conn, $question_sql);
        $question = mysqli_fetch_assoc($question_result);

        if (!$question) {
            header('Location: /Implose.gg-src/pages/user/index.php');
            exit();
        }

        $quiz_id = $question['quiz_id'];
        $quiz_sql = "SELECT * FROM QUIZ_T WHERE quiz_id = '$quiz_id'";
        $quiz_result = mysqli_query($conn, $quiz_sql);
        $quiz = mysqli_fetch_assoc($quiz_result);

        $course_id = $quiz['course_id'];
        $course_sql = "SELECT * FROM COURSE_T WHERE course_id = '$course_id'";
        $course_result = mysqli_query($conn, $course_sql);
        $course = mysqli_fetch_assoc($course_result);

        // check the correct answer
        if ($question['question_type'] === 'single_choice') {
            $correct_answer = $question['correct_option'];
        } else {
            $correct_answer = $question['correct_text_answer'];
        }

        // check whether the user's answer matches
        $is_correct = strcasecmp(trim($user_answer), trim($correct_answer)) === 0;

        // prepare PDF context in text
        $material_text = ai_extract_pdf_text($course['course_materials']);

        // find this attempt's learning record so the AI reply can be saved back to it
        $user_id = $_SESSION['user_id'];
        $record_sql = "SELECT learning_record_id FROM QUIZ_LEARNING_RECORD_T WHERE user_id = '$user_id' AND question_id = '$question_id' ORDER BY answered_at DESC LIMIT 1";
        $record_result = mysqli_query($conn, $record_sql);
        $record = mysqli_fetch_assoc($record_result);
        $record_id = $record['learning_record_id'];

        $_SESSION['ai_explain'] = [
            'record_id' => $record_id,
            'messages'  => [
                ['role' => 'system', 'content' => ai_build_system_prompt($course, $quiz, $material_text)],
                ['role' => 'user',   'content' => ai_build_attempt_message($question, $user_answer, $is_correct)]
            ]
        ];

        $quiz_page = "/Implose.gg-src/pages/user/game/live_quiz/quiz.php?quiz_id=$quiz_id";
    ?>

    <title>AI Explanation - Implose.gg</title>
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/ai_explanation.css">
</head>


<body>
    <?php
        $current_page = 'user_course';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">

        <div class="ai-page">

            <!-- left: question recap + next button -->
            <div class="ai-left-column">

                <div class="ai-recap pixel-panel">

                    <span class="course-title pixel-title">
                        <?php echo htmlspecialchars($course['title']); ?>
                    </span>

                    <div class="ai-recap-tags">
                        <span class="ai-chip">
                            Lv <?php echo $quiz['level_number']; ?> : <?php echo htmlspecialchars($quiz['title']); ?>
                        </span>

                        <?php if ($question['topic_tag']): ?>
                            <span class="ai-chip">#<?php echo htmlspecialchars($question['topic_tag']); ?></span>
                        <?php endif; ?>
                    </div>

                    <span class="question-title">QUESTION</span>

                    <p class="ai-question-text">
                        <?php echo htmlspecialchars($question['question_text']); ?>
                    </p>

                    <?php if ($question['question_type'] === 'single_choice'): ?>

                        <div class="ai-options">
                            <?php foreach (['a', 'b', 'c', 'd'] as $letter): ?>
                                <?php
                                    $is_user_pick = strtolower($user_answer) === $letter;
                                    $is_right_one = strtolower($question['correct_option']) === $letter;

                                    $row_class = '';
                                    if ($is_right_one)      $row_class = 'ai-opt-correct';
                                    else if ($is_user_pick) $row_class = 'ai-opt-wrong';
                                ?>


                                <div class="ai-option <?php echo $row_class; ?>">
                                    <span class="ai-option-letter">
                                        <?php echo strtoupper($letter); ?>
                                    </span>

                                    <span class="ai-option-text"><?php echo htmlspecialchars($question['option_' . $letter]); ?></span>

                                    <?php if ($is_user_pick): ?>
                                        <span class="ai-tag ai-tag-you">Your answer</span>
                                    <?php endif; ?>

                                    <?php if ($is_right_one): ?>
                                        <span class="ai-tag ai-tag-correct">Correct</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>

                        <?php
                            if ($is_correct) {
                                $answer_box_class = 'ai-opt-correct';
                            } else {
                                $answer_box_class = 'ai-opt-wrong';
                            }

                            if ($user_answer === '') {
                                $answer_display = '(blank)';
                            } else {
                                $answer_display = htmlspecialchars($user_answer);
                            }
                        ?>

                        <div class="ai-text-answers">
                            <div class="ai-answer-box <?php echo $answer_box_class; ?>">
                                <span class="ai-answer-label">YOUR ANSWER</span>
                                <span class="ai-answer-value"><?php echo $answer_display; ?></span>
                            </div>

                            <div class="ai-answer-box ai-opt-correct">
                                <span class="ai-answer-label">CORRECT ANSWER</span>
                                <span class="ai-answer-value"><?php echo htmlspecialchars($correct_answer); ?></span>
                            </div>
                        </div>

                    <?php endif; ?>

                    <div class="ai-recap-footer">
                        <span class="ai-chip"><?php echo $question['marks']; ?> marks</span>
                        <span class="ai-chip"><?php echo $question['time_limit']; ?>s limit</span>
                    </div>
                </div>

                <a class="ai-next-btn btn-red" href="<?php echo $quiz_page; ?>">Continue Quiz &rarr;</a>

            </div>


            <!-- right: AI tutor chat -->
            <div class="ai-tutor pixel-panel">

                <div class="ai-tutor-head">
                    <img class="ai-tutor-avatar" src="/Implose.gg-src/assets/images/avatar_test/avatar_robot.png" alt="Implose.gg AI Tutor">
                    
                    <div class="ai-tutor-title">
                        <span class="ai-tutor-name pixel-title">IMPLOSE.gg</span>

                        <span class="ai-tutor-role">
                            <span class="ai-status-dot"></span>
                            AI Tutor
                        </span>
                    </div>
                </div>

                <div class="ai-chat" id="ai-chat"></div>

                <form class="ai-input-row" id="ai-form">
                    <div class="txt-container">
                        <input type="text" id="ai-input" placeholder="Still unsure? Ask anything about this question..." maxlength="2000" autocomplete="off" disabled>
                        <button type="button" class="clear-btn" data-target="ai-input">
                            <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="clear">
                        </button>
                    </div>

                    <button type="submit" class="btn-pixel btn-pixel-red" id="ai-send" disabled>
                        <img src="/Implose.gg-src/assets/images/icons/send-1.svg" alt="send">
                    </button>
                </form>

            </div>
        </div>

    </div>


    <script src="/Implose.gg-src/assets/js/user/ai_explanation.js"></script>

</body>
</html>
