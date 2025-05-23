<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

require '../db.php';

// Create debts table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS debts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    description TEXT,
    date_added DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_paid DATETIME NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
)");

// Add Client
if (isset($_POST['add_client'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];

    $stmt = $pdo->prepare("INSERT INTO clients (name, email, contact) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $contact]);
    $success_message = "Client added successfully!";
}

// Delete Client
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $success_message = "Client deleted successfully!";
}

// Edit Client (fetch data)
$edit_client = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $edit_client = $stmt->fetch();
}

// Update Client
if (isset($_POST['update_client'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];

    $stmt = $pdo->prepare("UPDATE clients SET name = ?, email = ?, contact = ? WHERE id = ?");
    $stmt->execute([$name, $email, $contact, $id]);
    $success_message = "Client updated successfully!";
}

// Get client statistics
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_clients,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today,
        (SELECT COUNT(DISTINCT client_id) FROM orders WHERE DATE(order_date) = CURDATE()) as active_today
    FROM clients
")->fetch();

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$params = [];

if ($search) {
    $search_condition = "WHERE c.name LIKE ? OR c.email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Fetch all clients with search
$sql = "SELECT c.*, 
        COALESCE((SELECT SUM(total) FROM orders WHERE client_id = c.id), 0) as total_orders,
        COALESCE((
            SELECT SUM(amount) 
            FROM debts 
            WHERE client_id = c.id 
            AND status = 'pending'
        ), 0) as total_debt
        FROM clients c 
        $search_condition 
        ORDER BY c.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clients - CanteenLedger</title>
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="css/clients-style.css">
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
                <li><a href="clients.php" class="active"><i class="fas fa-users"></i> <span>Manage Clients</span></a></li>
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
                <h3>Total Clients</h3>
                <div class="value"><?= $stats['total_clients'] ?></div>
                <div class="label">Registered Clients</div>
            </div>
            <div class="stats-card">
                <h3>New Today</h3>
                <div class="value"><?= $stats['new_today'] ?></div>
                <div class="label">Added Today</div>
            </div>
            <div class="stats-card">
                <h3>Active Today</h3>
                <div class="value"><?= $stats['active_today'] ?></div>
                <div class="label">Made Orders Today</div>
            </div>
        </div>

        <!-- Add/Edit Client Form -->
        <div class="add-client-form">
        <form method="POST">
                <?php if ($edit_client): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($edit_client['id']) ?>">
    <?php endif; ?>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" 
                           value="<?= $edit_client ? htmlspecialchars($edit_client['name']) : '' ?>" 
                           placeholder="Enter client's full name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?= $edit_client ? htmlspecialchars($edit_client['email']) : '' ?>" 
                           placeholder="Enter email address" required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="text" id="contact" name="contact" 
                           value="<?= $edit_client ? htmlspecialchars($edit_client['contact']) : '' ?>" 
                           placeholder="Enter contact number">
                </div>

                <div class="form-actions">
                    <?php if ($edit_client): ?>
                        <button type="submit" name="update_client" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Client
                        </button>
                        <a href="clients.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php else: ?>
                        <button type="submit" name="add_client" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Client
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="clients-container">
            <!-- Search Box -->
            <div class="search-filter-section">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search clients..." 
                           value="<?= htmlspecialchars($search) ?>"
                           onkeyup="if(event.key === 'Enter') applySearch()">
                </div>
            </div>

            <?php if (empty($clients)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>No clients found</p>
                    <span>Add your first client using the form above</span>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="clients-table">
        <thead>
            <tr>
                                <th>Client Name</th>
                                <th>Contact Info</th>
                                <th>Total Orders</th>
                                <th>Outstanding Balance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): ?>
            <tr>
                                <td>
                                    <span class="client-name"><?= htmlspecialchars($client['name']) ?></span>
                                    <div class="client-id">#<?= htmlspecialchars($client['id']) ?></div>
                                </td>
                                <td>
                                    <div class="client-info">
                                        <div><i class="fas fa-envelope"></i> <?= htmlspecialchars($client['email']) ?></div>
                                        <?php if ($client['contact']): ?>
                                            <div><i class="fas fa-phone"></i> <?= htmlspecialchars($client['contact']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="client-info">₱<?= number_format($client['total_orders'], 2) ?></span>
                                </td>
                                <td>
                                    <span class="client-balance <?= $client['total_debt'] > 0 ? 'balance-negative' : 'balance-positive' ?>">
                                        ₱<?= number_format($client['total_debt'], 2) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?= $client['id'] ?>" class="btn btn-secondary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="?delete=<?= $client['id'] ?>" 
                                           onclick="return confirm('Are you sure you want to delete this client?')" 
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
    function applySearch() {
        const search = document.getElementById('searchInput').value;
        window.location.href = `clients.php?search=${encodeURIComponent(search)}`;
    }
    </script>
</body>
</html>
