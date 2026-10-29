<?php
require __DIR__ . '/auth.php';
requireAdmin();
if (empty($_SESSION['settings_csrf'])) $_SESSION['settings_csrf'] = bin2hex(random_bytes(32));
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['settings_csrf'], (string)($_POST['csrf_token'] ?? ''))) exit('Invalid request token.');
    $action = (string)($_POST['action'] ?? 'profile');
    if ($action === 'profile') {
        $statement = $pdo->prepare('UPDATE canteen_profile SET canteen_name = ?, operating_hours = ?, contact_info = ?, pickup_location = ? WHERE id = 1');
        $statement->execute([trim((string)$_POST['canteen_name']), trim((string)$_POST['operating_hours']), trim((string)$_POST['contact_info']), trim((string)$_POST['pickup_location'])]);
        $success = 'Canteen profile updated.';
    } elseif ($action === 'category') {
        $name = trim((string)($_POST['category_name'] ?? ''));
        if ($name === '' || strlen($name) > 80) $error = 'Enter a category name up to 80 characters.';
        else { try { $pdo->prepare('INSERT INTO product_categories (name) VALUES (?)')->execute([$name]); $success = 'Category added.'; } catch (PDOException $exception) { $error = 'That category already exists.'; } }
    } elseif ($action === 'delete_category') {
        $name = trim((string)($_POST['category_name'] ?? ''));
        $count = $pdo->prepare('SELECT COUNT(*) FROM menu_products WHERE category = ?'); $count->execute([$name]);
        if ((int)$count->fetchColumn() > 0) $error = 'Move products to another category before deleting this category.';
        else { $pdo->prepare('DELETE FROM product_categories WHERE name = ?')->execute([$name]); $success = 'Category deleted.'; }
    }
}
$profile = $pdo->query('SELECT * FROM canteen_profile WHERE id = 1')->fetch();
$categories = $pdo->query('SELECT name FROM product_categories ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
?><!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Canteen settings</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body class="dashboard-page">
        <header class="dashboard-header">
            <a class="brand" href="dashboard.php">Admin dashboard</a>
            <a class="button dark" href="logout.php">Log out</a>
        </header>
        <main class="settings">
            <a class="back-button" href="dashboard.php" aria-label="Back to admin dashboard">&larr; Back to dashboard</a>
            <h1>Canteen settings</h1>
            <?php if ($error): ?><p class="account-message"><?= e($error) ?>
            </p><?php endif; ?><?php if ($success): ?>
                <p class="account-success"><?= e($success) ?></p>
                <?php endif; ?>
                <section class="settings-card">
                    <h2>Canteen profile</h2>
                    <form class="settings-form" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['settings_csrf']) ?>">
                        <input type="hidden" name="action" value="profile">
                        <label>Canteen name
                            <input name="canteen_name" required maxlength="160" value="<?= e($profile['canteen_name'] ?? '') ?>">
                        </label>
                        <label>Operating hours
                            <input name="operating_hours" required maxlength="160" value="<?= e($profile['operating_hours'] ?? '') ?>">
                        </label>
                        <label>Contact info
                            <input name="contact_info" maxlength="255" value="<?= e($profile['contact_info'] ?? '') ?>">
                        </label>
                        <label>Pickup location / counter
                            <input name="pickup_location" required maxlength="255" value="<?= e($profile['pickup_location'] ?? '') ?>">
                        </label>
                        <button class="button dark" type="submit">Save profile</button>
                    </form>
                </section>
                <section class="settings-card">
                    <h2>Categories</h2>
                    <form class="settings-form" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['settings_csrf']) ?>">
                        <input type="hidden" name="action" value="category">
                        <label>New category
                            <input name="category_name" maxlength="80" required placeholder="Desserts">
                        </label>
                        <button class="button dark" type="submit">Add category</button>
                    </form>
                    <?php foreach ($categories as $category): ?>
                        <div class="category-row">
                            <span><?= e($category) ?></span>
                            <form method="post" onsubmit="return confirm('Delete this category?')">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['settings_csrf']) ?>">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="category_name" value="<?= e($category) ?>">
                                <button class="text-link" type="submit">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </section>
            </main>
        </body>
    </html>




