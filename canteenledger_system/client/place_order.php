<?php
session_start();
require '../db.php';

if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$client_id = $_SESSION['client_id'];

// Check cart
if (empty($_SESSION['cart'])) {
    echo "Your cart is empty. <a href='menu.php'>Go back to menu</a>";
    exit();
}

// Validate payment method input
if (!isset($_POST['payment_method']) || !in_array($_POST['payment_method'], ['cash', 'debt'])) {
    echo "Please select a valid payment method.";
    exit();
}

$payment_method = $_POST['payment_method'];

// Fetch cart items and calculate total
$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = $pdo->prepare("SELECT id, price FROM menu_items WHERE id IN ($placeholders)");
$stmt->execute($ids);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $item) {
    $qty = $_SESSION['cart'][$item['id']];
    $total += $item['price'] * $qty;
}

try {
    $pdo->beginTransaction();

    // Insert order
    $orderStmt = $pdo->prepare("INSERT INTO orders (client_id, total, payment_method, status) VALUES (?, ?, ?, 'pending')");
    $orderStmt->execute([$client_id, $total, $payment_method]);
    $order_id = $pdo->lastInsertId();

    // Insert order items
    $orderItemStmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $qty = $_SESSION['cart'][$item['id']];
        $orderItemStmt->execute([$order_id, $item['id'], $qty, $item['price']]);
    }

    if ($payment_method === 'debt') {
        // Update client's total debt
        $updateDebt = $pdo->prepare("UPDATE clients SET debt = debt + ? WHERE id = ?");
        $updateDebt->execute([$total, $client_id]);

        // Log to debt_history
        $debtLog = $pdo->prepare("
            INSERT INTO debt_history (client_id, order_id, amount, type, description)
            VALUES (?, ?, ?, 'add', ?)
        ");
        $debtLog->execute([$client_id, $order_id, $total, "Order #$order_id - unpaid"]);
    }

    $pdo->commit();

    // Clear cart
    $_SESSION['cart'] = [];

    echo "Order placed successfully! <a href='dashboard.php'>Go to dashboard</a>";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Failed to place order: " . $e->getMessage();
}
