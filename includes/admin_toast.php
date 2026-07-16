<?php
/*
 * Reusable Admin Toast Component
 * Expects $success_msg and $error_msg to be set before inclusion.
 */
$toast = null;
if (!empty($success_msg)) {
    $toast = ['type' => 'success', 'text' => $success_msg];
} elseif (!empty($error_msg)) {
    $toast = ['type' => 'error', 'text' => $error_msg];
}
?>
<?php if ($toast): ?>
    <div class="admin-toast admin-toast--<?= htmlspecialchars($toast['type']) ?>" id="admin-toast">
        <?= htmlspecialchars($toast['text']) ?>
    </div>
    <script>
        setTimeout(() => {
            const t = document.getElementById('admin-toast');
            if (t) t.classList.add('admin-toast--hide');
        }, 15000);
    </script>
<?php endif; ?>
