<?php
require_once '../src/db_connect.php';
$page_title = "إضافة منتج جديد";
require_once 'admin_header.php';

// Fetch categories for the dropdown
$sql_categories = "SELECT id, name FROM categories";
$result_categories = $conn->query($sql_categories);

$name = $description = $price = $sale_price = $quantity = $category_id = $notes = "";
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
    if ($sale_price !== NULL && !is_numeric($sale_price)) $errors[] = "سعر الخصم يجب أن يكون رقمًا.";
    if (empty($quantity) || !is_numeric($quantity)) $errors[] = "الكمية مطلوبة ويجب أن تكون رقمًا.";
    if (empty($category_id)) $errors[] = "القسم مطلوب.";

    // --- Image Upload Handling ---
    $image_filename = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "images/";
        $image_filename = uniqid() . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check === false) $errors[] = "الملف المرفوع ليس صورة.";
        if ($_FILES["image"]["size"] > 5000000) $errors[] = "عذرًا، حجم الملف كبير جدًا.";
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) $errors[] = "عذرًا، يُسمح فقط بملفات JPG, JPEG, PNG & GIF.";

        if (empty($errors)) {
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $errors[] = "عذرًا، حدث خطأ أثناء رفع الملف.";
            }
        }
    } else {
        $errors[] = "صورة المنتج مطلوبة.";
    }

    // --- Insert into Database ---
    if (empty($errors)) {
        $sql = "INSERT INTO products (category_id, name, description, price, sale_price, quantity, image, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issddiss", $category_id, $name, $description, $price, $sale_price, $quantity, $image_filename, $notes);
            if ($stmt->execute()) {
                header("location: manage_products.php");
                exit;
            } else {
                $errors[] = "خطأ في قاعدة البيانات: لم يتم حفظ المنتج.";
            }
            $stmt->close();
        }
    }
}
?>

<div class="content-box">
    <?php if (!empty($errors)): ?>
        <div class="error-container">
            <strong>الرجاء إصلاح الأخطاء التالية:</strong>
            <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">اسم المنتج</label>
            <input type="text" name="name" id="name" required>
        </div>
        <div class="form-group">
            <label for="description">الوصف</label>
            <textarea name="description" id="description" rows="4"></textarea>
        </div>
        <div class="form-group">
            <label for="price">السعر</label>
            <input type="number" name="price" id="price" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="sale_price">سعر الخصم (اختياري)</label>
            <input type="number" name="sale_price" id="sale_price" step="0.01">
        </div>
        <div class="form-group">
            <label for="quantity">الكمية</label>
            <input type="number" name="quantity" id="quantity" required>
        </div>
        <div class="form-group">
            <label for="category_id">القسم</label>
            <select name="category_id" id="category_id" required>
                <option value="">اختر قسماً</option>
                <?php while($cat = $result_categories->fetch_assoc()) {
                    echo "<option value='{$cat['id']}'>" . htmlspecialchars($cat['name']) . "</option>";
                } ?>
            </select>
        </div>
        <div class="form-group">
            <label for="image">صورة المنتج</label>
            <input type="file" name="image" id="image" required>
        </div>
         <div class="form-group">
            <label for="notes">ملاحظات (غير ظاهرة للزبون)</label>
            <textarea name="notes" id="notes" rows="3"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">إضافة المنتج</button>
    </form>
</div>

<?php
$conn->close();
require_once 'admin_footer.php';
?>
