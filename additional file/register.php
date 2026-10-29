<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';
if (currentUser()) { header('Location: index.php'); exit; }
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');
    $institutionId = trim((string)($_POST['institution_id'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    if ($fullName === '' || $username === '' || $email === '') $error = 'Please enter your name, username, and email.';
    elseif (strlen($fullName) > 120 || strlen($username) > 80 || !preg_match('/^[A-Za-z0-9_.-]+$/', $username)) $error = 'Use a name up to 120 characters and a username of up to 80 letters, numbers, dots, underscores, or hyphens.';
    elseif (strlen($institutionId) > 80 || strlen($email) > 160 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($phone) > 40) $error = 'Please enter valid contact details.';
    elseif (strlen($password) < 8) $error = 'Your password must be at least 8 characters.';
    elseif ($password !== $confirmPassword) $error = 'The passwords do not match.';
    else {
        try {
            $pdo->beginTransaction();
            $statement = $pdo->prepare('INSERT INTO users (full_name, username, password_hash, role, institution_id, email, email_verified, phone) VALUES (?, ?, ?, ?, ?, ?, 0, ?)');
            $statement->execute([$fullName, $username, password_hash($password, PASSWORD_DEFAULT), 'customer', $institutionId, $email, $phone]);
            $userId = (int)$pdo->lastInsertId();
            $code = (string)random_int(100000, 999999);
            $expiresAt = (new DateTimeImmutable('+10 minutes'))->format('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO email_verification_codes (user_id, code_hash, expires_at) VALUES (?, ?, ?)')
                ->execute([$userId, hash('sha256', $code), $expiresAt]);

            $mailSent = sendAppEmail(
                $email,
                'Verify your Romy and Allit Food Haven account',
                "Hello {$fullName},\n\nYour email verification code is: {$code}\n\nThis code expires in 10 minutes. Enter it on the verification page to finish creating your account."
            );
            if (!$mailSent) {
                $pdo->rollBack();
                $error = 'We could not send a verification code. Please try again later or contact the canteen.';
            } else {
                $pdo->commit();
                $_SESSION['pending_email_verification_user_id'] = $userId;
                header('Location: verify_email.php');
                exit;
            }
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'That username is already in use. Please choose another one.';
        }
    }
}
?><!DOCTYPE html>
<html lang="en"><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account | Romy and Allit Food Haven</title>
<link rel="stylesheet" href="styles.css">
</head>
<body class="account-page">
    <main class="account-card">
        <a class="brand" href="index.php">
            <span class="brand-mark">A</span>
            <span>ROMY AND ALLIT FOOD HAVEN <small>CANTEEN</small></span>
        </a>
        <p class="eyebrow"><span></span> Customer portal</p>
        <h1>Create your account.</h1>
        <p class="account-intro">Register to order faster next time.</p>
        <?php if ($error): ?>
            <p class="account-message"><?= e($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="account-success"><?= e($success) ?></p>
        <?php endif; ?>
        <form method="post" class="account-form" autocomplete="off">
            <label>Full name
                <input name="full_name" required autocomplete="off" value="<?= e((string)($_POST['full_name'] ?? '')) ?>">
            </label>
            <label>Username
                <input name="username" required autocomplete="off" value="<?= e((string)($_POST['username'] ?? '')) ?>">
            </label>
            <label>Institution ID
                <input name="institution_id" required autocomplete="off" value="<?= e((string)($_POST['institution_id'] ?? '')) ?>">
            </label>
            <label>Email
                <input name="email" required autocomplete="off" value="<?= e((string)($_POST['email'] ?? '')) ?>">
            </label>
            <label>Phone
                <input name="phone" required autocomplete="off" value="<?= e((string)($_POST['phone'] ?? '')) ?>">
            </label>
            <label>Password
                <span class="password-field">
                    <input type="password" name="password" required minlength="8" autocomplete="new-password">
                    <button class="password-toggle" type="button" aria-label="Show password">
                        <svg class="eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-3.1 3.8M6.2 6.2C3.4 8.2 2 12 2 12s3.5 6 10 6c1 0 1.9-.1 2.7-.4"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </span>
            </label>
            <label>Confirm password
                <span class="password-field">
                    <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
                    <button class="password-toggle" type="button" aria-label="Show confirm password">
                        <svg class="eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-3.1 3.8M6.2 6.2C3.4 8.2 2 12 2 12s3.5 6 10 6c1 0 1.9-.1 2.7-.4"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </span>
            </label>
            <button class="button dark" type="submit">Create account <span>→</span></button>
        </form>
        <p class="account-switch">Already have an account? <a href="login.php">Log in</a></p>
        <p class="account-switch"><a href="index.php">← Back to menu</a></p>
    </main>
    <script>
        document.querySelectorAll('.password-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                const input = button.parentElement.querySelector('input');
                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                button.classList.toggle('is-visible', !visible);
                button.setAttribute('aria-label', `${visible ? 'Show' : 'Hide'} ${input.name === 'confirm_password' ? 'confirm password' : 'password'}`);
            });
        });
    </script>
</body>
</html>
