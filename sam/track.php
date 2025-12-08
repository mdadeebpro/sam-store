<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تتبع طلبك - متجر سام</title>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.min.css">
    <style>
        .track-box {
            background: #fff;
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            text-align: center;
        }
        .status-icon { font-size: 50px; margin-bottom: 10px; display:block; }
        .st-new { color: #555; }
        .st-processing { color: #e67e22; }
        .st-shipping { color: #3498db; }
        .st-delivered { color: #27ae60; }
        .st-canceled { color: #c0392b; }
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="logo-container" style="text-decoration:none;">
            <span class="logo-text-sam">SAM</span>
            <span class="logo-text-store">STORE</span>
        </a>
        <h2 style="margin-top:10px;">📦 تتبع حالة الطلب</h2>
        <a href="index.php" class="btn-back">عودة للرئيسية</a>
    </header>

    <div class="container">

        <!-- نموذج البحث -->
        <div class="track-box">
            <form method="GET">
                <label>أدخل رقم الفاتورة:</label>
                <!-- تغيير الاسم input name من order_id إلى code -->
                <input type="number" name="code" class="form-input" placeholder="مثال: 202458912" required>
                <button type="submit" class="btn-add" style="margin-top:10px;">بحث</button>
            </form>
        </div>

        <!-- نتيجة البحث -->
        <?php
        if (isset($_GET['code'])) {
            $code = $_GET['code'];
            // البحث باستخدام العمود الجديد invoice_code
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE invoice_code = ?");
            $stmt->execute([$code]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                // تحديد النصوص والأيقونات حسب الحالة
                $st = $order['status'] ? $order['status'] : 'new';
                $info = [
                    'new' => ['icon'=>'🆕', 'text'=>'الطلب جديد (قيد المراجعة)', 'class'=>'st-new'],
                    'processing' => ['icon'=>'⚙️', 'text'=>'جاري تجهيز طلبك...', 'class'=>'st-processing'],
                    'shipping' => ['icon'=>'🚚', 'text'=>'الطلب خرج للتوصيل', 'class'=>'st-shipping'],
                    'delivered' => ['icon'=>'✅', 'text'=>'تم توصيل الطلب بنجاح', 'class'=>'st-delivered'],
                    'canceled' => ['icon'=>'❌', 'text'=>'تم إلغاء الطلب', 'class'=>'st-canceled'],
                ];
                $current = $info[$st];
                ?>

                <div class="track-box" style="border: 2px solid #eee;">
                    <span class="status-icon"><?= $current['icon'] ?></span>
                    <h3 class="<?= $current['class'] ?>"><?= htmlspecialchars($current['text']) ?></h3>
                    <hr style="margin:15px 0; border:0; border-top:1px dashed #ddd;">
                    <p><strong>العميل:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                    <p><strong>تاريخ الطلب:</strong> <?= date('Y-m-d', strtotime($order['created_at'])) ?></p>
                    <p><strong>الإجمالي:</strong> <?= htmlspecialchars($order['total_amount']) ?> ر.ي</p>
                </div>

                <?php
            } else {
                echo "<div class='track-box' style='color:red;'>عذراً، رقم الفاتورة غير صحيح.</div>";
            }
        }
        ?>

    </div>

</body>
</html>