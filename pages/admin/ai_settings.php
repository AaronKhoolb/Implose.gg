<!--
Programmer Name: Mr. Khoo Lay Bin
Program Name: /pages/admin/ai_settings.php
Description: Admin AI Settings Page - api settings
First Written on: Thursday, 02-Jul-2026
Edited on: Saturday, 04-Jul-2026
-->

<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/admin_ai_settings.css">

    <title>AI Settings — Implose.gg Admin</title>
</head>


<body class="admin-body">
    <?php
        $current_page = 'admin_aisettings';
        include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/admin/nav.php');
    ?>

    <div class="admin-main-content">

        <?php
            $settings_sql = "SELECT * FROM AI_SETTING_T WHERE ai_setting_id = 1";
            $settings_result = mysqli_query($conn, $settings_sql);
            $settings = mysqli_fetch_assoc($settings_result);
        ?>


        <!-- top -->
        <div class="ai-page-header">
            <h1>AI Settings</h1>
            <p>Configure the API used by the AI tutor on the quiz explanation page.</p>
        </div>


        <!-- form -->
        <div class="ai-container">
            <span class="ai-title">API Configuration</span>

            <form method="POST" action="/Implose.gg-src/actions/admin/update_ai_settings.php">

                <label class="ai-field">
                    <span class="ai-label">API ENDPOINT</span>
                    <input type="text" name="api_endpoint" required value="<?php echo $settings['api_endpoint']; ?>" placeholder="https://api.example.com/v1/chat/completions">
                </label>

                <label class="ai-field">
                    <span class="ai-label">API KEY</span>
                    <input type="password" name="api_key" autocomplete="new-password" value="<?php echo $settings['api_key']; ?>" placeholder="sk-...">
                </label>

                <label class="ai-field">
                    <span class="ai-label">MODEL</span>
                    <input type="text" name="model" required value="<?php echo $settings['model']; ?>" placeholder="claude-opus-4-7">
                </label>

                <button type="submit" class="ai-btn-save">Save Settings</button>
            </form>
        </div>

    </div>
</body>
</html>
