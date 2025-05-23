<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CanteenLedger</title>
    <link rel="stylesheet" href="css/dashboard-style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <nav>
            <ul class="nav-menu">
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="menu.php"><i class="fas fa-utensils"></i> <span>Manage Menu</span></a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> <span>View Orders</span></a></li>
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
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="welcome-container">
          <div class="welcome-section">
                <img src="../picture/logooa.png" alt="Canteen Ledger Logo">
                 <!-- <h1 class="welcome-message">Welcome to Canteen Ledger System</h1>
                <p class="welcome-submessage">eat.now.pay.on.payday</p>-->
                
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Today's Orders</h3>
                <div class="value">0</div>
                <div class="label">New Orders</div>
            </div>

            <div class="dashboard-card">
                <h3>Active Clients</h3>
                <div class="value">0</div>
                <div class="label">Registered Clients</div>
            </div>

            <div class="dashboard-card">
                <h3>Total Menu Items</h3>
                <div class="value">0</div>
                <div class="label">Available Items</div>
            </div>

            <div class="dashboard-card">
                <h3>Outstanding Debts</h3>
                <div class="value">₱0</div>
                <div class="label">Total Amount</div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide success message after 5 seconds
        setTimeout(() => {
            const successMessage = document.querySelector('.success-message');
            if (successMessage) {
                successMessage.style.opacity = '0';
                successMessage.style.transition = 'opacity 0.5s ease';
                setTimeout(() => successMessage.style.display = 'none', 500);
            }
        }, 5000);
    </script>
</body>
</html>
