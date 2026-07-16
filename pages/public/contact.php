<!--
Programmer Name: Max
Program Name: /pages/public/contact.php
Description: Contact Us page. Guests and users can send messages to the admin through a form. 
             Saves messages into CONTACT_MESSAGE_T.
First Written on: Tuesday, 30-Jun-2026
Edited on: Wednesday, 2-Jul-2026
-->

<?php

$current_page = 'guest_contact';
include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/db.php');

$success = false;
$error_message = "";


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $topic = trim($_POST['topic']);
    $message = trim($_POST['message']);


    if ($name == "" || $email == "" || $topic == "" || $message == "") {
        $error_message = "Please fill in all the fields.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // escape inputs before saving to database
        $name = mysqli_real_escape_string($conn, $name);
        $email = mysqli_real_escape_string($conn, $email);
        $topic = mysqli_real_escape_string($conn, $topic);
        $message = mysqli_real_escape_string($conn, $message);

        $sql = "INSERT INTO CONTACT_MESSAGE_T (name, email, topic, message, is_read, is_replied, submitted_at)
                VALUES ('$name', '$email', '$topic', '$message', 0, 0, NOW())";

        if (mysqli_query($conn, $sql)) {
            $success = true;
        } else {
            $error_message = "Could not send your message. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/includes/header.php'); ?>

    <link rel="stylesheet" href="/Implose.gg-src/assets/css/components/guest_navi.css">
    <link rel="stylesheet" href="/Implose.gg-src/assets/css/pages/contact.css">

    <title>Contact Us - Implose.gg</title>
</head>
<body>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/pages/public/guest_navi.php'); ?>

    <main class="contact-container">

        <a href="javascript:history.back()" class="contact-back-link">
            <img src="/Implose.gg-src/assets/images/icons/arrow-down.svg" alt="Back"> 
            Back
        </a>
        <?php if ($success) { ?>

            <div class="contact-success">
                <h1>Message Sent!</h1>
                <p>Thank you for reaching out. We will get back to you within 1-2 business days.</p>
                <a href="index.php" class="contact-back-btn">Back to Home</a>
            </div>

        <?php } else { ?>

            <header class="contact-header">
                <h1>Contact Us</h1>
                <p>Got a question or feedback? Drop us a message and we will reply within 1-2 business days.</p>
            </header>

            <?php if ($error_message != "") { ?>
                <div class="contact-error">
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php } ?>

            <form action="contact.php" method="post" class="contact-form">

                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required
                       value="<?php if (isset($_POST['name'])) echo htmlspecialchars($_POST['name']); ?>">

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required
                       value="<?php if (isset($_POST['email'])) echo htmlspecialchars($_POST['email']); ?>">

                <label for="topic">Topic</label>
                <select id="topic" name="topic" required>
                    <option value="">-- Choose a topic --</option>
                    <option value="General Question">General Question</option>
                    <option value="Bug Report">Bug Report</option>
                    <option value="Feedback">Feedback / Suggestion</option>
                    <option value="Account Issue">Account Issue</option>
                    <option value="Other">Other</option>
                </select>

                <label for="message">Message</label>
                <textarea id="message" name="message" rows="6" required><?php if (isset($_POST['message'])) echo htmlspecialchars($_POST['message']); ?></textarea>

                <button type="submit" class="contact-submit-btn">Send Message</button>
            </form>

        <?php } ?>

    </main>

</body>
</html>
