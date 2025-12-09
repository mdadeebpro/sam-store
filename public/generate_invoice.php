<?php
require_once '../src/db_connect.php';
require_once '../src/lib/ArPHP/Arabic.php';

// --- Get Order ID ---
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id === 0) {
    die("Invalid Order ID.");
}

// --- Fetch Order Data ---
$stmt = $conn->prepare(
    "SELECT o.customer_name, o.customer_phone, o.created_at, oi.quantity, oi.price, p.name as product_name
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     JOIN products p ON oi.product_id = p.id
     WHERE o.id = ?"
);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

if (empty($items)) {
    die("Order not found.");
}

// Instantiate the Arabic text handler
$Arabic = new \ArPHP\I18N\Arabic();

// --- Image Generation using GD ---
$image_width = 500;
$image_height = 700;
$font_path = __DIR__ . '/fonts/Amiri-Regular.ttf';
$font_size = 12;

$image = imagecreatetruecolor($image_width, $image_height);

// Colors
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);
$grey = imagecolorallocate($image, 150, 150, 150);

imagefill($image, 0, 0, $white);
imagerectangle($image, 10, 10, $image_width - 10, $image_height - 10, $grey);


// --- Content ---
$y_position = 50;

// Function to process and shape Arabic text
function shape_text($text, $arabic_handler) {
    $p = $arabic_handler->arIdentify($text);
    if (count($p)) {
        $text = $arabic_handler->utf8Glyphs($text);
    }
    return $text;
}

// Header
$header_text = shape_text("فاتورة - متجر سام", $Arabic);
imagettftext($image, 18, 0, 180, $y_position, $black, $font_path, $header_text);
$y_position += 50;

// Order Details
$details_text_1 = shape_text("رقم الطلب: " . $order_id, $Arabic);
$details_text_2 = shape_text("العميل: " . $items[0]['customer_name'], $Arabic);
$details_text_3 = shape_text("التاريخ: " . $items[0]['created_at'], $Arabic);

imagettftext($image, $font_size, 0, 350, $y_position, $black, $font_path, $details_text_1);
$y_position += 25;
imagettftext($image, $font_size, 0, 350, $y_position, $black, $font_path, $details_text_2);
$y_position += 25;
imagettftext($image, $font_size, 0, 350, $y_position, $black, $font_path, $details_text_3);
$y_position += 40;

// Table Header
imagettftext($image, $font_size, 0, 400, $y_position, $black, $font_path, shape_text("المنتج", $Arabic));
imagettftext($image, $font_size, 0, 150, $y_position, $black, $font_path, shape_text("الكمية", $Arabic));
imagettftext($image, $font_size, 0, 50, $y_position, $black, $font_path, shape_text("السعر", $Arabic));
$y_position += 10;
imageline($image, 40, $y_position, 460, $y_position, $grey);
$y_position += 25;

// Table Rows
$total = 0;
foreach ($items as $item) {
    imagettftext($image, $font_size, 0, 250, $y_position, $black, $font_path, shape_text($item['product_name'], $Arabic));
    imagettftext($image, $font_size, 0, 150, $y_position, $black, $font_path, $item['quantity']);
    imagettftext($image, $font_size, 0, 50, $y_position, $black, $font_path, $item['price']);
    $y_position += 25;
    $total += $item['quantity'] * $item['price'];
}

imageline($image, 40, $y_position, 460, $y_position, $grey);
$y_position += 30;

// Total
$total_text = shape_text("الإجمالي: " . number_format($total, 2), $Arabic);
imagettftext($image, 14, 0, 350, $y_position, $black, $font_path, $total_text);

// --- Output Image ---
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="invoice_'.$order_id.'.png"');
imagepng($image);
imagedestroy($image);
?>
