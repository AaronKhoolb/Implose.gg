<?php
/*
Programmer Name: Damian Loh Yi Feng
Program Name: /includes/course_rating.php
Description: Course rating helpers used by the marketplace, the
            per-course feedback page, and the admin dashboard so
            the same tier labels (Very Positive / Positive / Mixed
            / Negative / Very Negative) render everywhere.

            - course_feedback_ids_for_marketplace($conn, $mkt_id)
                Returns every local COURSE_T.course_id that
                contributes feedback to a marketplace course: its
                source course (if any) plus every fork.
            - course_rating_summary($conn, $course_ids)
                Aggregates COURSE_FEEDBACK_T for the given local
                course ids and returns count / avg / tier / label /
                positive / negative / neutral.
            - rating_tier_class($tier)
                Returns a CSS class suffix so partial templates don't
                need to know the exact tier list.

            Emoji -> numeric map (1..5): angry=1, sad=2, neutral=3,
            happy=4, excellent=5. Tiers rounded to whole-step
            boundaries against the mean.
First Written on: Saturday, 04-Jul-2026
Edited on: Sunday, 05-Jul-2026
*/

if (!function_exists('course_feedback_ids_for_marketplace')) {

    function course_feedback_ids_for_marketplace($conn, $mkt_id) {
        $mkt_id = (int) $mkt_id;
        $ids    = array();

        if ($mkt_id <= 0) {
            return $ids;
        }

        // source course (the original the publisher published from)
        $src_sql = "SELECT source_course_id FROM MARKETPLACE_COURSE_T
                     WHERE marketplace_course_id = '$mkt_id' LIMIT 1";
        $src_res = mysqli_query($conn, $src_sql);
        if ($src_res && mysqli_num_rows($src_res) > 0) {
            $src_row = mysqli_fetch_assoc($src_res);

            if (isset($src_row['source_course_id'])) {
                $src_id = (int) $src_row['source_course_id'];
            } else {
                $src_id = 0;
            }

            if ($src_id > 0) {
                $ids[] = $src_id;
            }
        }

        // every fork that traces back to this marketplace course
        $fork_sql = "SELECT course_id FROM COURSE_T WHERE forked_from = '$mkt_id'";
        $fork_res = mysqli_query($conn, $fork_sql);
        if ($fork_res) {
            while ($fork_row = mysqli_fetch_assoc($fork_res)) {
                $cid = (int) $fork_row['course_id'];

                // don't add the same id twice
                $already_in = false;
                foreach ($ids as $existing) {
                    if ($existing === $cid) {
                        $already_in = true;
                    }
                }
                if (!$already_in) {
                    $ids[] = $cid;
                }
            }
        }

        return $ids;
    }


    function course_rating_summary($conn, $course_ids) {
        // default response for "no ratings yet"
        $out = array(
            'count'    => 0,
            'avg'      => 0,
            'tier'     => 'none',
            'label'    => 'No Ratings Yet',
            'positive' => 0,
            'negative' => 0,
            'neutral'  => 0
        );

        if (empty($course_ids)) {
            return $out;
        }

        // cast every id to int and build the IN(...) list manually so we
        // never inject a raw value into SQL
        $safe_ids = array();
        foreach ($course_ids as $cid) {
            $cid = (int) $cid;
            if ($cid > 0) {
                $safe_ids[] = $cid;
            }
        }
        if (count($safe_ids) === 0) {
            return $out;
        }

        $in_list = '';
        for ($i = 0; $i < count($safe_ids); $i++) {
            if ($i > 0) {
                $in_list = $in_list . ',';
            }
            $in_list = $in_list . $safe_ids[$i];
        }

        $sql = "SELECT COUNT(*) AS total,
                       SUM(emoji_rating = 'excellent') AS excellent_c,
                       SUM(emoji_rating = 'happy')     AS happy_c,
                       SUM(emoji_rating = 'neutral')   AS neutral_c,
                       SUM(emoji_rating = 'sad')       AS sad_c,
                       SUM(emoji_rating = 'angry')     AS angry_c
                  FROM COURSE_FEEDBACK_T
                 WHERE course_id IN ($in_list)";

        $res = mysqli_query($conn, $sql);
        if (!$res) {
            return $out;
        }

        $r     = mysqli_fetch_assoc($res);
        $total = (int) $r['total'];

        if ($total === 0) {
            return $out;
        }

        $exc = (int) $r['excellent_c'];
        $hap = (int) $r['happy_c'];
        $neu = (int) $r['neutral_c'];
        $sad = (int) $r['sad_c'];
        $ang = (int) $r['angry_c'];

        $score = ($exc * 5) + ($hap * 4) + ($neu * 3) + ($sad * 2) + ($ang * 1);
        $avg   = $score / $total;

        // whole-step boundaries: 4.5 / 3.5 / 2.5 / 1.5
        if ($avg >= 4.5) {
            $tier  = 'very_positive';
            $label = 'Very Positive';
        } else if ($avg >= 3.5) {
            $tier  = 'positive';
            $label = 'Positive';
        } else if ($avg >= 2.5) {
            $tier  = 'mixed';
            $label = 'Mixed';
        } else if ($avg >= 1.5) {
            $tier  = 'negative';
            $label = 'Negative';
        } else {
            $tier  = 'very_negative';
            $label = 'Very Negative';
        }

        $out['count']    = $total;
        $out['avg']      = round($avg, 2);
        $out['tier']     = $tier;
        $out['label']    = $label;
        $out['positive'] = $exc + $hap;
        $out['negative'] = $ang + $sad;
        $out['neutral']  = $neu;

        return $out;
    }


    function rating_tier_class($tier) {
        // simple mapping so partials don't need to know exact tier names
        if ($tier === 'very_positive') {
            return 'mkt-rate--vpos';
        }
        if ($tier === 'positive') {
            return 'mkt-rate--pos';
        }
        if ($tier === 'mixed') {
            return 'mkt-rate--mix';
        }
        if ($tier === 'negative') {
            return 'mkt-rate--neg';
        }
        if ($tier === 'very_negative') {
            return 'mkt-rate--vneg';
        }
        return 'mkt-rate--none';
    }
}
