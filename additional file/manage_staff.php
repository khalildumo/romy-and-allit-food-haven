<?php
require __DIR__ . '/auth.php';
if (!currentUser() || currentUser()['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

if (empty($_SESSION['staff_csrf'])) $_SESSION['staff_csrf'] = bin2hex(random_bytes(32));
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['staff_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(400);
        exit('Invalid request token. Refresh and try again.');
    }
    $action = (string) ($_POST['action'] ?? 'create');
    if ($action === 'reset_password') {
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        if (!$userId || strlen($newPassword) < 12 || $newPassword !== $confirmPassword) {
            $error = 'Use matching passwords with at least 12 characters.';
        } else {
            $statement = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role IN ('admin', 'customer')");
            $statement->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
            $success = $statement->rowCount() ? 'Password reset successfully.' : 'Account not found.';
        }
    } else {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $role = (string) ($_POST['role'] ?? '');
    if ($fullName === '' || strlen($fullName) > 120 || !preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username)) {
        $error = 'Enter a name and a valid username (3–80 letters, numbers, dots, underscores, or hyphens).';
    } elseif (!in_array($role, ['admin', 'customer'], true) || strlen($password) < 12) {
        $error = 'Choose a valid account role and use a password with at least 12 characters.';
    } else {
        try {
            $statement = $pdo->prepare('INSERT INTO users (full_name, username, password_hash, role) VALUES (?, ?, ?, ?)');
            $statement->execute([$fullName, $username, password_hash($password, PASSWORD_DEFAULT), $role]);
            $success = ucfirst($role) . ' account created.';
        } catch (PDOException $exception) {
            $error = 'Could not create the account. The username may already be in use.';
        }
    }
    }
}
        $staff = $pdo->query("SELECT id, full_name, username, role, created_at FROM users WHERE role IN ('admin', 'customer') ORDER BY role, full_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Staff accounts | Romy and Allit Food Haven</title>
<link rel="stylesheet" href="styles.css">
    </head>
<body class="dashboard-page">
    <header class="dashboard-header">
        <a class="brand" href="dashboard.php">Admin dashboard</a>
        <div class="dashboard-actions">
            <a class="text-link" href="dashboard.php">Orders</a>
            <a class="button dark" href="logout.php">Log out</a>
        </div>
    </header>
    <main class="staff-wrap">
        <a class="back-button" href="dashboard.php" aria-label="Back to admin dashboard">&larr; Back</a>
        <p class="eyebrow"><span></span> Access control</p>
        <h1>Staff accounts</h1>
        <section class="staff-panel">
            <h2>Create staff account</h2>
            <?php if ($error): ?>
                <p class="account-message"><?= e($error) ?></p>
            <?php endif; ?>
            <?php if ($success): ?>
                <p class="account-success"><?= e($success) ?></p>
            <?php endif; ?>
            <form method="post" class="staff-form">
                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['staff_csrf']) ?>">
                <label>
                    Full name
                    <input name="full_name" maxlength="120" required>
                </label>
                <label>
                    Username
                    <input name="username" minlength="3" maxlength="80" pattern="[A-Za-z0-9_.-]+" required>
                </label>
                <label>
                    Account role
                    <select name="role" required>
                        <option value="customer">Staff</option>
                        <option value="admin">Administrator</option>
                    </select>
                </label>
                <label>
                    Temporary password (12+ characters)
                    <input type="password" name="password" minlength="12" required autocomplete="new-password">
                </label>
                <button class="button dark" type="submit">Create account</button>
            </form>
        </section>
        <section class="staff-panel">
            <h2>Accounts from the users database</h2>
            <div class="orders-table-wrap">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$staff): ?><tr><td colspan="5" class="no-orders">No staff accounts found.</td></tr><?php endif; ?>
                        <?php foreach ($staff as $person): ?>
                            <tr>
                                <td><?= e($person['full_name']) ?></td>
                                <td><?= e($person['username']) ?></td>
                                <td><?= e(ucfirst($person['role'])) ?></td>
                                <td><?= e(date('M j, Y', strtotime($person['created_at']))) ?></td>
                                <td>
                                    <details class="reset-password">
                                        <summary>Reset password</summary>
                                        <form method="post" onsubmit="return confirm('Reset this account password?');">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['staff_csrf']) ?>">
                                            <input type="hidden" name="action" value="reset_password">
                                            <input type="hidden" name="user_id" value="<?= (int) $person['id'] ?>">
                                            <input type="password" name="new_password" minlength="12" required autocomplete="new-password" placeholder="New password">
                                            <input type="password" name="confirm_password" minlength="12" required autocomplete="new-password" placeholder="Confirm password">
                                            <button class="action-button" type="submit">Save password</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body></html>
