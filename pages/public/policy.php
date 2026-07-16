<!--
Programmer Name: Max
Program Name: /pages/public/policy.php
Description: Guest Policy page. Displays platform rules, privacy policy, 
             and other legal information pulled from the POLICY_T database table.
First Written on: Monday, 26-May-2026
Edited on: Wednesday, 2-Jul-2026
-->

<?php
$current_page = 'guest_policy';
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');

 
 
$query_result = mysqli_query($conn, "SELECT title, content FROM POLICY_T ORDER BY policy_id ASC");


$date_result = mysqli_query($conn, "SELECT MAX(effective_date) AS newest_date FROM POLICY_T");
$date_row = mysqli_fetch_assoc($date_result);
 
 
$last_updated_date = date("F Y", strtotime($date_row['newest_date']));
?>
 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
 
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>
 
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/components/guest_navi.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/policy.css">
   
    <title>Privacy Policy & Terms - Implose.gg</title>
</head>
 
<body>
 
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/public/guest_navi.php'); ?>
    <main class="policy-container">
        <header class="policy-header">
            <h1>Platform Policy & Rules</h1>
            <p>Last updated: <?php echo $last_updated_date; ?></p>
        </header>
 
        <?php while ($row = mysqli_fetch_assoc($query_result)) { ?>
            <section class="policy-section">
                <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                <p><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
            </section>
        <?php } ?>
       
    </main>
 
</body>
</html>