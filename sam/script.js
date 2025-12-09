// =================================================================================
// SAM STORE PWA SCRIPT - V2
// =================================================================================

// --- 1. Global Variables & Initialization ---
let globalCartItems = [];
let pendingPid = 0;
let pendingHasQty = false;
let pendingElemId = '';

// --- 2. Core PWA & Offline Logic ---

// Function to add an item to the offline queue
function enqueueCartItem(item) {
    let queue = JSON.parse(localStorage.getItem('offlineCartQueue')) || [];
    queue.push(item);
    localStorage.setItem('offlineCartQueue', JSON.stringify(queue));
    console.log('Item enqueued for offline submission:', item);
}

// Function to process the offline queue when connection is restored
function processOfflineQueue() {
    let queue = JSON.parse(localStorage.getItem('offlineCartQueue')) || [];
    if (queue.length === 0) return;

    console.log(`Processing ${queue.length} items from offline queue.`);

    // Process items one by one
    let item = queue.shift(); // Get the first item

    let fd = new FormData();
    fd.append('action', 'add_to_cart');
    fd.append('product_id', item.pid);
    if (item.selectedSize) {
        fd.append('size', item.selectedSize);
    }

    fetch('api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                console.log('Successfully submitted queued item:', item);
                // Update cart count from server response
                document.getElementById('cartCount').innerText = data.count;
                // Save the updated queue
                localStorage.setItem('offlineCartQueue', JSON.stringify(queue));
                // Process the next item
                if (queue.length > 0) {
                    processOfflineQueue();
                }
            } else {
                console.error('Failed to submit queued item:', data.message);
                // If it fails, put it back at the start of the queue to retry later
                queue.unshift(item);
                localStorage.setItem('offlineCartQueue', JSON.stringify(queue));
            }
        })
        .catch(err => {
            console.error('Network error during queue processing:', err);
            // Put item back if fetch fails
            queue.unshift(item);
            localStorage.setItem('offlineCartQueue', JSON.stringify(queue));
        });
}

// Listen for the 'online' event to process the queue
window.addEventListener('online', processOfflineQueue);


// --- 3. Cart & Product Interaction Logic ---

function openCart() {
    document.getElementById('cartModal').style.display = 'flex';
    loadCartItems();
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function checkSizeAndAdd(pid, hasQty, elemId, sizesStr) {
    if (!sizesStr || sizesStr.trim() === '') {
        addToCartFinal(pid, hasQty, elemId, null);
        return;
    }

    pendingPid = pid;
    pendingHasQty = hasQty;
    pendingElemId = elemId;

    let sizesArr = sizesStr.split(',');
    let container = document.getElementById('sizesContainer');
    container.innerHTML = '';

    sizesArr.forEach(size => {
        size = size.trim();
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

function addToCartFinal(pid, hasQty, elemId, selectedSize) {
    if (!pid) return;

    let qtyElem = document.getElementById(elemId);
    let currentQty = hasQty && qtyElem ? parseInt(qtyElem.innerText) : 0;
    if (hasQty && currentQty <= 0) { alert("نفذت الكمية!"); return; }

    // --- OFFLINE LOGIC ---
    if (!navigator.onLine) {
        console.log('Offline mode detected. Queuing item.');
        enqueueCartItem({ pid, selectedSize });

        // Optimistic UI update
        let cartCountElem = document.getElementById('cartCount');
        cartCountElem.innerText = parseInt(cartCountElem.innerText) + 1;

        if (hasQty && qtyElem) {
            qtyElem.innerText = currentQty - 1;
        }
        alert('أنت غير متصل بالإنترنت. تمت إضافة المنتج للسلة مؤقتاً وسيتم تأكيده عند عودة الاتصال.');
        return;
    }
    // --- END OFFLINE LOGIC ---

    let fd = new FormData();
    fd.append('action', 'add_to_cart');
    fd.append('product_id', pid);
    if (selectedSize) {
        fd.append('size', selectedSize);
    }

    document.body.style.cursor = 'wait';

    fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        document.body.style.cursor = 'default';
        if(data.status === 'success') {
            document.getElementById('cartCount').innerText = data.count;
            if (hasQty && qtyElem) {
                qtyElem.innerText = currentQty - 1;
            }
        } else {
            alert(data.message);
        }
    })
    .catch(err => {
        document.body.style.cursor = 'default';
        console.error(err);
        alert('حدث خطأ في الشبكة. حاول مرة أخرى.');
    });
}


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

            let sizeDisplay = item.size ? `<span class="cart-size"> (مقاس: ${item.size})</span>` : '';
            let sizeForInvoice = item.size ? ` (${item.size})` : '';

            html += `
            <div class="cart-item">
                <img src="uploads/${item.image}" onclick="previewProduct(${index})">
                <div class="cart-details">
                    <div class="cart-name">${item.name} ${sizeDisplay}</div>
                    <div class="cart-price">${price} ر.ي</div>
                </div>
                <div class="cart-actions">
                    <div class="qty-group">
                        <button class="qty-btn" onclick="updateQty(${item.cart_id}, 'increase')">+</button>
                        <span class="qty-num">${item.qty}</span>
                        <button class="qty-btn" onclick="updateQty(${item.cart_id}, 'decrease')">-</button>
                    </div>
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

            invHtml += `<tr>
                            <td style="padding:5px;">${item.name} ${sizeForInvoice}</td>
                            <td>${price}</td>
                            <td>${item.qty}</td>
                        </tr>`;
        });

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
            alert(d.message);
        }
    });
}

function removeFromCart(id) {
    let fd = new FormData();
    fd.append('action', 'remove_from_cart');
    fd.append('cart_id', id);
    fetch('api.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
        loadCartItems();
        document.getElementById('cartCount').innerText = d.count;
    });
}

function previewProduct(index) {
    let item = globalCartItems[index];
    if (!item) return;

    let price = item.discount_price ? item.discount_price : item.price;
    let desc = item.description ? item.description.replace(/\n/g, "<br>") : "لا يوجد وصف إضافي لهذا المنتج.";
    let sizeHtml = item.size ? `<span style="display:block; font-size:0.9rem; color:#1a2a3a; margin-bottom:5px;">المقاس المختار: <b style="color:#d00000">${item.size}</b></span>` : '';

    let html = `
        <div class="preview-container">
            <div class="preview-img-box">
                <img src="uploads/${item.image}" alt="${item.name}">
            </div>
            <div class="preview-title">${item.name}</div>
            ${sizeHtml}
            <div class="preview-price">${price} ر.ي</div>
            <div class="preview-desc">
                <strong>📝 الوصف:</strong><br>
                ${desc}
            </div>
            <div class="preview-actions">
                <button onclick="closeModal('previewModal')" class="btn-modal btn-close-action">إغلاق</button>
                <button onclick="removeFromCart(${item.cart_id}); closeModal('previewModal');" class="btn-modal btn-remove-action">
                    <i class="fa-solid fa-trash-can"></i> حذف من السلة
                </button>
            </div>
        </div>
    `;
    document.getElementById('previewContent').innerHTML = html;
    document.getElementById('previewModal').style.display = 'flex';
}

function checkout() {
    if (globalCartItems.length === 0) {
        alert("السلة فارغة!"); return;
    }

    let name = document.getElementById('custName').value.trim();
    let phone = document.getElementById('custPhone').value.trim();
    let address = document.getElementById('custAddress').value.trim();
    let notes = document.getElementById('custNotes').value.trim();

    if(name.split(' ').length < 2) { alert('اكتب الاسم الثنائي'); return; }
    if(address.length < 2) { alert('يرجى كتابة العنوان بشكل واضح'); return; }

    let btn = document.getElementById('btnCheckout');
    let orgText = btn.innerText;
    btn.disabled = true;
    btn.innerText = "جاري المعالجة...";

    let fd = new FormData();
    fd.append('action', 'checkout');
    fd.append('name', name);
    fd.append('phone', phone);
    fd.append('address', address);
    fd.append('notes', notes);

    const myPhoneNumber = "967738183179";

    fetch('api.php', {method:'POST', body:fd})
    .then(r=>r.json())
    .then(d=>{
        if(d.status==='success'){
            document.getElementById('invName').innerText = name;
            document.getElementById('invPhone').innerText = phone;
            document.getElementById('invAddress').innerText = address;
            document.getElementById('invId').innerText = d.invoice_code;
            document.getElementById('invoice-area').style.display='block';

            html2canvas(document.getElementById('invoice-area')).then(canvas => {
                let imgData = canvas.toDataURL('image/png');

                let uploadFd = new FormData();
                uploadFd.append('action', 'save_invoice_image');
                uploadFd.append('image', imgData);

                fetch('api.php', {method:'POST', body:uploadFd})
                .then(res => res.json())
                .then(resData => {
                    if(resData.status === 'success') {
                        let currentUrl = window.location.href.substring(0, window.location.href.lastIndexOf('/'));
                        let fileUrl = currentUrl + "/invoices/" + resData.file;
                        let msg = `طلب جديد من: ${name}\n📱 الهاتف: ${phone}\n📍 العنوان: ${address}\n📝 ملاحظات: ${notes}\n📄 الفاتورة: ${fileUrl}`;
                        let whatsappUrl = `https://wa.me/${myPhoneNumber}?text=${encodeURIComponent(msg)}`;

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

// --- 4. UI & UX Functions ---
function toggleCatMenu() {
    let menu = document.getElementById('catsMenu');
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}

window.addEventListener('click', function(e) {
    let menu = document.getElementById('catsMenu');
    let clickedNavItem = e.target.closest('.nav-item');

    if (menu.style.display === 'block' && !menu.contains(e.target) && (!clickedNavItem || !clickedNavItem.onclick)) {
        menu.style.display = 'none';
    }
});

function toggleFav(btn, pid) {
    let icon = btn.querySelector('i');
    let fd = new FormData();
    fd.append('action', 'toggle_favorite');
    fd.append('product_id', pid);

    fetch('api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'added') {
            btn.classList.add('active');
            icon.classList.remove('fa-regular');
            icon.classList.add('fa-solid', 'fa-heart');
        } else {
            btn.classList.remove('active');
            icon.classList.remove('fa-solid');
            icon.classList.add('fa-regular', 'fa-heart');
        }
    })
    .catch(e => console.error(e));
}

function getLocation() {
    let addressField = document.getElementById("custAddress");
    if (navigator.geolocation) {
        addressField.value = "جاري جلب إحداثياتك... 📡";
        document.body.style.cursor = 'wait';
        navigator.geolocation.getCurrentPosition(showPosition, showError, {
            enableHighAccuracy: true,
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
    let googleMapsLink = `https://maps.google.com/?q=${lat},${long}`;
    let field = document.getElementById("custAddress");
    field.value = googleMapsLink;
    field.style.borderColor = "#28a745";
    setTimeout(() => { field.style.borderColor = "#ddd"; }, 2000);
}

function showError(error) {
    document.body.style.cursor = 'default';
    let field = document.getElementById("custAddress");
    field.value = "";
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


// --- 5. Event Listeners & Initializers ---
window.addEventListener("pageshow", function (event) {
  var historyTraversal = event.persisted || (typeof window.performance != "undefined" && window.performance.navigation.type === 2);
  if (historyTraversal) {
    window.location.reload();
  }
});

// Check for and process the offline queue on script load
document.addEventListener('DOMContentLoaded', () => {
    if (navigator.onLine) {
        processOfflineQueue();
    }
});
