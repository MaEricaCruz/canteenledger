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
if (!$client) {
    echo "Client not found."; 
    exit();
}

// Fetch total unpaid debt
$stmt = $pdo->prepare("SELECT IFNULL(SUM(total),0) AS debt FROM orders WHERE client_id = ? AND status != 'Completed' AND payment_method = 'debt'");
$stmt->execute([$client_id]);
$debt = $stmt->fetchColumn();

// Fetch recent orders
$stmt = $pdo->prepare("
    SELECT o.*, GROUP_CONCAT(CONCAT(mi.name, ':', oi.quantity) SEPARATOR '|') as items
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
    WHERE o.client_id = ?
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT 5
");
$stmt->execute([$client_id]);
$recent_orders = $stmt->fetchAll();

// Fetch recent payments
$stmt = $pdo->prepare("
    SELECT * FROM payments 
    WHERE client_id = ? 
    ORDER BY payment_date DESC 
    LIMIT 5
");
$stmt->execute([$client_id]);
$recent_payments = $stmt->fetchAll();

// Filter parameters
$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

// Check if debt_history table exists
$tableExists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'debt_history'");
    $tableExists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    // Table doesn't exist
}

$debts = [];
if ($tableExists) {
    $where = "client_id = ?";
    $params = [$client_id];

    if (!empty($from)) {
        $where .= " AND debt_date >= ?";
        $params[] = $from;
    }
    if (!empty($to)) {
        $where .= " AND debt_date <= ?";
        $params[] = $to . " 23:59:59";
    }

    // Fetch filtered debt history
    try {
        $debtStmt = $pdo->prepare("SELECT amount, debt_date FROM debt_history WHERE $where ORDER BY debt_date DESC");
        $debtStmt->execute($params);
        $debts = $debtStmt->fetchAll();
    } catch (PDOException $e) {
        // Handle query error gracefully
        $debts = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Canteen Ledger</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
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
            <a href="dashboard.php" class="active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="menu.php">
                <i class="fas fa-utensils"></i> Menu
            </a>
            <a href="orders.php">
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
        <div class="welcome-section">
            <h1>Welcome back, <?= htmlspecialchars($client['name']) ?>!</h1>
            <p>Here's an overview of your recent activity</p>
        </div>

        <div class="dashboard-grid">
            <!-- Recent Orders -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Recent Orders</h2>
                    <a href="orders.php" class="view-all">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php if (empty($recent_orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <p>No recent orders</p>
                </div>
                <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($recent_orders as $order): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <span class="order-id">Order #<?= $order['id'] ?></span>
                            <span class="order-date"><?= date('M d, Y', strtotime($order['order_date'])) ?></span>
                        </div>
                        <div class="order-amount">₱<?= number_format($order['total'], 2) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Payments -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>Recent Payments</h2>
                    <a href="payments.php" class="view-all">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php if (empty($recent_payments)): ?>
                <div class="empty-state">
                    <i class="fas fa-credit-card"></i>
                    <p>No recent payments</p>
                </div>
                <?php else: ?>
                <div class="payments-list">
                    <?php foreach ($recent_payments as $payment): ?>
                    <div class="payment-item">
                        <div class="payment-info">
                            <span class="payment-id">Payment #<?= $payment['id'] ?></span>
                            <span class="payment-date"><?= date('M d, Y', strtotime($payment['payment_date'])) ?></span>
                        </div>
                        <div class="payment-amount">₱<?= number_format($payment['amount'], 2) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
