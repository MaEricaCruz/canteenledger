<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

require '../db.php';

// Get date range from filters or use defaults
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales';

// Get overall statistics
$stats = $pdo->query("
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total), 0) as total_sales,
        COUNT(DISTINCT o.client_id) as unique_customers,
        COALESCE((SELECT SUM(amount) FROM debts WHERE status = 'pending'), 0) as total_pending_debts
    FROM orders o
    WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
")->fetch();

// Get sales data
if ($report_type === 'sales') {
    $sales_data = $pdo->query("
        SELECT 
            DATE(o.order_date) as date,
            COUNT(o.id) as order_count,
            SUM(o.total) as daily_total,
            COUNT(DISTINCT o.client_id) as customer_count
        FROM orders o
        WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
        GROUP BY DATE(o.order_date)
        ORDER BY date DESC
")->fetchAll();
}

// Get top selling items
elseif ($report_type === 'items') {
    $items_data = $pdo->query("
        SELECT 
            m.name,
            COUNT(oi.item_id) as times_ordered,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.quantity * oi.price) as total_revenue
        FROM order_items oi
        JOIN menu_items m ON oi.item_id = m.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
        GROUP BY m.id, m.name
        ORDER BY total_quantity DESC
        LIMIT 20
    ")->fetchAll();
}

// Get customer data
elseif ($report_type === 'customers') {
    $customers_data = $pdo->query("
        SELECT 
            c.name,
            COUNT(o.id) as order_count,
            SUM(o.total) as total_spent,
            COALESCE((
                SELECT SUM(amount)
                FROM debts d
                WHERE d.client_id = c.id
                AND d.status = 'pending'
            ), 0) as pending_debt
        FROM clients c
        LEFT JOIN orders o ON c.id = o.client_id
        AND DATE(o.order_date) BETWEEN '$start_date' AND '$end_date'
        GROUP BY c.id, c.name
        ORDER BY total_spent DESC
    ")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - CanteenLedger</title>
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="css/reports-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
       
        
        <nav>
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="menu.php"><i class="fas fa-utensils"></i> <span>Manage Menu</span></a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>View Orders</span></a></li>
                <li><a href="clients.php"><i class="fas fa-users"></i> <span>Manage Clients</span></a></li>
                <li><a href="debts.php"><i class="fas fa-file-invoice-dollar"></i> <span>Manage Debts</span></a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> <span>Reports</span></a></li>
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
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stats-card">
                <h3>Total Orders</h3>
                <div class="value"><?= $stats['total_orders'] ?></div>
                <div class="label">Orders in Period</div>
            </div>
            <div class="stats-card">
                <h3>Total Sales</h3>
                <div class="value">₱<?= number_format($stats['total_sales'], 2) ?></div>
                <div class="label">Revenue in Period</div>
            </div>
            <div class="stats-card">
                <h3>Unique Customers</h3>
                <div class="value"><?= $stats['unique_customers'] ?></div>
                <div class="label">Active Customers</div>
            </div>
            <div class="stats-card">
                <h3>Outstanding Debts</h3>
                <div class="value">₱<?= number_format($stats['total_pending_debts'], 2) ?></div>
                <div class="label">Total Pending</div>
            </div>
        </div>

        <!-- Report Filters -->
        <div class="report-filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="report_type">Report Type</label>
                        <select id="report_type" name="report_type" onchange="this.form.submit()">
                            <option value="sales" <?= $report_type === 'sales' ? 'selected' : '' ?>>Sales Report</option>
                            <option value="items" <?= $report_type === 'items' ? 'selected' : '' ?>>Top Items</option>
                            <option value="customers" <?= $report_type === 'customers' ? 'selected' : '' ?>>Customer Analysis</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" id="start_date" name="start_date" 
                               value="<?= htmlspecialchars($start_date) ?>" 
                               max="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
                    </div>
                    <div class="filter-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" 
                               value="<?= htmlspecialchars($end_date) ?>" 
                               max="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="button" class="btn btn-primary" onclick="exportReport()">
                        <i class="fas fa-download"></i> Export Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Report Content -->
        <div class="reports-container">
            <?php if ($report_type === 'sales'): ?>
                <div class="report-section">
                    <h2>Daily Sales Report</h2>
                    <?php if (empty($sales_data)): ?>
                        <div class="empty-state">
                            <i class="fas fa-chart-line"></i>
                            <p>No sales data available</p>
                            <span>Try selecting a different date range</span>
                        </div>
                    <?php else: ?>
                        <table class="report-table">
        <thead>
            <tr>
                                    <th>Date</th>
                                    <th>Orders</th>
                                    <th>Customers</th>
                                    <th>Total Sales</th>
            </tr>
        </thead>
        <tbody>
                                <?php foreach ($sales_data as $day): ?>
                                <tr>
                                    <td><?= date('M j, Y', strtotime($day['date'])) ?></td>
                                    <td><?= $day['order_count'] ?></td>
                                    <td><?= $day['customer_count'] ?></td>
                                    <td class="amount">₱<?= number_format($day['daily_total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
        </tbody>
    </table>
                    <?php endif; ?>
                </div>

            <?php elseif ($report_type === 'items'): ?>
                <div class="report-section">
                    <h2>Top Selling Items</h2>
                    <?php if (empty($items_data)): ?>
                        <div class="empty-state">
                            <i class="fas fa-utensils"></i>
                            <p>No items data available</p>
                            <span>Try selecting a different date range</span>
                        </div>
                    <?php else: ?>
                        <table class="report-table">
        <thead>
            <tr>
                                    <th>Item Name</th>
                                    <th>Times Ordered</th>
                                    <th>Total Quantity</th>
                                    <th>Total Revenue</th>
            </tr>
        </thead>
        <tbody>
                                <?php foreach ($items_data as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                    <td><?= $item['times_ordered'] ?></td>
                                    <td><?= $item['total_quantity'] ?></td>
                                    <td class="amount">₱<?= number_format($item['total_revenue'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
        </tbody>
    </table>
                    <?php endif; ?>
                </div>

            <?php elseif ($report_type === 'customers'): ?>
                <div class="report-section">
                    <h2>Customer Analysis</h2>
                    <?php if (empty($customers_data)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>No customer data available</p>
                            <span>Try selecting a different date range</span>
                        </div>
    <?php else: ?>
                        <table class="report-table">
            <thead>
                <tr>
                                    <th>Customer Name</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Pending Debt</th>
                </tr>
            </thead>
            <tbody>
                                <?php foreach ($customers_data as $customer): ?>
                                <tr>
                                    <td><?= htmlspecialchars($customer['name']) ?></td>
                                    <td><?= $customer['order_count'] ?></td>
                                    <td class="amount amount-positive">₱<?= number_format($customer['total_spent'], 2) ?></td>
                                    <td class="amount <?= $customer['pending_debt'] > 0 ? 'amount-negative' : '' ?>">
                                        ₱<?= number_format($customer['pending_debt'], 2) ?>
                                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function exportReport() {
        const reportType = document.getElementById('report_type').value;
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        
        // You can implement the export functionality here
        // For example, open a new window with the export URL:
        window.open(`export_report.php?type=${reportType}&start=${startDate}&end=${endDate}`, '_blank');
    }
    </script>
</body>
</html>
