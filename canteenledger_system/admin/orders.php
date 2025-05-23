<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

require '../db.php';

// Update order status if requested
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    $success_message = "Order status updated successfully!";
}

// Get filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date']) ? $_GET['date'] : 'all';

// Build the SQL query based on filters
$sql = "
SELECT o.id, o.client_id, o.status, o.total, o.order_date, c.name AS client_name
FROM orders o
JOIN clients c ON o.client_id = c.id
WHERE 1=1
";

if ($status_filter !== 'all') {
    $sql .= " AND o.status = " . $pdo->quote($status_filter);
}

if ($date_filter === 'today') {
    $sql .= " AND DATE(o.order_date) = CURDATE()";
} elseif ($date_filter === 'week') {
    $sql .= " AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($date_filter === 'month') {
    $sql .= " AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
}

$sql .= " ORDER BY o.order_date DESC";
$orders = $pdo->query($sql)->fetchAll();

// Get total orders and amount for stats
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total) as total_amount,
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_orders
    FROM orders
    WHERE DATE(order_date) = CURDATE()
")->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - CanteenLedger</title>
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="css/orders-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <nav>
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="menu.php"><i class="fas fa-utensils"></i> <span>Manage Menu</span></a></li>
                <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> <span>View Orders</span></a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> <span>Manage Clients</span></a></li>
                <li><a href="debts.php"><i class="fas fa-file-invoice-dollar"></i> <span>Manage Debts</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
            </ul>
        </nav>
        
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </div>

    <!-- Header -->
    <header class="admin-header">
        <div class="header-brand">
            <img src="../picture/logo.png" alt="Canteen Ledger Logo" class="header-logo">
            <span class="header-title">Canteen Ledger</span>
        </div>
          
            <div class="header-user">
                <img src="../picture/user.png" alt="Admin">
                <span>Admin</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stats-card">
                <h3>Today's Orders</h3>
                <div class="value"><?= $stats['total_orders'] ?></div>
                <div class="label">Total Orders</div>
            </div>
            <div class="stats-card">
                <h3>Total Sales</h3>
                <div class="value">₱<?= number_format($stats['total_amount'] ?? 0, 2) ?></div>
                <div class="label">Today's Revenue</div>
            </div>
            <div class="stats-card">
                <h3>Pending Orders</h3>
                <div class="value"><?= $stats['pending_orders'] ?></div>
                <div class="label">Need Attention</div>
            </div>
        </div>

        <div class="orders-container">
            <!-- Filters -->
            <div class="filters-section">
                <div class="filter-group">
                    <label for="status-filter">Status:</label>
                    <select id="status-filter" class="filter-select" onchange="applyFilters()">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Preparing" <?= $status_filter === 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                        <option value="Ready" <?= $status_filter === 'Ready' ? 'selected' : '' ?>>Ready</option>
                        <option value="Completed" <?= $status_filter === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="date-filter">Time Period:</label>
                    <select id="date-filter" class="filter-select" onchange="applyFilters()">
                        <option value="all" <?= $date_filter === 'all' ? 'selected' : '' ?>>All Time</option>
                        <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $date_filter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="month" <?= $date_filter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                    </select>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>No orders found</p>
                    <span>Try adjusting your filters or check back later</span>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Client</th>
                                <th>Order Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><span class="order-id">#<?= htmlspecialchars($order['id']) ?></span></td>
                                <td>
                                    <span class="client-name"><?= htmlspecialchars($order['client_name']) ?></span>
                                </td>
                                <td>
                                    <span class="order-date">
                                        <?= date('M j, Y g:i A', strtotime($order['order_date'])) ?>
                                    </span>
                                </td>
                                <td><span class="order-total">₱<?= number_format($order['total'], 2) ?></span></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                        <?= htmlspecialchars($order['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="status" class="status-select" required>
                                            <?php
                                            $statuses = ['Pending', 'Preparing', 'Ready', 'Completed'];
                                            foreach ($statuses as $status) {
                                                $selected = ($status == $order['status']) ? "selected" : "";
                                                echo "<option value=\"$status\" $selected>$status</option>";
                                            }
                                            ?>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-update">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function applyFilters() {
        const status = document.getElementById('status-filter').value;
        const date = document.getElementById('date-filter').value;
        window.location.href = `orders.php?status=${status}&date=${date}`;
    }
    </script>
</body>
</html>
