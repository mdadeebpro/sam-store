<?php
require_once '../src/db_connect.php';

header('Content-Type: application/json');

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$sql = "SELECT id, name, description, price, sale_price, image, quantity FROM products WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $product_id);

    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo json_encode($product);
    } else {
        echo json_encode(['error' => 'Product not found']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Failed to prepare statement']);
}

$conn->close();
?>
