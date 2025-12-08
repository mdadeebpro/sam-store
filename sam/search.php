<?php 
include 'db.php'; 
$query = $_GET['q'] ?? '';

$favMap = [];
if(isset($user_session)) {
    $fStmt = $pdo->prepare("SELECT product_id FROM favorites WHERE session_id = ?");
    $fStmt->execute([$user_session]);
    while ($fRow = $fStmt->fetch(PDO::FETCH_ASSOC)) { $favMap[$fRow['product_id']] = true; }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بحث: <?= htmlspecialchars($query) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        .products-grid { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; padding: 10px; }
        .product-card { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 12px; display: flex; flex-direction: column; justify-content: space-between; position: relative; margin-bottom: 15px; border: 1px solid #f0f0f0; transition: all 0.3s ease; width: calc(50% - 10px); box-sizing: border-box; overflow: hidden; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-color: #c8a76a; }
        .product-card img { width: 100%; height: 150px; object-fit: contain; background-color: #f9f9f9; border-radius: 8px; padding: 5px; margin-bottom: 10px; mix-blend-mode: multiply; }
        .product-card h4 { font-size: 0.95rem; color: #333; margin: 5px 0; font-weight: 800; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .product-desc { font-size: 0.75rem; color: #777; text-align: center; margin-bottom: 8px; height: 1.2em; overflow: hidden; }
        .price-box { display: flex; justify-content: center; align-items: baseline; gap: 5px; margin-bottom: 5px; }
        .new-price { color: #1a2a3a; font-size: 1.1rem; font-weight: 900; }
        .old-price { font-size: 0.8rem; color: #aaa; text-decoration: line-through; }
        .qty-label { font-size: 0.7rem; color: #28a745; background: #e8f5e9; padding: 2px 8px; border-radius: 10px; margin: 0 auto 10px auto; width: fit-content; display: block; }
        .btn-add { background: linear-gradient(45deg, #1a2a3a, #2c3e50); color: #fff; border: none; padding: 8px 15px; width: 100%; cursor: pointer; border-radius: 8px; font-size: 0.9rem; font-weight: bold; margin-top: auto; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 5px; font-family: 'Almarai', sans-serif; }
        .btn-add:hover { background: linear-gradient(45deg, #c8a76a, #d4b06a); transform: scale(1.02); }
        .fav-btn { position: absolute; top: 10px; left: 10px; width: 35px; height: 35px; background-color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2); cursor: pointer; border: none; z-index: 10; transition: transform 0.2s ease; padding: 0; }
        .fav-btn:hover { transform: scale(1.1); }
        .fav-btn i { font-size: 18px; color: #ccc; transition: color 0.2s; }
        .fav-btn.active i { color: #e74c3c; font-weight: 900; }
        .size-btn { padding: 10px 20px; border: 2px solid #ddd; background: #fff; cursor: pointer; border-radius: 5px; font-weight: bold; font-family: 'Almarai', sans-serif; transition: 0.2s; }
        .size-btn:hover { border-color: #1a2a3a; background: #1a2a3a; color: #fff; }
        @media (min-width: 768px) { .products-grid { gap: 15px; justify-content: flex-start; } .product-card { width: 200px; padding: 10px; } .product-card img { height: 160px; } }
    </style>
</head>
<body>
    <header>
        <a href="index.php" style="text-decoration:none;">
            <div class="logo-container" style="display:inline-flex; align-items:center; justify-content:center; gap:5px; border:2px solid #1a2a3a; padding:5px 15px; border-radius:4px;">
                <span style="font-family:'Almarai', sans-serif; font-size:28px; font-weight:900; color:#1a2a3a;">SAM</span>
                <span style="font-family:'Almarai', sans-serif; font-size:28px; font-weight:300; color:#c8a76a;">STORE</span>
            </div>
        </a>
        <h3 style="margin-top:10px;">نتائج البحث عن: "<?= htmlspecialchars($query) ?>"</h3>
        <a href="index.php" class="btn-back">عودة للرئيسية ⌂</a>
    </header>

    <div class="container">
        <div class="products-grid">
            <?php
            $sql = "SELECT p.*, (SELECT COALESCE(SUM(qty), 0) FROM cart WHERE product_id = p.id) as total_reserved 
                    FROM products p 
                    WHERE (name LIKE ? OR description LIKE ?) AND (quantity > 0 OR quantity IS NULL)";
            $stmt = $pdo->prepare($sql);
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm]);
            
            $found = false;
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                $found = true;
                if ($row['quantity'] !== null) {
                    $realQty = $row['quantity'] - $row['total_reserved'];
                    if ($realQty <= 0) continue;
                } else { $realQty = null; }
                $price = $row['discount_price'] ? $row['discount_price'] : $row['price'];
            ?>
            <div class="product-card">
                <button class="fav-btn <?= isset($favMap[$row['id']]) ? 'active' : '' ?>" onclick="toggleFav(this, <?= $row['id'] ?>)">
                    <i class="<?= isset($favMap[$row['id']]) ? 'fa-solid fa-heart' : 'fa-regular fa-heart' ?>"></i>
                </button>
                <img src="uploads/<?= $row['image'] ?>">
                <h4><?= $row['name'] ?></h4>
                <div class="price-box">
                    <?php if (!empty($row['discount_price']) && $row['discount_price'] > 0): ?>
                        <span class="old-price"><?= $row['price'] ?></span>
                        <span class="new-price"><?= $row['discount_price'] ?> ر.ي</span>
                    <?php else: ?>
                        <span class="new-price"><?= $row['price'] ?> ر.ي</span>
                    <?php endif; ?>
                </div>                <?php if ($realQty !== null): ?>
                    <div class="qty-label">متبقي: <span id="qty_s_<?= $row['id'] ?>"><?= $realQty ?></span></div>
                <?php else: ?> <div style="height:20px;"></div> <?php endif; ?>
                <button class="btn-add" onclick="checkSizeAndAdd(<?= $row['id'] ?>, <?= $realQty !== null ? 'true' : 'false' ?>, 'qty_s_<?= $row['id'] ?>', '<?= $row['sizes'] ?? '' ?>')">أضف للسلة</button>
            </div>
            <?php endwhile; ?>
            
            <?php if(!$found): ?>
                <p style="text-align:center; width:100%; padding:20px;">لا توجد منتجات تطابق بحثك.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ================== النوافذ والقوائم ================== -->
    
    <div id="catsMenu" class="cats-menu-container">
        <h4>تصفح الأقسام</h4>
        <a href="latest.php">✨ أحدث المنتجات</a>
        <?php
        $menuCats = $pdo->query("SELECT * FROM categories");
        while($c = $menuCats->fetch(PDO::FETCH_ASSOC)):
            $checkSql = "SELECT COUNT(*) FROM products WHERE category_id = ? AND (quantity > 0 OR quantity IS NULL)";
            $stmt = $pdo->prepare($checkSql); $stmt->execute([$c['id']]);
            if ($stmt->fetchColumn() > 0): ?>
            <a href="category.php?id=<?= $c['id'] ?>"><?= $c['name'] ?></a>
        <?php endif; endwhile; ?>
    </div>

    <div id="cartModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="document.getElementById('cartModal').style.display='none'">&times;</span>
            <h2 style="text-align:center;">سلة المشتريات</h2>
            <div id="cartItems"></div>
            <div style="margin-top:auto;">
                <input type="text" id="custName" class="form-input" placeholder="اسم المستلم (الأول والثاني) *" required>
                <input type="number" id="custPhone" class="form-input" placeholder="رقم الهاتف (اختياري)">
                <!-- <input type="text" id="custAddress" class="form-input" placeholder="العنوان بالتفصيل *" required> -->
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
                <textarea id="custNotes" class="form-input" placeholder="ملاحظات (اختياري)" style="height:50px; resize:none; font-family:'Almarai';"></textarea>
                <button id="btnCheckout" class="btn-add" style="background:#1a2a3a; margin-top:10px; padding:12px;" onclick="checkout()">شراء وإصدار فاتورة</button>
            </div>
        </div>
    </div>

    <div id="sizeModal" class="modal">
        <div class="modal-content" style="text-align:center; padding-top:30px; height:auto;">
            <span class="close-modal" onclick="document.getElementById('sizeModal').style.display='none'">&times;</span>
            <h3>اختر المقاس المطلوب</h3>
            <div id="sizesContainer" style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin:20px 0;"></div>
        </div>
    </div>

    <div id="previewModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="document.getElementById('previewModal').style.display='none'">&times;</span>
            <div id="previewContent"></div>
        </div>
    </div>

    <div id="invoice-area" style="display:none; background:#fff; padding:20px; border:1px solid #333; width:500px; font-family:'Almarai', sans-serif;">
        <div style="text-align:center; border-bottom:2px solid #eee; padding-bottom:10px; margin-bottom:10px;">
            <img src="logo.png" style="width:60px;">
            <h2 style="margin:5px 0; color:#1a2a3a;">SAM STORE</h2>
            <p style="margin:0; font-weight:bold; color:#d00000;">رقم الفاتورة: #<span id="invId"></span></p>
        </div>
        <div style="margin-bottom:15px; font-size:0.9rem;">
            <p style="margin:5px 0;"><strong>العميل:</strong> <span id="invName"></span></p>
            <p style="margin:5px 0;"><strong>الهاتف:</strong> <span id="invPhone"></span></p>
            <p style="margin:5px 0;"><strong>العنوان:</strong> <span id="invAddress"></span></p>
            <p style="margin:5px 0;"><strong>التاريخ:</strong> <?= date('Y-m-d h:i A') ?></p>
        </div>
        <table border="1" width="100%" style="border-collapse:collapse; text-align:center; border-color:#eee;">
            <thead style="background:#f9f9f9;"><tr><th style="padding:8px;">المنتج</th><th>السعر</th><th>الكمية</th></tr></thead>
            <tbody id="invBody"></tbody>
        </table>
        <div style="text-align:left; margin-top:15px;"><h3>الإجمالي: <span id="invTotal" style="color:#d00000;"></span> ر.ي</h3></div>
    </div>

    <nav class="bottom-nav">
        <a href="https://wa.me/967738183179" class="nav-item" target="_blank"><i class="fa-brands fa-whatsapp" style="font-size:1.4rem;"></i><span>تواصل</span></a>
        <a href="track.php" class="nav-item"><i class="fa-solid fa-truck-fast"></i><span>تتبع الطلب</span></a>
        <div class="nav-item center-fab-container">
            <div class="center-fab" onclick="openCart()"><i class="fa-solid fa-cart-shopping"></i><span class="nav-cart-count" id="cartCount"><?= getCartCount($pdo, $user_session) ?></span></div>
        </div>
        <a href="favorites.php" class="nav-item"><i class="fa-regular fa-heart"></i><span>المفضلة</span></a>
        <div class="nav-item" onclick="toggleCatMenu()"><i class="fa-solid fa-bars"></i><span>الأقسام</span></div>
    </nav>

    <!-- سكربتات JS (نفس index.php) -->
    <script>
        let globalCartItems = [];
        let pendingPid = 0; let pendingHasQty = false; let pendingElemId = '';

        function toggleCatMenu() {
            let menu = document.getElementById('catsMenu');
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        }
        window.addEventListener('click', function(e) {
            let menu = document.getElementById('catsMenu');
            let clickedNavItem = e.target.closest('.nav-item');
            if (menu.style.display === 'block') {
                if (!menu.contains(e.target)) {
                    if (!clickedNavItem || !clickedNavItem.onclick) { menu.style.display = 'none'; }
                }
            }
        });

        function openCart() { document.getElementById('cartModal').style.display = 'flex'; loadCartItems(); }

        function checkSizeAndAdd(pid, hasQty, elemId, sizesStr) {
            if (!sizesStr || sizesStr.trim() === '') { addToCartFinal(pid, hasQty, elemId, null); return; }
            pendingPid = pid; pendingHasQty = hasQty; pendingElemId = elemId;
            let sizesArr = sizesStr.split(',');
            let container = document.getElementById('sizesContainer'); container.innerHTML = '';
            sizesArr.forEach(size => {
                let btn = document.createElement('button'); btn.className = 'size-btn'; btn.innerText = size.trim();
                btn.onclick = function() { document.getElementById('sizeModal').style.display = 'none'; addToCartFinal(pendingPid, pendingHasQty, pendingElemId, size.trim()); };
                container.appendChild(btn);
            });
            document.getElementById('sizeModal').style.display = 'flex';
        }

        function addToCartFinal(pid, hasQty, elemId, selectedSize) {
            if (!pid) return;
            let qtyElem = document.getElementById(elemId);
            let currentQty = hasQty && qtyElem ? parseInt(qtyElem.innerText) : 0;
            if (hasQty && currentQty <= 0) { alert("نفذت الكمية!"); return; }
            let fd = new FormData(); fd.append('action', 'add_to_cart'); fd.append('product_id', pid); if (selectedSize) fd.append('size', selectedSize);
            document.body.style.cursor = 'wait';
            fetch('api.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
                document.body.style.cursor = 'default';
                if(data.status === 'success') {
                    document.getElementById('cartCount').innerText = data.count;
                    if (hasQty && qtyElem) { qtyElem.innerText = currentQty - 1; if (currentQty - 1 === 0) alert("تم حجز آخر قطعة!"); }
                    alert('تمت الإضافة للسلة');
                } else { alert(data.message); }
            }).catch(err => { document.body.style.cursor = 'default'; console.error(err); });
        }

        function loadCartItems() {
            let fd = new FormData(); fd.append('action', 'get_cart');
            fetch('api.php', { method: 'POST', body: fd }).then(r => r.json()).then(items => {
                globalCartItems = items; let html = '', invHtml = '', total = 0;
                items.forEach((item, index) => {
                    let price = item.discount_price ? item.discount_price : item.price;
                    total += price * item.qty;
                    let sizeDisplay = item.size ? `<span class="cart-size"> (مقاس: ${item.size})</span>` : '';
                    let sizeForInvoice = item.size ? ` (${item.size})` : '';
                    html += `<div class="cart-item"><img src="uploads/${item.image}" onclick="previewProduct(${index})"><div class="cart-details"><div class="cart-name">${item.name} ${sizeDisplay}</div><div class="cart-price">${price} ر.ي</div></div><div class="cart-actions"><div class="qty-group"><button class="qty-btn" onclick="updateQty(${item.cart_id}, 'increase')">+</button><span class="qty-num">${item.qty}</span><button class="qty-btn" onclick="updateQty(${item.cart_id}, 'decrease')">-</button></div><div class="tools-group"><button class="tool-btn view" onclick="previewProduct(${index})"><i class="fa-solid fa-eye"></i></button><button class="tool-btn delete" onclick="removeFromCart(${item.cart_id})"><i class="fa-solid fa-trash-can"></i></button></div></div></div>`;
                    invHtml += `<tr><td style="padding:5px;">${item.name} ${sizeForInvoice}</td><td>${price}</td><td>${item.qty}</td></tr>`;
                });
                if (items.length === 0) html = `<div style="text-align:center; padding:40px 20px; color:#888;"><i class="fa-solid fa-cart-arrow-down" style="font-size:3rem; margin-bottom:10px; color:#ddd;"></i><p>السلة فارغة</p></div>`;
                document.getElementById('cartItems').innerHTML = html; document.getElementById('invBody').innerHTML = invHtml; document.getElementById('invTotal').innerText = total;
            });
        }

        function updateQty(id, op) { let fd = new FormData(); fd.append('action', 'update_cart_qty'); fd.append('cart_id', id); fd.append('operation', op); fetch('api.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if (d.status === 'success') { loadCartItems(); document.getElementById('cartCount').innerText = d.count; } else { alert(d.message); } }); }
        function removeFromCart(id) { let fd = new FormData(); fd.append('action', 'remove_from_cart'); fd.append('cart_id', id); fetch('api.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ loadCartItems(); document.getElementById('cartCount').innerText = d.count; }); }

        function previewProduct(index) {
            let item = globalCartItems[index]; if (!item) return;
            let price = item.discount_price ? item.discount_price : item.price;
            let desc = item.description ? item.description.replace(/\n/g, "<br>") : "لا يوجد وصف.";
            let sizeHtml = item.size ? `<span style="display:block; font-size:0.9rem; color:#1a2a3a; margin-bottom:5px;">المقاس: <b style="color:#d00000">${item.size}</b></span>` : '';
            let html = `<div class="preview-container"><div class="preview-img-box"><img src="uploads/${item.image}"></div><div class="preview-title">${item.name}</div>${sizeHtml}<div class="preview-price">${price} ر.ي</div><div class="preview-desc"><strong>📝 الوصف:</strong><br>${desc}</div><div class="preview-actions"><button onclick="document.getElementById('previewModal').style.display='none'" class="btn-modal btn-close-action">إغلاق</button><button onclick="removeFromCart(${item.cart_id}); document.getElementById('previewModal').style.display='none';" class="btn-modal btn-remove-action"><i class="fa-solid fa-trash-can"></i> حذف</button></div></div>`;
            document.getElementById('previewContent').innerHTML = html; document.getElementById('previewModal').style.display = 'flex';
        }

        function checkout() {
            if (globalCartItems.length === 0) { alert("السلة فارغة!"); return; }
            let name = document.getElementById('custName').value.trim();
            let phone = document.getElementById('custPhone').value.trim();
            let address = document.getElementById('custAddress').value.trim();
            let notes = document.getElementById('custNotes').value.trim();
            if(name.split(' ').length < 2) { alert('اكتب الاسم الثنائي'); return; }
            if(address.length < 2) { alert('يرجى كتابة العنوان'); return; }
            
            let btn = document.getElementById('btnCheckout');
            let orgText = btn.innerText; btn.disabled = true; btn.innerText = "جاري المعالجة...";
            
            let fd = new FormData(); fd.append('action', 'checkout'); fd.append('name', name); fd.append('phone', phone); fd.append('address', address); fd.append('notes', notes);
            const myPhoneNumber = "967738183179"; 

            fetch('api.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
                if(d.status==='success'){
                    document.getElementById('invName').innerText = name; document.getElementById('invPhone').innerText = phone; document.getElementById('invAddress').innerText = address; document.getElementById('invId').innerText = d.invoice_code;
                    document.getElementById('invoice-area').style.display='block';
                    html2canvas(document.getElementById('invoice-area')).then(canvas => {
                        let imgData = canvas.toDataURL('image/png');
                        let uploadFd = new FormData(); uploadFd.append('action', 'save_invoice_image'); uploadFd.append('image', imgData);
                        fetch('api.php', {method:'POST', body:uploadFd}).then(res => res.json()).then(resData => {
                            if(resData.status === 'success') {
                                let currentUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/'));
                                let fileUrl = currentUrl + "/invoices/" + resData.file;
                                let msg = `طلب جديد: ${d.invoice_code}\n👤 ${name}\n📱 ${phone}\n📍 ${address}\n📝 ${notes}\n📄 الفاتورة: ${fileUrl}`;
                                let whatsappUrl = `https://wa.me/${myPhoneNumber}?text=${encodeURIComponent(msg)}`;
                                let link = document.createElement('a'); 
                                link.download = 'SAM_Invoice_' + Date.now() + '.png';
                                link.href = imgData; link.click();
                                window.open(whatsappUrl, '_blank');
                                setTimeout(() => { window.location.reload(); }, 1000);
                            }
                        });
                        document.getElementById('invoice-area').style.display='none';
                    });
                } else { alert(d.message); btn.disabled = false; btn.innerText = orgText; }
            }).catch(e => { console.error(e); alert("خطأ"); btn.disabled = false; btn.innerText = orgText; });
        }

        function toggleFav(btn, pid) {
            let icon = btn.querySelector('i');
            let fd = new FormData(); fd.append('action', 'toggle_favorite'); fd.append('product_id', pid);
            fetch('api.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
                if (data.status === 'added') { btn.classList.add('active'); icon.classList.remove('fa-regular'); icon.classList.add('fa-solid'); icon.classList.add('fa-heart'); } 
                else { btn.classList.remove('active'); icon.classList.remove('fa-solid'); icon.classList.add('fa-regular'); icon.classList.add('fa-heart'); }
            });
        }
        function getLocation() {
        let addressField = document.getElementById("custAddress");
        
        if (navigator.geolocation) {
            addressField.value = "جاري جلب الموقع... 📡";
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
</body>
</html>