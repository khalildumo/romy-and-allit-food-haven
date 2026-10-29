<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

if (currentUser()) {
    header('Location: ' . dashboardForRole(currentUser()['role']));
    exit;
}

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $sent = true;

    if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 160) {
        $statement = $pdo->prepare('SELECT id, full_name FROM users WHERE email = ? LIMIT 1');
        $statement->execute([$email]);
        $user = $statement->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
            $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([$user['id']]);
            $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)')
                ->execute([$user['id'], $tokenHash, $expiresAt]);

            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            $scheme = $https ? 'https' : 'http';
            $host = preg_replace('/[^A-Za-z0-9.:-]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $resetUrl = $scheme . '://' . $host . $basePath . '/reset_password.php?token=' . urlencode($token);
            $subject = 'Reset your Romy and Allit Food Haven password';
            $message = "Hello " . $user['full_name'] . ",\n\nUse this link to choose a new password. It expires in one hour:\n\n" . $resetUrl . "\n\nIf you did not request this, you can ignore this email.";
            sendAppEmail($email, $subject, $message);
        }
    }
}
?><!DOCTYPE html>
<html lang="en"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot password | Romy and Allit Food Haven</title>
    <link rel="stylesheet" href="styles.css">
</head><body class="account-page">
    <main class="account-card">
        <p class="eyebrow"><span></span> Romy and Allit Food Haven</p>
        <h1>Forgot your password?</h1>
        <p class="account-intro">Enter the email address on your account and we’ll send a password reset link.</p>
        <?php if ($sent): ?>
            <p class="account-success">If an account matches that email, a reset link has been sent. Check your inbox.</p>
        <?php endif; ?>
        <form method="post" class="account-form">
            <label>Email address
                <input type="email" name="email" maxlength="160" required autocomplete="email">
            </label>
            <button class="button dark" type="submit">Send reset link <span>→</span></button>
        </form>
        <p class="account-switch"><a href="login.php">Back to log in</a></p>
        <p class="account-switch"><a href="index.php">← Back to menu</a></p>
    </main>
</body></html>
