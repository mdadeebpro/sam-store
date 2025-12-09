<?php
require_once '../src/db_connect.php';
$page_title = "تعديل القسم";
require_once 'admin_header.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: manage_categories.php");
    exit;
}

$category_id = $_GET['id'];
$category_name = "";
$error = "";

// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_category_name = trim($_POST['category_name']);
    if (!empty($new_category_name)) {
        $sql = "UPDATE categories SET name = ? WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $new_category_name, $category_id);
            if ($stmt->execute()) {
                header("location: manage_categories.php");
                exit;
            } else {
                $error = "خطأ في تحديث القسم.";
            }
            $stmt->close();
        }
    } else {
        $error = "اسم القسم لا يمكن أن يكون فارغًا.";
    }
}


// --- Fetch Current Category Name ---
$sql = "SELECT name FROM categories WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $category_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $category = $result->fetch_assoc();
            $category_name = $category['name'];
        } else {
            header("location: manage_categories.php");
            exit;
        }
    }
    $stmt->close();
}
?>

<div class="content-box">
    <h2>تعديل: <?php echo htmlspecialchars($category_name); ?></h2>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <form action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="post">
        <div class="form-group">
            <label for="category_name">الاسم الجديد للقسم</label>
            <input type="text" name="category_name" id="category_name" value="<?php echo htmlspecialchars($category_name); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
    </form>
</div>

<?php
$conn->close();
require_once 'admin_footer.php';
?>
