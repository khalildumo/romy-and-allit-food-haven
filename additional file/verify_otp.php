
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';

if (
    empty($_SESSION['otp_email']) ||
    empty($_SESSION['otp_last_id'])
) {
    header('Location: send_otp.php');
    exit;
}

$email = $_SESSION['otp_email'];
$otpId = (int) $_SESSION['otp_last_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');

    if (!preg_match('/^\d{6}$/', $otp)) {
        $message = 'Please enter a valid six-digit OTP.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, otp_hash, attempts
            FROM email_otps
            WHERE id = ?
              AND email = ?
              AND used_at IS NULL
              AND expires_at >= UTC_TIMESTAMP()
              AND attempts < 5
            LIMIT 1
        ");
        $stmt->execute([$otpId, $email]);
        $record = $stmt->fetch();

        if (!$record) {
            $message =
                'Your OTP is expired, used, or has too many failed ' .
                'attempts. Please request a new code.';
        } elseif (!password_verify($otp, $record['otp_hash'])) {
            $stmt = $pdo->prepare("
                UPDATE email_otps
                SET attempts = attempts + 1
                WHERE id = ?
                  AND used_at IS NULL
                  AND expires_at >= UTC_TIMESTAMP()
                  AND attempts < 5
            ");
            $stmt->execute([$otpId]);

            $message = 'Incorrect OTP. Please try again.';
        } else {
            // Atomically mark the OTP as used.
            $stmt = $pdo->prepare("
                UPDATE email_otps
                SET used_at = UTC_TIMESTAMP()
                WHERE id = ?
                  AND email = ?
                  AND used_at IS NULL
                  AND expires_at >= UTC_TIMESTAMP()
                  AND attempts < 5
            ");
            $stmt->execute([$otpId, $email]);

            if ($stmt->rowCount() === 1) {
                session_regenerate_id(true);

                $_SESSION['email_verified'] = $email;
                unset(
                    $_SESSION['otp_email'],
                    $_SESSION['otp_last_id']
                );

                header('Location: otp_success.php');
                exit;
            }

            $message =
                'The code has expired or was already used. ' .
                'Please request a new OTP.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify OTP | Romy and Allit Food Haven</title>
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

        input {
            width: 100%;
            padding: 14px;
            margin: 12px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 22px;
            text-align: center;
            letter-spacing: 8px;
        }

        button {
            width: 100%;
            padding: 13px;
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

        a { color: #198754; }
    </style>
</head>
<body>
    <main class="card">
        <h2>Enter Your OTP</h2>
        <p>
            A six-digit verification code was sent to
            <strong>
                <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
            </strong>.
        </p>

        <?php if ($message !== ''): ?>
            <p class="message">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST">
            <label for="otp">Six-digit code</label>
            <input
                type="text"
                id="otp"
                name="otp"
                inputmode="numeric"
                pattern="[0-9]{6}"
                minlength="6"
                maxlength="6"
                autocomplete="one-time-code"
                required
            >
            <button type="submit">Verify OTP</button>
        </form>

        <p>
            <a href="send_otp.php">Request a new OTP</a>
        </p>
    </main>
</body>
</html>
