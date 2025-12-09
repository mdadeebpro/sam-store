document.addEventListener('DOMContentLoaded', () => {
    // --- Element Selections ---
    const cartIcon = document.querySelector('.cart-icon');
    const cartCountElement = document.getElementById('cart-count');
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');

    // Cart Modal
    const cartModal = document.getElementById('cart-modal');
    const closeCartModalButton = cartModal.querySelector('.close-button');
    const cartItemsContainer = document.getElementById('cart-items-container');
    const checkoutButton = document.getElementById('checkout-btn');
    const customerNameInput = document.getElementById('customer-name');
    const customerPhoneInput = document.getElementById('customer-phone');

    // Preview Modal
    const previewModal = document.getElementById('preview-modal');
    const closePreviewModalButton = previewModal.querySelector('.preview-close');
    const previewContent = document.getElementById('preview-content');

    // --- State ---
    let cart = JSON.parse(localStorage.getItem('sam_store_cart')) || {};

    // --- Core Cart Functions ---
    function updateCartCount() {
        const totalItems = Object.values(cart).reduce((sum, item) => sum + item.quantity, 0);
        cartCountElement.textContent = totalItems;
    }

    function saveCart() {
        localStorage.setItem('sam_store_cart', JSON.stringify(cart));
    }

    function clearCart() {
        cart = {};
        saveCart();
        updateCartCount();
        displayCartItems();
    }

    function addToCart(productId) {
        if (cart[productId]) {
            cart[productId].quantity++;
        } else {
            cart[productId] = { quantity: 1 };
        }
        saveCart();
        updateCartCount();
        alert('تمت إضافة المنتج إلى السلة!');
    }

    function removeFromCart(productId) {
        if (cart[productId]) {
            delete cart[productId];
            saveCart();
            updateCartCount();
            displayCartItems(); // Refresh the cart display
        }
    }

    // --- Display Functions ---
    async function displayCartItems() {
        // ... (this function remains the same as before)
        cartItemsContainer.innerHTML = 'جاري تحميل المنتجات...';
        const productIds = Object.keys(cart);

        if (productIds.length === 0) {
            cartItemsContainer.innerHTML = '<p>سلة المشتريات فارغة.</p>';
            return;
        }

        try {
            const response = await fetch('cart_details.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(productIds)
            });
            const products = await response.json();

            if (products.error) { throw new Error(products.error); }

            cartItemsContainer.innerHTML = ''; // Clear loading message
            products.forEach(product => {
                const cartItem = cart[product.id];
                const itemHtml = `
                    <div class="cart-item" data-product-id="${product.id}">
                        <img src="images/${product.image}" alt="${product.name}">
                        <div class="cart-item-info">
                            <h4>${product.name}</h4>
                            <p>الكمية: ${cartItem.quantity}</p>
                            <p>السعر: ${product.sale_price || product.price}</p>
                        </div>
                        <div class="cart-item-actions">
                            <button class="preview-btn">معاينة</button>
                            <button class="remove-from-cart-btn">حذف</button>
                        </div>
                    </div>`;
                cartItemsContainer.innerHTML += itemHtml;
            });

        } catch (error) {
            cartItemsContainer.innerHTML = '<p>حدث خطأ أثناء تحميل بيانات السلة.</p>';
            console.error('Error fetching cart details:', error);
        }
    }

    async function showProductPreview(productId) {
        // ... (this function remains the same as before)
        previewContent.innerHTML = 'جاري تحميل المنتج...';
        previewModal.style.display = 'block';
        try {
            const response = await fetch(`product_details.php?id=${productId}`);
            const product = await response.json();

            if (product.error) { throw new Error(product.error); }

            previewContent.innerHTML = `
                <img src="images/${product.image}" alt="${product.name}" style="width:100%; border-radius:5px;">
                <h2>${product.name}</h2>
                <p>${product.description || 'لا يوجد وصف متاح.'}</p>
                <div class="price">
                     ${product.sale_price ?
                        `<span class="current-price">${product.sale_price}</span> <span class="original-price"><s>${product.price}</s></span>` :
                        `<span class="current-price">${product.price}</span>`
                     }
                </div>
                <p>الكمية المتاحة: ${product.quantity}</p>
                <div class="preview-actions" data-product-id="${product.id}">
                    <button class="remove-from-cart-btn">إزالة المنتج من السلة</button>
                    <button class="preview-close">خروج</button>
                </div>
            `;
        } catch (error) {
            previewContent.innerHTML = `<p>حدث خطأ: ${error.message}</p>`;
        }
    }

    // --- Checkout Function ---
    async function handleCheckout() {
        const customerName = customerNameInput.value.trim();
        if (!customerName) {
            alert('الرجاء إدخال اسم المستلم.');
            return;
        }

        if (Object.keys(cart).length === 0) {
            alert('سلة المشتريات فارغة!');
            return;
        }

        const orderData = {
            customer_name: customerName,
            customer_phone: customerPhoneInput.value.trim(),
            cart: cart
        };

        try {
            checkoutButton.textContent = 'جاري المعالجة...';
            checkoutButton.disabled = true;

            const response = await fetch('checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });

            const result = await response.json();

            if (result.success) {
                alert('تم إرسال طلبك بنجاح! سيتم الآن تحميل الفاتورة.');
                // Trigger invoice download
                window.location.href = `generate_invoice.php?order_id=${result.order_id}`;

                // Clear cart and close modal
                clearCart();
                cartModal.style.display = 'none';
                customerNameInput.value = '';
                customerPhoneInput.value = '';

            } else {
                throw new Error(result.error || 'حدث خطأ غير معروف.');
            }

        } catch (error) {
            alert(`فشلت عملية الشراء: ${error.message}`);
        } finally {
            checkoutButton.textContent = 'إتمام الشراء';
            checkoutButton.disabled = false;
        }
    }

    // --- Event Listeners ---
    addToCartButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const productId = e.target.dataset.productId;
            addToCart(productId);
        });
    });

    checkoutButton.addEventListener('click', handleCheckout);

    cartIcon.addEventListener('click', () => {
        cartModal.style.display = 'block';
        displayCartItems();
    });

    // ... (rest of the event listeners for modals and dynamic buttons remain the same)
    [closeCartModalButton, closePreviewModalButton].forEach(btn => {
        btn.addEventListener('click', () => {
            cartModal.style.display = 'none';
            previewModal.style.display = 'none';
        });
    });

    window.addEventListener('click', (e) => {
        if (e.target == cartModal) cartModal.style.display = 'none';
        if (e.target == previewModal) previewModal.style.display = 'none';
    });

    cartItemsContainer.addEventListener('click', (e) => {
        const itemElement = e.target.closest('.cart-item');
        if (!itemElement) return;
        const productId = itemElement.dataset.productId;

        if (e.target.classList.contains('remove-from-cart-btn')) {
            removeFromCart(productId);
        } else if (e.target.classList.contains('preview-btn')) {
            showProductPreview(productId);
        }
    });

    previewContent.addEventListener('click', (e) => {
        const actionsDiv = e.target.closest('.preview-actions');
        if (!actionsDiv) return;
        const productId = actionsDiv.dataset.productId;

        if (e.target.classList.contains('remove-from-cart-btn')) {
            removeFromCart(productId);
            previewModal.style.display = 'none';
        } else if (e.target.classList.contains('preview-close')) {
            previewModal.style.display = 'none';
        }
    });

    // --- Initial Setup ---
    updateCartCount();
});
