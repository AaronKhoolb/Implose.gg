<?php
/*
Programmer Name: Mr. Ng Jiunn Chyn
Program Name: /includes/analytics_engine.php
Description: Analytics helpers used by /pages/admin/learning_analytics.php.
            - getTopicAccuracy / getBehaviorPatterns: per-user breakdowns the
              admin page loops through when rendering the user-performance table
            - getSystemWideAnalytics, getSystemTopicAccuracy,
              getSystemBehaviorPatterns, getSystemProgressOverTime,
              getUserPerformanceSummary: aggregates for the admin dashboard
            All queries source from QUIZ_LEARNING_RECORD_T + QUESTION_T + USER_T.
            (The user-facing /pages/user/learning_analytics.php runs its own
             inline queries and no longer depends on this file.)
First Written on: Tuesday, 24-Jun-2026
Edited on: Wednesday, 02-Jul-2026
*/

// Get accuracy per topic_tag for a user. Returns array of rows with: topic_tag, total, correct, accuracy_pct
 
function getTopicAccuracy($conn, $user_id) {
    $user_id = $user_id;

    $sql = "SELECT
                q.topic_tag,
                COUNT(*) AS total,
                SUM(lr.is_correct) AS correct,
                ROUND(AVG(lr.is_correct) * 100, 1) AS accuracy_pct
            FROM QUIZ_LEARNING_RECORD_T lr
            JOIN QUESTION_T q ON lr.question_id = q.question_id
            WHERE lr.user_id = $user_id
              AND q.topic_tag IS NOT NULL
              AND q.topic_tag != ''
            GROUP BY q.topic_tag
            ORDER BY accuracy_pct ASC";

    $result = mysqli_query($conn, $sql);
    $topics = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $entry = array();
            $entry['topic_tag']    = $row['topic_tag'];
            $entry['total']        = $row['total'];
            $entry['correct']      = $row['correct'];
            $entry['accuracy_pct'] = $row['accuracy_pct'];
            $topics[] = $entry;
        }
    }

    return $topics;
}


// Classify each answer into behavior patterns. Returns: rushing, struggling, proficient, slow_correct, moderate_wrong, total

function getBehaviorPatterns($conn, $user_id) {
    $user_id = $user_id;

    $sql = "SELECT
                lr.is_correct,
                lr.response_time,
                q.time_limit
            FROM QUIZ_LEARNING_RECORD_T lr
            JOIN QUESTION_T q ON lr.question_id = q.question_id
            WHERE lr.user_id = $user_id";

    $result = mysqli_query($conn, $sql);

    $patterns = array();
    $patterns['rushing']        = 0;
    $patterns['struggling']     = 0;
    $patterns['proficient']     = 0;
    $patterns['slow_correct']   = 0;
    $patterns['moderate_wrong'] = 0;
    $patterns['total']          = 0;

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $patterns['total']++;
            $is_correct    = $row['is_correct'];
            $response_time = $row['response_time'];
            $time_limit    = $row['time_limit'];

            if ($time_limit <= 0) {
                $time_limit = 1;
            }

            $ratio = $response_time / $time_limit;

            if (!$is_correct && $ratio < 0.25) {
                $patterns['rushing']++;
            } elseif (!$is_correct && $ratio > 0.75) {
                $patterns['struggling']++;
            } elseif ($is_correct && $ratio <= 0.75) {
                $patterns['proficient']++;
            } elseif ($is_correct && $ratio > 0.75) {
                $patterns['slow_correct']++;
            } else {
                $patterns['moderate_wrong']++;
            }
        }
    }

    return $patterns;
}


// ADMIN: System-wide analyticsReturns aggregate stats across all users.

function getSystemWideAnalytics($conn) {
    $sql = "SELECT
                COUNT(*) AS total_attempts,
                ROUND(AVG(is_correct) * 100, 1) AS avg_accuracy,
                COUNT(DISTINCT user_id) AS active_learners,
                ROUND(AVG(response_time), 1) AS avg_response_time
            FROM QUIZ_LEARNING_RECORD_T";

    $result = mysqli_query($conn, $sql);

    $stats = array();
    $stats['total_attempts']    = 0;
    $stats['avg_accuracy']      = 0;
    $stats['active_learners']   = 0;
    $stats['avg_response_time'] = 0;
    $stats['most_weak_topic']   = 'N/A';

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            $stats['total_attempts']    = ($row['total_attempts']    ?? 0);
            $stats['avg_accuracy']      = ($row['avg_accuracy']      ?? 0);
            $stats['active_learners']   = ($row['active_learners']   ?? 0);
            $stats['avg_response_time'] = ($row['avg_response_time'] ?? 0);
        }
    }

    // Most common weak topic (lowest accuracy across all users)
    $sql2 = "SELECT
                 q.topic_tag,
                 ROUND(AVG(lr.is_correct) * 100, 1) AS accuracy_pct
              FROM QUIZ_LEARNING_RECORD_T lr
              JOIN QUESTION_T q ON lr.question_id = q.question_id
              WHERE q.topic_tag IS NOT NULL AND q.topic_tag != ''
              GROUP BY q.topic_tag
              HAVING COUNT(*) >= 2
              ORDER BY accuracy_pct ASC
              LIMIT 1";

    $result2 = mysqli_query($conn, $sql2);
    if ($result2) {
        $row2 = mysqli_fetch_assoc($result2);
        if ($row2) {
            $stats['most_weak_topic'] = $row2['topic_tag'];
        }
    }

    return $stats;
}


/*
 * ADMIN: Get system-wide topic accuracy breakdown.
 */
function getSystemTopicAccuracy($conn) {
    $sql = "SELECT
                q.topic_tag,
                COUNT(*) AS total,
                SUM(lr.is_correct) AS correct,
                ROUND(AVG(lr.is_correct) * 100, 1) AS accuracy_pct
            FROM QUIZ_LEARNING_RECORD_T lr
            JOIN QUESTION_T q ON lr.question_id = q.question_id
            WHERE q.topic_tag IS NOT NULL AND q.topic_tag != ''
            GROUP BY q.topic_tag
            ORDER BY accuracy_pct ASC";

    $result = mysqli_query($conn, $sql);
    $topics = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $entry = array();
            $entry['topic_tag']    = $row['topic_tag'];
            $entry['total']        = $row['total'];
            $entry['correct']      = $row['correct'];
            $entry['accuracy_pct'] = $row['accuracy_pct'];
            $topics[] = $entry;
        }
    }

    return $topics;
}


/*
 * ADMIN: Get system-wide behavior pattern distribution.
 */
function getSystemBehaviorPatterns($conn) {
    $sql = "SELECT
                lr.is_correct,
                lr.response_time,
                q.time_limit
            FROM QUIZ_LEARNING_RECORD_T lr
            JOIN QUESTION_T q ON lr.question_id = q.question_id";

    $result = mysqli_query($conn, $sql);

    $patterns = array();
    $patterns['rushing']    = 0;
    $patterns['struggling'] = 0;
    $patterns['proficient'] = 0;
    $patterns['total']      = 0;

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $patterns['total']++;
            $is_correct    = $row['is_correct'];
            $response_time = $row['response_time'];
            $time_limit    = max(1, $row['time_limit']);
            $ratio         = $response_time / $time_limit;

            if (!$is_correct && $ratio < 0.25) {
                $patterns['rushing']++;
            } elseif (!$is_correct && $ratio > 0.75) {
                $patterns['struggling']++;
            } elseif ($is_correct && $ratio <= 0.75) {
                $patterns['proficient']++;
            }
        }
    }

    return $patterns;
}


// ADMIN: Get system-wide daily accuracy over last N days.
 
function getSystemProgressOverTime($conn, $days = 30) {
    $days = $days;

    $sql = "SELECT
                DATE(answered_at) AS answer_date,
                COUNT(*) AS total,
                SUM(is_correct) AS correct,
                ROUND(AVG(is_correct) * 100, 1) AS accuracy_pct
            FROM QUIZ_LEARNING_RECORD_T
            WHERE answered_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
            GROUP BY DATE(answered_at)
            ORDER BY answer_date ASC";

    $result   = mysqli_query($conn, $sql);
    $progress = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $entry = array();
            $entry['date']         = $row['answer_date'];
            $entry['total']        = $row['total'];
            $entry['correct']      = $row['correct'];
            $entry['accuracy_pct'] = $row['accuracy_pct'];
            $progress[] = $entry;
        }
    }

    return $progress;
}


// ADMIN: Get per-user performance summary for the user performance table.
 
function getUserPerformanceSummary($conn) {
    $sql = "SELECT
                lr.user_id,
                u.username,
                u.avatar_path,
                COUNT(*) AS total_answered,
                SUM(lr.is_correct) AS total_correct,
                ROUND(AVG(lr.is_correct) * 100, 1) AS accuracy_pct,
                ROUND(AVG(lr.response_time), 1) AS avg_response_time,
                MAX(lr.answered_at) AS last_activity
            FROM QUIZ_LEARNING_RECORD_T lr
            JOIN USER_T u ON lr.user_id = u.user_id
            GROUP BY lr.user_id, u.username, u.avatar_path
            ORDER BY accuracy_pct ASC";

    $result = mysqli_query($conn, $sql);
    $users  = array();

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $entry = array();
            $entry['user_id']           = $row['user_id'];
            $entry['username']          = $row['username'] ?? '(No username)';
            $entry['avatar_path']       = $row['avatar_path'];
            $entry['total_answered']    = $row['total_answered'];
            $entry['total_correct']     = $row['total_correct'];
            $entry['accuracy_pct']      = $row['accuracy_pct'];
            $entry['avg_response_time'] = $row['avg_response_time'];
            $entry['last_activity']     = $row['last_activity'];
            $users[] = $entry;
        }
    }

    return $users;
}

?>
