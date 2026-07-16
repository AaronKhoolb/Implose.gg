<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/marketplace/course_banner.php
Description: course detail banner (put in view, fork and publish preview)
First Written on: Monday, 30-Jun-2026
Edited on: Wednesday, 01-Jul-2026
-->

<?php
    $banner_thumb = '/Implose.gg-src/' . $course['thumbnail_path'];
    $banner_avatar = '/Implose.gg-src/' . $course['creator_avatar'];

    // // fork count is set in course_details.php
    if (isset($course['fork_count'])) {
        $banner_forks = $course['fork_count'];
    } else {
        $banner_forks = 0;
    }
?>


<div class="pixel-panel mkt-detail-header">

    <!-- Left: course thumbnail -->
    <div class="mkt-detail-thumb">
        <img src="<?php echo $banner_thumb; ?>" alt="thumbnail">
    </div>


    <!-- Right: course info -->
    <div class="mkt-detail-info">

        <!-- title -->
        <h1 class="pixel-title"><?php echo $course['title']; ?></h1>


        <!-- creator -->
        <div class="mkt-detail-creator">
            <!-- avatar -->
            <img src="<?php echo $banner_avatar; ?>" alt="creator">

            <!-- name -->
            <span>by <strong> <?php echo $course['creator_name']; ?></strong></span>

            <!-- publish date -->
            <span style="opacity:0.5;">·</span>
            <span>Published <?php echo date('M j, Y', strtotime($course['created_at'])); ?></span>

            <!-- YOURS tag -->
            <?php if ($is_owner) { ?>
                <span style="opacity:0.5;">·</span>
                <span class="you-own">YOURS</span>
            <?php } ?>
        </div>


        <!-- description -->
        <div class="mkt-detail-desc">
            <?php echo nl2br($course['description']); ?>
        </div>


        <!-- statistics -->
        <div class="mkt-detail-meta">
            <!-- quizzes count -->
            <span><strong><?php echo $total_quizzes; ?></strong> quizzes</span>

            <!-- questions count -->
            <span><strong><?php echo $total_questions; ?></strong> questions</span>

            <!-- forks count -->
            <span><strong><?php echo $banner_forks; ?></strong> forks</span>
        </div>


        <!-- action buttons (show when $show_actions == true) -->
        <?php if (!empty($show_actions)) { ?>
            <div class="mkt-detail-actions">

                <!-- left group: fork / view materials / delete -->
                <div class="mkt-detail-actions-left">

                    <!-- not owner - can fork -->
                    <?php if (!$is_owner) { ?>
                        <form action="/Implose.gg-src/actions/user/marketplace/fork_course.php" method="post" onsubmit="return confirm('Fork this course to your library?');" style="margin:0;">
                            <input type="hidden" name="marketplace_course_id" value="<?php echo $course['marketplace_course_id']; ?>">

                            <button type="submit" class="btn-pixel btn-pixel-red">Fork Course</button>
                        </form>
                    <?php } ?>

                    <!-- mozilla PDF viewer -->
                    <?php $back_url = '/Implose.gg-src/pages/user/marketplace/course_details.php?id=' . $course['marketplace_course_id']; ?>
                    <a class="btn-pixel" href="/Implose.gg-src/pages/pdf_viewer.php?file=<?php echo urlencode($course['course_materials']); ?>&back=<?php echo urlencode($back_url); ?>">
                        View Materials
                    </a>

                    <!-- owner - can delete -->
                    <?php if ($is_owner) { ?>
                        <form action="/Implose.gg-src/actions/user/marketplace/delete_marketplace_course.php" method="post" onsubmit="return confirm('Unpublish this course?');" style="margin:0;">
                            <input type="hidden" name="marketplace_course_id" value="<?php echo $course['marketplace_course_id']; ?>">

                            <button type="submit" class="btn-pixel mkt-danger-btn mkt-icon-btn" title="Unpublish">
                                <img src="/Implose.gg-src/assets/images/icons/trash.svg" alt="Delete">
                            </button>
                        </form>
                    <?php } ?>
                </div>


                <!-- right group: report / share -->
                <div class="mkt-detail-actions-right">

                    <!-- report btn -->
                    <button type="button" class="btn-pixel mkt-icon-btn" id="course-report-btn" title="Report">
                        <img src="/Implose.gg-src/assets/images/icons/shield-alert.svg" alt="Report">
                    </button>

                    <!-- share btn -->
                    <button type="button" class="btn-pixel mkt-icon-btn share-btn" title="Share"
                        data-url="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/Implose.gg-src/pages/user/marketplace/course_details.php?id=' . $course['marketplace_course_id']; ?>"
                        data-title="<?php echo htmlspecialchars($course['title']); ?> | Implose.gg Marketplace"
                        data-description="<?php echo htmlspecialchars($course['description']); ?>">

                        <img src="/Implose.gg-src/assets/images/icons/send-2.svg" alt="Share">
                    </button>
                </div>

            </div>
        <?php } ?>

    </div>
</div>
