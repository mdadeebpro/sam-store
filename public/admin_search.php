<?php
require_once '../src/db_connect.php';
$page_title = "نتائج البحث";
require_once 'admin_header.php';

$query = "";
$products = [];
$categories = [];

if (isset($_GET['query']) && !empty(trim($_GET['query']))) {
    $query = trim($_GET['query']);
    $search_term = "%{$query}%";

    // Search in products
    $sql_products = "SELECT id, name, image FROM products WHERE name LIKE ?";
    if ($stmt_products = $conn->prepare($sql_products)) {
        $stmt_products->bind_param("s", $search_term);
        $stmt_products->execute();
        $result_products = $stmt_products->get_result();
        $products = $result_products->fetch_all(MYSQLI_ASSOC);
        $stmt_products->close();
    }

    // Search in categories
    $sql_categories = "SELECT id, name FROM categories WHERE name LIKE ?";
    if ($stmt_categories = $conn->prepare($sql_categories)) {
        $stmt_categories->bind_param("s", $search_term);
        $stmt_categories->execute();
        $result_categories = $stmt_categories->get_result();
        $categories = $result_categories->fetch_all(MYSQLI_ASSOC);
        $stmt_categories->close();
    }
}
?>

<div class="content-box">
    <h2>نتائج البحث عن "<?php echo htmlspecialchars($query); ?>"</h2>
</div>

<!-- Products Results -->
<div class="content-box">
    <h3>المنتجات (<?php echo count($products); ?>)</h3>
    <?php if (count($products) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>صورة</th>
                    <th>اسم المنتج</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image"></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn">تعديل</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>لم يتم العثور على منتجات مطابقة.</p>
    <?php endif; ?>
</div>

<!-- Categories Results -->
<div class="content-box">
    <h3>الأقسام (<?php echo count($categories); ?>)</h3>
    <?php if (count($categories) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>اسم القسم</th>
                    <th>إجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td><a href="edit_category.php?id=<?php echo $category['id']; ?>" class="btn">تعديل</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>لم يتم العثور على أقسام مطابقة.</p>
    <?php endif; ?>
</div>


<?php
$conn->close();
require_once 'admin_footer.php';
?>
