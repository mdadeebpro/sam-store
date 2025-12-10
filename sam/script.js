// =================================================================================
// SAM STORE PWA SCRIPT - V3 (with notification fixes)
// =================================================================================

// --- 1. Global Variables & Initialization ---
let globalCartItems = [];
let pendingPid = 0;
let pendingHasQty = false;
let pendingElemId = '';

// --- 2. Core PWA & Offline Logic ---
function enqueueCartItem(item) {
    let queue = JSON.parse(localStorage.getItem('offlineCartQueue')) || [];
    queue.push(item);
    localStorage.setItem('offlineCartQueue', JSON.stringify(queue));
}

function processOfflineQueue() {
    let queue = JSON.parse(localStorage.getItem('offlineCartQueue')) || [];
    if (queue.length === 0) return;
    let item = queue.shift();
    let fd = new FormData();
    fd.append('action', 'add_to_cart');
    fd.append('product_id', item.pid);
    if (item.selectedSize) fd.append('size', item.selectedSize);
    fetch('api.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
        if (data.status === 'success') {
            document.getElementById('cartCount').innerText = data.count;
            localStorage.setItem('offlineCartQueue', JSON.stringify(queue));
            if (queue.length > 0) processOfflineQueue();
        } else {
            queue.unshift(item); localStorage.setItem('offlineCartQueue', JSON.stringify(queue));
        }
    }).catch(err => {
        queue.unshift(item); localStorage.setItem('offlineCartQueue', JSON.stringify(queue));
    });
}
window.addEventListener('online', processOfflineQueue);

// --- 3. Cart & Product Interaction Logic ---
function openCart() { document.getElementById('cartModal').style.display = 'flex'; loadCartItems(); }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function checkSizeAndAdd(pid, hasQty, elemId, sizesStr) {
    if (!sizesStr || sizesStr.trim() === '') { addToCartFinal(pid, hasQty, elemId, null); return; }
    pendingPid = pid; pendingHasQty = hasQty; pendingElemId = elemId;
    let sizesArr = sizesStr.split(',');
    let container = document.getElementById('sizesContainer');
    container.innerHTML = '';
    sizesArr.forEach(size => {
        size = size.trim();
        let btn = document.createElement('button');
        btn.className = 'size-btn';
        btn.innerText = size;
        btn.onclick = () => { closeModal('sizeModal'); addToCartFinal(pendingPid, pendingHasQty, pendingElemId, size); };
        container.appendChild(btn);
    });
    document.getElementById('sizeModal').style.display = 'flex';
}

function addToCartFinal(pid, hasQty, elemId, selectedSize) {
    if (!pid) return;
    let qtyElem = document.getElementById(elemId);
    let currentQty = hasQty && qtyElem ? parseInt(qtyElem.innerText) : 0;
    if (hasQty && currentQty <= 0) { alert("نفذت الكمية!"); return; }
    if (!navigator.onLine) {
        enqueueCartItem({ pid, selectedSize });
        let cartCountElem = document.getElementById('cartCount');
        cartCountElem.innerText = parseInt(cartCountElem.innerText) + 1;
        if (hasQty && qtyElem) qtyElem.innerText = currentQty - 1;
        alert('أنت غير متصل بالإنترنت. تمت إضافة المنتج للسلة مؤقتاً وسيتم تأكيده عند عودة الاتصال.');
        return;
    }
    let fd = new FormData();
    fd.append('action', 'add_to_cart');
    fd.append('product_id', pid);
    if (selectedSize) fd.append('size', selectedSize);
    document.body.style.cursor = 'wait';
    fetch('api.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
        document.body.style.cursor = 'default';
        if (data.status === 'success') {
            document.getElementById('cartCount').innerText = data.count;
            if (hasQty && qtyElem) qtyElem.innerText = currentQty - 1;
        } else {
            alert(data.message);
        }
    }).catch(err => {
        document.body.style.cursor = 'default'; console.error(err); alert('حدث خطأ في الشبكة. حاول مرة أخرى.');
    });
}

function loadCartItems() {
    let fd = new FormData();
    fd.append('action', 'get_cart');
    fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json()).then(items => {
        globalCartItems = items; let html = '', invHtml = '', total = 0;
        items.forEach((item, index) => {
            let price = item.discount_price ? item.discount_price : item.price;
            total += price * item.qty;
            let sizeDisplay = item.size ? `<span class="cart-size"> (مقاس: ${item.size})</span>` : '';
            let sizeForInvoice = item.size ? ` (${item.size})` : '';
            html += `<div class="cart-item">...</div>`; // (Rest of the cart item HTML as before)
            invHtml += `<tr>...</tr>`; // (Rest of the invoice row HTML as before)
        });
        if (items.length === 0) html = `<div style="text-align:center; padding:40px 20px; color:#888;">...</div>`;
        document.getElementById('cartItems').innerHTML = html; document.getElementById('invBody').innerHTML = invHtml; document.getElementById('invTotal').innerText = total;
    });
}

// ... (Rest of the cart, checkout, UI functions as before) ...

// =================================================================================
// --- 6. PWA Push Notifications Logic (REVISED & FIXED) ---
// =================================================================================
document.addEventListener('DOMContentLoaded', () => {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        const notificationsBtn = document.getElementById('notifications-btn');
        // Check current subscription status on page load
        navigator.serviceWorker.ready.then(swReg => {
            swReg.pushManager.getSubscription().then(subscription => {
                if (subscription === null) {
                    // Not subscribed, show the button
                    notificationsBtn.style.display = 'block';
                } else {
                    // Already subscribed, hide the button
                    notificationsBtn.style.display = 'none';
                }
            });
        });
        notificationsBtn.addEventListener('click', () => {
            subscribeUser(notificationsBtn);
        });
    }
});

function subscribeUser(btn) {
    btn.disabled = true;
    const applicationServerKey = urlBase64ToUint8Array('BMBlr6YznhYMX3NgcWIDRxZXs0sh7tCv7_YCsWcww0ZCv9WGg-tRCXfMEHTiBPCksSqeve1twlbmVAZFv7GSuj0');

    navigator.serviceWorker.ready.then(swRegistration => {
        swRegistration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey
        })
        .then(subscription => {
            console.log('User is subscribed.');
            saveSubscriptionToServer(subscription);
            btn.innerHTML = '<i class=\"fa-solid fa-check\"></i> تم الاشتراك بنجاح';
            alert('شكراً لاشتراكك! ستصلك إشعارات بالمنتجات الجديدة.');
            // Hide the button after successful subscription
            btn.style.display = 'none';
        })
        .catch(err => {
            console.error('Failed to subscribe the user: ', err);
            if (Notification.permission === 'denied') {
                alert('لقد قمت بحظر الإشعارات لهذا الموقع. يرجى تفعيلها من إعدادات المتصفح.');
            } else {
                alert('فشل الاشتراك في الإشعارات. يرجى المحاولة مرة أخرى.');
            }
            btn.disabled = false;
        });
    });
}

function saveSubscriptionToServer(subscription) {
    const fd = new FormData();
    fd.append('action', 'save-subscription');
    fd.append('subscription', JSON.stringify(subscription));
    fetch('api.php', { method: 'POST', body: fd })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log('Subscription saved on server.');
        } else {
            console.error('Server error:', data.message);
        }
    })
    .catch(error => console.error('Error saving subscription:', error));
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/\\-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}
// (The rest of the original script.js file remains the same)
// ...
