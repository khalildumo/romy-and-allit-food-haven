
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/smtp_config.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        // Generate a secure random OTP.
        $otp = (string) random_int(100000, 999999);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT);

        // Use UTC consistently for OTP expiration.
        $expiresAt = gmdate('Y-m-d H:i:s', time() + 300);

        try {
            $pdo->beginTransaction();

            // Invalidate earlier unused codes for this email.
            $stmt = $pdo->prepare("
                UPDATE email_otps
                SET used_at = UTC_TIMESTAMP()
                WHERE email = ?
                  AND used_at IS NULL
            ");
            $stmt->execute([$email]);

            $stmt = $pdo->prepare("
                INSERT INTO email_otps
                    (email, otp_hash, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$email, $otpHash, $expiresAt]);

            $otpId = (int) $pdo->lastInsertId();
            $pdo->commit();

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
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your Email Verification Code';

            $safeOtp = htmlspecialchars(
                $otp,
                ENT_QUOTES,
                'UTF-8'
            );

            $mail->Body = "
                <div style='font-family:Arial,sans-serif;
                            max-width:500px;margin:auto;padding:25px;
                            border:1px solid #ddd;border-radius:12px;'>
                    <h2>Email Verification</h2>
                    <p>Your verification code is:</p>
                    <h1 style='letter-spacing:8px;color:#198754;'>
                        {$safeOtp}
                    </h1>
                    <p>This code expires in 5 minutes.</p>
                    <p>If you did not request this code, you can ignore
                       this email.</p>
                </div>
            ";

            $mail->AltBody =
                "Your email verification code is $otp. " .
                "It expires in 5 minutes.";

            try {
                $mail->send();
            } catch (\Throwable $mailError) {
                // Invalidate a code that could not be delivered.
                $stmt = $pdo->prepare("
                    UPDATE email_otps
                    SET used_at = UTC_TIMESTAMP()
                    WHERE id = ? AND used_at IS NULL
                ");
                $stmt->execute([$otpId]);

                throw $mailError;
            }

            $_SESSION['otp_email'] = $email;
            $_SESSION['otp_last_id'] = $otpId;

            header('Location: verify_otp.php');
            exit;

        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('OTP email error: ' . $e->getMessage());

            $message =
                'We could not send your OTP. Check your Gmail SMTP ' .
                'settings and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Send OTP | Romy and Allit Food Haven</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            background: #f3f7f4;
            font-family: Arial, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 420px;
            padding: 32px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px #00000012;
        }

        h2 { color: #198754; margin-top: 0; }

        label {
            display: block;
            margin: 18px 0 8px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 16px;
            border: 0;
            border-radius: 8px;
            background: #198754;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .message {
            padding: 12px;
            background: #fff3cd;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <main class="card">
        <h2>Verify Your Email</h2>
        <p>Enter your email address to receive a verification code.</p>

        <?php if ($message !== ''): ?>
            <p class="message">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <label for="email">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                required
                autocomplete="email"
            >
            <button type="submit">Send OTP</button>
        </form>
    </main>
</body>
</html>
