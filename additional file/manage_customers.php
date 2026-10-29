<?php
require __DIR__ . '/auth.php';
if (!currentUser() || currentUser()['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

if (empty($_SESSION['customers_csrf'])) $_SESSION['customers_csrf'] = bin2hex(random_bytes(32));
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['customers_csrf'], (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(400);
        exit('Invalid request token. Refresh and try again.');
    }
    $action = (string)($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        $error = 'Select a valid customer account.';
    } elseif ($action === 'delete') {
        $statement = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'customer'");
        $statement->execute([$id]);
        $success = $statement->rowCount() ? 'Customer account deleted.' : 'Customer account was not found.';
    } elseif ($action === 'edit') {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $institutionId = trim((string)($_POST['institution_id'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        if ($fullName === '' || strlen($fullName) > 120 || !preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username)) {
            $error = 'Enter a name and a valid username (3-80 letters, numbers, dots, underscores, or hyphens).';
        } elseif (strlen($institutionId) > 80 || strlen($email) > 160 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) || strlen($phone) > 40) {
            $error = 'Check the institution ID, email, and phone fields.';
        } else {
            try {
                $statement = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, institution_id = ?, email = ?, phone = ? WHERE id = ? AND role = 'customer'");
                $statement->execute([$fullName, $username, $institutionId, $email, $phone, $id]);
                $success = 'Customer details updated.';
            } catch (PDOException $exception) {
                $error = 'Could not update the account. The username may already be in use.';
            }
        }
    }
}

$customers = $pdo->query(
    "SELECT id, full_name, username, institution_id, email, phone, created_at
     FROM users
     WHERE role = 'customer'
     ORDER BY created_at DESC, full_name ASC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage customers | Romy and Allit Food Haven</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="dashboard-page">
    <header class="dashboard-header">
        <a class="brand" href="dashboard.php">Admin dashboard</a>
        <div class="dashboard-actions">
            <span><?= count($customers) ?> customer accounts</span>
            <a class="button dark" href="logout.php">Log out</a>
        </div>
    </header>
    <main class="staff-wrap">
        <a class="back-button" href="dashboard.php">&larr; Back to dashboard</a>
        <p class="eyebrow"><span></span> Customer accounts</p>
        <h1>Manage customers</h1>
        <p class="account-intro">Customer details currently saved in the users database.</p>
        <?php if ($error): ?><p class="account-message"><?= e($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p class="account-success"><?= e($success) ?></p><?php endif; ?>
        <section class="staff-panel">
            <h2>Registered customers <small>(<?= count($customers) ?>)</small></h2>
            <div class="orders-table-wrap">
                <table class="staff-table customer-table">
                    <thead>
                        <tr><th>Name</th><th>Username</th><th>Institution ID</th><th>Email</th><th>Phone</th><th>Joined</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$customers): ?>
                            <tr><td colspan="7" class="no-orders">No customer accounts found in the database.</td></tr>
                        <?php else: foreach ($customers as $customer): ?>
                            <tr>
                                <td><?= e($customer['full_name']) ?></td>
                                <td><?= e($customer['username']) ?></td>
                                <td><?= e($customer['institution_id'] ?: '—') ?></td>
                                <td><?= e($customer['email'] ?: '—') ?></td>
                                <td><?= e($customer['phone'] ?: '—') ?></td>
                                <td><?= e(date('M j, Y', strtotime($customer['created_at']))) ?></td>
                                <td class="customer-actions">
                                    <details>
                                        <summary class="customer-edit-button">Edit</summary>
                                        <form method="post" class="customer-edit-form">
                                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['customers_csrf']) ?>">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?= (int)$customer['id'] ?>">
                                            <label>Name<input name="full_name" maxlength="120" required value="<?= e($customer['full_name']) ?>"></label>
                                            <label>Username<input name="username" minlength="3" maxlength="80" pattern="[A-Za-z0-9_.-]+" required value="<?= e($customer['username']) ?>"></label>
                                            <label>Institution ID<input name="institution_id" maxlength="80" value="<?= e($customer['institution_id']) ?>"></label>
                                            <label>Email<input type="email" name="email" maxlength="160" value="<?= e($customer['email']) ?>"></label>
                                            <label>Phone<input name="phone" maxlength="40" value="<?= e($customer['phone']) ?>"></label>
                                            <button class="button dark" type="submit">Save changes</button>
                                        </form>
                                    </details>
                                    <form method="post" onsubmit="return confirm('Delete this customer account? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['customers_csrf']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$customer['id'] ?>">
                                        <button class="customer-delete-button" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
