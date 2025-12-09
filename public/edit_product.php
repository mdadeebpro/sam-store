<?php
require_once '../src/db_connect.php';
$page_title = "تعديل المنتج";
require_once 'admin_header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: manage_products.php");
    exit;
}

$product_id = $_GET['id'];

// Fetch categories for the dropdown
$sql_categories = "SELECT id, name FROM categories";
$result_categories = $conn->query($sql_categories);

// Fetch product data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    header("location: manage_products.php");
    exit;
}
$product = $result->fetch_assoc();
$stmt->close();

$name = $product['name'];
$description = $product['description'];
$price = $product['price'];
$sale_price = $product['sale_price'];
$quantity = $product['quantity'];
$category_id = $product['category_id'];
$notes = $product['notes'];
$current_image = $product['image'];
$errors = [];

// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $sale_price = !empty($_POST['sale_price']) ? trim($_POST['sale_price']) : NULL;
    $quantity = trim($_POST['quantity']);
    $category_id = trim($_POST['category_id']);
    $notes = trim($_POST['notes']);

    if (empty($name)) $errors[] = "اسم المنتج مطلوب.";
    if (empty($price) || !is_numeric($price)) $errors[] = "السعر مطلوب ويجب أن يكون رقمًا.";

    // --- Image Upload Handling (Optional) ---
    $new_image_filename = $current_image;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "images/";
        $new_image_filename = uniqid() . basename($_FILES["image"]["name"]);

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $new_image_filename)) {
            if(file_exists($target_dir . $current_image)) {
                unlink($target_dir . $current_image);
            }
        } else {
            $errors[] = "خطأ في رفع الصورة الجديدة.";
        }
    }

    // --- Update Database ---
    if (empty($errors)) {
        $sql = "UPDATE products SET category_id=?, name=?, description=?, price=?, sale_price=?, quantity=?, image=?, notes=? WHERE id=?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issddissi", $category_id, $name, $description, $price, $sale_price, $quantity, $new_image_filename, $notes, $product_id);
            if ($stmt->execute()) {
                header("location: manage_products.php");
                exit;
            } else {
                $errors[] = "خطأ في قاعدة البيانات: لم يتم تحديث المنتج.";
            }
            $stmt->close();
        }
    }
}
?>

<div class="content-box">
    <?php if (!empty($errors)): ?>
        <div class="error-container">
            <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>اسم المنتج</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        <div class="form-group">
            <label>الوصف</label>
            <textarea name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
        </div>
        <div class="form-group">
            <label>السعر</label>
            <input type="number" name="price" step="0.01" value="<?php echo htmlspecialchars($price); ?>" required>
        </div>
         <div class="form-group">
            <label>سعر الخصم</label>
            <input type="number" name="sale_price" step="0.01" value="<?php echo htmlspecialchars($sale_price); ?>">
        </div>
        <div class="form-group">
            <label>الكمية</label>
            <input type="number" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>" required>
        </div>
        <div class="form-group">
            <label>القسم</label>
            <select name="category_id" required>
                <?php
                mysqli_data_seek($result_categories, 0);
                while($cat = $result_categories->fetch_assoc()) {
                    $selected = ($cat['id'] == $category_id) ? 'selected' : '';
                    echo "<option value='{$cat['id']}' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                } ?>
            </select>
        </div>
         <div class="form-group">
            <label>صورة المنتج الحالية</label>
            <img src="images/<?php echo htmlspecialchars($current_image); ?>" width="100">
            <p><i>اترك الحقل التالي فارغاً للإبقاء على الصورة الحالية.</i></p>
            <input type="file" name="image">
        </div>
        <div class="form-group">
            <label>ملاحظات</label>
            <textarea name="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
    </form>
</div>

<?php
$conn->close();
require_once 'admin_footer.php';
?>
