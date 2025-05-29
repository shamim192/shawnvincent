<?php

namespace App\Traits;

use Mailgun\Mailgun;

trait Mail
{
    public function OtpMailGun($to, $subject, $message, $otp)
    {
        $data = date('Y');
        $html = <<<EOT
<h1>Your OTP Code</h1>
<p>Please use the following 6-digit code to verify your email:</p>
 
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - Ownhustle</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
 
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #f5f5f5;
        }
 
        .header {
            background-color: #010c0f;
            padding: 15px;
            text-align: center;
            color: white;
            border-radius: 8px 8px 0 0;
        }
 
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
 
        .content {
            padding: 20px;
            font-size: 16px;
            color: #141414;
        }
 
        .footer {
            background-color: #f5f5f5;
            padding: 15px;
            text-align: center;
            border-radius: 0 0 8px 8px;
        }
 
        .button {
            display: inline-block;
            background-color: #010c0f;
            color: #ffffff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 16px;
        }
 
        .button:hover {
            background-color: #f3ac4e;
        }
 
        .verification-code {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 16px;
            text-align: center;
            font-weight: bold;
            letter-spacing: 2px;
        }
    </style>
</head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Verify Your Email</h1>
            </div>
            <div class="content">
                <p>Thank you for joining Music Hype World Wide! To complete your registration, please verify your email address using the code below:</p>
                <div class="verification-code">
                    $otp
                </div>
                <p>This code will expire in 5 minutes.</p>
                <p>If you have any questions, feel free to contact our support team.</p>
                <p>Thank you,<br>Music Hype World Wide Team</p>
            </div>
            <div class="footer">
                <p>&copy; $data Music Hype World Wide. All rights reserved.</p>
            </div>
        </div>
    </body>
</html>
EOT;
        try {
            $mg = Mailgun::create(env('MAILGUN_SECRET'));
            $mg->messages()->send(
                env('MAILGUN_DOMAIN'),
                [
                    'from' => env('MAIL_FROM_ADDRESS'),
                    'to' => $to,
                    'subject' => $subject,
                    'html' => $html
                ]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function MailGun($to, $subject, $message)
    {
        $mg = Mailgun::create(env('MAILGUN_SECRET'));
        $mg->messages()->send(
            env('MAILGUN_DOMAIN'),
            [
                'from' => env('MAIL_FROM_ADDRESS'),
                'to' => $to,
                'subject' => $subject,
                'text' => $message
            ]
        );

        return true;
    }
}
