/**
 * Toko Digital - Frontend JS
 * Vanilla JS (PRD 10.2). Fetch API untuk cart & checkout.
 */

(function () {
    'use strict';

    /**
     * Helper: ambil CSRF token dari meta tag atau form.
     */
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            return meta.getAttribute('content');
        }
        var input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    /**
     * Helper: format Rupiah di sisi client.
     */
    window.formatRupiah = function (amount) {
        return 'Rp' + Number(amount || 0).toLocaleString('id-ID');
    };

    /**
     * Toast sederhana.
     */
    function showToast(message, type) {
        var existing = document.getElementById('app-toast');
        if (existing) {
            existing.remove();
        }

        var toast = document.createElement('div');
        toast.id = 'app-toast';
        toast.setAttribute('role', 'status');
        toast.style.cssText = [
            'position:fixed', 'bottom:20px', 'left:50%', 'transform:translateX(-50%)',
            'background:#1a2332', 'color:#fff', 'padding:10px 18px', 'border-radius:7px',
            'font-size:0.9rem', 'z-index:1090', 'box-shadow:0 6px 20px rgba(26,35,50,.25)',
            'opacity:0', 'transition:opacity 200ms ease', 'max-width:90vw', 'text-align:center'
        ].join(';');

        toast.textContent = message;
        document.body.appendChild(toast);

        requestAnimationFrame(function () {
            toast.style.opacity = '1';
        });

        setTimeout(function () {
            toast.style.opacity = '0';
            setTimeout(function () {
                toast.remove();
            }, 250);
        }, 2800);
    }

    window.showToast = showToast;

    /**
     * Add to cart via fetch.
     */
    document.querySelectorAll('[data-add-to-cart]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var productId = btn.getAttribute('data-add-to-cart');

            btn.disabled = true;
            var originalText = btn.textContent;
            btn.textContent = 'Menambahkan...';

            fetch(appConfig.apiUrl + 'cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'add',
                    product_id: productId,
                    csrf_token: getCsrfToken()
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        showToast(data.message || 'Produk ditambahkan');
                        updateCartBadge(data.data ? data.data.count : null);
                    } else {
                        showToast(data.message || 'Gagal menambahkan produk');
                    }
                })
                .catch(function () {
                    showToast('Koneksi bermasalah');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });
    });

    /**
     * Update badge cart di navbar.
     */
    function updateCartBadge(count) {
        if (count === null || count === undefined) {
            return;
        }
        var badge = document.querySelector('.cart-badge');
        if (count > 0) {
            if (!badge) {
                var link = document.querySelector('a[href$="cart.php"]');
                if (link) {
                    var span = document.createElement('span');
                    span.className = 'cart-badge';
                    span.textContent = count;
                    link.appendChild(span);
                }
            } else {
                badge.textContent = count;
            }
        } else if (badge) {
            badge.remove();
        }
    }

    /**
     * Remove from cart (halaman cart).
     */
    document.querySelectorAll('[data-remove-from-cart]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var productId = btn.getAttribute('data-remove-from-cart');

            fetch(appConfig.apiUrl + 'cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'remove',
                    product_id: productId,
                    csrf_token: getCsrfToken()
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        var row = btn.closest('[data-cart-row]');
                        if (row) {
                            row.classList.add('fade-out');
                            setTimeout(function () {
                                row.remove();
                                updateTotals();
                            }, 180);
                        }
                        updateCartBadge(data.data ? data.data.count : null);
                        showToast(data.message || 'Produk dihapus');
                    } else {
                        showToast(data.message || 'Gagal menghapus produk');
                    }
                })
                .catch(function () {
                    showToast('Koneksi bermasalah');
                });
        });
    });

    /**
     * Recalculate total setelah remove item.
     */
    function updateTotals() {
        var rows = document.querySelectorAll('[data-cart-row]');
        var total = 0;
        rows.forEach(function (row) {
            var price = parseFloat(row.getAttribute('data-price') || '0');
            total += price;
        });

        var totalEl = document.querySelector('[data-cart-total]');
        if (totalEl) {
            totalEl.textContent = window.formatRupiah(total);
        }

        var subtotalEl = document.querySelector('[data-cart-subtotal]');
        if (subtotalEl) {
            subtotalEl.textContent = window.formatRupiah(total);
        }

        // Empty state
        if (rows.length === 0) {
            var empty = document.querySelector('[data-cart-empty]');
            var checkoutBtn = document.querySelector('[data-cart-checkout]');
            if (empty) empty.style.display = '';
            if (checkoutBtn) checkoutBtn.disabled = true;
        }
    }

    /**
     * Polling status pembayaran (halaman payment).
     */
    var statusPoller = document.querySelector('[data-payment-poll]');
    if (statusPoller) {
        var orderNumber = statusPoller.getAttribute('data-payment-poll');
        var attempts = 0;
        var maxAttempts = 120; // ~10 menit @ 5s

        var timer = setInterval(function () {
            attempts++;
            if (attempts > maxAttempts) {
                clearInterval(timer);
                return;
            }

            fetch(appConfig.apiUrl + 'payment-status.php?order_number=' + encodeURIComponent(orderNumber))
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success && data.data.status !== 'pending') {
                        clearInterval(timer);
                        window.location.reload();
                    }
                })
                .catch(function () { /* retry */ });
        }, 5000);
    }

    /**
     * Lazy load images (PRD 53).
     */
    if ('loading' in HTMLImageElement.prototype) {
        document.querySelectorAll('img[loading]').forEach(function (img) {
            // browser native lazy load
        });
    }

})();
