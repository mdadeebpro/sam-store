<?php
require_once '../src/db_connect.php';
$page_title = "إدارة الأقسام";
require_once 'admin_header.php';

$category_name = "";
$error = "";

// --- Handle Add Category ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    if (!empty($category_name)) {
        $sql = "INSERT INTO categories (name) VALUES (?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $category_name);
            if (!$stmt->execute()) {
                $error = "خطأ: لم يتم إضافة القسم.";
            }
            $stmt->close();
            header("location: manage_categories.php");
            exit;
        }
    } else {
        $error = "اسم القسم لا يمكن أن يكون فارغًا.";
    }
}

// --- Handle Delete Category ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];
    $sql = "DELETE FROM categories WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $category_id);
        if (!$stmt->execute()) {
            $error = "خطأ: لم يتم حذف القسم.";
        }
        $stmt->close();
        header("location: manage_categories.php");
        exit;
    }
}

// --- Fetch Existing Categories ---
$sql_categories = "SELECT id, name FROM categories ORDER BY name";
$result_categories = $conn->query($sql_categories);
?>

<?php if ($error) echo "<p class='error'>$error</p>"; ?>

<div class="content-box">
    <h2>إضافة قسم جديد</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <label for="category_name">اسم القسم</label>
            <input type="text" name="category_name" id="category_name" required>
        </div>
        <button type="submit" name="add_category" class="btn btn-primary">إضافة القسم</button>
    </form>
</div>

<div class="content-box">
    <h2>الأقسام الحالية</h2>
    <?php if ($result_categories && $result_categories->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>اسم القسم</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($category = $result_categories->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td>
                            <a href="edit_category.php?id=<?php echo $category['id']; ?>" class="btn">تعديل</a>
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="inline-form">
                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                <button type="submit" name="delete_category" class="btn btn-danger" onclick="return confirm('هل أنت متأكد؟ سيتم حذف جميع المنتجات في هذا القسم.');">حذف</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>لا توجد أقسام مضافة حالياً.</p>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once 'admin_footer.php';
?>
