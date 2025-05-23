<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

require '../db.php';

// Add Debt
if (isset($_POST['add_debt'])) {
    $client_id = $_POST['client_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO debts (client_id, amount, description) VALUES (?, ?, ?)");
    $stmt->execute([$client_id, $amount, $description]);
    $success_message = "Debt record added successfully!";
}

// Mark as Paid
if (isset($_POST['mark_paid'])) {
    $debt_id = $_POST['debt_id'];
    $stmt = $pdo->prepare("UPDATE debts SET status = 'paid', date_paid = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$debt_id]);
    $success_message = "Debt marked as paid successfully!";
}

// Delete Debt
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM debts WHERE id = ?");
    $stmt->execute([$id]);
    $success_message = "Debt record deleted successfully!";
}

// Get debt statistics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_debts,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_debts,
        COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as total_pending_amount
    FROM debts
")->fetch();

// Get all clients for the dropdown
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();

// Search and filter functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT d.*, c.name as client_name 
        FROM debts d 
        JOIN clients c ON d.client_id = c.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (c.name LIKE ? OR d.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter !== 'all') {
    $sql .= " AND d.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY d.date_added DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$debts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Debts - CanteenLedger</title>
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="css/debts-style.css">
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
                <li><a href="debts.php" class="active"><i class="fas fa-file-invoice-dollar"></i> <span>Manage Debts</span></a></li>
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
                <h3>Total Debts</h3>
                <div class="value"><?= $stats['total_debts'] ?></div>
                <div class="label">Total Records</div>
            </div>
            <div class="stats-card">
                <h3>Pending Debts</h3>
                <div class="value"><?= $stats['pending_debts'] ?></div>
                <div class="label">Need Collection</div>
            </div>
            <div class="stats-card">
                <h3>Total Outstanding</h3>
                <div class="value">₱<?= number_format($stats['total_pending_amount'], 2) ?></div>
                <div class="label">To be Collected</div>
            </div>
        </div>

        <!-- Add Debt Form -->
        <div class="add-debt-form">
            <form method="POST">
                <div class="form-group">
                    <label for="client_id">Select Client</label>
                    <select id="client_id" name="client_id" required>
                        <option value="">Select a client...</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" 
                           placeholder="Enter debt amount" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Enter debt description" required></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" name="add_debt" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Debt
                    </button>
                </div>
            </form>
        </div>

        <div class="debts-container">
            <!-- Search and Filter Section -->
            <div class="search-filter-section">
                <div class="filter-group">
                    <label for="status-filter">Status:</label>
                    <select id="status-filter" class="filter-select" onchange="applyFilters()">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>Paid</option>
                    </select>
                </div>
            </div>

            <?php if (empty($debts)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <p>No debt records found</p>
                    <span>Add your first debt record using the form above</span>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="debts-table">
        <thead>
            <tr>
                                <th>Client</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Dates</th>
                                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
                            <?php foreach ($debts as $debt): ?>
                            <tr>
                                <td>
                                    <span class="client-name"><?= htmlspecialchars($debt['client_name']) ?></span>
                                    <div class="debt-id">#<?= htmlspecialchars($debt['id']) ?></div>
                                </td>
                                <td>
                                    <div class="debt-description"><?= htmlspecialchars($debt['description']) ?></div>
                                </td>
                                <td>
                                    <span class="debt-amount">₱<?= number_format($debt['amount'], 2) ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($debt['status']) ?>">
                                        <?= ucfirst($debt['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <span>
                                            <i class="fas fa-calendar-plus"></i>
                                            <?= date('M j, Y', strtotime($debt['date_added'])) ?>
                                        </span>
                                        <?php if ($debt['date_paid']): ?>
                                        <span>
                                            <i class="fas fa-calendar-check"></i>
                                            <?= date('M j, Y', strtotime($debt['date_paid'])) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($debt['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="debt_id" value="<?= $debt['id'] ?>">
                                                <button type="submit" name="mark_paid" class="btn btn-success">
                                                    <i class="fas fa-check"></i> Mark Paid
                                                </button>
                    </form>
                    <?php endif; ?>
                                        <a href="?delete=<?= $debt['id'] ?>" 
                                           onclick="return confirm('Are you sure you want to delete this debt record?')" 
                                           class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
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
        window.location.href = `debts.php?status=${status}`;
    }
    </script>
</body>
</html>
