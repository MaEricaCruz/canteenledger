<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

require '../db.php';

// Handle Add
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $image = null;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = '../uploads/menu/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $image;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // File uploaded successfully
                $stmt = $pdo->prepare("INSERT INTO menu_items (name, price, image) VALUES (?, ?, ?)");
                $stmt->execute([$name, $price, $image]);
                $success_message = "Item added successfully!";
            } else {
                $error_message = "Failed to upload image. Please try again.";
            }
        } else {
            $error_message = "Invalid file type. Only JPG and PNG files are allowed.";
        }
    } else {
        // No image uploaded, insert without image
        $stmt = $pdo->prepare("INSERT INTO menu_items (name, price) VALUES (?, ?)");
        $stmt->execute([$name, $price]);
        $success_message = "Item added successfully!";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get image filename before deleting the record
    $stmt = $pdo->prepare("SELECT image FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    
    // Delete the record
    $pdo->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$id]);
    
    // Delete the image file if it exists
    if ($item && $item['image']) {
        $image_path = '../uploads/menu/' . $item['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    $success_message = "Item deleted successfully!";
}

// Handle Edit
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    
    $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, price = ? WHERE id = ?");
    $stmt->execute([$name, $price, $id]);
    $success_message = "Item updated successfully!";
}

// Fetch All Items
$menu = $pdo->query("SELECT * FROM menu_items ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - CanteenLedger</title>
    <link rel="stylesheet" href="css/dashboard-style.css">
    <link rel="stylesheet" href="css/menu-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            margin-top: 10px;
            display: none;
        }
        .file-input-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        .file-input-container input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .file-input-button {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .file-input-button:hover {
            background: #e9ecef;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <nav>
            <ul class="nav-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                <li><a href="menu.php" class="active"><i class="fas fa-utensils"></i> <span>Manage Menu</span></a></li>
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
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Add New Item Form -->
        <div class="add-item-form">
            <h3>Add New Item</h3>
            <form method="POST" class="form-row" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Food Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter food name" required>
                </div>
                <div class="form-group">
                    <label for="price">Price (₱)</label>
                    <input type="number" id="price" name="price" step="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label for="image">Image</label>
                    <div class="file-input-container">
                        <div class="file-input-button">
                            <i class="fas fa-image"></i>
                            <span>Choose Image</span>
                        </div>
                        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png" onchange="previewImage(this)">
                    </div>
                    <img id="imagePreview" class="image-preview" alt="Preview">
                </div>
                <button type="submit" name="add" class="add-item-btn">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </form>
        </div>

        <!-- Current Menu -->
        <div class="menu-container">
            <h3>Current Menu</h3>
            <?php if (empty($menu)): ?>
                <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <p>No menu items found. Add your first item above!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="menu-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Food Name</th>
                                <th class="price-column">Price</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($menu as $item): ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">
                                    <td>
                                        <?php if ($item['image']): ?>
                                            <img src="../uploads/menu/<?= htmlspecialchars($item['image']) ?>" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #f8f9fa; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="text" name="name" value="<?= htmlspecialchars($item['name']) ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($item['price']) ?>" required>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="submit" name="update" class="btn btn-secondary">
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                            <a href="?delete=<?= htmlspecialchars($item['id']) ?>" 
                                               onclick="return confirm('Are you sure you want to delete this item?')" 
                                               class="btn btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const file = input.files[0];
            const reader = new FileReader();

            reader.onloadend = function() {
                preview.src = reader.result;
                preview.style.display = 'block';
            }

            if (file) {
                reader.readAsDataURL(file);
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html>
