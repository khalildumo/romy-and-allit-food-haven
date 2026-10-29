<?php
require __DIR__ . '/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are accepted.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$name = trim((string)($input['student_name'] ?? ''));
$studentId = trim((string)($input['student_id'] ?? ''));
$paymentMethod = (string)($input['payment_method'] ?? '');
$fulfillmentType = (string)($input['fulfillment_type'] ?? 'pickup');
$preferredTime = trim((string)($input['preferred_time'] ?? ''));
$specialInstructions = trim((string)($input['special_instructions'] ?? ''));
$items = $input['items'] ?? [];
$customerId = !empty($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;

if ($name === '' || strlen($name) > 120 || strlen($studentId) > 60 || !in_array($fulfillmentType, ['pickup', 'dine_in'], true) || strlen($preferredTime) > 40 || strlen($specialInstructions) > 500 || !in_array($paymentMethod, ['cash', 'gcash'], true) || !is_array($items) || !$items || count($items) > 50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please provide the student details, items, and a valid payment method.']);
    exit;
}

$quantities = [];
foreach ($items as $item) {
    $productId = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
    if ($productId === false && isset($item['name']) && is_string($item['name'])) {
        $legacyProduct = $pdo->prepare('SELECT id FROM menu_products WHERE name = ? AND is_available = 1 LIMIT 1');
        $legacyProduct->execute([trim($item['name'])]);
        $productId = $legacyProduct->fetchColumn();
    }
    $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
    if ($productId === false || $productId < 1 || $quantity === false || $quantity < 1 || $quantity > 50) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'The cart contains an invalid item or quantity.']);
        exit;
    }
    $quantities[$productId] = ($quantities[$productId] ?? 0) + $quantity;
    if ($quantities[$productId] > 50) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'You can order up to 50 of each item.']);
        exit;
    }
}

$ids = array_keys($quantities);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$productQuery = $pdo->prepare("SELECT id, name, price, stock_quantity, stock_enabled FROM menu_products WHERE is_available = 1 AND id IN ($placeholders)");
$productQuery->execute($ids);
$products = [];
foreach ($productQuery->fetchAll() as $product) $products[(int)$product['id']] = $product;
if (count($products) !== count($quantities)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'One or more items are unavailable. Refresh the menu and try again.']);
    exit;
}

$cleanItems = [];
$total = 0;
foreach ($quantities as $productId => $quantity) {
    $product = $products[$productId];
    $price = (float)$product['price'];
    if ((int)$product['stock_enabled'] === 1 && (int)$product['stock_quantity'] < $quantity) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $product['name'] . ' does not have enough stock available.']);
        exit;
    }
    $cleanItems[] = ['id' => $productId, 'name' => $product['name'], 'price' => $price, 'quantity' => $quantity, 'stock_enabled' => (int)$product['stock_enabled']];
    $total += $price * $quantity;
}

if (!$cleanItems || $total <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

$orderNumber = 'AW-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
try {
    $pdo->beginTransaction();
    $stockStatement = $pdo->prepare('UPDATE menu_products SET stock_quantity = stock_quantity - :quantity WHERE id = :id AND stock_enabled = 1 AND stock_quantity >= :quantity');
    foreach ($cleanItems as $item) {
        if ($item['stock_enabled'] !== 1) continue;
        $stockStatement->execute([':quantity' => $item['quantity'], ':id' => $item['id']]);
        if ($stockStatement->rowCount() === 0) throw new RuntimeException($item['name'] . ' became unavailable. Please refresh and try again.');
    }
    $statement = $pdo->prepare('INSERT INTO orders (customer_id, order_number, student_name, student_id, payment_method, payment_status, fulfillment_type, preferred_time, special_instructions, order_status, total_amount, items_json) VALUES (:customer_id, :order_number, :student_name, :student_id, :payment_method, :payment_status, :fulfillment_type, :preferred_time, :special_instructions, :order_status, :total_amount, :items_json)');
    $statement->execute([
        ':customer_id' => $customerId,
        ':order_number' => $orderNumber,
        ':student_name' => $name,
        ':student_id' => $studentId,
        ':payment_method' => $paymentMethod,
        ':payment_status' => $paymentMethod === 'gcash' ? 'paid' : 'pay_on_pickup',
        ':fulfillment_type' => $fulfillmentType,
        ':preferred_time' => $preferredTime,
        ':special_instructions' => $specialInstructions,
        ':order_status' => 'pending',
        ':total_amount' => $total,
        ':items_json' => json_encode(array_map(static function (array $item): array { unset($item['stock_enabled']); return $item; }, $cleanItems), JSON_UNESCAPED_UNICODE),
    ]);
    $pdo->commit();
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'The order could not be saved. Please try again.']);
    exit;
} catch (RuntimeException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
    exit;
}

echo json_encode(['success' => true, 'order_number' => $orderNumber, 'total' => number_format($total, 2), 'payment_method' => $paymentMethod, 'created_at' => date('F j, Y g:i A')]);
