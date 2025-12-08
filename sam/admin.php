<?php
include 'db.php';

// === 1. إعدادات السيرفر ===
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// === 2. التحقق من الدخول ===
if (!isset($_SESSION['admin_id'])) {
    if (isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $pass = trim($_POST['password']);
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        if ($admin && password_verify($pass, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            
            // --- التعديل الجديد: حفظ الصلاحية في الجلسة ---
            $_SESSION['is_super'] = $admin['is_super']; // 1 or 0
            // ----------------------------------------------

            if ($admin['must_change_password']) { header("Location: admin.php?action=change_pass"); exit; }
            header("Location: admin.php"); exit;
        } else { $error = "بيانات خاطئة"; }
    }
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>دخول</title><link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap" rel="stylesheet"><style>body{font-family:"Almarai",sans-serif;background:#f4f4f4;height:100vh;display:flex;justify-content:center;align-items:center}form{background:#fff;padding:30px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,0.1);width:90%;max-width:400px;text-align:center}input{width:100%;padding:12px;margin:10px 0;border:1px solid #ddd;border-radius:5px;box-sizing:border-box}button{width:100%;padding:12px;background:#007bff;color:#fff;border:none;border-radius:5px;font-weight:bold}</style></head><body><form method="POST"><h2>دخول المشرف</h2><input type="email" name="email" placeholder="البريد" required><input type="password" name="password" placeholder="كلمة المرور" required><button name="login">دخول</button><p style="color:red">'.($error??'').'</p></form></body></html>';
    exit;
}

// === تحديد المشرف الرئيسي (بناءً على قاعدة البيانات الآن) ===
// هل هو مشرف عام (القيمة 1)؟
$isSuperAdmin = (isset($_SESSION['is_super']) && $_SESSION['is_super'] == 1);

// === 3. معالجة الطلبات (POST) ===
if (isset($_POST['delete_all_orders'])) {
    $pdo->exec("DELETE FROM orders"); $pdo->exec("ALTER TABLE orders AUTO_INCREMENT = 1"); $pdo->exec("ALTER TABLE order_items AUTO_INCREMENT = 1");
    header("Location: admin.php?tab=tab-orders&t=".time()); exit;
}
if (isset($_POST['update_order_status'])) {
    $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$_POST['status'], $_POST['order_id']]);
    header("Location: admin.php?tab=tab-orders&t=".time()); exit;
}
if (isset($_POST['add_category'])) { $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$_POST['cat_name']]); header("Location: admin.php?tab=tab-cats&t=".time()); exit; }
if (isset($_POST['delete_category'])) { $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$_POST['cat_id']]); header("Location: admin.php?tab=tab-cats&t=".time()); exit; }
if (isset($_POST['update_category'])) { $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?")->execute([$_POST['cat_name'], $_POST['cat_id']]); header("Location: admin.php?tab=tab-cats&t=".time()); exit; }

if (isset($_POST['add_product'])) {
    $name=$_POST['name']; $desc=$_POST['description']; $supplier=!empty($_POST['supplier'])?$_POST['supplier']:'غير محدد';
    $price=$_POST['price']; $qty=$_POST['qty']!==''?$_POST['qty']:null; $disc=!empty($_POST['discount'])?$_POST['discount']:null; 
    $cat=$_POST['category']; $note=$_POST['note']; $sizes=!empty($_POST['sizes'])?$_POST['sizes']:null;
    if ($disc!==null && $disc>=$price) { echo "<script>alert('خطأ: سعر الخصم أكبر من الرسمي');window.history.back();</script>"; exit; }
    $imgName=time().'_'.$_FILES['image']['name']; move_uploaded_file($_FILES['image']['tmp_name'],'uploads/'.$imgName);
    $pdo->prepare("INSERT INTO products (name,description,supplier,sizes,category_id,price,discount_price,quantity,image,admin_note) VALUES (?,?,?,?,?,?,?,?,?,?)")->execute([$name,$desc,$supplier,$sizes,$cat,$price,$disc,$qty,$imgName,$note]);
    header("Location: admin.php?tab=tab-products&t=".time()); exit;
}
if (isset($_POST['delete_product'])) { $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$_POST['prod_id']]); header("Location: admin.php?tab=tab-products&t=".time()); exit; }
if (isset($_POST['update_product'])) {
    $id=$_POST['prod_id']; $name=$_POST['name']; $desc=$_POST['description']; $supplier=!empty($_POST['supplier'])?$_POST['supplier']:'غير محدد';
    $price=$_POST['price']; $qty=$_POST['qty']!==''?$_POST['qty']:null; $disc=!empty($_POST['discount'])?$_POST['discount']:null; 
    $cat=$_POST['category']; $note=$_POST['note']; $sizes=!empty($_POST['sizes'])?$_POST['sizes']:null;
    if ($disc!==null && $disc>=$price) { echo "<script>alert('خطأ: سعر الخصم أكبر من الرسمي');window.history.back();</script>"; exit; }
    if (!empty($_FILES['image']['name'])) {
        $imgName=time().'_'.$_FILES['image']['name']; move_uploaded_file($_FILES['image']['tmp_name'],'uploads/'.$imgName);
        $pdo->prepare("UPDATE products SET name=?,description=?,supplier=?,sizes=?,category_id=?,price=?,discount_price=?,quantity=?,admin_note=?,image=? WHERE id=?")->execute([$name,$desc,$supplier,$sizes,$cat,$price,$disc,$qty,$note,$imgName,$id]);
    } else {
        $pdo->prepare("UPDATE products SET name=?,description=?,supplier=?,sizes=?,category_id=?,price=?,discount_price=?,quantity=?,admin_note=? WHERE id=?")->execute([$name,$desc,$supplier,$sizes,$cat,$price,$disc,$qty,$note,$id]);
    }
    header("Location: admin.php?tab=tab-products&t=".time()); exit;
}

if (isset($_POST['add_admin'])) { $pdo->prepare("INSERT INTO admins (email,password,must_change_password) VALUES (?,?,1)")->execute([$_POST['new_admin_email'],password_hash($_POST['new_admin_pass'], PASSWORD_BCRYPT)]); header("Location: admin.php?tab=tab-admins&t=".time()); exit; }

// --- حذف مشرف (يعتمد على الصلاحية في قاعدة البيانات) ---
if (isset($_POST['delete_admin']) && $isSuperAdmin) { 
    $delId = $_POST['admin_id_del']; 
    // لا يمكن للمشرف حذف نفسه
    if ($delId != $_SESSION['admin_id']) { 
        $pdo->prepare("DELETE FROM admins WHERE id = ?")->execute([$delId]); 
    } 
    header("Location: admin.php?tab=tab-admins&t=".time()); exit; 
}

// === 4. جلب البيانات ===
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$adminList = $pdo->query("SELECT * FROM admins")->fetchAll(PDO::FETCH_ASSOC);

$searchQuery = $_GET['search'] ?? '';
$searchProds = []; $searchCats = []; $searchOrds = [];

if ($searchQuery) {
    $term = "%$searchQuery%";
    $pS = $pdo->prepare("SELECT p.*, c.name as cat_name, (SELECT COALESCE(SUM(qty), 0) FROM cart WHERE product_id = p.id) as total_reserved FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.name LIKE ? OR p.admin_note LIKE ? OR p.supplier LIKE ?");
    $pS->execute([$term, $term, $term]); $searchProds = $pS->fetchAll(PDO::FETCH_ASSOC);
    $cS = $pdo->prepare("SELECT * FROM categories WHERE name LIKE ?"); $cS->execute([$term]); $searchCats = $cS->fetchAll(PDO::FETCH_ASSOC);
    $oS = $pdo->prepare("SELECT o.*, GROUP_CONCAT(CONCAT(oi.product_name, IF(oi.size IS NOT NULL AND oi.size != '', CONCAT(' <span style=\'color:#d00000; font-weight:bold;\'>[', oi.size, ']</span>'), ''), ' <span style=\'color:#666; font-size:0.85em;\'>(x', oi.qty, ')</span>') SEPARATOR '<br>') as items_summary FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id WHERE o.customer_name LIKE ? OR o.invoice_code LIKE ? GROUP BY o.id ORDER BY o.created_at DESC");
    $oS->execute([$term, $term]); $searchOrds = $oS->fetchAll(PDO::FETCH_ASSOC);
} else {
    $products = $pdo->query("SELECT p.*, c.name as cat_name, (SELECT COALESCE(SUM(qty), 0) FROM cart WHERE product_id = p.id) as total_reserved FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $ordQ = "SELECT o.*, GROUP_CONCAT(CONCAT(oi.product_name, IF(oi.size IS NOT NULL AND oi.size != '', CONCAT(' <span style=\'color:#d00000; font-weight:bold;\'>[', oi.size, ']</span>'), ''), ' <span style=\'color:#666; font-size:0.85em;\'>(x', oi.qty, ')</span>') SEPARATOR '<br>') as items_summary FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id GROUP BY o.id ORDER BY o.created_at DESC LIMIT 50";
    $orders = $pdo->query($ordQ)->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Almarai', sans-serif; background-color: #f0f2f5; margin: 0; padding: 10px; color: #333; direction: rtl; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1, h2, h3 { margin: 0; color: #1a2a3a; }
        .tabs-nav { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 15px; scrollbar-width: none; }
        .tab-btn { padding: 10px 20px; background: #fff; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; white-space: nowrap; font-family: inherit; font-weight: bold; color: #555; transition: 0.2s; }
        .tab-btn.active { background: #1a2a3a; color: #fff; border-color: #1a2a3a; }
        .tab-content { display: none; } .tab-content.active { display: block; }
        .panel { background: #fff; border-radius: 10px; padding: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 20px; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .btn { padding: 8px 15px; border-radius: 6px; cursor: pointer; border: none; color: #fff; font-family: inherit; font-size: 0.9rem; margin: 2px; }
        .btn-green { background: #28a745; } .btn-orange { background: #fd7e14; } .btn-red { background: #dc3545; } .btn-blue { background: #007bff; }
        input, select, textarea { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-family: inherit; font-size: 1rem;}
        .search-bar { width: 100%; padding: 12px; border: 2px solid #1a2a3a; border-radius: 8px; margin-bottom: 20px; font-size: 1rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: center; vertical-align: middle; word-wrap: break-word; }
        th { background: #1a2a3a; color: #fff; }
        .thumb { width: 50px; height: 50px; border-radius: 5px; object-fit: cover; }
        @media screen and (max-width: 768px) {
            .container > div:first-child { flex-direction: column-reverse; gap: 10px; align-items: center !important; }
            .container > div:first-child h2 { font-size: 1.5rem; margin-bottom: 5px; }
            .btn-red { width: 100%; text-align: center; padding: 10px; }
            table, thead, tbody, th, td, tr { display: block; }
            thead tr { position: absolute; top: -9999px; left: -9999px; }
            tr { background: #fff; margin-bottom: 15px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid #e0e0e0; padding: 15px; }
            td { border: none; border-bottom: 1px dashed #eee; padding: 10px 0; display: flex; justify-content: space-between; align-items: center; text-align: left; font-size: 0.95rem; }
            td:last-child { border-bottom: none; }
            td:before { content: attr(data-label); font-weight: bold; color: #777; margin-left: 10px; white-space: nowrap; }
            td[data-label="المنتجات"], td[data-label="العميل"] { display: block; text-align: right; background-color: #f9f9f9; padding: 10px; border-radius: 8px; margin-top: 5px; line-height: 1.8; }
            td[data-label="المنتجات"]:before, td[data-label="العميل"]:before { display: block; margin-bottom: 5px; border-bottom: 1px solid #ddd; width: 100%; }
            td[data-label="صورة"] { justify-content: center; border-bottom: none; } td[data-label="صورة"]:before { display: none; }
            td[data-label="المنتج"] { display: block; text-align: center; font-size: 1.1rem; } td[data-label="المنتج"]:before { display: none; }
            td[data-label="تحكم"] { display: flex; gap: 10px; margin-top: 10px; border-top: 1px solid #eee; padding-top: 15px; } td[data-label="تحكم"]:before { display: none; }
            td[data-label="تحكم"] button, td[data-label="تحكم"] form { flex: 1; width: 100%; } .btn { width: 100%; padding: 10px; font-size: 1rem; }
            td[data-label="الحالة"] { display: block; text-align: right; } td[data-label="الحالة"] select { width: 100%; margin-top: 5px; padding: 10px; }
            .res th{color: red; display: block;}
        }
        .modal { display: none; position: fixed; z-index: 3000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; }
        .modal-content { background: #fff; padding: 20px; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
        .close { float: left; font-size: 24px; cursor: pointer; }
        label { display: block; margin-top: 10px; font-weight: bold; color: #555; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h2 style="color:#1a2a3a;">لوحة التحكم ⚙️</h2>
        <a href="logout.php" class="btn btn-red">خروج</a>
    </div>

    <form method="GET" action="admin.php">
        <input type="text" name="search" class="search-bar" placeholder="🔍 ابحث عن منتج، قسم، عميل..." value="<?= htmlspecialchars($searchQuery) ?>">
        <?php if($searchQuery): ?>
            <a href="admin.php" class="btn btn-blue" style="margin-bottom:20px; display:inline-block;">إلغاء البحث</a>
        <?php endif; ?>
    </form>

    <?php if ($searchQuery): ?>
        <!-- نتائج البحث -->
        <?php if (count($searchProds) > 0): ?>
            <div class="panel"><h3>نتائج المنتجات</h3><table><thead><tr><th>صورة</th><th>المنتج</th><th>السعر</th><th>القسم</th><th>تحكم</th></tr></thead><tbody><?php foreach($searchProds as $prod): ?><tr><td data-label="صورة"><img src="uploads/<?= $prod['image'] ?>" class="thumb"></td><td data-label="المنتج"><b><?= $prod['name'] ?></b></td><td data-label="السعر"><?= $prod['price'] ?></td><td data-label="القسم"><?= $prod['cat_name'] ?></td><td data-label="تحكم"><button class="btn btn-orange" onclick='openEditProd(<?= json_encode($prod) ?>)'>تعديل</button><form method="POST" style="display:inline;" onsubmit="return confirm("حذف؟");"><input type="hidden" name="prod_id" value="<?= $prod['id'] ?>"><button name="delete_product" class="btn btn-red">حذف</button></form></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>
        <?php if (count($searchCats) > 0): ?>
            <div class="panel"><h3>نتائج الأقسام</h3><table><thead><tr><th>ID</th><th>الاسم</th><th>تحكم</th></tr></thead><tbody><?php foreach($searchCats as $cat): ?><tr><td data-label="ID"><?= $cat['id'] ?></td><td data-label="الاسم"><?= $cat['name'] ?></td><td data-label="تحكم"><button class="btn btn-orange" onclick='openEditCat(<?= json_encode($cat) ?>)'>تعديل</button><form method="POST" style="display:inline;" onsubmit="return confirm("حذف؟");"><input type="hidden" name="cat_id" value="<?= $cat['id'] ?>"><button name="delete_category" class="btn btn-red">حذف</button></form></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>
        <?php if (count($searchOrds) > 0): ?>
            <div class="panel"><h3>نتائج الطلبات</h3><table><thead><tr><th>رقم</th><th>العميل</th><th>المنتجات</th><th>الإجمالي</th><th>الحالة</th><th>التاريخ</th></tr></thead><tbody><?php foreach($searchOrds as $ord): ?><tr><td data-label="رقم">#<?= $ord['id'] ?></td><td data-label="العميل"><?= $ord['customer_name'] ?></td><td data-label="المنتجات"><?= $ord['items_summary'] ?></td><td data-label="الإجمالي"><?= $ord['total_amount'] ?></td><td data-label="الحالة"><?= $ord['status'] ?></td><td data-label="التاريخ"><?= $ord['created_at'] ?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php endif; ?>
        <?php if (count($searchProds)==0 && count($searchCats)==0 && count($searchOrds)==0): ?><div class="panel" style="text-align:center;"><h3>لا توجد نتائج</h3></div><?php endif; ?>

    <?php else: ?>
        <div class="tabs-nav">
            <button class="tab-btn active" id="btn-orders" onclick="openTab(event, 'tab-orders')">📦 الطلبات</button>
            <button class="tab-btn" id="btn-products" onclick="openTab(event, 'tab-products')">👕 المنتجات</button>
            <button class="tab-btn" id="btn-cats" onclick="openTab(event, 'tab-cats')">📂 الأقسام</button>
            <button class="tab-btn" id="btn-admins" onclick="openTab(event, 'tab-admins')">👤 المشرفين</button>
            <button class="tab-btn" id="btn-procurement" onclick="openTab(event, 'tab-procurement')">🛒 المشتريات</button>
        </div>

        <div id="tab-orders" class="tab-content active">
            <div class="panel">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;"><h3>سجل الطلبات</h3><form method="POST" onsubmit="return confirm('تفريغ؟');"><button name="delete_all_orders" class="btn btn-red">🗑️ تفريغ</button></form></div>
                <table><thead><tr><th>رقم</th><th>العميل</th><th>المنتجات</th><th>الإجمالي</th><th>الحالة</th><th>التاريخ</th></tr></thead><tbody><?php $stArr=['new'=>['جديد','#333'],'processing'=>['تجهيز','#e67e22'],'shipping'=>['توصيل','#3498db'],'delivered'=>['تم','#27ae60'],'canceled'=>['ملغي','#c0392b']]; foreach($orders as $ord): $k=$ord['status']?:'new'; ?><tr><td data-label="رقم">#<?= $ord['id'] ?><br><small><?= $ord['invoice_code'] ?></small></td><td data-label="العميل"><b><?= $ord['customer_name'] ?></b><br><small><?= $ord['customer_phone'] ?></small><br><small><?= $ord['address'] ?></small><?php if($ord['notes']) echo "<br><small style='color:red'>📝 {$ord['notes']}</small>"; ?></td><td data-label="المنتجات"><?= $ord['items_summary'] ?></td><td data-label="الإجمالي" style="color:green;font-weight:bold;"><?= $ord['total_amount'] ?></td><td data-label="الحالة"><form method="POST"><input type="hidden" name="order_id" value="<?= $ord['id'] ?>"><select name="status" onchange="this.form.submit()" style="background:<?= $stArr[$k][1] ?>;color:#fff;border:none;font-weight:bold;padding:5px;"><?php foreach($stArr as $x=>$y): ?><option value="<?= $x ?>" <?= $k==$x?'selected':'' ?> style="background:#fff;color:#000;"><?= $y[0] ?></option><?php endforeach; ?></select><input type="hidden" name="update_order_status" value="1"></form></td><td data-label="التاريخ"><?= date('Y-m-d', strtotime($ord['created_at'])) ?></td></tr><?php endforeach; ?></tbody></table>
            </div>
        </div>

        <div id="tab-products" class="tab-content">
            <div class="panel"><div style="display:flex; justify-content:space-between; margin-bottom:10px;"><h3>المنتجات</h3><button class="btn btn-green" onclick="openModal('addProdModal')">+ منتج</button></div><table><thead><tr><th>صورة</th><th>المنتج</th><th>السعر</th><th>المخزون</th><th>القسم</th><th>تحكم</th></tr></thead><tbody><?php foreach($products as $prod): $sTxt="∞"; $bg=""; if($prod['quantity']!==null){ $n=$prod['quantity']-$prod['total_reserved']; if($n<=0){$sTxt="🚫 نفذت";$bg="background:#fff0f0";} elseif($n<5){$sTxt="⚠️ $n";$bg="background:#fffbe6";} else{$sTxt="✅ $n";} } ?><tr style="<?= $bg ?>"><td data-label="صورة"><img src="uploads/<?= $prod['image'] ?>" class="thumb"></td><td data-label="المنتج"><b><?= $prod['name'] ?></b><?php if($prod['admin_note']) echo "<br><small style='color:red'>📝 {$prod['admin_note']}</small>"; ?></td><td data-label="السعر"><?php if($prod['discount_price']): ?><s><?= $prod['price'] ?></s> <b style="color:#d00000"><?= $prod['discount_price'] ?></b><?php else: echo $prod['price']; endif; ?></td><td data-label="المخزون"><?= $sTxt ?></td><td data-label="القسم"><?= $prod['cat_name'] ?></td><td data-label="تحكم"><button class="btn btn-orange" onclick='openEditProd(<?= json_encode($prod) ?>)'>تعديل</button><form method="POST" style="display:inline;" onsubmit="return confirm('حذف؟');"><input type="hidden" name="prod_id" value="<?= $prod['id'] ?>"><button name="delete_product" class="btn btn-red">حذف</button></form></td></tr><?php endforeach; ?></tbody></table></div>
        </div>

        <div id="tab-cats" class="tab-content">
            <div class="panel"><button class="btn btn-green" onclick="openModal('addCatModal')">+ قسم</button><table><thead><tr><th>ID</th><th>الاسم</th><th>تحكم</th></tr></thead><tbody><?php foreach($categories as $cat): ?><tr><td data-label="ID"><?= $cat['id'] ?></td><td data-label="الاسم"><?= $cat['name'] ?></td><td data-label="تحكم"><button class="btn btn-orange" onclick='openEditCat(<?= json_encode($cat) ?>)'>تعديل</button><form method="POST" style="display:inline;" onsubmit="return confirm('حذف؟');"><input type="hidden" name="cat_id" value="<?= $cat['id'] ?>"><button name="delete_category" class="btn btn-red">حذف</button></form></td></tr><?php endforeach; ?></tbody></table></div>
        </div>

        <div id="tab-admins" class="tab-content">
            <div class="panel"><h3>إضافة مشرف</h3><form method="POST" style="display:flex; gap:10px; margin-bottom:20px;"><input type="email" name="new_admin_email" placeholder="البريد" required><input type="text" name="new_admin_pass" placeholder="كلمة المرور" required><button name="add_admin" class="btn btn-blue">إضافة</button></form><h3>المشرفين</h3><table><thead><tr><th>ID</th><th>البريد</th><th>الصلاحية</th><th>تحكم</th></tr></thead><tbody><?php foreach($adminList as $adm): ?><tr><td data-label="ID"><?= $adm['id'] ?></td><td data-label="البريد"><?= $adm['email'] ?></td><td data-label="الصلاحية"><?= $adm['is_super'] == 1 ? '<span style="color:green;font-weight:bold;">عام</span>' : 'عادي' ?></td><td data-label="تحكم"><?php if($isSuperAdmin && $adm['id'] != $_SESSION['admin_id']): ?><form method="POST" onsubmit="return confirm('حذف؟');"><input type="hidden" name="admin_id_del" value="<?= $adm['id'] ?>"><button name="delete_admin" class="btn btn-red">حذف</button></form><?php else: echo '--'; endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
        </div>

        <!-- 5. تبويب قائمة المشتريات (للتجار) -->
        <div id="tab-procurement" class="tab-content">
            <div class="panel">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3>🛒 قائمة التجهيز (حسب المورد)</h3>
                    <button onclick="window.print()" class="btn btn-blue">طباعة القائمة</button>
                </div>
                <?php
                $procSql = "SELECT p.supplier, oi.product_name, oi.size, SUM(oi.qty) as total_qty_needed FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN products p ON oi.product_name = p.name WHERE o.status IN ('new', 'processing') GROUP BY p.supplier, oi.product_name, oi.size ORDER BY p.supplier ASC";
                $procStmt = $pdo->query($procSql);
                $procList = $procStmt->fetchAll(PDO::FETCH_GROUP);
                ?>
                <div class="table-responsive">
                    <?php if(count($procList) > 0): ?>
                        <?php foreach($procList as $supplier => $items): ?>
                            <div style="margin-bottom: 20px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                                <div style="background:#1a2a3a; color:#fff; padding:10px; font-weight:bold;">
                                    🏪 المورد: <?= $supplier ? $supplier : 'غير محدد' ?>
                                </div>
                                <table style="width:100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden;">                                    
                                    <thead >
                                        <tr style="background:#f9f9f9; border-bottom:2px solid #eee;">
                                            <th style="color:#333; background:#eee;">المنتج</th>
                                            <th style="color:#333; background:#eee;">المقاس</th>
                                            <th style="color:#333; background:#eee;">العدد المطلوب</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($items as $item): ?>
                                        <tr style="border-bottom:1px solid #eee;">
                                            <td data-label="المنتج" style="padding:10px; font-weight:bold; color:#1a2a3a;"><?= $item['product_name'] ?></td>
                                            <td data-label="المقاس" style=" padding:10px; color:#d00000; font-weight:bold;"><?= $item['size'] ? $item['size'] : '-' ?></td>
                                            <td data-label="العدد المطلوب" style="padding:10px; font-size:1.1rem; font-weight:900;" ><?= $item['total_qty_needed'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center; padding:20px;">لا توجد طلبات جديدة للتجهيز.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div id="addCatModal" class="modal"><div class="modal-content"><span class="close" onclick="closeModal('addCatModal')">&times;</span><h3>إضافة قسم</h3><form method="POST"><label>اسم القسم</label><input type="text" name="cat_name" required><button name="add_category" class="btn btn-green" style="margin-top:10px; width:100%;">حفظ</button></form></div></div>
<div id="editCatModal" class="modal"><div class="modal-content"><span class="close" onclick="closeModal('editCatModal')">&times;</span><h3>تعديل قسم</h3><form method="POST"><input type="hidden" name="cat_id" id="edit_cat_id"><label>اسم القسم</label><input type="text" name="cat_name" id="edit_cat_name" required><button name="update_category" class="btn btn-orange" style="margin-top:10px; width:100%;">تحديث</button></form></div></div>

<div id="addProdModal" class="modal">
    <div class="modal-content"><span class="close" onclick="closeModal('addProdModal')">&times;</span><h3>إضافة منتج</h3>
    <form method="POST" enctype="multipart/form-data" onsubmit="return validatePrice(this)">
        <label>القسم</label><select name="category" required><?php foreach($categories as $c) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?></select>
        <label>اسم المنتج</label><input type="text" name="name" required placeholder="اسم المنتج">
        <label>الوصف</label><textarea name="description" placeholder="وصف المنتج للزبون" style="height:60px;"></textarea>
        <label>المقاسات (اختياري)</label><input type="text" name="sizes" placeholder="مثال: S,M,L">
        <label>صورة المنتج</label><input type="file" name="image" required>
        <label>اسم المورد (للتجار)</label><input type="text" name="supplier" placeholder="مثال: محلات الأمل بالجملة">
        <label>السعر الرسمي</label><input type="number" name="price" required placeholder="السعر">
        <label>سعر بعد الخصم (اختياري)</label><input type="number" name="discount" placeholder="اتركه فارغاً إذا لا يوجد خصم">
        <label>الكمية المتوفرة</label><input type="number" name="qty" placeholder="اتركه فارغاً إذا الكمية مفتوحة">
        <label>ملاحظة للمشرف (سرية)</label><textarea name="note" placeholder="لن تظهر للزبون"></textarea>
        <button name="add_product" class="btn btn-green" style="margin-top:10px; width:100%;">نشر المنتج</button>
    </form></div>
</div>

<div id="editProdModal" class="modal">
    <div class="modal-content"><span class="close" onclick="closeModal('editProdModal')">&times;</span><h3>تعديل منتج</h3>
    <form method="POST" enctype="multipart/form-data" onsubmit="return validatePrice(this)">
        <input type="hidden" name="prod_id" id="edit_prod_id">
        <label>القسم</label><select name="category" id="edit_prod_cat" required><?php foreach($categories as $c) echo "<option value='{$c['id']}'>{$c['name']}</option>"; ?></select>
        <label>اسم المنتج</label><input type="text" name="name" id="edit_prod_name" required>
        <label>الوصف</label><textarea name="description" id="edit_prod_desc" style="height:60px;"></textarea>
        <label>المقاسات</label><input type="text" name="sizes" id="edit_prod_sizes">
        <label>تغيير الصورة</label><input type="file" name="image">
        <label>اسم المورد (للتجار)</label><input type="text" name="supplier" id="edit_prod_supplier">
        <label>السعر الرسمي</label><input type="number" name="price" id="edit_prod_price" required>
        <label>سعر بعد الخصم</label><input type="number" name="discount" id="edit_prod_disc">
        <label>الكمية</label><input type="number" name="qty" id="edit_prod_qty">
        <label>ملاحظة للمشرف</label><textarea name="note" id="edit_prod_note"></textarea>
        <button name="update_product" class="btn btn-orange" style="margin-top:10px; width:100%;">حفظ التعديلات</button>
    </form></div>
</div>

<script>
    function openTab(evt, name) {
        var i, x = document.getElementsByClassName("tab-content"), b = document.getElementsByClassName("tab-btn");
        for (i = 0; i < x.length; i++) x[i].style.display = "none";
        for (i = 0; i < b.length; i++) b[i].className = b[i].className.replace(" active", "");
        document.getElementById(name).style.display = "block";
        if(evt) evt.currentTarget.className += " active";
        const url = new URL(window.location); url.searchParams.set('tab', name); window.history.pushState({}, '', url);
    }
    
    // الحل النهائي لمشكلة العودة للتبويب الصحيح
    window.onload = function() {
        const t = new URLSearchParams(window.location.search).get('tab');
        if(t) {
            // نستخدم معرفات ID للأزرار لضمان دقة الاختيار
            let btnId = '';
            if(t === 'tab-orders') btnId = 'btn-orders';
            else if(t === 'tab-products') btnId = 'btn-products';
            else if(t === 'tab-cats') btnId = 'btn-cats';
            else if(t === 'tab-admins') btnId = 'btn-admins';
            else if(t === 'tab-procurement') btnId = 'btn-procurement';
            
            if(btnId) { document.getElementById(btnId).click(); }
        }
    };

    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    function openEditCat(c) { document.getElementById('edit_cat_id').value=c.id; document.getElementById('edit_cat_name').value=c.name; openModal('editCatModal'); }
    
    function openEditProd(p) {
        document.getElementById('edit_prod_id').value=p.id; 
        document.getElementById('edit_prod_name').value=p.name; 
        document.getElementById('edit_prod_desc').value=p.description; 
        document.getElementById('edit_prod_sizes').value=p.sizes; 
        document.getElementById('edit_prod_price').value=p.price; 
        document.getElementById('edit_prod_disc').value=p.discount_price; 
        document.getElementById('edit_prod_qty').value=p.quantity; 
        document.getElementById('edit_prod_note').value=p.admin_note; 
        document.getElementById('edit_prod_cat').value=p.category_id;
        document.getElementById('edit_prod_supplier').value=p.supplier; 
        openModal('editProdModal');
    }
    
    function validatePrice(form) {
        let price = parseFloat(form.querySelector('input[name="price"]').value);
        let discIn = form.querySelector('input[name="discount"]').value;
        if(discIn.trim() !== "") { if(parseFloat(discIn) >= price) { alert("تنبيه: سعر الخصم يجب أن يكون أقل من السعر الرسمي!"); return false; } }
        return true;
    }
    window.onclick = function(event) { if (event.target.classList.contains('modal')) event.target.style.display = 'none'; }
</script>
</body>
</html>