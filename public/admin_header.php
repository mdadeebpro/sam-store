<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: admin.php");
    exit;
}

// Function to check if the current link is active
function is_active($page_name) {
    return basename($_SERVER['PHP_SELF']) == $page_name ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - لوحة التحكم' : 'لوحة التحكم'; ?></title>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <h2>متجر سام</h2>
            <div class="sidebar-search">
                <form action="admin_search.php" method="get">
                    <input type="search" name="query" placeholder="ابحث عن منتج، قسم..." required>
                    <button type="submit">بحث</button>
                </form>
            </div>
            <nav>
                <ul>
                    <li><a href="dashboard.php" class="<?php echo is_active('dashboard.php'); ?>">لوحة التحكم</a></li>
                    <li><a href="manage_products.php" class="<?php echo is_active('manage_products.php'); ?>">إدارة المنتجات</a></li>
                    <li><a href="manage_categories.php" class="<?php echo is_active('manage_categories.php'); ?>">إدارة الأقسام</a></li>
                    <li><a href="manage_admins.php" class="<?php echo is_active('manage_admins.php'); ?>">إدارة المشرفين</a></li>
                </ul>
            </nav>
            <div class="logout">
                <a href="logout.php" class="btn btn-danger">تسجيل الخروج</a>
            </div>
        </aside>

        <main class="main-content">
            <header>
                <h1><?php echo isset($page_title) ? $page_title : 'صفحة غير معنونة'; ?></h1>
            </header>
