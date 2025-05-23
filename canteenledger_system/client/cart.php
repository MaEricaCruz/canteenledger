<?php
session_start();
require '../db.php';

if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$client_id = $_SESSION['client_id'];

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Fetch client info
$stmt = $pdo->prepare("SELECT name FROM clients WHERE id = ?");
$stmt->execute([$client_id]);
$client = $stmt->fetch();

// Fetch cart items with product details
$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $item_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($item_ids) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id IN ($placeholders)");
    $stmt->execute($item_ids);
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($menu_items as $item) {
        $quantity = $_SESSION['cart'][$item['id']];
        $subtotal = $item['price'] * $quantity;
        $total += $subtotal;
        
        $cart_items[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $quantity,
            'subtotal' => $subtotal,
            'image' => $item['image']
        ];
    }
}

// Handle POST requests for updating cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $item_id = $_POST['item_id'] ?? '';
    
    switch ($action) {
        case 'update':
            $quantity = max(1, intval($_POST['quantity']));
            $_SESSION['cart'][$item_id] = $quantity;
            break;
            
        case 'remove':
            unset($_SESSION['cart'][$item_id]);
            break;
            
        case 'clear':
            $_SESSION['cart'] = [];
            break;
    }
    
    // Redirect to refresh the page
    header('Location: cart.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Canteen Ledger</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/cart.css">
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
            <a href="orders.php">
                <i class="fas fa-shopping-cart"></i> My Orders
            </a>
            <a href="cart.php" class="active">
                <i class="fas fa-shopping-basket"></i> Cart
            </a>
            <a href="logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="cart-container">
            <h1>Your Cart</h1>
            
            <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
                <a href="menu.php" class="back-to-menu">
                    Back to Menu
                </a>
            </div>
            <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="item-image">
                        <img src="../uploads/menu/<?= htmlspecialchars($item['image'] ?? 'default.jpg') ?>" 
                             alt="<?= htmlspecialchars($item['name']) ?>">
                    </div>
                    <div class="item-details">
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="item-price">₱<?= number_format($item['price'], 2) ?></p>
                    </div>
                    <div class="item-quantity">
                        <form method="POST" class="quantity-form">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <button type="button" class="quantity-btn minus" onclick="updateQuantity(this, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" name="quantity" value="<?= $item['quantity'] ?>" 
                                   min="1" class="quantity-input" onchange="this.form.submit()">
                            <button type="button" class="quantity-btn plus" onclick="updateQuantity(this, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </form>
                    </div>
                    <div class="item-subtotal">
                        ₱<?= number_format($item['subtotal'], 2) ?>
                    </div>
                    <form method="POST" class="remove-form">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="remove-btn">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <div class="summary-row">
                    <span>Total Items:</span>
                    <span><?= count($cart_items) ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total Amount:</span>
                    <span>₱<?= number_format($total, 2) ?></span>
                </div>
                <div class="cart-actions">
                    <form method="POST" class="clear-cart-form">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="clear-cart-btn">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                    </form>
                    <a href="checkout.php" class="checkout-btn">
                        <i class="fas fa-shopping-cart"></i> Proceed to Checkout
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateQuantity(button, change) {
            const input = button.parentNode.querySelector('.quantity-input');
            const currentValue = parseInt(input.value);
            const newValue = Math.max(1, currentValue + change);
            
            if (currentValue !== newValue) {
                input.value = newValue;
                input.form.submit();
            }
        }
    </script>
</body>
</html>
