<?php
require_once '../src/db_connect.php';

// Check if category ID is set
if(!isset($_GET['id']) || empty($_GET['id'])){
    header("location: index.php");
    exit;
}

$category_id = $_GET['id'];

// Fetch category name
$sql_category_name = "SELECT name FROM categories WHERE id = ?";
$category_name = "القسم"; // Default name
if($stmt_cat_name = $conn->prepare($sql_category_name)){
    $stmt_cat_name->bind_param("i", $category_id);
    if($stmt_cat_name->execute()){
        $result_cat_name = $stmt_cat_name->get_result();
        if($result_cat_name->num_rows == 1){
            $category = $result_cat_name->fetch_assoc();
            $category_name = $category['name'];
        }
    }
    $stmt_cat_name->close();
}


// Fetch all products for the category
$sql_products = "SELECT id, name, price, sale_price, image FROM products WHERE category_id = ?";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category_name); ?> - متجر سام</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header">
        <h1><?php echo htmlspecialchars($category_name); ?></h1>
        <p><a href="index.php" style="color:white; text-decoration:none;">العودة للصفحة الرئيسية</a></p>
    </header>

    <main class="container">
        <div class="products-grid">
            <?php
            if($stmt_products = $conn->prepare($sql_products)) {
                $stmt_products->bind_param("i", $category_id);
                $stmt_products->execute();
                $result_products = $stmt_products->get_result();

                if ($result_products->num_rows > 0) {
                    while($product = $result_products->fetch_assoc()):
            ?>
                        <div class="product-card">
                            <img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="price">
                                <?php if($product['sale_price']): ?>
                                    <span class="current-price"><?php echo htmlspecialchars($product['sale_price']); ?></span>
                                    <span class="original-price"><s><?php echo htmlspecialchars($product['price']); ?></s></span>
                                <?php else: ?>
                                    <span class="current-price"><?php echo htmlspecialchars($product['price']); ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="add-to-cart-btn" data-product-id="<?php echo $product['id']; ?>">أضف إلى السلة</button>
                        </div>
            <?php
                    endwhile;
                } else {
                    echo "<p>لا توجد منتجات في هذا القسم حالياً.</p>";
                }
                $stmt_products->close();
            }
            ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="cart-icon">
            🛒
            <span id="cart-count">0</span>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
