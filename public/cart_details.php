<?php
require_once '../src/db_connect.php';

header('Content-Type: application/json');

// Get the product IDs from the POST request
$product_ids_json = file_get_contents('php://input');
$product_ids = json_decode($product_ids_json, true);

if (empty($product_ids) || !is_array($product_ids)) {
    echo json_encode(['error' => 'No product IDs provided']);
    exit;
}

// Prepare placeholders for the IN clause
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));
$types = str_repeat('i', count($product_ids));

$sql = "SELECT id, name, price, sale_price, image FROM products WHERE id IN ($placeholders)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Dynamically bind parameters
    $stmt->bind_param($types, ...$product_ids);

    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode($products);

    $stmt->close();
} else {
    echo json_encode(['error' => 'Failed to prepare statement']);
}

$conn->close();
?>
