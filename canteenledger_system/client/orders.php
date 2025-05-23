<?php
session_start();
require '../db.php';

if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$client_id = $_SESSION['client_id'];

// Fetch client info
$stmt = $pdo->prepare("SELECT name FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

// Fetch orders with their items
$stmt = $pdo->prepare("
    SELECT o.*, 
           GROUP_CONCAT(CONCAT(mi.name, ':', oi.quantity, ':', mi.price) SEPARATOR '|') as items
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
    WHERE o.client_id = ?
    GROUP BY o.id
    ORDER BY o.order_date DESC
");
$stmt->execute([$client_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Canteen Ledger</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/orders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="dashboard-header">
        <div class="header-content">
            <div class="header-left">
            <img src="../picture/logo.png" alt="Canteen Ledger Logo" class="header-logo">
            <span class="header-title">Canteen Ledger</span>

            </div>
            <div class="header-user">
                <img src="../picture/user.png" alt="Admin">
                <div class="user-info">
                    <span><?= htmlspecialchars($client['name']) ?></span>
            </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo-container">
            <img src="../picture/logooa.png" alt="Canteen Ledger Logo">
        </div>
        <nav>
            <a href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="menu.php">
                <i class="fas fa-utensils"></i> Menu
            </a>
            <a href="orders.php" class="active">
                <i class="fas fa-shopping-cart"></i> My Orders
            </a>
            <a href="cart.php">
                <i class="fas fa-shopping-basket"></i> Cart
            </a>
            <a href="logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="orders-container">
            <h1>My Orders</h1>
            
            <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <i class="fas fa-shopping-bag"></i>
                <p>You haven't placed any orders yet.</p>
                <button onclick="window.location.href='menu.php'" class="browse-menu-btn">
                    Browse Menu
                </button>
            </div>
            <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <span class="order-id">Order #<?= htmlspecialchars($order['id']) ?></span>
                        <span class="order-date"><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></span>
                        <span class="status-badge status-<?= strtolower($order['status']) ?>">
                            <?= htmlspecialchars($order['status']) ?>
                        </span>
                    </div>
                    <div class="order-content">
                        <div class="order-items">
                            <?php
                            if ($order['items']) {
                                $items = explode('|', $order['items']);
                                foreach ($items as $item) {
                                    list($name, $quantity, $price) = explode(':', $item);
                                    $subtotal = $quantity * $price;
                            ?>
                            <div class="order-item">
                                <span class="item-name"><?= htmlspecialchars($name) ?></span>
                                <div class="item-details">
                                    <span class="item-quantity">x<?= $quantity ?></span>
                                    <span class="item-price">₱<?= number_format($subtotal, 2) ?></span>
                                </div>
                            </div>
                            <?php
                                }
                            }
                            ?>
                        </div>
                        <div class="order-summary">
                            <div class="payment-badge">
                                <?= ucfirst(htmlspecialchars($order['payment_method'])) ?>
                            </div>
                            <div class="order-total">
                                Total: <span>₱<?= number_format($order['total'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 