<?php
require __DIR__ . '/auth.php';

if (currentUser()) {
    header('Location: ' . dashboardForRole(currentUser()['role']));
    exit;
}

$error = '';
$success = '';
$mode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $mode === 'register') {
    header('Location: register.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'register') {
    header('Location: register.php');
    exit;
}

if (($_GET['registered'] ?? '') === '1') {
    $success = 'Account created successfully. You can now log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = ($_POST['mode'] ?? 'login') === 'register' ? 'register' : 'login';
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $fullName = trim((string)($_POST['full_name'] ?? ''));

    if ($mode === 'register') {
        if (
            $fullName === '' ||
            $username === '' ||
            strlen($fullName) > 120 ||
            strlen($username) > 80 ||
            !preg_match('/^[A-Za-z0-9_.-]+$/', $username) ||
            strlen($password) < 8 ||
            $password !== (string)($_POST['confirm_password'] ?? $password)
        ) {
            $error = 'Enter a valid name and username and use a password with at least 8 characters.';
        } else {
            try {
                $statement = $pdo->prepare(
                    'INSERT INTO users (full_name, username, password_hash, role)
                     VALUES (?, ?, ?, ?)'
                );

                $statement->execute([
                    $fullName,
                    $username,
                    password_hash($password, PASSWORD_DEFAULT),
                    'customer'
                ]);

                header('Location: login.php?registered=1');
                exit;
            } catch (PDOException $exception) {
                $error = 'That username is already in use.';
            }
        }
    } else {
        $statement = $pdo->prepare(
            'SELECT id, full_name, username, password_hash, role, email_verified
             FROM users
             WHERE username = ?
             LIMIT 1'
        );

        $statement->execute([$username]);
        $user = $statement->fetch();

        if ($user && password_verify($password, $user['password_hash']) && !(int)$user['email_verified']) {
            $_SESSION['pending_email_verification_user_id'] = (int)$user['id'];
            header('Location: verify_email.php');
            exit;
        }

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            unset($user['password_hash']);
            $_SESSION['user'] = $user;

            header('Location: ' . dashboardForRole($user['role']));
            exit;
        }

        $error = 'Invalid username or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $mode === 'register' ? 'Create Account' : 'Log In' ?>
        | Romy and Allit Food Haven
    </title>

<link rel="stylesheet" href="styles.css">
</head>

<body class="account-page">
    <main class="account-card">

        <p class="eyebrow">
            <span></span>
            Romy and Allit Food Haven
        </p>

        <h1>
            <?= $mode === 'register'
                ? 'Create your account.'
                : 'Welcome back.' ?>
        </h1>

        <p class="account-intro">
            <?= $mode === 'register'
                ? 'Register to order faster next time.'
                : 'Log in to continue to your Romy and Allit Food Haven account.' ?>
        </p>

        <?php if ($error): ?>
            <p class="account-message">
                <?= e($error) ?>
            </p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="account-success">
                <?= e($success) ?>
            </p>
        <?php endif; ?>

        <form method="post" class="account-form" autocomplete="off">

            <input type="hidden" name="mode" value="<?= e($mode) ?>">

            <?php if ($mode === 'register'): ?>
                <label>
                    Full name
                    <input
                        type="text"
                        name="full_name"
                        maxlength="120"
                        required
                        autocomplete="off"
                    >
                </label>
            <?php endif; ?>

            <label>
                Username
                <input
                    type="text"
                    name="username"
                    maxlength="80"
                    required
                    autocomplete="off"
                >
            </label>

            <label>
                Password
                <span class="password-field">
                    <input
                        type="password"
                        name="password"
                        required
                        <?= $mode === 'register'
                            ? 'minlength="8"'
                            : '' ?>
                        autocomplete="<?= $mode === 'register'
                            ? 'new-password'
                            : 'current-password' ?>"
                    >
                    <button class="password-toggle" type="button" aria-label="Show password">
                        <svg class="eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-3.1 3.8M6.2 6.2C3.4 8.2 2 12 2 12s3.5 6 10 6c1 0 1.9-.1 2.7-.4"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </span>
            </label>

            <?php if ($mode !== 'register'): ?>
                <p class="account-switch account-forgot"><a href="forgot_password.php">Forgot your password?</a></p>
            <?php endif; ?>

            <?php if ($mode === 'register'): ?>
                <label>
                    Confirm password
                    <span class="password-field">
                        <input
                            type="password"
                            name="confirm_password"
                            minlength="8"
                            required
                            autocomplete="new-password"
                        >
                        <button class="password-toggle" type="button" aria-label="Show confirm password">
                            <svg class="eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                            <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6.5 0 10 6 10 6a18 18 0 0 1-3.1 3.8M6.2 6.2C3.4 8.2 2 12 2 12s3.5 6 10 6c1 0 1.9-.1 2.7-.4"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </span>
                </label>
            <?php endif; ?>

            <button class="button dark" type="submit">
                <?= $mode === 'register'
                    ? 'Create Account'
                    : 'Log In' ?>
                <span>→</span>
            </button>

        </form>

        <p class="account-switch">
            <?php if ($mode === 'register'): ?>
                Already have an account?
                <a href="login.php">Log in</a>
            <?php else: ?>
                New customer?
                <a href="register.php">
                    Create an account
                </a>
            <?php endif; ?>
        </p>

        <p class="account-switch">
            <a href="index.php">← Back to menu</a>
        </p>

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
