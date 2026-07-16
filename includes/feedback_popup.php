<!--
Programmer Name: Damian Loh Yi Feng
Program Name: /includes/feedback_popup.php
Description: Renders an emoji feedback popup after the user completes a quiz.
            Triggered by $_SESSION['feedback_prompt'] = ['quiz_id' => X,
            'course_id' => Y, 'quiz_title' => 'optional']. Once rendered the
            session entry is cleared so it doesn't repeat.
            The popup is a centered modal with five emoji ratings and an
            optional comment box. Submission goes to /actions/user/submit_feedback.php.
            X button dismisses without saving.
            Designed to be included on every user-facing page (via user/nav.php).
First Written on: Saturday, 27-Jun-2026
Edited on: Saturday, 27-Jun-2026
-->

<?php
$feedback_prompt = null;
if (session_status() === PHP_SESSION_ACTIVE
    && isset($_SESSION['feedback_prompt'])
    && is_array($_SESSION['feedback_prompt'])) {
    $feedback_prompt = $_SESSION['feedback_prompt'];
    // clear so a refresh doesn't re-show the same popup
    unset($_SESSION['feedback_prompt']);
}
?>

<?php if ($feedback_prompt): ?>

<link rel="stylesheet" href="/Implose.gg-src/assets/css/components/feedback_popup.css">

<div class="fb-overlay" id="fb-overlay">
    <div class="fb-modal pixel-panel" role="dialog" aria-labelledby="fb-title">

        <button type="button" class="fb-close" id="fb-close" aria-label="Dismiss">
            <img src="/Implose.gg-src/assets/images/icons/xmark_pixel.svg" alt="close">
        </button>

        <h2 id="fb-title" class="fb-title pixel-title">How was the quiz?</h2>
        <p class="fb-subtitle">Tap an emoji to rate it. Comment is optional.</p>

        <div class="fb-emoji-row" id="fb-emoji-row">
            <button type="button" class="fb-emoji" data-rating="angry"     aria-label="Big cry">
                <span class="fb-emoji-face">😭</span>
                <span class="fb-emoji-label">Big cry</span>
            </button>
            <button type="button" class="fb-emoji" data-rating="sad"       aria-label="Small cry">
                <span class="fb-emoji-face">😢</span>
                <span class="fb-emoji-label">Small cry</span>
            </button>
            <button type="button" class="fb-emoji" data-rating="neutral"   aria-label="Neutral">
                <span class="fb-emoji-face">😐</span>
                <span class="fb-emoji-label">Neutral</span>
            </button>
            <button type="button" class="fb-emoji" data-rating="happy"     aria-label="Small happy">
                <span class="fb-emoji-face">😊</span>
                <span class="fb-emoji-label">Small happy</span>
            </button>
            <button type="button" class="fb-emoji" data-rating="excellent" aria-label="Big happy">
                <span class="fb-emoji-face">😄</span>
                <span class="fb-emoji-label">Big happy</span>
            </button>
        </div>

        <label class="fb-comment-label" for="fb-comment">
            Want to leave a comment? <span class="fb-comment-optional">(optional)</span>
        </label>
        <textarea
            id="fb-comment"
            class="fb-comment"
            maxlength="500"
            rows="3"
            placeholder="Tell us what you thought..."></textarea>

        <div class="fb-actions">
            <button type="button" class="btn-pixel fb-skip" id="fb-skip">Skip</button>
            <button type="button" class="btn-red fb-submit" id="fb-submit" disabled>Submit</button>
        </div>

        <div class="fb-status" id="fb-status" aria-live="polite"></div>
    </div>
</div>

<script>
(function () {
    const overlay     = document.getElementById('fb-overlay');
    const modal       = overlay.querySelector('.fb-modal');
    const closeBtn    = document.getElementById('fb-close');
    const skipBtn     = document.getElementById('fb-skip');
    const submitBtn   = document.getElementById('fb-submit');
    const commentEl   = document.getElementById('fb-comment');
    const statusEl    = document.getElementById('fb-status');
    const emojiBtns   = overlay.querySelectorAll('.fb-emoji');

    const quizId    = <?= json_encode($feedback_prompt['quiz_id']   ?? null) ?>;
    const courseId  = <?= json_encode($feedback_prompt['course_id'] ?? null) ?>;
    let selectedRating = null;

    /* Emoji selection */
    emojiBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            emojiBtns.forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            selectedRating = btn.dataset.rating;
            submitBtn.disabled = false;
        });
    });

    /* Dismiss without saving */
    function dismiss() {
        overlay.classList.add('fb-overlay--hide');
        setTimeout(() => overlay.remove(), 280);
    }
    closeBtn.addEventListener('click', dismiss);
    skipBtn.addEventListener('click', dismiss);

    /* Submit via fetch */
    submitBtn.addEventListener('click', () => {
        if (!selectedRating) return;

        submitBtn.disabled = true;
        statusEl.textContent = 'Saving...';

        const form = new FormData();
        form.append('emoji_rating', selectedRating);
        form.append('description',  commentEl.value.trim());
        if (quizId   !== null) form.append('quiz_id',   quizId);
        if (courseId !== null) form.append('course_id', courseId);

        fetch('/Implose.gg-src/actions/user/submit_feedback.php', {
            method: 'POST',
            body: form
        })
        .then(r => r.text())
        .then(txt => {
            if (txt === 'success') {
                statusEl.textContent = 'Thanks for your feedback!';
                setTimeout(dismiss, 1000);
            } else {
                submitBtn.disabled = false;
                statusEl.textContent = txt || 'Failed to save. Try again.';
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            statusEl.textContent = 'Network error. Try again.';
        });
    });

    /* Click outside modal closes too */
    overlay.addEventListener('click', e => {
        if (e.target === overlay) dismiss();
    });
})();
</script>

<?php endif; ?>
