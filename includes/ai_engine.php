<?php
/*
Programmer Name: Mr. Khoo Lay Bin
Program Name: /includes/ai_engine.php
Description: AI explanation feature
            - extract text from PDF
            - build the tutor system instruction
            - send a chat request to the AI API and get the JSON back
First Written on: Thursday, 02-Jul-2026
Edited on: Saturday, 04-Jul-2026
*/

require_once($_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/vendor/autoload.php');


// use Smalot PDF Parser to extract text from the course material PDF
function ai_extract_pdf_text($material_path) {
    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/Implose.gg-src/' . $material_path;

    $parser = new \Smalot\PdfParser\Parser();
    return $parser->parseFile($full_path)->getText();
}


// build the tutor system prompt
function ai_build_system_prompt($course, $quiz, $material_text) {
    $course_title = $course['title'];
    $course_desc  = $course['description'];
    $level        = $quiz['level_number'];
    $quiz_title   = $quiz['title'];
    $quiz_desc    = $quiz['description'];

    return <<<PROMPT
You are Implose.gg, a friendly AI study tutor built into a quiz platform. A student just answered a quiz question in the course "$course_title" ($course_desc), specifically Level $level "$quiz_title" ($quiz_desc). Review their attempt with them one-on-one.

Here's the course material to reference when relevant:
"""
$material_text
"""

Sound like a warm, encouraging tutor, never robotic. If the student got it right, open with a short celebration and reinforce why it's correct. If they got it wrong, be gentle — briefly explain what their answer meant and why it doesn't fit, then walk through the correct reasoning step by step. Always react to THEIR specific answer, and explain the underlying concept, not just the answer.

Keep replies around 200 words. Output HTML directly using <p>, <strong>, <ul><li>, <code>, and <pre><code>. Do not use markdown syntax like ** or backticks. Do not use headings. Do not repeat the question. Do not add sign-offs. Reply in the student's language (default English).

For follow-up questions, answer directly without repeating the opening celebration or re-explaining the original answer unless asked. If asked something unrelated to studying, playfully steer back to the lesson.
PROMPT;
}


// throw question, option, correct ans, user ans to ai
function ai_build_attempt_message($question, $user_answer, $is_correct) {
    $msg = "Question: " . $question['question_text'] . "\n";

    if ($question['question_type'] === 'single_choice') {
        $msg .= "Options:\n"
              . "A) " . $question['option_a'] . "\n"
              . "B) " . $question['option_b'] . "\n"
              . "C) " . $question['option_c'] . "\n"
              . "D) " . $question['option_d'] . "\n"
              . "Correct option: " . strtoupper($question['correct_option']) . "\n"
              . "My answer: " . strtoupper($user_answer) . "\n";
    } else {
        if ($user_answer === '') {
            $answer_display = "(left blank)";
        } else {
            $answer_display = $user_answer;
        }
        $msg .= "Correct answer: " . $question['correct_text_answer'] . "\n"
              . "My answer: " . $answer_display . "\n";
    }

    if ($is_correct) {
        $result = "RIGHT";
    } else {
        $result = "WRONG";
    }
    $msg .= "Result: I got it " . $result . ".\n"
          . "Please review my attempt and explain.";

    return $msg;
}


// use cURL to send a chat request to the AI API and return the decoded JSON reply
function ai_chat($settings, $messages, $timeout = 300) {
    $payload = json_encode([
        'model'    => $settings['model'],
        'messages' => $messages,
        'stream'   => false
    ]);

    $curl = curl_init($settings['api_endpoint']);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $settings['api_key']]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

    $body = curl_exec($curl);

    return json_decode($body, true);
}
