<?php
/*
Programmer Name: Damian Loh Yi Feng
Program Name: /includes/achievement.php
Description: Achievement awarding helper
            - award_achievement($conn, $user_id, $trigger_code)
                Unlocks any achievement(s) whose trigger_code matches,
                adds the points reward, and writes a SYSTEM_LOG entry.
            - check_points_milestones($conn, $user_id)
                Reads the user's CURRENT total_points and awards any
                POINTS_* milestones that have been reached.
            - check_streak_milestones($conn, $user_id, $streak_count)
                Awards any STREAK_* milestones at or below the given count.
First Written on: Saturday, 27-Jun-2026
Edited on: Sunday, 05-Jul-2026
*/

if (!function_exists('award_achievement')) {

    /**
     * Award every achievement with the given trigger_code that the user
     * hasn't already unlocked. Points are added to USER_T.total_points.
     * Each unlock is queued into $_SESSION['achievement_popups'] so the
     * popup partial can render it on the user's next page render.
     */
    function award_achievement($conn, $user_id, $trigger_code) {
        if (!$conn || !$user_id || !$trigger_code) {
            return [];
        }

        $user_id      = (int) $user_id;
        $trigger_code = mysqli_real_escape_string($conn, $trigger_code);

        // pull every achievement matching this trigger that the user does NOT yet have
        $find_sql = "
            SELECT a.achievement_id, a.title, a.description, a.badge_icon_path, a.points_reward
            FROM ACHIEVEMENT_T a
            WHERE a.trigger_code = '$trigger_code'
              AND NOT EXISTS (
                  SELECT 1 FROM USER_ACHIEVEMENT_T ua
                  WHERE ua.achievement_id = a.achievement_id
                    AND ua.user_id = '$user_id'
              )
        ";

        $find_result = mysqli_query($conn, $find_sql);
        if (!$find_result) {
            return [];
        }

        $unlocked = [];

        while ($row = mysqli_fetch_assoc($find_result)) {
            $aid    = (int) $row['achievement_id'];
            $title  = $row['title'];
            $desc   = $row['description'] ?? '';
            $badge  = $row['badge_icon_path'];
            $points = (int) $row['points_reward'];

            // unlock record
            $insert_sql = "INSERT INTO USER_ACHIEVEMENT_T (user_id, achievement_id, unlocked_at)
                           VALUES ('$user_id', '$aid', NOW())";
            if (!mysqli_query($conn, $insert_sql)) {
                continue;
            }

            // grant points
            if ($points > 0) {
                $add_pts_sql = "UPDATE USER_T
                                SET total_points = total_points + $points,
                                    updated_at = NOW()
                                WHERE user_id = '$user_id'";
                mysqli_query($conn, $add_pts_sql);
            }

            // system log (if the global helper is loaded)
            if (function_exists('add_system_log')) {
                $log_title = mysqli_real_escape_string($conn, $title);
                add_system_log(
                    $conn,
                    $user_id,
                    'Achievement Unlocked',
                    "User unlocked achievement #$aid ($log_title), earned $points points."
                );
            }

            // queue popup for next page render (skip if session isn't active)
            if (session_status() === PHP_SESSION_ACTIVE) {
                if (!isset($_SESSION['achievement_popups']) || !is_array($_SESSION['achievement_popups'])) {
                    $_SESSION['achievement_popups'] = [];
                }
                $_SESSION['achievement_popups'][] = [
                    'id'          => $aid,
                    'title'       => $title,
                    'description' => $desc,
                    'badge'       => $badge ? '/Implose.gg-src/' . $badge : '',
                    'points'      => $points,
                ];
            }

            $unlocked[] = [
                'achievement_id' => $aid,
                'title'          => $title,
                'points'         => $points,
            ];
        }

        return $unlocked;
    }


    /**
     * Award any points milestone the user has reached.
     * Call after total_points was updated.
     */
    function check_points_milestones($conn, $user_id) {
        $user_id = (int) $user_id;

        $r = mysqli_query($conn, "SELECT total_points FROM USER_T WHERE user_id = '$user_id' LIMIT 1");
        if (!$r) return [];
        $row = mysqli_fetch_assoc($r);
        if (!$row) return [];

        $points = (int) ($row['total_points'] ?? 0);
        $out = [];

        if ($points >= 100)  $out = array_merge($out, award_achievement($conn, $user_id, 'POINTS_100'));
        if ($points >= 500)  $out = array_merge($out, award_achievement($conn, $user_id, 'POINTS_500'));
        if ($points >= 1000) $out = array_merge($out, award_achievement($conn, $user_id, 'POINTS_1000'));

        return $out;
    }


    /**
     * Award any streak milestone the user has reached.
     */
    function check_streak_milestones($conn, $user_id, $streak_count) {
        $user_id      = (int) $user_id;
        $streak_count = (int) $streak_count;
        $out = [];

        if ($streak_count >= 3)  $out = array_merge($out, award_achievement($conn, $user_id, 'STREAK_3'));
        if ($streak_count >= 7)  $out = array_merge($out, award_achievement($conn, $user_id, 'STREAK_7'));
        if ($streak_count >= 30) $out = array_merge($out, award_achievement($conn, $user_id, 'STREAK_30'));

        return $out;
    }


    /**
     * Canonical list of trigger codes shown in the admin dropdown.
     * Keep in sync with check_points_milestones / check_streak_milestones above.
     *
     * The action-side hooks for the second block (FIRST_QUIZ_COMPLETE through
     * FIRST_BOSS_WIN) live in the corresponding feature actions — call
     * award_achievement($conn, $user_id, '<CODE>') from:
     *   - FIRST_QUIZ_COMPLETE    → quiz submission action (first time the user
     *                              finishes any quiz)
     *   - QUIZ_PERFECT           → quiz submission action (when the submission
     *                              has zero wrong answers)
     *   - FIRST_CHAT_USE         → chat send action (first message the user
     *                              ever sends)
     *   - QUIZ_STREAK_10         → quiz submission action (after counting the
     *                              user's last 10 quizzes; award if all are
     *                              fully correct)
     *   - LEADERBOARD_TOP10      → leaderboard refresh / rank-recalc action
     *                              (when this user's rank becomes ≤ 10)
     *   - LEADERBOARD_TOP1       → leaderboard refresh / rank-recalc action
     *                              (when this user's rank becomes 1)
     *   - FIRST_REDEMPTION       → reward-redemption action (after the first
     *                              successful redemption row is inserted)
     *   - FIRST_BOSS_WIN         → boss-win action (after the first successful
     *                              boss-defeat record is written)
     */
    function achievement_trigger_options() {
        return [
            'MANUAL'               => 'Manual — awarded by admin only',
            'FIRST_LOGIN'          => 'First Login — first time signing in',
            'PROFILE_COMPLETE'     => 'Profile Complete — finished profile setup',
            'POINTS_100'           => 'Reach 100 points',
            'POINTS_500'           => 'Reach 500 points',
            'POINTS_1000'          => 'Reach 1,000 points',
            'STREAK_3'             => 'Reach 3-day login streak',
            'STREAK_7'             => 'Reach 7-day login streak',
            'STREAK_30'            => 'Reach 30-day login streak',
            'FIRST_QUESTION_ANSWERED' => 'First quiz question answered',
            'FIRST_QUIZ_COMPLETE'  => 'First Quiz Complete',
            'FIRST_COURSE_COMPLETE'=> 'First course fully cleared',
            'QUIZ_PERFECT'         => 'Quiz with zero mistakes',
            'FIRST_CHAT_USE'       => 'First chat message sent',
            'QUIZ_STREAK_10'       => '10 consecutive perfect quizzes',
            'LEADERBOARD_TOP10'    => 'Reach top 10 on leaderboard',
            'LEADERBOARD_TOP1'     => 'Reach #1 on leaderboard',
            'FIRST_REDEMPTION'     => 'First reward redemption',
            'FIRST_BOSS_WIN'       => 'First boss defeated',
            'FIRST_COURSE_RATED'   => 'First feedback on a course',
        ];
    }
}

?>
