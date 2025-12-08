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
                     <div style="height:20px;"></div>
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
                        <div style="height:20px;"></div>
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
            <h2>سلة المشتريات</h2>
            <div id="cartItems"></div>
            <hr>
            <div style="margin-top:15px;">
                <input type="text" id="custName" class="form-input" placeholder="اسم المستلم (الأول والثاني) *" required>
                <input type="number" id="custPhone" class="form-input" placeholder="رقم الهاتف (اختياري)">
                <!-- الحقول الجديدة -->
                 <!-- حقل العنوان مع زر الموقع الاحترافي -->
                <div style="position: relative; margin-bottom: 5px;">
                    <input type="text" id="custAddress" class="form-input"
                           placeholder="العنوان بالتفصيل (أو اضغط الأيقونة 🎯)"
                           required
                           style="padding-left: 45px; margin-bottom: 0;">

                    <!-- زر الأيقونة -->
                    <button type="button" onclick="getLocation()" title="تحديد موقعي الحالي"
                            style="position: absolute; left: 0; top: 0; bottom: 0; width: 40px;
                                   border: none; background: #e9ecef; border-top-left-radius: 4px; border-bottom-left-radius: 4px;
                                   color: #d00000; cursor: pointer; display:flex; align-items:center; justify-content:center; transition:0.2s;">
                        <i class="fa-solid fa-location-crosshairs" style="font-size: 1.2rem;"></i>
                    </button>
                </div>
                <small style="display:block; color:#777; margin-bottom:8px; font-size:0.75rem;">
                    اضغط الأيقونة لتعبئة الحقل برابط الخريطة تلقائياً 🌍
                </small>
                <textarea id="custNotes" class="form-input" placeholder="ملاحظات إضافية (اختياري)" style="height:60px; resize:none; font-family:'Almarai', sans-serif;"></textarea>
            </div>
            <button id="btnCheckout" class="btn-add" style="background:#1a2a3a; margin-top:10px;" onclick="checkout()">شراء وإصدار فاتورة</button>
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
    <div id="invoice-area" style="display:none;">
        <div style="text-align:center; border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:10px;">
        <a href="index.php" class="logo-container">
            <span class="logo-text-store">STORE</span>
            <span class="logo-text-sam">SAM</span>
        </a>
            <p style="margin:0; color:#777;">فاتورة ضريبية مبسطة</p>
            <p style="margin:0; font-weight:bold; color:#d00000;">رقم الفاتورة: #<span id="invId"></span></p>
        </div>

        <div style="margin-bottom:15px; font-size:0.9rem;">
            <p style="margin:5px 0;"><strong>العميل:</strong> <span id="invName"></span></p>
            <p style="margin:5px 0;"><strong>الهاتف:</strong> <span id="invPhone"></span></p>
            <p style="margin:5px 0;"><strong>العنوان:</strong> <span id="invAddress"></span></p>
            <p style="margin:5px 0;"><strong>التاريخ:</strong> <?= date('Y-m-d h:i A') ?></p>
        </div>

        <table border="1" width="100%" style="border-collapse:collapse; text-align:center; border-color:#eee;">
            <thead style="background:#f9f9f9;">
                <tr>
                    <th style="padding:8px;">المنتج</th>
                    <th>السعر</th>
                    <th>الكمية</th>
                </tr>
            </thead>
            <tbody id="invBody"></tbody>
        </table>

        <div style="text-align:left; margin-top:15px;">
            <h3>الإجمالي: <span id="invTotal" style="color:#d00000;"></span> ر.ي</h3>
        </div>

        <p>- <span style="color: red; font-weight: bold;">#ملاحظة :</span> يرجى ارسال الفاتورة الى مالك المتجر ليتم تجهيز طلبك، شكرا لتعاملكم معنا.</p>

    </div>

<script>
    // متغير لتخزين بيانات السلة للمعاينة
    let globalCartItems = [];

    // --- 1. فتح وإغلاق السلة ---
    function openCart() {
        document.getElementById('cartModal').style.display = 'flex';
        loadCartItems();
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

// متغيرات مؤقتة لحفظ المنتج المراد إضافته
    let pendingPid = 0;
    let pendingHasQty = false;
    let pendingElemId = '';

    // الدالة الأولى: الفحص
    function checkSizeAndAdd(pid, hasQty, elemId, sizesStr) {
        // 1. إذا كان المنتج لا يحتوي على مقاسات (فارغ)
        if (!sizesStr || sizesStr.trim() === '') {
            addToCartFinal(pid, hasQty, elemId, null); // إضافة مباشرة بدون مقاس
            return;
        }

        // 2. إذا كان له مقاسات، نفتح النافذة
        pendingPid = pid;
        pendingHasQty = hasQty;
        pendingElemId = elemId;

        let sizesArr = sizesStr.split(',');
        let container = document.getElementById('sizesContainer');
        container.innerHTML = '';

        sizesArr.forEach(size => {
            size = size.trim();
            // إنشاء زر لكل مقاس
            let btn = document.createElement('button');
            btn.className = 'size-btn';
            btn.innerText = size;
            btn.onclick = function() {
                closeModal('sizeModal');
                addToCartFinal(pendingPid, pendingHasQty, pendingElemId, size);
            };
            container.appendChild(btn);
        });

        document.getElementById('sizeModal').style.display = 'flex';
    }

    // الدالة الثانية: التنفيذ الفعلي (استبدال addToCart القديمة)
    function addToCartFinal(pid, hasQty, elemId, selectedSize) {
        if (!pid) return;

        // التحقق البصري من الكمية
        let qtyElem = document.getElementById(elemId);
        let currentQty = hasQty && qtyElem ? parseInt(qtyElem.innerText) : 0;
        if (hasQty && currentQty <= 0) { alert("نفذت الكمية!"); return; }

        let fd = new FormData();
        fd.append('action', 'add_to_cart');
        fd.append('product_id', pid);
        if (selectedSize) {
            fd.append('size', selectedSize); // إرسال المقاس المختار
        }

        document.body.style.cursor = 'wait';

        fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            document.body.style.cursor = 'default';
            if(data.status === 'success') {
                document.getElementById('cartCount').innerText = data.count;
                // إنقاص الرقم من الشاشة
                if (hasQty && qtyElem) {
                    qtyElem.innerText = currentQty - 1;
                    if (currentQty - 1 === 0) alert("تم حجز آخر قطعة!");
                }
                // رسالة تأكيد للمقاس (اختياري)
                // if(selectedSize) alert('تم إضافة مقاس ' + selectedSize);
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            document.body.style.cursor = 'default';
            console.error(err);
        });
    }

    // --- 3. تحميل عناصر السلة ---
    // --- 3. تحميل عناصر السلة (التصميم الجديد) ---
    function loadCartItems() {
        let fd = new FormData();
        fd.append('action', 'get_cart');

        fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(items => {
            globalCartItems = items;
            let html = '', invHtml = '', total = 0;

            items.forEach((item, index) => {
                let price = item.discount_price ? item.discount_price : item.price;
                total += price * item.qty;

                // تجهيز نص المقاس
                let sizeDisplay = item.size ? `<span class="cart-size"> (مقاس: ${item.size})</span>` : '';
                let sizeForInvoice = item.size ? ` (${item.size})` : '';

                // --- الهيكل الجديد للبطاقة ---
                html += `
                <div class="cart-item">
                    <!-- 1. الصورة (يمين) -->
                    <img src="uploads/${item.image}" onclick="previewProduct(${index})">

                    <!-- 2. التفاصيل (وسط) -->
                    <div class="cart-details">
                        <div class="cart-name">${item.name} ${sizeDisplay}</div>
                        <div class="cart-price">${price} ر.ي</div>
                    </div>

                    <!-- 3. التحكم (يسار) -->
                    <div class="cart-actions">
                        <!-- أزرار الكمية (كبسولة) -->
                        <div class="qty-group">
                            <button class="qty-btn" onclick="updateQty(${item.cart_id}, 'increase')">+</button>
                            <span class="qty-num">${item.qty}</span>
                            <button class="qty-btn" onclick="updateQty(${item.cart_id}, 'decrease')">-</button>
                        </div>

                        <!-- أزرار الحذف والمعاينة -->
                        <div class="tools-group">
                            <button class="tool-btn view" onclick="previewProduct(${index})" title="معاينة">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="tool-btn delete" onclick="removeFromCart(${item.cart_id})" title="حذف">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                </div>`;

                // جدول الفاتورة (مخفي)
                invHtml += `<tr>
                                <td style="padding:5px;">${item.name} ${sizeForInvoice}</td>
                                <td>${price}</td>
                                <td>${item.qty}</td>
                            </tr>`;
            });

            // في حال السلة فارغة
            if (items.length === 0) {
                html = `
                <div style="text-align:center; padding:40px 20px; color:#888;">
                    <i class="fa-solid fa-cart-arrow-down" style="font-size:3rem; margin-bottom:10px; color:#ddd;"></i>
                    <p>سلة المشتريات فارغة</p>
                </div>`;
            }

            document.getElementById('cartItems').innerHTML = html;
            document.getElementById('invBody').innerHTML = invHtml;
            document.getElementById('invTotal').innerText = total;
        });
    }

    // --- 4. تحديث كمية السلة ---
    function updateQty(id, op) {
        let fd = new FormData();
        fd.append('action', 'update_cart_qty');
        fd.append('cart_id', id);
        fd.append('operation', op);

        fetch('api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(d=>{
            if (d.status === 'success') {
                loadCartItems();
                document.getElementById('cartCount').innerText = d.count;
            } else {
                alert(d.message); // رسالة الخطأ عند تجاوز الحد
            }
        });
    }

    // --- 5. حذف من السلة ---
    function removeFromCart(id) {
        let fd = new FormData();
        fd.append('action', 'remove_from_cart');
        fd.append('cart_id', id);
        fetch('api.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
            loadCartItems();
            document.getElementById('cartCount').innerText = d.count;
        });
    }

    // --- 6. معاينة المنتج (Modal) ---
    // --- 6. معاينة المنتج (تصميم احترافي) ---
    function previewProduct(index) {
        let item = globalCartItems[index];
        if (!item) return;

        let price = item.discount_price ? item.discount_price : item.price;
        // تنسيق الوصف: إذا فارغ نكتب رسالة لطيفة
        let desc = item.description ? item.description.replace(/\n/g, "<br>") : "لا يوجد وصف إضافي لهذا المنتج.";

        // عرض المقاس إن وجد
        let sizeHtml = item.size ? `<span style="display:block; font-size:0.9rem; color:#1a2a3a; margin-bottom:5px;">المقاس المختار: <b style="color:#d00000">${item.size}</b></span>` : '';

        let html = `
            <div class="preview-container">
                <!-- الصورة -->
                <div class="preview-img-box">
                    <img src="uploads/${item.image}" alt="${item.name}">
                </div>

                <!-- التفاصيل -->
                <div class="preview-title">${item.name}</div>
                ${sizeHtml}
                <div class="preview-price">${price} ر.ي</div>

                <div class="preview-desc">
                    <strong>📝 الوصف:</strong><br>
                    ${desc}
                </div>

                <!-- الأزرار -->
                <div class="preview-actions">
                    <button onclick="closeModal('previewModal')" class="btn-modal btn-close-action">
                        إغلاق
                    </button>

                    <button onclick="removeFromCart(${item.cart_id}); closeModal('previewModal');" class="btn-modal btn-remove-action">
                        <i class="fa-solid fa-trash-can"></i> حذف من السلة
                    </button>
                </div>
            </div>
        `;
        document.getElementById('previewContent').innerHTML = html;
        document.getElementById('previewModal').style.display = 'flex';
    }

// --- 7. الشراء (Checkout) مع واتساب ---
    function checkout() {
        if (globalCartItems.length === 0) {
            alert("السلة فارغة!"); return;
        }

        let name = document.getElementById('custName').value.trim();
        let phone = document.getElementById('custPhone').value.trim();
        // جلب القيم الجديدة
        let address = document.getElementById('custAddress').value.trim();
        let notes = document.getElementById('custNotes').value.trim();

        // التحقق من المدخلات
        if(name.split(' ').length < 2) { alert('اكتب الاسم الثنائي'); return; }
        // if(phone.length < 6) { alert('رقم الهاتف غير صحيح'); return; }
        if(address.length < 2) { alert('يرجى كتابة العنوان بشكل واضح'); return; }

        let btn = document.getElementById('btnCheckout');
        let orgText = btn.innerText;
        btn.disabled = true;
        btn.innerText = "جاري المعالجة...";

        let fd = new FormData();
        fd.append('action', 'checkout');
        fd.append('name', name);
        fd.append('phone', phone);
        // إرسال البيانات الجديدة
        fd.append('address', address);
        fd.append('notes', notes);

        // رقم هاتفك للواتساب
        const myPhoneNumber = "967738183179";

        fetch('api.php', {method:'POST', body:fd})
        .then(r=>r.json())
        .then(d=>{
            if(d.status==='success'){
                // --- تعبئة الفاتورة بالبيانات الجديدة ---
                document.getElementById('invName').innerText = name;

                // سنقوم بإضافة العنوان ورقم الهاتف للفاتورة بإنشاء عناصر HTML لها
                // تأكد أنك ستضيف هذه العناصر في HTML الفاتورة أدناه (الخطوة 4)
                document.getElementById('invPhone').innerText = phone;
                document.getElementById('invAddress').innerText = address;
                // document.getElementById('invId').innerText = d.order_id;
                document.getElementById('invId').innerText = d.invoice_code;
                document.getElementById('invoice-area').style.display='block';

                html2canvas(document.getElementById('invoice-area')).then(canvas => {
                    let imgData = canvas.toDataURL('image/png');

                    // رفع الصورة
                    let uploadFd = new FormData();
                    uploadFd.append('action', 'save_invoice_image');
                    uploadFd.append('image', imgData);

                    fetch('api.php', {method:'POST', body:uploadFd})
                    .then(res => res.json())
                    .then(resData => {
                        if(resData.status === 'success') {
                            let currentUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/'));
                            let fileUrl = currentUrl + "/invoices/" + resData.file;

                            // إضافة العنوان للرسالة
                            let msg = `طلب جديد من: ${name}\n📱 الهاتف: ${phone}\n📍 العنوان: ${address}\n📝 ملاحظات: ${notes}\n📄 الفاتورة: ${fileUrl}`;

                            let whatsappUrl = `https://wa.me/${myPhoneNumber}?text=${encodeURIComponent(msg)}`;

                            // تنزيل الصورة للزبون
                            let link = document.createElement('a');
                            link.download = 'SAM_Invoice_' + Date.now() + '.png';
                            link.href = imgData;
                            link.click();

                            window.open(whatsappUrl, '_blank');
                            setTimeout(() => { window.location.reload(); }, 1000);
                        }
                    });
                    document.getElementById('invoice-area').style.display='none';
                });
            } else {
                alert(d.message);
                btn.disabled = false;
                btn.innerText = orgText;
            }
        })
        .catch(e => { console.error(e); alert("خطأ"); btn.disabled = false; btn.innerText = orgText; });
    }

    // --- عند العودة من صفحة أخرى ---
    window.onload = function() {
        const u = new URLSearchParams(window.location.search);
        if(u.get('open_cart')==='1') {
            openCart();
            window.history.replaceState({},document.title,"index.php");
        }
    };
    // دالة فتح وإغلاق قائمة الأقسام
    function toggleCatMenu() {
        let menu = document.getElementById('catsMenu');
        if (menu.style.display === 'block') {
            menu.style.display = 'none';
        } else {
            menu.style.display = 'block';
        }
    }
    // إغلاق القائمة عند الضغط في أي مكان خارجها
     window.addEventListener('click', function(e) {
        let menu = document.getElementById('catsMenu');
        // نبحث عن أقرب عنصر nav-item تم ضغطه (لأن الأيقونة داخل div)
        let clickedNavItem = e.target.closest('.nav-item');

        // التحقق: إذا كانت القائمة مفتوحة
        if (menu.style.display === 'block') {
            // إذا لم نضغط داخل القائمة، ولم نضغط على زر القائمة (الأول في الشريط)
            if (!menu.contains(e.target)) {
                // التأكد أن الضغط لم يكن على أيقونة القائمة نفسها
                // زر القائمة هو العنصر الأول الذي يستدعي toggleCatMenu
                if (!clickedNavItem || !clickedNavItem.onclick) {
                    menu.style.display = 'none';
                }
            }
        }
    });
    function toggleFav(btn, pid) {
        let icon = btn.querySelector('i'); // الوصول للأيقونة داخل الزر

        let fd = new FormData();
        fd.append('action', 'toggle_favorite');
        fd.append('product_id', pid);

        fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'added') {
                btn.classList.add('active');
                // تغيير الأيقونة إلى قلب ممتلئ
                icon.classList.remove('fa-regular');
                icon.classList.add('fa-solid');
                icon.classList.add('fa-heart');
            } else {
                btn.classList.remove('active');
                // تغيير الأيقونة إلى قلب فارغ
                icon.classList.remove('fa-solid');
                icon.classList.add('fa-regular');
                icon.classList.add('fa-heart');
            }
        })
        .catch(e => console.error(e));
    }
    // --- حل مشكلة تحديث الصفحة عند العودة من الخلف ---
    window.addEventListener( "pageshow", function ( event ) {
      // الـ history.navigationMode deprecated ولكن هذا الفحص يعمل في معظم المتصفحات
      // للتحقق مما إذا كانت الصفحة مخزنة في الـ Cache
      var historyTraversal = event.persisted ||
                             ( typeof window.performance != "undefined" &&
                                  window.performance.navigation.type === 2 );
      if ( historyTraversal ) {
        // إعادة تحميل الصفحة لجلب حالة المفضلة الجديدة من قاعدة البيانات
        window.location.reload();
      }
    });
    function getLocation() {
        let addressField = document.getElementById("custAddress");

        if (navigator.geolocation) {
            addressField.value = "جاري جلب إحداثياتك... 📡";
            document.body.style.cursor = 'wait';

            // طلب دقة عالية (High Accuracy)
            navigator.geolocation.getCurrentPosition(showPosition, showError, {
                enableHighAccuracy: true, // محاولة الحصول على أدق موقع ممكن (GPS)
                timeout: 10000,
                maximumAge: 0
            });
        } else {
            alert("المتصفح لا يدعم تحديد الموقع.");
        }
    }

    function showPosition(position) {
        document.body.style.cursor = 'default';
        let lat = position.coords.latitude;
        let long = position.coords.longitude;

        // رابط يفتح تطبيق الخرائط مباشرة
        let googleMapsLink = `https://maps.google.com/?q=${lat},${long}`;

        // وضع الرابط في الحقل
        let field = document.getElementById("custAddress");
        field.value = googleMapsLink;

        // وميض للحقل لتأكيد العملية
        field.style.borderColor = "#28a745";
        setTimeout(() => { field.style.borderColor = "#ddd"; }, 2000);
    }

    function showError(error) {
        document.body.style.cursor = 'default';
        let field = document.getElementById("custAddress");
        field.value = ""; // تفريغ الحقل
        field.placeholder = "تعذر تحديد الموقع، اكتب العنوان يدوياً";

        switch(error.code) {
            case error.PERMISSION_DENIED:
                alert("يجب السماح للموقع بالوصول للموقع الجغرافي من إعدادات المتصفح.");
                break;
            case error.POSITION_UNAVAILABLE:
                alert("معلومات الموقع غير متوفرة (تأكد من تشغيل GPS).");
                break;
            case error.TIMEOUT:
                alert("انتهت مهلة الانتظار.");
                break;
            default:
                alert("حدث خطأ غير معروف.");
        }
    }
</script>

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
    <div class="modal-content" style="text-align:center; padding-top:30px;">
        <span class="close-modal" onclick="closeModal('sizeModal')">&times;</span>
        <h3>اختر المقاس المطلوب</h3>
        <div id="sizesContainer" style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin:20px 0;">
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
</body>
</html>