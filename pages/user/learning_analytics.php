<!--
Programmer Name: Mr. Ng Jiunn Chyn
Program Name: /pages/user/learning_analytics.php
Description: Boss Battle Learning Analytics Dashboard
            - Scope: ONLY the final boss-battle quiz of each course (MAX level_number)
            - Only the LATEST attempt per boss quiz is shown — historical replays are excluded
              so the dashboard reflects the user's most recent performance, not an average
              across every past retry.
            - Reads QUIZ_LEARNING_RECORD_T joined to QUESTION_T / QUIZ_T for topic tags
            - Tracks accuracy AND response time per topic_tag across boss questions
            - Detects weak topic tags (low accuracy or slow response ratio)
            - Recommends earlier practice quizzes (non-boss levels) that cover those tags
            - Review section is filtered to boss-battle questions only
            - Visualisations: topic accuracy bar, topic response-time bar, per-boss accuracy
First Written on: Tuesday, 24-Jun-2026
Edited on: Sunday, 05-Jul-2026
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <!-- Learning Analytics CSS -->
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/user_learning_analytics.css">

    <!-- import Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <title>Learning Analytics — Implose.gg</title>
    <meta name="description" content="Track your quiz performance, identify weak areas, and get personalized recommendations.">
</head>


<body>
    <?php
        $current_page = 'user_analytics';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/user/nav.php');
    ?>

    <div class="main-content">
        <div class="analytics-page">

            <h1 class="analytics-page-title pixel-title">Boss Battle Analytics</h1>
            <p class="analytics-page-subtitle">Your latest run on the final boss battle of each course — see how you just performed, which topic tags tripped you up, and jump straight to the earlier quizzes that cover them.</p>

            <?php
        
                // BOSS-BATTLE ANALYTICS ENGINE (inline — follows implose_gg.sql schema)    
                $uid = ($_SESSION['user_id'] ?? 0);

                // ── 1) Boss-Battle level detection ──

                $boss_quiz_ids = array();
                $sql_boss = "SELECT qz.quiz_id FROM QUIZ_T qz INNER JOIN (SELECT course_id, MAX(level_number) AS max_level
                             FROM QUIZ_T GROUP BY course_id ) mx ON mx.course_id = qz.course_id AND mx.max_level = qz.level_number";
                if ($res_boss = mysqli_query($conn, $sql_boss)) {
                    while ($row = mysqli_fetch_assoc($res_boss)) {
                        $boss_quiz_ids[] = $row['quiz_id'];
                    }
                }
                $boss_in = !empty($boss_quiz_ids) ? implode(',', $boss_quiz_ids) : '0';

                // ── 1b) Locate the single most-recent boss attempt ──
                $latest_boss_quiz_id = 0;

                $sql_latest = "SELECT q.quiz_id FROM QUIZ_LEARNING_RECORD_T lr JOIN QUESTION_T q ON lr.question_id = q.question_id
                               WHERE lr.user_id = $uid AND q.quiz_id IN ($boss_in) GROUP BY q.quiz_id ORDER BY MAX(lr.answered_at) DESC LIMIT 1";
                if ($res_latest = mysqli_query($conn, $sql_latest)) {
                    if ($row_latest = mysqli_fetch_assoc($res_latest)) {
                        $latest_boss_quiz_id = (int)$row_latest['quiz_id'];
                    }
                }

                if ($latest_boss_quiz_id > 0) {
                    // Correlated subquery: keep this row only if its learning_record_id
                    $latest_filter = "AND q.quiz_id = $latest_boss_quiz_id AND lr.learning_record_id = (SELECT MAX(lr2.learning_record_id) FROM QUIZ_LEARNING_RECORD_T lr2 WHERE lr2.user_id = lr.user_id AND lr2.question_id = lr.question_id)";
                } else {
                    // no boss records at all — force every downstream query to return zero
                    $latest_filter = "AND 1 = 0";
                }

                // ── 1c) Course of the latest boss quiz ──
                $latest_course_id = 0;
                if ($latest_boss_quiz_id > 0) {
                    $sql_cid = "SELECT course_id FROM QUIZ_T WHERE quiz_id = $latest_boss_quiz_id LIMIT 1";
                    if ($res_cid = mysqli_query($conn, $sql_cid)) {
                        if ($row_cid = mysqli_fetch_assoc($res_cid)) {
                            $latest_course_id = (int)$row_cid['course_id'];
                        }
                    }
                }

                // ── 2) Overall boss stats: answered, correct, accuracy, avg response ──
                $overall_stats = array(
                    'total_answered' => 0,
                    'total_correct' => 0,
                    'accuracy_pct' => 0,
                    'avg_response_time' => 0
                );

                $sql = "SELECT
                            COUNT(*) AS total_answered, COALESCE(SUM(lr.is_correct), 0) AS total_correct,
                            ROUND(AVG(lr.is_correct) * 100, 1) AS accuracy_pct, ROUND(AVG(lr.response_time), 1) AS avg_response_time 
                            FROM QUIZ_LEARNING_RECORD_T lr JOIN QUESTION_T q ON lr.question_id = q.question_id 
                            WHERE lr.user_id = $uid AND q.quiz_id IN ($boss_in) $latest_filter";
                if ($res = mysqli_query($conn, $sql)) {
                    if ($row = mysqli_fetch_assoc($res)) {
                        $overall_stats = array(
                            'total_answered' => ($row['total_answered'] ?? 0),
                            'total_correct' => ($row['total_correct'] ?? 0),
                            'accuracy_pct' => ($row['accuracy_pct'] ?? 0),
                            'avg_response_time' => ($row['avg_response_time'] ?? 0)
                        );
                    }
                }

                $has_data = $overall_stats['total_answered'] > 0;

                // ── 3) Topic performance in boss battle (accuracy + response time) ──
                
                $topic_accuracy = array();
                $sql = "SELECT
                            q.topic_tag,
                            COUNT(*) AS total, COALESCE(SUM(lr.is_correct), 0) AS correct,
                            ROUND(AVG(lr.is_correct) * 100, 1) AS accuracy_pct, ROUND(AVG(lr.response_time), 1) AS avg_rt,
                            ROUND(AVG(lr.response_time / GREATEST(q.time_limit, 1)), 3) AS avg_ratio,
                            ROUND(AVG(q.time_limit), 1) AS avg_time_limit
                        FROM QUIZ_LEARNING_RECORD_T lr JOIN QUESTION_T q ON lr.question_id = q.question_id
                        WHERE lr.user_id = $uid AND q.quiz_id IN ($boss_in) AND q.topic_tag IS NOT NULL AND q.topic_tag <> '' $latest_filter
                        GROUP BY q.topic_tag ORDER BY accuracy_pct ASC, avg_ratio DESC";
                if ($res = mysqli_query($conn, $sql)) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $entry = array();
                        $entry['topic_tag']=$row['topic_tag'];
                        $entry['total']=(int)$row['total'];
                        $entry['correct']=(int)$row['correct'];
                        $entry['accuracy_pct']=(float)$row['accuracy_pct'];
                        $entry['avg_rt']=(float)$row['avg_rt'];
                        $entry['avg_ratio']=(float)$row['avg_ratio'];
                        $entry['avg_time_limit'] = (float)$row['avg_time_limit'];
                        $topic_accuracy[] = $entry;
                    }
                }

                // ── 4) Per-topic behaviour patterns in boss battle (rushing / struggling / proficient) ──
                // Ratio = response_time / time_limit
                //   ratio < 0.25 & wrong   → rushing (guessed too fast)
                //   ratio > 0.75           → struggling (ran out the clock)
                //   correct & ratio ≤ 0.75 → proficient
                $topic_behavior = array(); // topic_tag → counts

                $sql = "SELECT
                            q.topic_tag, lr.is_correct, lr.response_time, q.time_limit
                            FROM QUIZ_LEARNING_RECORD_T lr
                            JOIN QUESTION_T q ON lr.question_id = q.question_id
                            WHERE lr.user_id = $uid AND q.quiz_id IN ($boss_in) $latest_filter";
                if ($res = mysqli_query($conn, $sql)) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $tag = $row['topic_tag'] ?? '';
                        $is_correct = $row['is_correct'];
                        $rt = $row['response_time'];
                        $tl = max(1, $row['time_limit']);
                        $ratio = $rt / $tl;

                        $bucket = 'proficient';
                        if ($ratio > 0.75) {
                            $bucket = 'struggling';
                        } elseif (!$is_correct && $ratio < 0.25) {
                            $bucket = 'rushing';
                        } elseif ($is_correct) {
                            $bucket = 'proficient';
                        } else {
                            $bucket = 'rushing';
                        }

                        if ($tag !== '') {
                            if (!isset($topic_behavior[$tag])) {
                                $topic_behavior[$tag] = array('rushing' => 0, 'struggling' => 0, 'proficient' => 0);
                            }
                            $topic_behavior[$tag][$bucket]++;
                        }
                    }
                }

                // Determine dominant behaviour per topic
                $topic_dominant = array();
                foreach ($topic_behavior as $tag => $counts) {
                    $dominant = 'proficient';
                    $max = $counts['proficient'];
                    if ($counts['rushing'] > $max) { $dominant = 'rushing'; $max = $counts['rushing']; }
                    if ($counts['struggling'] > $max) { $dominant = 'struggling'; }
                    $entry = array();
                    $entry['dominant_behavior']=$dominant;
                    $entry['rushing']=$counts['rushing'];
                    $entry['struggling']=$counts['struggling'];
                    $entry['proficient']=$counts['proficient'];
                    $topic_dominant[$tag]=$entry;
                }

                // ── 5) Boss weak / great topics ──
                // Weak = boss accuracy < 60%  OR  slow (avg_ratio > 0.6)  OR  struggling-dominant
                // Great = boss accuracy ≥ 80% AND not slow
                $weak_topics = array();
                $great_topics = array();
                foreach ($topic_accuracy as $t) {
                    $beh = $topic_dominant[$t['topic_tag']] ?? null;
                    $t['behavior'] = $beh;

                    $is_slow = ($t['avg_ratio'] > 0.6);
                    $is_weak =
                        $t['accuracy_pct'] < 60
                        || $is_slow
                        || ($beh && $beh['dominant_behavior'] === 'struggling');

                    if ($is_weak) {
                        $t['is_slow'] = $is_slow;
                        $weak_topics[] = $t;
                    } elseif ($t['accuracy_pct'] >= 80 && !$is_slow) {
                        $great_topics[] = $t;
                    }
                }

                // Sort great topics by accuracy descending
                usort($great_topics, function($a, $b) {
                    return $b['accuracy_pct'] <=> $a['accuracy_pct'];
                });

                // ── 6b) Boss-battle question-level review ──
                // Same rushing / struggling / proficient rules as section 4, applied
                // per record so review cards align with the topic verdicts above.
                $review_questions = array();
                $review_counts = array('all' => 0, 'rushing' => 0, 'struggling' => 0, 'proficient' => 0, 'wrong' => 0);

                $sql = "SELECT
                            lr.learning_record_id, lr.question_id, lr.selected_option, lr.text_answer, lr.is_correct, lr.marks_earned, lr.response_time, lr.answered_at,
                            q.question_text, q.question_type, q.topic_tag, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option, q.correct_text_answer,q.marks,
                            q.time_limit, qz.title AS quiz_title
                        FROM QUIZ_LEARNING_RECORD_T lr JOIN QUESTION_T q ON lr.question_id = q.question_id LEFT JOIN QUIZ_T qz ON q.quiz_id = qz.quiz_id
                        WHERE lr.user_id = $uid AND q.quiz_id IN ($boss_in) $latest_filter
                        ORDER BY lr.answered_at DESC";
                if ($res = mysqli_query($conn, $sql)) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $rt = (float)$row['response_time'];
                        $tl = max(1, (int)$row['time_limit']);
                        $ratio = $rt / $tl;

                        $bucket = 'proficient';
                        if ($ratio > 0.75) {
                            $bucket = 'struggling';
                        } elseif (!$row['is_correct'] && $ratio < 0.25) {
                            $bucket = 'rushing';
                        } elseif ($row['is_correct']) {
                            $bucket = 'proficient';
                        } else {
                            $bucket = 'rushing';
                        }

                        $entry = array();
                        $entry['record_id'] = $row['learning_record_id']; $entry['question_id'] = $row['question_id']; $entry['question_text'] = $row['question_text'];
                        $entry['question_type'] = $row['question_type']; $entry['topic_tag'] = $row['topic_tag']; $entry['options'] = array(
                            'a' => $row['option_a'],
                            'b' => $row['option_b'],
                            'c' => $row['option_c'],
                            'd' => $row['option_d']
                        );
                        $entry['correct_option'] = $row['correct_option']; $entry['correct_text_answer'] = $row['correct_text_answer']; $entry['selected_option'] = $row['selected_option']; $entry['text_answer'] = $row['text_answer'];
                        $entry['is_correct'] = (int)$row['is_correct']; $entry['marks_earned'] = (int)$row['marks_earned']; $entry['marks'] = (int)$row['marks']; $entry['response_time'] = $rt; $entry['time_limit'] = $tl;
                        $entry['behavior'] = $bucket; $entry['quiz_title'] = $row['quiz_title']; $entry['answered_at'] = $row['answered_at']; $review_questions[] = $entry; $review_counts['all']++; $review_counts[$bucket]++;
                        if (!$entry['is_correct']) { $review_counts['wrong']++; }
                    }
                }


                // ── 6c) Weak-topic → practice course lookup ──
                $topic_course_map = array();
                if (!empty($weak_topics)) {
                    $tags_esc = array();
                    foreach ($weak_topics as $w) {
                        $tags_esc[] = "'" . mysqli_real_escape_string($conn, $w['topic_tag']) . "'";
                    }
                    $tags_in_map = implode(',', $tags_esc);

                    $sql = "SELECT qt.topic_tag, qz.course_id, qz.level_number, qz.title AS quiz_title FROM QUESTION_T qt JOIN QUIZ_T   qz ON qt.quiz_id   = qz.quiz_id JOIN COURSE_T c  ON qz.course_id = c.course_id JOIN ( SELECT course_id, MAX(level_number) AS max_level FROM QUIZ_T GROUP BY course_id ) mx ON mx.course_id = qz.course_id WHERE qt.topic_tag IN ($tags_in_map) AND qz.level_number < mx.max_level AND c.is_deleted = 0 ORDER BY qz.level_number ASC";
                    if ($res = mysqli_query($conn, $sql)) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            $tag = $row['topic_tag'];
                            // first (lowest level) hit wins — ORDER BY makes that reliable
                            if (!isset($topic_course_map[$tag])) {
                                $topic_course_map[$tag] = array(
                                    'course_id' => (int)$row['course_id'],
                                    'quiz_title' => $row['quiz_title']
                                );
                            }
                        }
                    }
                }

                // ── 7) Targeted revision recommendations ──
                $recommendations = array();
                if (!empty($weak_topics)) {
                    $tags = array();
                    foreach ($weak_topics as $w) {
                        $tags[] = "'" . mysqli_real_escape_string($conn, $w['topic_tag']) . "'";
                    }
                    $tags_in = implode(',', $tags);

                    $sql = "SELECT DISTINCT qz.quiz_id, qz.title AS quiz_title, qz.description AS quiz_description, qz.level_number, c.title AS course_title, c.course_id, qt.topic_tag FROM QUESTION_T qt JOIN QUIZ_T qz ON qt.quiz_id = qz.quiz_id JOIN COURSE_T c ON qz.course_id = c.course_id JOIN (
                            SELECT course_id, MAX(level_number) AS max_level FROM QUIZ_T GROUP BY course_id) mx ON mx.course_id = qz.course_id
                            WHERE qt.topic_tag IN ($tags_in) AND qz.level_number < mx.max_level AND c.is_deleted = 0 ORDER BY FIELD(qt.topic_tag, $tags_in), qz.level_number ASC LIMIT 6";
                    if ($res = mysqli_query($conn, $sql)) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            $entry = array();
                            $entry['quiz_id'] = $row['quiz_id'];
                            $entry['quiz_title'] = $row['quiz_title'];
                            $entry['quiz_description'] = $row['quiz_description'];
                            $entry['level_number'] = $row['level_number'];
                            $entry['course_title'] = $row['course_title'];
                            $entry['course_id'] = $row['course_id'];
                            $entry['topic_tag'] = $row['topic_tag'];
                            $recommendations[] = $entry;
                        }
                    }
                }
            ?>

            <?php if (!$has_data): ?>

                <!-- ── Empty State ── -->
                <div class="analytics-empty-state pixel-panel">
                    <span class="analytics-empty-icon">👑</span>
                    <h2 class="analytics-empty-title pixel-title">No Boss Battle Finished Yet</h2>
                    <p class="analytics-empty-text">
                        Finish a course's final boss-battle quiz to unlock your analytics. The dashboard shows only your most recent run — accuracy, response time, and the topics that tripped you up — then points you back to the earlier quizzes that cover them.
                    </p>
                </div>

            <?php else: ?>

                <!-- SECTION 1: Boss Battle Overview Stat Card -->
                <div class="analytics-stats-row">

                    <div class="analytics-stat-card pixel-panel">
                        <span class="analytics-stat-label">Boss Questions Answered</span>
                        <span class="analytics-stat-value accent-cyan">
                            <?php echo $overall_stats['total_answered']; ?>
                        </span>
                    </div>

                    <div class="analytics-stat-card pixel-panel">
                        <span class="analytics-stat-label">Boss Accuracy</span>
                        <span class="analytics-stat-value accent-green">
                            <?php echo $overall_stats['accuracy_pct']; ?><span class="analytics-stat-unit">%</span>
                        </span>
                    </div>

                    <div class="analytics-stat-card pixel-panel">
                        <span class="analytics-stat-label">Avg Response</span>
                        <span class="analytics-stat-value accent-orange">
                            <?php echo $overall_stats['avg_response_time']; ?><span class="analytics-stat-unit">s</span>
                        </span>
                    </div>

                    <div class="analytics-stat-card pixel-panel">
                        <span class="analytics-stat-label">Boss Correct</span>
                        <span class="analytics-stat-value accent-red">
                            <?php echo $overall_stats['total_correct']; ?>
                        </span>
                    </div>

                </div>


                <!-- SECTION 2: Boss Topic Accuracy (bar) — how well you did on each topic tag in the final level. -->
                <div class="analytics-charts-row">
                    <div class="analytics-chart-card pixel-panel">
                        <h3 class="pixel-title">Boss Topic Accuracy</h3>
                        <p class="chart-subtitle">Percentage of boss-battle questions you answered correctly per topic tag in your latest run.</p>
                        <div class="chart-container">
                            <canvas id="topicChart"></canvas>
                        </div>
                    </div>
                </div>


                <!-- SECTION 2B: Boss Topic Response Time (bar) — how long you took vs. each question's time limit. -->
                <div class="analytics-charts-row">
                    <div class="analytics-chart-card pixel-panel">
                        <h3 class="pixel-title">Boss Topic Response Time</h3>
                        <p class="chart-subtitle">Average seconds spent per topic tag in your latest run versus that topic's time limit. Bars near the limit indicate topics you're eating too much of the clock on.</p>
                        <div class="chart-container">
                            <canvas id="timeChart"></canvas>
                        </div>
                    </div>
                </div>


                <!-- SECTION 4: Weak & Great Topics Panels -->
                <div class="analytics-topics-row">

                    <!-- Weak Topics -->
                    <div class="analytics-topic-panel pixel-panel">
                        <h3 class="pixel-title">
                            <span>⚠️</span> Weak Boss Topics
                        </h3>

                        <?php if (empty($weak_topics)): ?>
                            <div class="topic-empty">
                                <span class="topic-empty-icon">🎉</span>
                                No weak boss topics — you're crushing every final level!
                            </div>
                        <?php else: ?>
                            <div class="topic-list">
                                <?php foreach ($weak_topics as $wt): ?>
                                    <?php $revise = $topic_course_map[$wt['topic_tag']] ?? null; ?>
                                    <div class="topic-item">
                                        <div class="topic-item-left">
                                            <span class="topic-item-name"><?php echo htmlspecialchars($wt['topic_tag']); ?></span>
                                            <div class="topic-item-meta">
                                                <span class="topic-accuracy weak"><?php echo $wt['accuracy_pct']; ?>%</span>
                                                <span class="topic-time <?php echo !empty($wt['is_slow']) ? 'slow' : ''; ?>">
                                                    ⏱ <?php echo $wt['avg_rt']; ?>s
                                                </span>
                                                <?php if (!empty($wt['behavior'])): ?>
                                                    <span class="behavior-badge <?php echo $wt['behavior']['dominant_behavior']; ?>">
                                                        <?php echo ucfirst($wt['behavior']['dominant_behavior']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($revise): ?>
                                            <div class="topic-item-right">
                                                <a href="/Implose.gg-src/pages/user/game/manage_course.php?course_id=<?php echo (int)$revise['course_id']; ?>"
                                                   class="btn-red btn-pixel">
                                                    Revise
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Great Topics -->
                    <div class="analytics-topic-panel pixel-panel">
                        <h3 class="pixel-title">
                            <span>⭐</span> Strong Boss Topics
                        </h3>

                        <?php if (empty($great_topics)): ?>
                            <div class="topic-empty">
                                <span class="topic-empty-icon">💪</span>
                                Finish more boss battles to unlock your strongest topics.
                            </div>
                        <?php else: ?>
                            <div class="topic-list">
                                <?php foreach ($great_topics as $gt): ?>
                                    <div class="topic-item">
                                        <div class="topic-item-left">
                                            <span class="topic-item-name"><?php echo htmlspecialchars($gt['topic_tag']); ?></span>
                                            <div class="topic-item-meta">
                                                <span class="topic-accuracy great"><?php echo $gt['accuracy_pct']; ?>%</span>
                                                <span class="topic-time">⏱ <?php echo $gt['avg_rt']; ?>s</span>
                                                <?php if (!empty($gt['behavior'])): ?>
                                                    <span class="behavior-badge <?php echo $gt['behavior']['dominant_behavior']; ?>">
                                                        <?php echo ucfirst($gt['behavior']['dominant_behavior']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>


                <!-- SECTION 4B: Review Questions (per-question analytics) -->
                <?php if (!empty($review_questions)): ?>
                <div class="analytics-review-section">
                    <div class="analytics-review-panel pixel-panel">

                        <div class="review-header">
                            <h3 class="pixel-title">👑 Review Boss Battle Questions</h3>
                            <p class="review-subtitle">
                                Every question below is from your latest run of a course's final boss battle. Click a card to reveal the answer, your timing, and its learning pattern.
                            </p>
                        </div>

                        <!-- Filter tabs -->
                        <div class="review-filters" role="tablist">
                            <button type="button" class="review-filter active" data-filter="all">
                                All <span class="filter-count"><?php echo $review_counts['all']; ?></span>
                            </button>
                            <button type="button" class="review-filter" data-filter="rushing">
                                🏃 Rushing <span class="filter-count"><?php echo $review_counts['rushing']; ?></span>
                            </button>
                            <button type="button" class="review-filter" data-filter="struggling">
                                🤔 Struggling <span class="filter-count"><?php echo $review_counts['struggling']; ?></span>
                            </button>
                            <button type="button" class="review-filter" data-filter="proficient">
                                ⭐ Proficient <span class="filter-count"><?php echo $review_counts['proficient']; ?></span>
                            </button>
                            <button type="button" class="review-filter" data-filter="wrong">
                                ❌ Wrong Only <span class="filter-count"><?php echo $review_counts['wrong']; ?></span>
                            </button>
                        </div>

                        <!-- Question list -->
                        <div class="review-list">
                            <?php foreach ($review_questions as $i => $rq): ?>
                                <?php
                                    $is_wrong = ($rq['is_correct'] === 0);
                                    $behavior = $rq['behavior'];
                                    $filter_classes = 'behavior-' . $behavior;
                                    if ($is_wrong) { $filter_classes .= ' is-wrong'; }
                                ?>
                                <div class="review-card <?php echo $filter_classes; ?>"
                                     data-behavior="<?php echo $behavior; ?>"
                                     data-wrong="<?php echo $is_wrong ? '1' : '0'; ?>">

                                    <!-- Card header (always visible, click to toggle) -->
                                    <button type="button" class="review-card-header" aria-expanded="false">
                                        <div class="review-card-num">
                                            <span class="review-num-badge"><?php echo ($i + 1); ?></span>
                                        </div>

                                        <div class="review-card-main">
                                            <div class="review-card-question">
                                                <?php echo htmlspecialchars($rq['question_text']); ?>
                                            </div>
                                            <div class="review-card-meta">
                                                <?php if (!empty($rq['topic_tag'])): ?>
                                                    <span class="review-tag topic">
                                                        <?php echo htmlspecialchars($rq['topic_tag']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($rq['quiz_title'])): ?>
                                                    <span class="review-tag quiz">
                                                        <?php echo htmlspecialchars($rq['quiz_title']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="review-card-side">
                                            <div class="review-time <?php echo $behavior; ?>">
                                                <span class="review-time-num"><?php echo number_format($rq['response_time'], 1); ?>s</span>
                                                <span class="review-time-limit">/ <?php echo $rq['time_limit']; ?>s</span>
                                            </div>
                                            <span class="behavior-badge <?php echo $behavior; ?>">
                                                <?php echo ucfirst($behavior); ?>
                                            </span>
                                            <span class="review-verdict <?php echo $is_wrong ? 'wrong' : 'correct'; ?>">
                                                <?php echo $is_wrong ? '✕ Wrong' : '✓ Correct'; ?>
                                            </span>
                                            <span class="review-toggle-icon">▼</span>
                                        </div>
                                    </button>

                                    <!-- Card body (revealed on click) -->
                                    <div class="review-card-body">
                                        <?php if ($rq['question_type'] === 'single_choice'): ?>
                                            <div class="review-options">
                                                <?php foreach (['a', 'b', 'c', 'd'] as $letter): ?>
                                                    <?php
                                                        $opt_text = $rq['options'][$letter] ?? null;
                                                        if ($opt_text === null || $opt_text === '') continue;
                                                        $is_correct_opt = ($rq['correct_option'] === $letter);
                                                        $is_selected = ($rq['selected_option'] === $letter);
                                                        $opt_class = '';
                                                        if ($is_correct_opt) $opt_class = 'correct';
                                                        elseif ($is_selected) $opt_class = 'wrong-pick';
                                                    ?>
                                                    <div class="review-option <?php echo $opt_class; ?>">
                                                        <span class="review-option-letter"><?php echo strtoupper($letter); ?></span>
                                                        <span class="review-option-text"><?php echo htmlspecialchars($opt_text); ?></span>
                                                        <span class="review-option-badges">
                                                            <?php if ($is_selected): ?>
                                                                <span class="opt-badge your">Your answer</span>
                                                            <?php endif; ?>
                                                            <?php if ($is_correct_opt): ?>
                                                                <span class="opt-badge correct">Correct</span>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="review-text-answers">
                                                <div class="review-text-row">
                                                    <span class="review-text-label">Your answer</span>
                                                    <span class="review-text-value <?php echo $is_wrong ? 'wrong-pick' : 'correct'; ?>">
                                                        <?php echo htmlspecialchars($rq['text_answer'] ?? '(no answer)'); ?>
                                                    </span>
                                                </div>
                                                <div class="review-text-row">
                                                    <span class="review-text-label">Correct answer</span>
                                                    <span class="review-text-value correct">
                                                        <?php echo htmlspecialchars($rq['correct_text_answer'] ?? ''); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Ask AI to explain this question -->
                                        <div class="review-ai-row">
                                            <button type="button"
                                                    class="review-ai-btn"
                                                    data-record-id="<?php echo (int)$rq['record_id']; ?>"
                                                    data-question="<?php echo htmlspecialchars($rq['question_text'], ENT_QUOTES); ?>">
                                                <span class="review-ai-btn-icon">🤖</span>
                                                <span class="review-ai-btn-text">Ask AI to Explain</span>
                                            </button>
                                            <span class="review-ai-hint">Get a personalised breakdown of your answer.</span>
                                        </div>

                                        <div class="review-footer">
                                            <span class="review-footer-item">
                                                <strong>Marks earned:</strong>
                                                <?php echo $rq['marks_earned']; ?> / <?php echo $rq['marks']; ?>
                                            </span>
                                            <span class="review-footer-item">
                                                <strong>Answered:</strong>
                                                <?php echo date('M j, Y g:i A', strtotime($rq['answered_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="review-empty-filter" style="display: none;">
                            <span class="review-empty-icon">🔍</span>
                            No questions match this filter.
                        </div>
                    </div>
                </div>
                <?php endif; ?>


                <!-- SECTION 5: Recommended Quizzes -->
                <?php if (!empty($recommendations)): ?>
                <div class="analytics-recommendations">
                    <div class="analytics-recommendations-panel pixel-panel">
                        <h3 class="pixel-title">🎯 Revise These To Beat the Boss</h3>
                        <p class="chart-subtitle">Practice quizzes from earlier levels that cover the topics you struggled with in your latest boss run.</p>

                        <div class="recommendations-grid">
                            <?php foreach ($recommendations as $rec): ?>
                                <div class="recommendation-card">
                                    <span class="recommendation-topic-tag"><?php echo htmlspecialchars($rec['topic_tag']); ?></span>
                                    <span class="recommendation-title"><?php echo htmlspecialchars($rec['quiz_title']); ?></span>
                                    <span class="recommendation-course">
                                        <?php echo htmlspecialchars($rec['course_title']); ?>
                                        &nbsp;·&nbsp;Level <?php echo (int)$rec['level_number']; ?>
                                    </span>
                                    <div class="recommendation-btn">
                                        <a href="/Implose.gg-src/pages/user/game/manage_course.php?course_id=<?php echo (int)$rec['course_id']; ?>" class="btn-red">
                                            Revise Now
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>


                <!-- SECTION 6: Bottom Navigation — Revision / Next -->
                <div class="analytics-bottom-nav">
                    <?php if ($latest_course_id > 0): ?>
                        <a href="/Implose.gg-src/pages/user/game/manage_course.php?course_id=<?php echo $latest_course_id; ?>"
                           class="btn-red">
                            Revision
                        </a>
                    <?php endif; ?>
                    <a href="/Implose.gg-src/pages/user/game/view_course.php?tab=all"
                       class="btn-red">
                        Next
                    </a>
                </div>


            <?php endif; ?>

        </div>
    </div>


    <?php if ($has_data && !empty($review_questions)): ?>
    <!-- AI Tutor Modal (shared across all review cards) -->
    <div class="ai-modal-overlay" id="ai-modal" aria-hidden="true">
        <div class="ai-modal pixel-panel" role="dialog" aria-modal="true" aria-labelledby="ai-modal-title">

            <div class="ai-modal-head">
                <div class="ai-modal-title-block">
                    <span class="ai-modal-eyebrow">🤖 AI Tutor</span>
                    <h3 class="ai-modal-title pixel-title" id="ai-modal-title">Explanation</h3>
                    <p class="ai-modal-question" id="ai-modal-question"></p>
                </div>
                <button type="button" class="ai-modal-close" id="ai-modal-close" aria-label="Close AI explanation">✕</button>
            </div>

            <div class="ai-chat" id="ai-chat" aria-live="polite"></div>
        </div>
    </div>
    <?php endif; ?>


    <?php if ($has_data): ?>
    <script>
    // ── Chart.js Global Defaults ──
    Chart.defaults.color = '#b8bec9';
    Chart.defaults.font.family = "'Pixelify Sans', sans-serif";
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding = 16;


    // CHART 1: Boss Topic Accuracy (Bar) — per topic_tag 
    (function() {
        const topicData = <?php echo json_encode($topic_accuracy); ?>;
        if (!topicData.length) return;

        const labels = topicData.map(function(t) { return t.topic_tag; });
        const values = topicData.map(function(t) { return t.accuracy_pct; });
        const colors = values.map(function(v) {
            if (v >= 80) return 'rgba(74, 222, 128, 0.85)';
            if (v >= 60) return 'rgba(245, 158, 11, 0.85)';
            return 'rgba(239, 68, 68, 0.85)';
        });
        const borderColors = values.map(function(v) {
            if (v >= 80) return '#4ADE80';
            if (v >= 60) return '#f59e0b';
            return '#ef4444';
        });

        new Chart(document.getElementById('topicChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Boss Accuracy %',
                    data: values,
                    backgroundColor: colors,
                    hoverBackgroundColor: colors,
                    borderColor: borderColors,
                    hoverBorderColor: borderColors,
                    borderWidth: 2,
                    borderRadius: 4,
                    maxBarThickness: 48
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                animations: { colors: false, numbers: false },
                transitions: { active: { animation: { duration: 0 } } },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(255,255,255,0.06)' },
                        ticks: { callback: function(v) { return v + '%'; } }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var t = topicData[ctx.dataIndex];
                                return [
                                    ctx.parsed.y + '% accuracy',
                                    t.correct + ' / ' + t.total + ' correct',
                                    'Avg response: ' + t.avg_rt + 's'
                                ];
                            }
                        }
                    }
                }
            }
        });
    })();


    // CHART 2: Boss Topic Response Time (Bar + limit line)
    // Green = well under limit, orange = getting slow,
    // red = eating most of the time limit.
    (function() {
        const topicData = <?php echo json_encode($topic_accuracy); ?>;
        if (!topicData.length) return;

        const labels = topicData.map(function(t) { return t.topic_tag; });
        const values = topicData.map(function(t) { return t.avg_rt; });
        const ratios = topicData.map(function(t) { return t.avg_ratio; });
        const limits = topicData.map(function(t) { return t.avg_time_limit; });

        const colors = ratios.map(function(r) {
            if (r > 0.75) return 'rgba(239, 68, 68, 0.85)';
            if (r > 0.5) return 'rgba(245, 158, 11, 0.85)';
            return 'rgba(74, 222, 128, 0.85)';
        });
        const borderColors = ratios.map(function(r) {
            if (r > 0.75) return '#ef4444';
            if (r > 0.5) return '#f59e0b';
            return '#4ADE80';
        });

        new Chart(document.getElementById('timeChart'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Avg Response (s)',
                        data: values,
                        backgroundColor: colors,
                        hoverBackgroundColor: colors,
                        borderColor: borderColors,
                        hoverBorderColor: borderColors,
                        borderWidth: 2,
                        borderRadius: 4,
                        maxBarThickness: 48,
                        order: 2
                    },
                    {
                        label: 'Time Limit (s)',
                        data: limits,
                        type: 'line',
                        borderColor: 'rgba(0, 212, 255, 0.85)',
                        hoverBorderColor: 'rgba(0, 212, 255, 0.85)',
                        backgroundColor: 'rgba(0, 212, 255, 0.2)',
                        hoverBackgroundColor: 'rgba(0, 212, 255, 0.2)',
                        borderWidth: 2,
                        borderDash: [6, 6],
                        pointRadius: 4,
                        pointHoverRadius: 4,
                        pointBackgroundColor: '#00d4ff',
                        pointHoverBackgroundColor: '#00d4ff',
                        fill: false,
                        tension: 0,
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                animations: { colors: false, numbers: false },
                transitions: { active: { animation: { duration: 0 } } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.06)' },
                        ticks: { callback: function(v) { return v + 's'; } },
                        title: { display: true, text: 'Seconds', color: '#b8bec9' }
                    },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { display: true, position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                if (ctx.datasetIndex === 0) {
                                    var t = topicData[ctx.dataIndex];
                                    var pct = Math.round(t.avg_ratio * 100);
                                    return [
                                        'Avg response: ' + t.avg_rt + 's',
                                        'Used ' + pct + '% of time limit'
                                    ];
                                }
                                return 'Time limit: ' + ctx.parsed.y + 's';
                            }
                        }
                    }
                }
            }
        });
    })();


    
    // REVIEW QUESTIONS: expand/collapse + filter tabs
    (function() {
        const cards = document.querySelectorAll('.review-card');
        const tabs = document.querySelectorAll('.review-filter');
        const empty = document.querySelector('.review-empty-filter');
        const list = document.querySelector('.review-list');
        if (!cards.length) return;

        // Toggle expand/collapse on card header click
        cards.forEach(function(card) {
            const header = card.querySelector('.review-card-header');
            if (!header) return;
            header.addEventListener('click', function() {
                const isOpen = card.classList.toggle('expanded');
                header.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });

        // Filter tabs
        function applyFilter(filter) {
            let visible = 0;
            cards.forEach(function(card) {
                const beh = card.getAttribute('data-behavior');
                const wrong = card.getAttribute('data-wrong') === '1';
                let show;
                if (filter === 'all') show = true;
                else if (filter === 'wrong') show = wrong;
                else show = (beh === filter);
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (empty) empty.style.display = (visible === 0) ? '' : 'none';
            if (list) list.style.display = (visible === 0) ? 'none' : '';
        }

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                tabs.forEach(function(t) { t.classList.remove('active'); });
                tab.classList.add('active');
                applyFilter(tab.getAttribute('data-filter'));
            });
        });
    })();


    // AI TUTOR MODAL: open per-question AI explanation
    (function() {
        const modal = document.getElementById('ai-modal');
        const closeBtn = document.getElementById('ai-modal-close');
        const questionEl = document.getElementById('ai-modal-question');
        const chatBox = document.getElementById('ai-chat');
        const triggers = document.querySelectorAll('.review-ai-btn');

        if (!modal || !triggers.length) return;

        const startUrl = '/Implose.gg-src/api/game/ai_explanation/start.php';
        const askUrl = '/Implose.gg-src/api/game/ai_explanation/ask.php';

        const loadingVerbs = ['Thinking','Pondering','Reviewing','Reasoning','Analysing','Considering','Piecing it together','Working it out','Reflecting','Reading the material','Cross-checking','Explaining'];


        // ── Helpers ──
        function scrollChat() {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function addTutorBubble(html) {
            const bubble = document.createElement('div');
            bubble.className = 'ai-msg ai-msg-tutor';
            bubble.innerHTML = '<div class="ai-msg-body">' + html + '</div>';
            chatBox.appendChild(bubble);
            scrollChat();
            return bubble;
        }

        function addLoadingBubble() {
            const bubble = document.createElement('div');
            bubble.className = 'ai-msg ai-msg-tutor';
            bubble.innerHTML =
                '<div class="ai-msg-loading">' +
                    '<span class="ai-loading-verb"></span>' +
                    '<span class="ai-loading-dots"><i>.</i><i>.</i><i>.</i></span>' +
                '</div>' +
                '<div class="ai-msg-body"></div>';
            chatBox.appendChild(bubble);
            scrollChat();

            const verbEl = bubble.querySelector('.ai-loading-verb');
            verbEl.textContent = loadingVerbs[Math.floor(Math.random() * loadingVerbs.length)];
            const timer = setInterval(function() {
                verbEl.textContent = loadingVerbs[Math.floor(Math.random() * loadingVerbs.length)];
            }, 900);

            return { bubble: bubble, timer: timer };
        }

        function showError(msg) {
            addTutorBubble('<span class="ai-error">⚠️ ' + msg + '</span>');
        }


        // ── Fetch the fresh explanation from ask.php ──
        function fetchExplanation() {
            const loading = addLoadingBubble();

            const body = new FormData();
            body.append('mode', 'explain');
            body.append('message', '');

            fetch(askUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    clearInterval(loading.timer);
                    const bodyEl = loading.bubble.querySelector('.ai-msg-body');
                    loading.bubble.querySelector('.ai-msg-loading').remove();

                    if (data.error) {
                        bodyEl.innerHTML = '<span class="ai-error">⚠️ ' + data.error + '</span>';
                    } else {
                        bodyEl.innerHTML = data.reply_html || '';
                    }
                    scrollChat();
                })
                .catch(function() {
                    clearInterval(loading.timer);
                    const bodyEl = loading.bubble.querySelector('.ai-msg-body');
                    loading.bubble.querySelector('.ai-msg-loading').remove();
                    bodyEl.innerHTML = '<span class="ai-error">⚠️ Network error. Please try again.</span>';
                });
        }


        // ── Open modal + prime the session ──────────
        function openModal(recordId, questionText) {
            chatBox.innerHTML = '';
            questionEl.textContent = questionText || '';
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('ai-modal-locked');

            const loading = addLoadingBubble();

            const body = new FormData();
            body.append('record_id', recordId);

            fetch(startUrl, { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    clearInterval(loading.timer);
                    loading.bubble.remove();

                    if (data.error) {
                        showError(data.error);
                        return;
                    }

                    if (data.cached && data.reply_html) {
                        // we've explained this record before, show it directly
                        addTutorBubble(data.reply_html);
                    } else {
                        // fresh session — ask the tutor for the first explanation
                        fetchExplanation();
                    }
                })
                .catch(function() {
                    clearInterval(loading.timer);
                    loading.bubble.remove();
                    showError('Could not start the AI tutor. Please try again.');
                });
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('ai-modal-locked');
            chatBox.innerHTML = '';
        }


        // ── Wire up events ──────────────────────────
        triggers.forEach(function(btn) {
            btn.addEventListener('click', function(ev) {
                ev.stopPropagation(); // don't collapse the card
                const rid = parseInt(btn.getAttribute('data-record-id'), 10);
                const q = btn.getAttribute('data-question') || '';
                if (rid > 0) openModal(rid, q);
            });
        });

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function(ev) {
            if (ev.target === modal) closeModal();
        });
        document.addEventListener('keydown', function(ev) {
            if (ev.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
        });
    })();


    </script>
    <?php endif; ?>

</body>
</html>
