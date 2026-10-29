<?php
require __DIR__ . '/auth.php';
requireLogin();
$statement = $pdo->prepare('SELECT order_number, payment_method, fulfillment_type, preferred_time, special_instructions, order_status, total_amount, items_json, created_at FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
$statement->execute([(int)currentUser()['id']]);
$orders = $statement->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My orders | Romy and Allit Food Haven</title>
<link rel="stylesheet" href="styles.css">
</head>
<body class="dashboard-page">
    <header class="dashboard-header">
        <a class="brand" href="index.php">Romy and Allit Food Haven</a>
        <a class="button dark" href="logout.php">Log out</a>
    </header>
    <main class="orders">
        <a class="back-button" href="index.php" aria-label="Back to menu">&larr; Back to menu</a>
        <p class="eyebrow"><span></span> Customer portal</p>
        <h1>My orders</h1>
        <?php if (!$orders): ?>
            <p>No orders yet. <a href="index.php#menu">Browse the menu</a></p>
        <?php endif; ?>
        <?php foreach ($orders as $order):
            $items = json_decode($order['items_json'], true) ?: [];
        ?>
            <article class="order-card">
                <header>
                    <strong><?= e($order['order_number']) ?></strong>
                    <span class="status <?= e($order['order_status']) ?>"><?= e(ucfirst(str_replace('_', ' ', $order['order_status']))) ?></span>
                </header>
                <ul>
                    <?php foreach ($items as $item): ?>
                        <li><?= e((string)$item['name']) ?> &times; <?= (int)$item['quantity'] ?> &mdash; &#8369;<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="order-meta">
                    Total: &#8369;<?= number_format((float)$order['total_amount'], 2) ?> &middot;
                    <?= e(ucfirst(str_replace('_', ' ', $order['fulfillment_type']))) ?> &middot;
                    <?= e($order['preferred_time'] ?: 'No preferred time') ?><br>
                    <?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?>
                    <?php if ($order['special_instructions'] !== ''): ?>
                        <br>Note: <?= e($order['special_instructions']) ?>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </main>
</body>
</html>
