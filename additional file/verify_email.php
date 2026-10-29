<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

$userId = (int)($_SESSION['pending_email_verification_user_id'] ?? 0);
if (!$userId) {
    header('Location: register.php');
    exit;
}
$userQuery = $pdo->prepare('SELECT id, full_name, username, role, email, email_verified FROM users WHERE id = ? LIMIT 1');
$userQuery->execute([$userId]);
$user = $userQuery->fetch();
if (!$user) {
    unset($_SESSION['pending_email_verification_user_id']);
    header('Location: register.php');
    exit;
}
if ((int)$user['email_verified']) {
    unset($_SESSION['pending_email_verification_user_id']);
    header('Location: login.php');
    exit;
}

$error = '';
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'resend') {
        $lastCode = $pdo->prepare('SELECT created_at FROM email_verification_codes WHERE user_id = ?');
        $lastCode->execute([$userId]);
        $record = $lastCode->fetch();
        if ($record && strtotime($record['created_at']) > time() - 60) {
            $error = 'Please wait a minute before requesting another code.';
        } else {
            $code = (string)random_int(100000, 999999);
            $expiry = (new DateTimeImmutable('+10 minutes'))->format('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO email_verification_codes (user_id, code_hash, expires_at, attempts) VALUES (?, ?, ?, 0) ON DUPLICATE KEY UPDATE code_hash = VALUES(code_hash), expires_at = VALUES(expires_at), attempts = 0, created_at = CURRENT_TIMESTAMP')
                ->execute([$userId, hash('sha256', $code), $expiry]);
            if (sendAppEmail($user['email'], 'Your email verification code', "Hello {$user['full_name']},\n\nYour new verification code is: {$code}\n\nThis code expires in 10 minutes.")) {
                $notice = 'A new verification code was sent to your email.';
            } else {
                $error = 'We could not send a code right now. Please try again later.';
            }
        }
    } else {
        $code = trim((string)($_POST['code'] ?? ''));
        $statement = $pdo->prepare('SELECT code_hash, expires_at, attempts FROM email_verification_codes WHERE user_id = ? LIMIT 1');
        $statement->execute([$userId]);
        $record = $statement->fetch();
        if (!preg_match('/^\d{6}$/', $code) || !$record || strtotime($record['expires_at']) < time() || (int)$record['attempts'] >= 5) {
            $error = 'That code is invalid or expired. Request a new code and try again.';
        } elseif (!hash_equals($record['code_hash'], hash('sha256', $code))) {
            $pdo->prepare('UPDATE email_verification_codes SET attempts = attempts + 1 WHERE user_id = ?')->execute([$userId]);
            $error = 'That code is incorrect. Please check your email and try again.';
        } else {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE users SET email_verified = 1 WHERE id = ?')->execute([$userId]);
            $pdo->prepare('DELETE FROM email_verification_codes WHERE user_id = ?')->execute([$userId]);
            $pdo->commit();
            session_regenerate_id(true);
            unset($user['email'], $user['email_verified']);
            $_SESSION['user'] = ['id' => $user['id'], 'full_name' => $user['full_name'], 'username' => $user['username'], 'role' => $user['role']];
            unset($_SESSION['pending_email_verification_user_id']);
            header('Location: ' . dashboardForRole($user['role']));
            exit;
        }
    }
}
?><!DOCTYPE html>
<html lang="en"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify email | Romy and Allit Food Haven</title>
    <link rel="stylesheet" href="styles.css">
</head><body class="account-page">
    <main class="account-card">
        <p class="eyebrow"><span></span> Customer portal</p>
        <h1>Check your email.</h1>
        <p class="account-intro">We sent a 6-digit verification code to <?= e($user['email']) ?>. It expires in 10 minutes.</p>
        <?php if ($error): ?><p class="account-message"><?= e($error) ?></p><?php endif; ?>
        <?php if ($notice): ?><p class="account-success"><?= e($notice) ?></p><?php endif; ?>
        <form method="post" class="account-form">
            <label>Verification code
                <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
            </label>
            <button class="button dark" type="submit">Verify email <span>→</span></button>
        </form>
        <form method="post" class="account-form" style="margin-top:14px">
            <input type="hidden" name="action" value="resend">
            <button class="button" type="submit">Send a new code</button>
        </form>
        <p class="account-switch"><a href="login.php">Back to log in</a></p>
    </main>
</body></html>
