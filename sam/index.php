<?php
include 'db.php';
$favMap = [];
if(isset($user_session)) {
    $fStmt = $pdo->prepare("SELECT product_id FROM favorites WHERE session_id = ?");
    $fStmt->execute([$user_session]);
    while ($fRow = $fStmt->fetch(PDO::FETCH_ASSOC)) {
        $favMap[$fRow['product_id']] = true;
    }
}
// =========================================================
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متجر سام للملابس والأدوات المنزلية</title>
    <link rel="icon" type="image/x-icon" href="s.png">
    <link rel="stylesheet" href="style.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

</head>
<body>

    <header>
        <!-- رابط الشعار يعود للصفحة الرئيسية -->
        <a href="index.php" class="logo-container">
            <span class="logo-text-store">STORE</span>
            <span class="logo-text-sam">SAM</span>
        </a>
        <!-- <img src="sam.png" alt="شعار" style="max-height:50px;"> -->
        <!-- <h1>متجر سام</h1> -->
          <form action="search.php" method="GET" class="search-container">
            <input type="text" name="q" class="search-input" placeholder="ابحث عن منتج..." required>
            <button type="submit" class="search-btn"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
  <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
</svg></button>
        </form>
    </header>

    <!-- <div class="marquee-container">
        <div class="marquee">أهلاً بكم في متجر سام.. تسوق ممتع وعروض مميزة!</div>
    </div> -->

    <div class="container">

        <!-- ================= قسم أحدث المنتجات ================= -->
        <?php
        ob_start();

        $sqlLatest = "SELECT p.*,
                      (SELECT COALESCE(SUM(qty), 0) FROM cart WHERE product_id = p.id) as total_reserved
                      FROM products p
                      WHERE (quantity > 0 OR quantity IS NULL)
                      ORDER BY created_at DESC LIMIT 10";
        $stmt = $pdo->query($sqlLatest);
        $hasLatest = false;

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
            // حساب الكمية
            if ($row['quantity'] !== null) {
                $realQty = $row['quantity'] - $row['total_reserved'];
                if ($realQty <= 0) continue;
            } else {
                $realQty = null;
            }
            $hasLatest = true;
            $price = $row['discount_price'] ? $row['discount_price'] : $row['price'];
        ?>
            <div class="product-card">
                <!-- زر المفضلة (أحدث المنتجات) -->
                <button class="fav-btn <?= isset($favMap[$row['id']]) ? 'active' : '' ?>"
        onclick="toggleFav(this, <?= $row['id'] ?>)">
    <i class="<?= isset($favMap[$row['id']]) ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ?>"></i>
</button>

                <img src="uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                <h4><?= htmlspecialchars($row['name']) ?></h4>
                <p class="product-desc"><?= htmlspecialchars($row['description'] ?? '') ?></p>

                <div class="price-box">
                    <?= $row['discount_price'] ? "<span class='old-price'>" . htmlspecialchars($row['price']) . "</span>" : "" ?>
                    <span class="new-price"><?= htmlspecialchars($price) ?> ر.ي</span>
                </div>

                <?php if ($realQty !== null): ?>
                    <div class="qty-label">
                        <!-- لاحظ الـ ID هنا يبدأ بـ qty_idx -->
                        متبقي: <span id="qty_idx_<?= $row['id'] ?>"><?= htmlspecialchars($realQty) ?></span>
                    </div>
                <?php else: ?>
                     <div class="spacer-20"></div>
                <?php endif; ?>

                <!-- زر الإضافة (يستخدم qty_idx ليتطابق مع الـ ID في الأعلى) -->
                <button class="btn-add" onclick="checkSizeAndAdd(<?= $row['id'] ?>, <?= $realQty !== null ? 'true' : 'false' ?>, 'qty_idx_<?= $row['id'] ?>', '<?= htmlspecialchars($row['sizes'] ?? '') ?>')">أضف للسلة</button>
            </div>
        <?php endwhile;

        $latestHTML = ob_get_clean();

        if ($hasLatest):
        ?>
            <div class="section-header">
                <h3>أحدث المنتجات</h3>
                <button onclick="window.location.href='latest.php'">عرض الكل</button>
            </div>
            <div class="products-row">
                <?= $latestHTML ?>
            </div>
        <?php endif; ?>


        <!-- ================= باقي الأقسام ================= -->
        <?php
        // استخدام GROUP BY name لمنع التكرار
        $cats = $pdo->query("SELECT * FROM categories GROUP BY name ORDER BY id DESC");

        while($cat = $cats->fetch(PDO::FETCH_ASSOC)):

            ob_start();
            $hasProducts = false;

            $catSql = "SELECT p.*,
                       (SELECT COALESCE(SUM(qty), 0) FROM cart WHERE product_id = p.id) as total_reserved
                       FROM products p
                       WHERE category_id = ? AND (quantity > 0 OR quantity IS NULL)
                       ORDER BY id DESC";
            $cStmt = $pdo->prepare($catSql);
            // ملاحظة: إذا كان هناك تكرار في الأسماء بـ IDs مختلفة، هذا الاستعلام سيجلب منتجات الـ ID الأول فقط
            // هذا الحل يخفي التكرار في العرض
            $cStmt->execute([$cat['id']]);

            while($prod = $cStmt->fetch(PDO::FETCH_ASSOC)):
                if ($prod['quantity'] !== null) {
                    $realQty = $prod['quantity'] - $prod['total_reserved'];
                    if ($realQty <= 0) continue;
                } else {
                    $realQty = null;
                }
                $hasProducts = true;
                $price = $prod['discount_price'] ? $prod['discount_price'] : $prod['price'];
            ?>
                <div class="product-card">
                    <button class="fav-btn <?= isset($favMap[$prod['id']]) ? 'active' : '' ?>"
                            onclick="toggleFav(this, <?= $prod['id'] ?>)">
                        <i class="<?= isset($favMap[$prod['id']]) ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ?>"></i>
                    </button>

                    <img src="uploads/<?= htmlspecialchars($prod['image']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
                    <h4><?= htmlspecialchars($prod['name']) ?></h4>
                    <p class="product-desc"><?= htmlspecialchars($prod['description'] ?? '') ?></p>

                    <div class="price-box">
                        <?= $prod['discount_price'] ? "<span class='old-price'>" . htmlspecialchars($prod['price']) . "</span>" : "" ?>
                        <span class="new-price"><?= htmlspecialchars($price) ?> ر.ي</span>
                    </div>

                    <?php if ($realQty !== null): ?>
                        <div class="qty-label">
                            متبقي: <span id="qty_cat_<?= $prod['id'] ?>"><?= htmlspecialchars($realQty) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="spacer-20"></div>
                    <?php endif; ?>

                    <button class="btn-add" onclick="checkSizeAndAdd(<?= $prod['id'] ?>, <?= $realQty !== null ? 'true' : 'false' ?>, 'qty_cat_<?= $prod['id'] ?>', '<?= htmlspecialchars($prod['sizes'] ?? '') ?>')">أضف للسلة</button>
                </div>
            <?php endwhile;

            $catHTML = ob_get_clean();

            if ($hasProducts):
            ?>
                <div class="section-header">
                    <h3><?= htmlspecialchars($cat['name']) ?></h3>
                    <button onclick="window.location.href='category.php?id=<?= $cat['id'] ?>'">عرض الكل</button>
                </div>
                <div class="products-row">
                    <?= $catHTML ?>
                </div>
            <?php
            endif;
        endwhile;
        ?>

    <!-- زر السلة العائم -->
    <!-- <div class="cart-float" onclick="openCart()">
        🛒 <span class="cart-count" id="cartCount">= getCartCount($pdo, $user_session) ?></span>
    </div> -->
    <!-- زر الأقسام العائم -->
    <!-- <div class="cats-float" onclick="toggleCatMenu()" title="تصفح الأقسام">
        ☰
    </div> -->

    <!-- قائمة الأقسام -->
    <div id="catsMenu" class="cats-menu-container">
        <h4>تصفح الأقسام</h4>

        <!-- رابط ثابت لأحدث المنتجات -->
        <a href="latest.php">✨ أحدث المنتجات</a>

        <?php
        // جلب الأقسام التي تحتوي على منتجات متاحة فقط
        $menuCats = $pdo->query("SELECT * FROM categories");
        while($c = $menuCats->fetch(PDO::FETCH_ASSOC)):

            // التحقق من وجود منتجات غير نافذة في هذا القسم
            $checkSql = "SELECT COUNT(*) FROM products
                         WHERE category_id = ?
                         AND (quantity > 0 OR quantity IS NULL)";

            // ملاحظة: هذا فحص سريع، الفحص الدقيق للمحجوز يتطلب استعلاماً أثقل
            // لكن لغرض القائمة السريعة، هذا يكفي لإخفاء الأقسام الفارغة تماماً
            $stmt = $pdo->prepare($checkSql);
            $stmt->execute([$c['id']]);

            if ($stmt->fetchColumn() > 0):
        ?>
            <a href="category.php?id=<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></a>
        <?php
            endif;
        endwhile;
        ?>
    </div>
    <!-- نافذة السلة -->
    <div id="cartModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('cartModal')">&times;</span>
            <h2 class="modal-title">سلة المشتريات</h2>
            <div id="cartItems"></div>
            <hr>
            <div class="modal-footer">
                <input type="text" id="custName" class="form-input" placeholder="اسم المستلم (الأول والثاني) *" required>
                <input type="number" id="custPhone" class="form-input" placeholder="رقم الهاتف (اختياري)">
                <div class="form-group-relative">
                    <input type="text" id="custAddress" class="form-input input-with-icon"
                           placeholder="العنوان بالتفصيل (أو اضغط الأيقونة 🎯)"
                           required>
                    <button type="button" onclick="getLocation()" title="تحديد موقعي الحالي" class="location-btn">
                        <i class="fa-solid fa-location-crosshairs"></i>
                    </button>
                </div>
                <small class="form-hint">
                    اضغط الأيقونة لتعبئة الحقل برابط الخريطة تلقائياً 🌍
                </small>
                <textarea id="custNotes" class="form-input notes-textarea" placeholder="ملاحظات إضافية (اختياري)"></textarea>
            </div>
            <button id="btnCheckout" class="btn-add btn-checkout" onclick="checkout()">شراء وإصدار فاتورة</button>
        </div>
    </div>

    <!-- نافذة معاينة المنتج -->
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('previewModal')">&times;</span>
            <div id="previewContent"></div>
        </div>
    </div>

    <!-- منطقة الفاتورة المخفية -->
    <div id="invoice-area">
        <div class="invoice-header">
        <a href="index.php" class="logo-container">
            <span class="logo-text-store">STORE</span>
            <span class="logo-text-sam">SAM</span>
        </a>
            <p class="invoice-subtitle">فاتورة ضريبية مبسطة</p>
            <p class="invoice-id">رقم الفاتورة: #<span id="invId"></span></p>
        </div>

        <div class="invoice-details">
            <p class="invoice-detail-item"><strong>العميل:</strong> <span id="invName"></span></p>
            <p class="invoice-detail-item"><strong>الهاتف:</strong> <span id="invPhone"></span></p>
            <p class="invoice-detail-item"><strong>العنوان:</strong> <span id="invAddress"></span></p>
            <p class="invoice-detail-item"><strong>التاريخ:</strong> <?= date('Y-m-d h:i A') ?></p>
        </div>

        <table border="1" class="invoice-table">
            <thead class="invoice-table-header">
                <tr>
                    <th class="invoice-table-header-cell">المنتج</th>
                    <th>السعر</th>
                    <th>الكمية</th>
                </tr>
            </thead>
            <tbody id="invBody"></tbody>
        </table>

        <div class="invoice-total-section">
            <h3>الإجمالي: <span id="invTotal" class="invoice-total-amount"></span> ر.ي</h3>
        </div>

        <p>- <span class="invoice-notes">#ملاحظة :</span> يرجى ارسال الفاتورة الى مالك المتجر ليتم تجهيز طلبك، شكرا لتعاملكم معنا.</p>

    </div>

<!-- زر واتساب العائم -->
<!-- <a href="https://wa.me/967738183179" class="whatsapp-float" target="_blank">
    <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" width="30" height="30">
</a> -->
<!-- حساب عدد المفضلات -->
    <?php
    $favCount = 0;
    if(isset($user_session)) {
        $fcStmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE session_id = ?");
        $fcStmt->execute([$user_session]);
        $favCount = $fcStmt->fetchColumn();
    }
    ?>

    <!-- زر المفضلة العائم -->
    <!-- <a href="favorites.php" class="fav-float" title="مفضلاتي">
        <i class="fa-solid fa-heart"></i>
        php if($favCount > 0): ?>
            <span class="fav-count">= $favCount ?></span>
        php endif; ?>
    </a> -->

<!-- نافذة اختيار المقاس -->
<div id="sizeModal" class="modal">
    <div class="modal-content size-modal-content">
        <span class="close-modal" onclick="closeModal('sizeModal')">&times;</span>
        <h3>اختر المقاس المطلوب</h3>
        <div id="sizesContainer" class="sizes-container">
            <!-- سيتم توليد الأزرار هنا بالجافاسكريبت -->
        </div>
    </div>
</div>
<!-- الشريط السفلي الثابت (Bottom Navigation Bar) -->
<nav class="bottom-nav">
        <!-- 5. زر واتساب -->
        <a href="https://wa.me/967738183179" class="nav-item" target="_blank">
            <i class="fa-brands fa-whatsapp" style="font-size: 1.4rem;"></i>
            <span>تواصل</span>
        </a>
        <!-- 2. زر تتبع الطلب -->
        <a href="track.php" class="nav-item">
            <i class="fa-solid fa-truck-fast"></i>
            <span>تتبع الطلب</span>
        </a>

        <!-- 3. زر السلة (المركزي البارز) -->
        <div class="nav-item center-fab-container">
            <div class="center-fab" onclick="openCart()">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="nav-cart-count" id="cartCount"><?= getCartCount($pdo, $user_session) ?></span>
            </div>
        </div>

        <!-- 4. زر المفضلة -->
        <a href="favorites.php" class="nav-item">
            <i class="fa-regular fa-heart"></i>
            <span>المفضلة</span>
        </a>

        <!-- 1. زر القائمة (الأقسام) -->
        <div class="nav-item" onclick="toggleCatMenu()">
            <i class="fa-solid fa-bars"></i>
            <span>الأقسام</span>
        </div>
    </nav>
    <script src="script.js"></script>
</body>
</html>