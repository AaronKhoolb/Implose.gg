<?php
/*
Programmer Name: Mr. Khoo Lay Bin
Program Name: /includes/send_otp_email.php
Description: send otp email function
First Written on: Thursday, 18-May-2026
Edited on: Tuesday, 26-May-2026
*/

require_once($_SERVER['DOCUMENT_ROOT'] . "/Implose.gg-src/vendor/autoload.php");

function send_otp_email($email, $otp_code){
    $env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . "/Implose.gg-src/.env");

    $resend = Resend::client($env['SMTP_API_KEY']);

    $resend->emails->send([
    'from' => $env['SMTP_EMAIL'],
    'to' => [$email],
    'subject' => 'Your Implose.gg verification code',
    'html' => "
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>

        <link rel='preconnect' href='https://fonts.googleapis.com'>
        <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
        <link href='https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@400..700&family=Press+Start+2P&family=Saira:wght@400;600;700;800&display=swap' rel='stylesheet'>

        <div style='width:100%;background:#090c12;padding:28px 14px;box-sizing:border-box;font-family:\"Pixelify Sans\",-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Arial,sans-serif;color:#f4f4f4;'>

            <div style='max-width:500px;margin:0 auto;background:#1b2029;border:4px solid #0d1016;box-shadow:0 0 0 4px #303847,0 8px 0 rgba(0,0,0,0.45),inset 0 4px 0 rgba(255,255,255,0.08),inset 0 -4px 0 rgba(0,0,0,0.35);padding:24px 22px;box-sizing:border-box;'>

                <div style='font-family:\"Press Start 2P\",-apple-system;font-size:25px;font-weight:800;line-height:1.5;color:#f4f4f4;margin-bottom:20px;text-shadow:0 4px 0 #2f5a2a,0 8px 0 rgba(0,0,0,0.55);'>
                    Implose.gg
                </div>

                <div style='height:4px;background:#0d1016;margin-bottom:22px;'></div>

                <div style='font-family:\"Pixelify Sans\",-apple-system;font-size:20px;font-weight:700;line-height:1.2;color:#f4f4f4;margin-bottom:18px;'>
                    Your verification code
                </div>

                <div style='background:#242b38;border:4px solid #0d1016;box-shadow:inset 0 4px 0 rgba(255,255,255,0.08),inset 0 -4px 0 rgba(0,0,0,0.35);padding:18px 8px;text-align:center;margin-bottom:20px;box-sizing:border-box;'>

                    <div style='font-family:\"Saira\",-apple-system;font-size:30px;font-weight:800;letter-spacing:6px;line-height:1.2;color:#f4f4f4;white-space:nowrap;'>
                        $otp_code
                    </div>

                </div>

                <br>

                <div style='font-family:\"Pixelify Sans\",-apple-system;font-size:12px;line-height:1.6;color:#b8bec9;'>
                    This code expires in 10 minutes.<br>
                    If you did not request this, you can ignore this email.
                </div>

            </div>

        </div>
    ",
    ]);
}

?>