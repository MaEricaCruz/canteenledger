<?php
require '../db.php';

// Get date filters from GET parameters
$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

$where = "1=1";
$params = [];

if (!empty($from)) {
    $where .= " AND dh.debt_date >= ?";
    $params[] = $from;
}
if (!empty($to)) {
    $where .= " AND dh.debt_date <= ?";
    $params[] = $to . " 23:59:59";
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=debt_history.csv');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// CSV column headers
fputcsv($output, ['Client Name', 'Debt Amount (₱)', 'Date']);

// Prepare and execute query
$sql = "
    SELECT c.name, dh.amount, dh.debt_date
    FROM debt_history dh
    JOIN clients c ON dh.client_id = c.id
    WHERE $where
    ORDER BY dh.debt_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Fetch and write data rows
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['name'],
        number_format($row['amount'], 2),
        date('Y-m-d', strtotime($row['debt_date']))
    ]);
}

fclose($output);
exit;
