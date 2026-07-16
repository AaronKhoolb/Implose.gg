


    <nav class="guest-navbar">
        <div class="guest-logo-picture">
            <span class="logo-text">Implose.gg</span>
        </div>
        <div class="guest-nav-right">
            <ul class="guest-nav-links">
                <li>
                    <a href="index.php" class="guest-nav-select <?php if ($current_page === 'guest_home') { echo 'btn-pixel guest-active'; } ?>">
                        <img src="/Implose.gg-src/assets/images/icons/nav_home.svg" class="nav-img" alt="Home">
                        <span class="nav-text">Home</span>
                    </a>
                </li>
                <li>
                    <a href="faq.php" class="guest-nav-select <?php if ($current_page === 'guest_faq') { echo 'btn-pixel guest-active'; } ?>">
                        <img src="/Implose.gg-src/assets/images/icons/nav_faq.svg" class="nav-img" alt="FAQ">
                        <span class="nav-text">FAQ</span>
                    </a>
                </li>
                <li>
                    <a href="about_us.php" class="guest-nav-select <?php if ($current_page === 'guest_about') { echo 'btn-pixel guest-active'; } ?>">
                        <img src="/Implose.gg-src/assets/images/icons/nav_about_us.svg" class="nav-img" alt="About Us">
                        <span class="nav-text">About Us</span>
                    </a>
                </li>
                <li>
                    <a href="policy.php" class="guest-nav-select <?php if ($current_page === 'guest_policy') { echo 'btn-pixel guest-active'; } ?>">
                        <img src="/Implose.gg-src/assets/images/icons/nav_policy.svg" class="nav-img" alt="Policy">
                        <span class="nav-text">Policy</span>
                    </a>
                </li>
            </ul>
        </div>
    
        <div class="guest-login-actions">
            <a href="/Implose.gg-src/pages/auth/sign_up.php" class="login-button">
                <span class="login-text-btn">Login / Sign Up</span>
                <img src="/Implose.gg-src/assets/images/icons/login.svg" class="login-icon-btn" alt="Login">
            </a>
        </div>
    </nav>