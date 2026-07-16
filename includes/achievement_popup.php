<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /includes/achievement_popup.php
Description: Renders queued achievement popups from $_SESSION['achievement_popups'].
            Shown in the bottom-left of the screen as a pixel-panel toast that
            slides in, stays for 10 seconds, then fades out. After rendering
            the queue is cleared so the same popup doesn't repeat.
            Designed to be included on every user-facing page (via user/nav.php).
First Written on: Saturday, 27-Jun-2026
Edited on: Saturday, 27-Jun-2026
-->

<?php
$ach_popups = [];
if (session_status() === PHP_SESSION_ACTIVE
    && isset($_SESSION['achievement_popups'])
    && is_array($_SESSION['achievement_popups'])) {
    $ach_popups = $_SESSION['achievement_popups'];
    // clear queue immediately so a page reload doesn't repeat the popup
    unset($_SESSION['achievement_popups']);
}
?>

<?php if (!empty($ach_popups)): ?>

<!-- Achievement popup styles loaded inline so any page including the partial gets them -->
<link rel="stylesheet" href="/Implose.gg-src/assets/css/components/achievement_popup.css">

<div class="ach-popup-stack" id="ach-popup-stack">
    <?php foreach ($ach_popups as $i => $p):
        $title = $p['title']       ?? 'Achievement';
        $desc  = $p['description'] ?? '';
        $badge = $p['badge']       ?? '';
        $pts   = (int) ($p['points'] ?? 0);
    ?>
        <div class="ach-popup" style="animation-delay: <?= $i * 0.18 ?>s;" data-popup-index="<?= $i ?>">
            <a class="ach-popup-link" href="/Implose.gg-src/pages/user/achievement/achievement.php"
               aria-label="Open achievements page">
                <div class="ach-popup-badge <?= $badge ? '' : 'ach-popup-badge--fallback' ?>">
                    <?php if ($badge): ?>
                        <img src="<?= htmlspecialchars($badge) ?>" alt="badge"
                             onerror="this.style.display='none'; this.parentElement.classList.add('ach-popup-badge--fallback');">
                    <?php endif; ?>
                </div>
                <div class="ach-popup-body">
                    <span class="ach-popup-tag">Achievement Unlocked</span>
                    <span class="ach-popup-title"><?= htmlspecialchars($title) ?></span>
                    <?php if ($desc !== ''): ?>
                        <span class="ach-popup-desc"><?= htmlspecialchars($desc) ?></span>
                    <?php endif; ?>
                    <?php if ($pts > 0): ?>
                        <span class="ach-popup-points">+<?= $pts ?> coins</span>
                    <?php endif; ?>
                </div>
            </a>
            <button type="button" class="ach-popup-close" aria-label="Dismiss"
                onclick="event.stopPropagation(); this.closest('.ach-popup').classList.add('ach-popup--hide');">
                <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="dismiss">
            </button>
        </div>
    <?php endforeach; ?>
</div>

<script>
(function () {
    /* Auto-dismiss each popup after 10 seconds (offset by its enter delay so
       they stack in cleanly). */
    document.querySelectorAll('.ach-popup').forEach((el, i) => {
        const delay = 10000 + i * 180;
        setTimeout(() => el.classList.add('ach-popup--hide'), delay);
    });
})();
</script>

<?php endif; ?>
