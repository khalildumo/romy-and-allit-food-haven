
<?php
require __DIR__ . '/auth.php';
if (!currentUser() || currentUser()['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
if (empty($_SESSION['status_csrf'])) $_SESSION['status_csrf'] = bin2hex(random_bytes(32));

$orders = $pdo->query(
    'SELECT order_number, student_name, student_id, payment_method,
            payment_status, order_status, total_amount, items_json, created_at
     FROM orders
     ORDER BY created_at DESC'
)->fetchAll();
$products = $pdo->query('SELECT id, name, category, price, is_available, stock_quantity, stock_enabled FROM menu_products ORDER BY category, name')->fetchAll();
$accounts = $pdo->query("SELECT id, full_name, username, role, created_at FROM users ORDER BY role, full_name")->fetchAll();
$customerCount = count(array_filter(
    $accounts,
    static fn(array $account): bool => $account['role'] === 'customer'
));

$paid = count(array_filter(
    $orders,
    static fn(array $order): bool =>
        $order['payment_status'] === 'paid'
));

$revenue = array_sum(array_map(
    static fn(array $order): float =>
        (float) $order['total_amount'],
    $orders
));
$today = date('Y-m-d');
$dailyRevenue = 0.0;
$topItems = [];
foreach ($orders as $order) {
    if (substr((string)$order['created_at'], 0, 10) === $today) $dailyRevenue += (float)$order['total_amount'];
    foreach (json_decode((string)$order['items_json'], true) ?: [] as $item) {
        $itemName = (string)($item['name'] ?? 'Item');
        $topItems[$itemName] = ($topItems[$itemName] ?? 0) + (int)($item['quantity'] ?? 0);
    }
}
arsort($topItems);
$topItems = array_slice($topItems, 0, 5, true);

$orderStatuses = [
    'pending' => 'Pending',
    'preparing' => 'Preparing',
    'ready' => 'Ready for Pick-up',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];

$paymentLabels = [
    'gcash' => 'GCash',
    'cash' => 'Cash',
    'bank_transfer' => 'Bank transfer',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Romy and Allit Food Haven</title>

<link rel="stylesheet" href="styles.css">
</head>

<body class="dashboard-page">

    <header class="dashboard-header">

        <a class="brand" href="index.php">ROMY AND ALLIT FOOD HAVEN</a>

        <div class="dashboard-actions">
            <span>
                Hi, <?= e(currentUser()['full_name']) ?>
            </span>

            <a class="button dark" href="logout.php">
                Log out
            </a>
        </div>

    </header>

    <main class="dashboard-content">

        <div class="dashboard-title">
            <div>
                <p class="eyebrow">
                    <span></span> Admin dashboard
                </p>

                <h1>Order overview.</h1>
                <p class="dashboard-subtitle">A quick look at today’s sales, orders, and canteen activity.</p>
            </div>

            <div class="dashboard-actions">
                <a class="button dark" href="index.php">View customer site</a>
                <a class="text-link" href="index.php">
                View customer site →
                </a>
            </div>
        </div>

        <nav class="dashboard-tools" aria-label="Dashboard tools">
            <a href="manage_products.php"><strong>Menu products</strong><small><?= count($products) ?> products · Manage menu</small><b>→</b></a>
            <a href="manage_customers.php"><strong>Customers</strong><small><?= (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn() ?> registered accounts · View customers</small><b>→</b></a>
            <a href="admin_settings.php"><strong>Canteen settings</strong><small>Hours, categories and details</small><b>→</b></a>
        </nav>

        <section class="stats-grid" aria-label="Business summary">

            <div>
                <small>Total orders</small>
                <strong><?= count($orders) ?></strong>
            </div>

            <div>
                <small>Paid orders</small>
                <strong><?= $paid ?></strong>
            </div>

            <div>
                <small>Total order value</small>
                <strong>
                    ₱<?= number_format($revenue, 2) ?>
                </strong>
            </div>

            <div>
                <small>Today's sales</small>
                <strong>₱<?= number_format($dailyRevenue, 2) ?></strong>
            </div>

            <div>
                <small>Needs attention</small>
                <strong><?= count(array_filter($orders, static fn(array $order): bool => in_array(($order['order_status'] ?? 'pending'), ['pending', 'preparing'], true))) ?></strong>
            </div>

        </section>

        <section class="orders-panel dashboard-section-spaced">
            <div class="panel-heading"><h2>Top-selling items</h2><span>All time</span></div>
            <?php if (!$topItems): ?><p>No sales data yet.</p><?php else: ?><ol><?php foreach ($topItems as $itemName => $quantity): ?><li><?= e($itemName) ?> — <?= (int)$quantity ?> sold</li><?php endforeach; ?></ol><?php endif; ?>
        </section>

        <section class="orders-panel dashboard-section-spaced">
            <div class="panel-heading"><h2>Menu products</h2></div>
            <div class="orders-table-wrap"><table>
                <thead><tr><th>ID</th><th>Product</th><th>Category</th><th>Price</th><th>Availability</th></tr></thead>
                <tbody><?php if (!$products): ?><tr><td colspan="5" class="no-orders">No menu products in the database.</td></tr><?php else: foreach ($products as $product): ?>
                    <?php $productInStock = (int) $product['is_available'] === 1 && ((int) $product['stock_enabled'] !== 1 || (int) $product['stock_quantity'] > 0); ?>
                    <tr>
                        <td><?= (int) $product['id'] ?></td>
                        <td><?= e($product['name']) ?></td>
                        <td><?= e(ucfirst($product['category'])) ?></td>
                        <td>₱<?= number_format((float) $product['price'], 2) ?></td>
                        <td><span class="stock-status <?= $productInStock ? 'available' : 'sold-out' ?>"><?= $productInStock ? 'Available' : 'Sold out' ?></span></td>
                    </tr>
                <?php endforeach; endif; ?></tbody>
            </table></div>
        </section>

        <section class="orders-panel dashboard-section-spaced">
            <div class="panel-heading"><h2>User accounts</h2></div>
            <div class="orders-table-wrap"><table>
                <thead><tr><th>ID</th><th>Name</th><th>Username</th><th>Role</th><th>Created</th></tr></thead>
                <tbody><?php if (!$accounts): ?><tr><td colspan="5" class="no-orders">No user accounts in the database.</td></tr><?php else: foreach ($accounts as $account): ?>
                    <tr><td><?= (int) $account['id'] ?></td><td><?= e($account['full_name']) ?></td><td><?= e($account['username']) ?></td><td><?= e(ucfirst($account['role'])) ?></td><td><?= e(date('M j, Y', strtotime($account['created_at']))) ?></td></tr>
                <?php endforeach; endif; ?></tbody>
            </table></div>
        </section>

        <section class="orders-panel">

            <div class="panel-heading">
                <h2>Recent orders</h2>
                <span><?= date('F j, Y') ?></span>
            </div>

            <div class="orders-table-wrap">

                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Student</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Placed</th>
                            <th>Update status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (empty($orders)): ?>

                        <tr>
                            <td colspan="7" class="no-orders">
                                No orders yet.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($orders as $order): ?>

                            <?php
                            $paymentMethod = $order['payment_method'];

                            $paymentLabel = $paymentLabels[$paymentMethod] ?? ucfirst(str_replace('_', ' ', $paymentMethod));

                            $status = $order['order_status'] ?? 'pending';
                            $statusLabel = ucfirst(str_replace('_', ' ', $status));
                            ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?= e($order['order_number']) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= e($order['student_name']) ?>

                                    <small>
                                        <?= e($order['student_id']) ?>
                                    </small>
                                </td>

                                <td>
                                    <?= e($paymentLabel) ?>
                                </td>

                                <td>
                                    <span class="status <?= e($status) ?>" data-status-for="<?= e($order['order_number']) ?>">
                                        <?= e($statusLabel) ?>
                                    </span>
                                </td>

                                <td>
                                    ₱<?= number_format(
                                        (float) $order['total_amount'],
                                        2
                                    ) ?>
                                </td>

                                <td>
                                    <?php
                                    $timestamp = strtotime($order['created_at']);
                                    ?>

                                    <?= $timestamp !== false
                                        ? e(date('M j, g:i A', $timestamp))
                                        : '—' ?>
                                </td>

                                <td>
                                    <select class="order-status-select" data-order="<?= e($order['order_number']) ?>">
                                        <?php foreach ($orderStatuses as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>

        </section>

    </main>

<script>
const statusCsrf = <?= json_encode($_SESSION['status_csrf']) ?>;
document.querySelectorAll('.order-status-select').forEach(select => select.addEventListener('change', async () => {
    select.disabled = true;
    try {
        const response = await fetch('order_status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_number: select.dataset.order, status: select.value, csrf_token: statusCsrf }) });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Status update failed.');
        const badge = document.querySelector(`[data-status-for="${CSS.escape(select.dataset.order)}"]`);
        badge.textContent = select.options[select.selectedIndex].text;
        badge.className = `status ${select.value}`;
    } catch (error) { alert(error.message); window.location.reload(); }
    select.disabled = false;
}));
setTimeout(() => window.location.reload(), 30000);
</script>
</body>
</html>
