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

// Fetch menu items
$stmt = $pdo->prepare("SELECT * FROM menu_items");
$stmt->execute();
$menu_items = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Canteen Ledger</title>
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/menu.css">
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
            <a href="menu.php" class="active">
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
        <h1>Menu</h1>

        <!-- Search Bar -->
        <div class="search-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search menu items...">
            </div>
        </div>

        <!-- Menu Grid -->
        <div class="menu-grid">
            <?php foreach($menu_items as $item): ?>
            <div class="menu-item">
                <img src="../uploads/menu/<?= htmlspecialchars($item['image'] ?? 'default.jpg') ?>" 
                     alt="<?= htmlspecialchars($item['name']) ?>" 
                     class="menu-item-image">
                <div class="menu-item-content">
                    <div class="menu-item-header">
                        <h3 class="menu-item-title"><?= htmlspecialchars($item['name']) ?></h3>
                        <span class="menu-item-price">₱<?= number_format($item['price'], 2) ?></span>
                    </div>
                    <p class="menu-item-description"><?= htmlspecialchars($item['description'] ?? '') ?></p>
                    <div class="order-controls">
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn minus" onclick="decrementQuantity(<?= $item['id'] ?>)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" class="quantity-input" id="quantity-<?= $item['id'] ?>" value="1" min="1">
                            <button type="button" class="quantity-btn plus" onclick="incrementQuantity(<?= $item['id'] ?>)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button class="add-to-cart" onclick="addToCart(<?= $item['id'] ?>)">
                            <i class="fas fa-cart-plus"></i>
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cart Section -->
    <div class="cart-section">
        <div class="cart-header">
            <h2 class="cart-title">Your Cart</h2>
            <button id="closeCart">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="cart-items">
            <!-- Cart items will be dynamically added here -->
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total:</span>
                <span id="cartTotal">₱0.00</span>
            </div>
            <button class="checkout-btn">
                Proceed to Checkout
            </button>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                const title = item.querySelector('.menu-item-title').textContent.toLowerCase();
                const description = item.querySelector('.menu-item-description').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Quantity controls
        function decrementQuantity(itemId) {
            const input = document.getElementById(`quantity-${itemId}`);
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }

        function incrementQuantity(itemId) {
            const input = document.getElementById(`quantity-${itemId}`);
            input.value = parseInt(input.value) + 1;
        }

        // Add to cart functionality
        function addToCart(itemId) {
            const quantity = parseInt(document.getElementById(`quantity-${itemId}`).value);
            // Here you would typically make an AJAX call to add the item to the cart
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: itemId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item added to cart successfully!');
                } else {
                    alert('Failed to add item to cart. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        // Close cart
        document.getElementById('closeCart').addEventListener('click', function() {
            document.querySelector('.cart-section').classList.remove('active');
        });
    </script>
</body>
</html>
