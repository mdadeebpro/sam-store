<?php
require_once '../src/db_connect.php';

// Fetch categories that have products
$sql_categories = "SELECT c.id, c.name FROM categories c JOIN products p ON c.id = p.category_id GROUP BY c.id, c.name ORDER BY c.id";
$result_categories = $conn->query($sql_categories);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متجر سام للملابس والأدوات المنزلية</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header">
        <h1>متجر سام للملابس والأدوات المنزلية</h1>
    </header>

    <div class="scrolling-bar">
        <p>مرحباً بكم في متجرنا! نتمنى لكم تسوقاً ممتعاً.</p>
    </div>

    <main class="container">
        <?php if ($result_categories && $result_categories->num_rows > 0): ?>
            <?php while($category = $result_categories->fetch_assoc()): ?>
                <section class="category-section">
                    <div class="category-header">
                        <h2><?php echo htmlspecialchars($category['name']); ?></h2>
                        <a href="category.php?id=<?php echo $category['id']; ?>" class="view-all">عرض الكل</a>
                    </div>

                    <div class="products-carousel">
                        <?php
                        // Fetch products for the current category
                        $sql_products = "SELECT id, name, price, sale_price, image FROM products WHERE category_id = ? ORDER BY created_at DESC LIMIT 10";
                        if($stmt_products = $conn->prepare($sql_products)) {
                            $stmt_products->bind_param("i", $category['id']);
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
                </section>
            <?php endwhile; ?>
        <?php else: ?>
            <p>لا توجد أقسام أو منتجات لعرضها حالياً. يرجى المحاولة مرة أخرى لاحقاً.</p>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <div class="cart-icon">
            🛒
            <span id="cart-count">0</span>
        </div>
    </footer>

    <script src="js/main.js"></script>

    <!-- Cart Modal -->
    <div id="cart-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>سلة المشتريات</h2>
            <div id="cart-items-container">
                <!-- Cart items will be loaded here dynamically -->
            </div>
            <div id="checkout-form">
                <h3>بيانات المستلم</h3>
                <input type="text" id="customer-name" placeholder="اسم المستلم الرباعي" required>
                <input type="tel" id="customer-phone" placeholder="رقم الهاتف (اختياري)">
                <button id="checkout-btn">إتمام الشراء</button>
            </div>
        </div>
    </div>

    <!-- Product Preview Modal -->
    <div id="preview-modal" class="modal">
        <div class="modal-content">
            <span class="close-button preview-close">&times;</span>
            <div id="preview-content">
                <!-- Product details will be loaded here -->
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
