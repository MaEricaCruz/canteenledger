<?php
require 'canteenledger_system/db.php';

try {
    // Read the SQL file
    $sql = file_get_contents('create_menu_items.sql');
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "Menu items table created successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 