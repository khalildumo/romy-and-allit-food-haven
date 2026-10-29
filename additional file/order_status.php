<?php
require __DIR__ . '/auth.php';
header('Content-Type: application/json; charset=utf-8');

$user = currentUser();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are accepted.']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$token = (string) ($_SESSION['status_csrf'] ?? '');
if ($token === '' || !hash_equals($token, (string) ($input['csrf_token'] ?? ''))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request token.']);
    exit;
}
$orderNumber = trim((string) ($input['order_number'] ?? ''));
$status = (string) ($input['status'] ?? '');
if ($orderNumber === '' || strlen($orderNumber) > 30 || !in_array($status, ['pending', 'preparing', 'ready', 'completed', 'cancelled'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid order status update.']);
    exit;
}
$currentStatement = $pdo->prepare('SELECT order_status FROM orders WHERE order_number = ?');
$currentStatement->execute([$orderNumber]);
$currentStatus = $currentStatement->fetchColumn();
if ($currentStatus === false) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}
$allowed = [
    'pending' => ['pending', 'preparing', 'cancelled'],
    'preparing' => ['preparing', 'ready', 'cancelled'],
    'ready' => ['ready', 'completed'],
    'completed' => ['completed'],
    'cancelled' => ['cancelled'],
];
if (!in_array($status, $allowed[$currentStatus] ?? [], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Orders must follow the required status sequence.']);
    exit;
}
$statement = $pdo->prepare('UPDATE orders SET order_status = ? WHERE order_number = ?');
$statement->execute([$status, $orderNumber]);
if ($statement->rowCount() === 0) {
    echo json_encode(['success' => true, 'order_status' => $status]);
    exit;
}
echo json_encode(['success' => true, 'order_status' => $status]);
