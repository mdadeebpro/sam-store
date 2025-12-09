<?php
// api.php
include 'db.php';

$action = $_POST['action'] ?? '';

if ($action == 'add_to_cart') {
    $pid = $_POST['product_id'] ?? 0;
    $selectedSize = $_POST['size'] ?? null; // <--- المقاس المختار

    // التحقق من المنتج
    $stmt = $pdo->prepare("SELECT id, quantity, sizes FROM products WHERE id = ?");
    $stmt->execute([$pid]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prod) { echo json_encode(['status'=>'error', 'message'=>'المنتج غير موجود']); exit; }

    // التحقق: إذا كان المنتج له مقاسات، هل أرسل الزبون مقاساً؟
    if ($prod['sizes'] && empty($selectedSize)) {
        echo json_encode(['status'=>'error', 'message'=>'يرجى اختيار المقاس']); exit;
    }
    // إذا المنتج ليس له مقاسات، نجعل المقاس المحفوظ NULL
    if (!$prod['sizes']) { $selectedSize = null; }

    // حساب المحجوز (بشكل عام لهذا المنتج بغض النظر عن المقاس)
    $resStmt = $pdo->prepare("SELECT SUM(qty) as total_reserved FROM cart WHERE product_id = ?");
    $resStmt->execute([$pid]);
    $totalReserved = $resStmt->fetchColumn() ?: 0;

    // فحص توفر الكمية
    $allowAdd = false;
    if ($prod['quantity'] === null) {
        $allowAdd = true;
    } else {
        if (($prod['quantity'] - $totalReserved) > 0) $allowAdd = true;
    }

    if ($allowAdd) {
        // البحث في السلة: يجب أن يطابق المنتج + المقاس
        // نستخدم شرط المقاس (إما يساويه أو كلاهما NULL)
        $checkSql = "SELECT id FROM cart WHERE session_id = ? AND product_id = ? AND (size = ? OR (size IS NULL AND ? IS NULL))";
        $check = $pdo->prepare($checkSql);
        $check->execute([$user_session, $pid, $selectedSize, $selectedSize]);
        $cartItem = $check->fetch(PDO::FETCH_ASSOC);

        if ($cartItem) {
            $pdo->prepare("UPDATE cart SET qty = qty + 1 WHERE id = ?")->execute([$cartItem['id']]);
        } else {
            $pdo->prepare("INSERT INTO cart (session_id, product_id, qty, size) VALUES (?, ?, 1, ?)")
                ->execute([$user_session, $pid, $selectedSize]);
        }
        echo json_encode(['status' => 'success', 'count' => getCartCount($pdo, $user_session)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'نفذت الكمية']);
    }
    exit;
}
// --- جلب السلة ---
if ($action == 'get_cart') {
    // تمت إضافة c.size للاستعلام
    $stmt = $pdo->prepare("
        SELECT c.id as cart_id, c.qty, c.size, p.id as pid, p.name, p.price, p.discount_price, p.image, p.description 
        FROM cart c JOIN products p ON c.product_id = p.id 
        WHERE c.session_id = ?");
    $stmt->execute([$user_session]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($items);
    exit;
}

// --- حذف من السلة ---
if ($action == 'remove_from_cart') {
    $cart_id = $_POST['cart_id'];
    $pdo->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?")->execute([$cart_id, $user_session]);
    echo json_encode(['status' => 'success', 'count' => getCartCount($pdo, $user_session)]);
}

// --- إتمام الشراء (Checkout) ---
if ($action == 'checkout') {
    $name = $_POST['name'];
    
    $pdo->beginTransaction();
    try {
        // 1. حساب الإجمالي
        $total = 0;
        $stmt = $pdo->prepare("SELECT c.product_id, c.qty, p.name, c.size, p.price, p.discount_price 
                               FROM cart c JOIN products p ON c.product_id = p.id 
                               WHERE c.session_id = ?");
        $stmt->execute([$user_session]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($items) == 0) throw new Exception("السلة فارغة");

        foreach ($items as $item) {
            $price = $item['discount_price'] ? $item['discount_price'] : $item['price'];
            $total += $price * $item['qty'];
        }

        $invCode = date('Y') . rand(10000, 99999);
        // استقبال المتغيرات الجديدة
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $notes = $_POST['notes'];

        // الاستعلام المحدث
        $ordSql = "INSERT INTO orders (invoice_code, customer_name, customer_phone, address, notes, total_amount) VALUES (?, ?, ?, ?, ?, ?)";
        $pdo->prepare($ordSql)->execute([$invCode, $name, $phone, $address, $notes, $total]);
        $orderId = $pdo->lastInsertId();

        // 3. حفظ تفاصيل المنتجات وخصم الكمية
        foreach ($items as $item) {
            $finalPrice = $item['discount_price'] ? $item['discount_price'] : $item['price'];
            
            // إضافة لجدول order_items
            $itemSql = "INSERT INTO order_items (order_id, product_name, price, qty, size) VALUES (?, ?, ?, ?, ?)";
            $pdo->prepare($itemSql)->execute([$orderId, $item['name'], $finalPrice, $item['qty'], $item['size'] ?? null]); // <--- تم إضافة الحجم

            // خصم الكمية من المخزون
            $chk = $pdo->prepare("SELECT quantity FROM products WHERE id = ?");
            $chk->execute([$item['product_id']]);
            $prodData = $chk->fetch();

            if ($prodData['quantity'] !== null) {
                $update = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
                $update->execute([$item['qty'], $item['product_id'], $item['qty']]);
                if ($update->rowCount() == 0) throw new Exception("نفذت الكمية لبعض المنتجات");
            }
        }

        // 4. تفريغ السلة
        $pdo->prepare("DELETE FROM cart WHERE session_id = ?")->execute([$user_session]);
        
        $pdo->commit();
        
        // --- هنا النجاح (داخل try) ---
        echo json_encode(['status' => 'success', 'invoice_code' => $invCode]);

    } catch (Exception $e) {
        $pdo->rollBack();
        
        // --- هنا الفشل (داخل catch) ---
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// --- تعديل: تحديث الكمية (زيادة / نقصان) مع التحقق من المخزون ---
if ($action == 'update_cart_qty') {
    $cart_id = $_POST['cart_id'];
    $op = $_POST['operation']; // 'increase' or 'decrease'

    // 1. جلب معلومات المنتج والكمية الحالية في السلة ومعلومات المخزون
    // نستخدم JOIN لنجلب كمية المنتج الأصلية + كمية السلة الحالية
    $stmt = $pdo->prepare("
        SELECT c.qty as my_qty, c.product_id, p.quantity as max_qty 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.id = ? AND c.session_id = ?
    ");
    $stmt->execute([$cart_id, $user_session]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        if ($op == 'increase') {
            // --- التحقق من المخزون قبل الزيادة ---
            
            // حساب إجمالي المحجوز في كل السلات لهذا المنتج
            $sumStmt = $pdo->prepare("SELECT SUM(qty) as total_reserved FROM cart WHERE product_id = ?");
            $sumStmt->execute([$item['product_id']]);
            $totalReserved = $sumStmt->fetchColumn();

            // إذا كانت الكمية محددة (ليست NULL)
            if ($item['max_qty'] !== null) {
                // هل إجمالي المحجوز وصل للحد الأقصى؟
                if ($totalReserved >= $item['max_qty']) {
                    echo json_encode(['status' => 'error', 'message' => 'عذراً، لقد وصلت للحد الأقصى من الكمية المتوفرة!']);
                    exit;
                }
            }

            // إذا نجح الفحص، قم بالزيادة
            $pdo->prepare("UPDATE cart SET qty = qty + 1 WHERE id = ?")->execute([$cart_id]);

        } elseif ($op == 'decrease') {
            if ($item['my_qty'] > 1) {
                $pdo->prepare("UPDATE cart SET qty = qty - 1 WHERE id = ?")->execute([$cart_id]);
            } else {
                // حذف المنتج إذا وصل للصفر
                $pdo->prepare("DELETE FROM cart WHERE id = ?")->execute([$cart_id]);
            }
        }
        
        echo json_encode(['status' => 'success', 'count' => getCartCount($pdo, $user_session)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'المنتج غير موجود']);
    }
}
// --- 6. حفظ صورة الفاتورة وإرجاع الرابط ---
if ($action == 'save_invoice_image') {
    $img = $_POST['image'];
    $files = glob('invoices/*.png');
    $now = time();
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 518400) {
                unlink($file);
        }
    }
}
    // تنظيف بيانات الصورة (حذف المقدمة data:image/png;base64,)
    $img = str_replace('data:image/png;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $data = base64_decode($img);
    
    // توليد اسم فريد للملف
    $fileName = 'inv_' . time() . '_' . rand(1000,9999) . '.png';
    $filePath = 'invoices/' . $fileName;
    
    // حفظ الملف في المجلد
    if (file_put_contents($filePath, $data)) {
        // إرجاع اسم الملف للجافاسكريبت
        echo json_encode(['status' => 'success', 'file' => $fileName]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'فشل حفظ الصورة']);
    }
    exit;
}
// --- 7. إدارة المفضلة (إضافة / حذف) ---
if ($action == 'toggle_favorite') {
    $pid = $_POST['product_id'];
    
    // فحص هل المنتج موجود في مفضلة هذا المستخدم؟
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE session_id = ? AND product_id = ?");
    $stmt->execute([$user_session, $pid]);
    $fav = $stmt->fetch();
    
    if ($fav) {
        // موجود -> احذفه
        $pdo->prepare("DELETE FROM favorites WHERE id = ?")->execute([$fav['id']]);
        echo json_encode(['status' => 'removed']);
    } else {
        // غير موجود -> أضفه
        $pdo->prepare("INSERT INTO favorites (session_id, product_id) VALUES (?, ?)")->execute([$user_session, $pid]);
        echo json_encode(['status' => 'added']);
    }
    exit;
}

// --- 8. حفظ اشتراك الإشعارات ---
if ($action == 'save-subscription') {
    $subscription_data = $_POST['subscription'];

    // Check if the subscription is valid JSON
    $subscription = json_decode($subscription_data, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($subscription['endpoint'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid subscription data.']);
        exit;
    }

    // Check if the subscription endpoint already exists to avoid duplicates
    $stmt = $pdo->prepare("SELECT id FROM push_subscriptions WHERE subscription LIKE ?");
    $endpoint_search = '%' . $subscription['endpoint'] . '%';
    $stmt->execute([$endpoint_search]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Already exists, no need to insert again
        echo json_encode(['status' => 'success', 'message' => 'Subscription already exists.']);
    } else {
        // Insert the new subscription
        $stmt = $pdo->prepare("INSERT INTO push_subscriptions (subscription) VALUES (?)");
        if ($stmt->execute([$subscription_data])) {
            echo json_encode(['status' => 'success', 'message' => 'Subscription saved.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save subscription.']);
        }
    }
    exit;
}
?>