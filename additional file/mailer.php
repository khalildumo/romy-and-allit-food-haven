<?php
require_once __DIR__ . '/smtp_config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

/** Send a UTF-8 email through the configured Gmail SMTP account. */
function sendAppEmail(string $recipient, string $subject, string $message): bool
{
    if (!defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD') || SMTP_USERNAME === '' || SMTP_PASSWORD === '') {
        error_log('Gmail SMTP credentials are missing in smtp_config.php.');
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($recipient);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = $message;
        $mail->send();
        return true;
    } catch (\Throwable $exception) {
        error_log('Gmail SMTP send failed: ' . $exception->getMessage());
        return false;
    }
}
