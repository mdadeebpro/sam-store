<?php
session_start();
require_once '../src/db_connect.php';

header('Content-Type: application/json');

// --- Get Input Data ---
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['cart']) || !isset($input['customer_name'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input.']);
    exit;
}

$cart = $input['cart'];
$customer_name = trim($input['customer_name']);
$customer_phone = isset($input['customer_phone']) ? trim($input['customer_phone']) : null;

if (empty($customer_name) || empty($cart)) {
    echo json_encode(['success' => false, 'error' => 'Customer name and cart cannot be empty.']);
    exit;
}

// --- Database Transaction ---
$conn->begin_transaction();

try {
    // 1. Create a unique customer ID and save it
    $customer_id = uniqid('cust_', true);
    $stmt = $conn->prepare("INSERT INTO customers (id) VALUES (?)");
    $stmt->bind_param("s", $customer_id);
    $stmt->execute();
    $stmt->close();

    // 2. Create the order
    $stmt = $conn->prepare("INSERT INTO orders (customer_id, customer_name, customer_phone) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $customer_id, $customer_name, $customer_phone);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // 3. Get product details and insert order items
    $product_ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
    $types = str_repeat('i', count($product_ids));

    $sql = "SELECT id, price, sale_price, quantity FROM products WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$product_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    $products_from_db = [];
    while($row = $result->fetch_assoc()) {
        $products_from_db[$row['id']] = $row;
    }
    $stmt->close();

    // 4. Insert order items and update product quantities
    $stmt_insert_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt_update_qty = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");

    foreach ($cart as $product_id => $item) {
        $product_db = $products_from_db[$product_id];
        $price = $product_db['sale_price'] ?? $product_db['price'];

        // Check stock
        if ($product_db['quantity'] < $item['quantity']) {
            throw new Exception("Not enough stock for product ID $product_id.");
        }

        // Insert order item
        $stmt_insert_item->bind_param("iiid", $order_id, $product_id, $item['quantity'], $price);
        $stmt_insert_item->execute();

        // Update product quantity
        $stmt_update_qty->bind_param("ii", $item['quantity'], $product_id);
        $stmt_update_qty->execute();
    }
    $stmt_insert_item->close();
    $stmt_update_qty->close();

    // If all good, commit the transaction
    $conn->commit();

    // --- Generate Invoice Image ---
    // For simplicity, we'll just confirm success. Invoice generation is complex.
    // In a real project, this would be a separate, robust service.
    // We will pass the order ID to a new script to generate the invoice.

    echo json_encode(['success' => true, 'order_id' => $order_id]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
}

$conn->close();
?>
