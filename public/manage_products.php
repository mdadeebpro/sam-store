<?php
require_once '../src/db_connect.php';
$page_title = "إدارة المنتجات";
require_once 'admin_header.php';

$error = "";

// --- Handle Delete Product ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];

    $sql_img = "SELECT image FROM products WHERE id = ?";
    if($stmt_img = $conn->prepare($sql_img)){
        $stmt_img->bind_param("i", $product_id);
        $stmt_img->execute();
        $result_img = $stmt_img->get_result();
        if($result_img->num_rows == 1){
            $image_filename = $result_img->fetch_assoc()['image'];
            if(file_exists("images/" . $image_filename)){
                unlink("images/" . $image_filename);
            }
        }
        $stmt_img->close();
    }

    $sql_delete = "DELETE FROM products WHERE id = ?";
    if ($stmt_delete = $conn->prepare($sql_delete)) {
        $stmt_delete->bind_param("i", $product_id);
        if (!$stmt_delete->execute()) {
            $error = "خطأ: لم يتم حذف المنتج.";
        }
        $stmt_delete->close();
        header("location: manage_products.php");
        exit;
    }
}

// --- Fetch Existing Products with Category Name ---
$sql_products = "SELECT p.id, p.name, p.price, p.quantity, p.image, c.name AS category_name
                 FROM products p
                 JOIN categories c ON p.category_id = c.id
                 ORDER BY p.created_at DESC";
$result_products = $conn->query($sql_products);
?>

<div class="content-box">
    <a href="add_product.php" class="btn btn-primary btn-add">إضافة منتج جديد</a>

    <?php if ($error) echo "<p class='error'>$error</p>"; ?>

    <h2>المنتجات الحالية</h2>
    <?php if ($result_products && $result_products->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                    <th>صورة</th>
                    <th>اسم المنتج</th>
                    <th>القسم</th>
                    <th>السعر</th>
                    <th>الكمية</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = $result_products->fetch_assoc()): ?>
                    <tr>
                        <td><img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="Product Image"></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['price']); ?></td>
                        <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn">تعديل</a>
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="inline-form">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" name="delete_product" class="btn btn-danger" onclick="return confirm('هل أنت متأكد؟');">حذف</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p>لا توجد منتجات مضافة حالياً.</p>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once 'admin_footer.php';
?>
