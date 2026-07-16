<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/marketplace/quiz_list.php
Description: quiz + question list (put in course details)
First Written on: Monday, 29-Jun-2026
Edited on: Wednesday, 02-Jul-2026
-->

<?php
    // total levels/quiz in this course
    $total_levels = count($quiz_list);
?>


<div class="mkt-quiz-list">
    <?php
        // loop each level in the course
        for ($level_index = 0; $level_index < $total_levels; $level_index++) {
            $quiz = $quiz_list[$level_index];

            // last level = boss battle
            $is_boss = ($level_index === $total_levels - 1);

            if ($is_boss) {
                $level_label = 'Boss Battle';
            } else {
                $level_label = 'Level ' . $quiz['level_number'];
            }
    ?>

        <!-- quiz row -->
        <div class="pixel-panel mkt-quiz">

            <!-- Top -->
            <div class="mkt-quiz-top">

                <!-- level tag -->
                <div class="mkt-quiz-level <?php if ($is_boss) echo 'boss'; ?>">
                    <?php echo $level_label; ?>
                </div>

                <div class="mkt-quiz-info">
                    <!-- quiz title -->
                    <div class="mkt-quiz-title pixel-title">
                        <?php echo $quiz['title']; ?>
                    </div>

                    <!-- quiz description -->
                    <div class="mkt-quiz-desc">
                        <?php echo $quiz['description']; ?>
                    </div>
                </div>
            </div>


            <!-- Right: question count -->
            <div class="mkt-quiz-top-right">
                <strong><?php echo count($quiz['questions']); ?></strong> questions
            </div>


            <!-- question preview -->
            <div class="mkt-question-preview">
                <?php foreach ($quiz['questions'] as $question) { ?>

                    <!-- each question -->
                    <div class="mkt-question-item">

                        <!-- question text -->
                        <div class="mkt-question-text">
                            <?php echo $question['question_text']; ?>
                        </div>


                        <!-- MCQ question -->
                        <?php if ($question['question_type'] === 'single_choice') { ?>
                            <div class="mkt-question-options">
                                <?php
                                    $letters = ['a', 'b', 'c', 'd'];

                                    foreach ($letters as $letter) {
                                        $option_value = $question['option_' . $letter];

                                        // skip empty options
                                        if ($option_value === null || $option_value === '') {
                                            continue;
                                        }

                                        $is_correct = ($question['correct_option'] === $letter);
                                ?>

                                    <!-- mark the correct option green -->
                                    <div class="mkt-question-option <?php if ($is_correct) echo 'is-correct'; ?>">
                                        <!-- option letter -->
                                        <span class="opt-letter">
                                            <?php echo strtoupper($letter); ?>
                                        </span>

                                        <!-- option value -->
                                        <span>
                                            <?php echo $option_value; ?>
                                        </span>

                                        <!-- add correct tag -->
                                        <?php if ($is_correct) { ?>
                                            <span class="opt-correct-tag">CORRECT</span>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>

                        <!-- short answer question -->
                        <?php } else { ?>
                            <div class="mkt-question-answer">
                                <span class="ans-label">
                                    ANSWER:
                                </span>

                                <span class="ans-text"><?php echo $question['correct_text_answer']; ?></span>
                            </div>
                        <?php } ?>


                        <!-- bottom: meta row -->
                        <div class="mkt-question-meta">
                            <!-- left: topic tag -->
                            <div class="mkt-question-meta-left">
                                <span class="mkt-question-tag">
                                    <?php echo $question['topic_tag']; ?>
                                </span>
                            </div>

                            <!-- right -->
                            <div class="mkt-question-meta-right">
                                <!-- question type -->
                                <span class="mkt-question-tag mkt-question-type">
                                    <?php echo $question['question_type']; ?>
                                </span>

                                <!-- marks -->
                                <span class="mkt-question-num">
                                    <?php echo $question['marks']; ?> marks
                                </span>

                                <!-- time limit -->
                                <span class="mkt-question-num">
                                    <?php echo $question['time_limit']; ?>s
                                </span>
                            </div>
                        </div>
                    </div>

                <?php } ?>
            </div>
        </div>

    <?php } ?>
</div>
