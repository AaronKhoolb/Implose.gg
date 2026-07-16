<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/user/account/nav.php
Description: user account center navigation (will be included in profile settings, acc security, session management)
First Written on: Friday, 19-Jun-2026
Edited on: Friday, 19-Jun-2026
-->

<div class="account-left pixel-panel">
    <div class="account-left-nav">
        <ul>
            <li class="account-left-nav-item">
                <a href="/Implose.gg-src/pages/user/account/index.php" class="<?php if ($current_page === 'profile_settings') { echo "active"; } ?>">
                    <img src="/Implose.gg-src/assets/images/icons/pen-square.svg" alt="">
                    <span>Profile Settings</span>
                </a>
            </li>

            
            <li class="account-left-nav-item">
                <a href="/Implose.gg-src/pages/user/account/account_security.php" class="<?php if ($current_page === 'account_security') { echo "active"; } ?>">
                    <img src="/Implose.gg-src/assets/images/icons/user-shield.svg" alt="">
                    <span>Account Security</span>
                </a>
            </li>


            <li class="account-left-nav-item">
                <a href="/Implose.gg-src/pages/user/account/sessions.php?view=active_sessions" class="<?php if ($current_page === 'sessions') { echo "active"; } ?>">
                    <img src="/Implose.gg-src/assets/images/icons/smartphone.svg" alt="">
                    <span>Sessions</span>
                </a>
            </li>


            <li class="account-left-nav-item">
                <a href="/Implose.gg-src/pages/user/account/feedback_detail.php" class="<?php if ($current_page === 'feedback_detail') { echo "active"; } ?>">
                    <img src="/Implose.gg-src/assets/images/icons/chat-error.svg" alt="">
                    <span>My Feedback</span>
                </a>
            </li>


            <li class="account-left-nav-item">
                <a href="/Implose.gg-src/actions/auth/sign_out.php">
                    <img src="/Implose.gg-src/assets/images/icons/logout.svg" alt="">
                    <span>Logout</span>
                </a>
            </li>

        </ul>
    </div>
</div>