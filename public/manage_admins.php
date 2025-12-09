<?php
require_once '../src/db_connect.php';
$page_title = "إدارة المشرفين";
require_once 'admin_header.php';

$is_super_admin = isset($_SESSION["is_super_admin"]) && $_SESSION["is_super_admin"] == 1;

$email = $password = "";
$email_err = $password_err = $general_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST" && $is_super_admin){
    if(empty(trim($_POST["email"]))){
        $email_err = "الرجاء إدخال بريد إلكتروني.";
    } else {
        $sql = "SELECT id FROM admins WHERE email = ?";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("s", $param_email);
            $param_email = trim($_POST["email"]);
            if($stmt->execute()){
                $stmt->store_result();
                if($stmt->num_rows == 1){
                    $email_err = "هذا البريد الإلكتروني مستخدم بالفعل.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                $general_err = "عفوًا! حدث خطأ ما.";
            }
            $stmt->close();
        }
    }

    if(empty(trim($_POST["password"]))){
        $password_err = "الرجاء إدخال كلمة مرور.";
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "يجب أن تتكون كلمة المرور من 6 أحرف على الأقل.";
    } else{
        $password = trim($_POST["password"]);
    }

    if(empty($email_err) && empty($password_err) && empty($general_err)){
        $sql = "INSERT INTO admins (email, password, password_reset) VALUES (?, ?, 1)";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("ss", $param_email, $param_password);
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            if($stmt->execute()){
                header("location: manage_admins.php");
            } else{
                $general_err = "حدث خطأ ما. الرجاء المحاولة مرة أخرى لاحقًا.";
            }
            $stmt->close();
        }
    }
}

$sql_admins = "SELECT id, email, is_super_admin FROM admins";
$result_admins = $conn->query($sql_admins);

?>

<?php if($is_super_admin): ?>
    <div class="content-box">
        <h2>إضافة مشرف جديد</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <?php if($general_err) echo "<p class='error'>$general_err</p>"; ?>
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" value="<?php echo $email; ?>">
                <span class="error"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <label>كلمة المرور المؤقتة</label>
                <input type="password" name="password" value="<?php echo $password; ?>">
                <span class="error"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" value="إضافة" class="btn btn-primary">
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="content-box">
        <p>ليس لديك الصلاحية لإضافة مشرفين جدد.</p>
    </div>
<?php endif; ?>

<div class="content-box">
    <h2>المشرفون الحاليون</h2>
    <?php if($result_admins && $result_admins->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                    <th>البريد الإلكتروني</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php while($admin = $result_admins->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td><?php echo $admin['is_super_admin'] ? 'مشرف خارق' : 'مشرف'; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p>لا يوجد مشرفون لعرضهم.</p>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once 'admin_footer.php';
?>
