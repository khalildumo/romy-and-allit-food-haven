<?php
require __DIR__ . '/auth.php';

if (currentUser()) {
    header('Location: ' . dashboardForRole(currentUser()['role']));
    exit;
}

$token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$validToken = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
$error = '';
$success = false;
$reset = null;

if ($validToken) {
    $statement = $pdo->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND expires_at > NOW() LIMIT 1');
    $statement->execute([hash('sha256', $token)]);
    $reset = $statement->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    if (!$reset) {
        $error = 'This reset link is invalid or has expired. Request a new one.';
    } elseif (strlen($password) < 8) {
        $error = 'Your password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT user_id FROM password_reset_tokens WHERE id = ? AND expires_at > NOW() FOR UPDATE');
        $lock->execute([$reset['id']]);
        $lockedReset = $lock->fetch();
        if (!$lockedReset) {
            $pdo->rollBack();
            $error = 'This reset link is invalid or has expired. Request a new one.';
        } else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $lockedReset['user_id']]);
            $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([$lockedReset['user_id']]);
            $pdo->commit();
            $success = true;
        }
    }
}
?><!DOCTYPE html>
<html lang="en"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password | Romy and Allit Food Haven</title>
    <link rel="stylesheet" href="styles.css">
</head><body class="account-page">
    <main class="account-card">
        <p class="eyebrow"><span></span> Romy and Allit Food Haven</p>
        <?php if ($success): ?>
            <h1>Password updated.</h1>
            <p class="account-success">Your password has been changed. You can log in with your new password.</p>
            <p class="account-switch"><a href="login.php">Go to log in</a></p>
        <?php elseif (!$reset): ?>
            <h1>Reset link expired.</h1>
            <p class="account-message">This password reset link is invalid or has expired. Request a new one to continue.</p>
            <p class="account-switch"><a href="forgot_password.php">Request a new link</a></p>
        <?php else: ?>
            <h1>Choose a new password.</h1>
            <p class="account-intro">Use at least 8 characters for your new password.</p>
            <?php if ($error): ?><p class="account-message"><?= e($error) ?></p><?php endif; ?>
            <form method="post" class="account-form">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <label>New password
                    <input type="password" name="password" required minlength="8" autocomplete="new-password">
                </label>
                <label>Confirm new password
                    <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
                </label>
                <button class="button dark" type="submit">Save new password <span>→</span></button>
            </form>
        <?php endif; ?>
    </main>
</body></html>
