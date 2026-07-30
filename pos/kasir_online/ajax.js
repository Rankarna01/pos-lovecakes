document.addEventListener('alpine:init', () => {
    Alpine.data('posOnlineApp', () => ({
        // Orders Data
        orders: [],
        grabConfig: {},
        isLoading: false,
        isPolling: true,
        pollingInterval: null,
        soundEnabled: true,

        // Filters
        channelFilter: 'all', // 'all', 'grab', 'gojek', 'wa_delivery'
        statusFilter: 'new',  // 'new', 'cooking', 'ready', 'completed', 'cancelled'
        searchQuery: '',

        // Detail Modal State
        selectedOrder: null,
        showDetailModal: false,

        // Manual Order Drawer & Catalog State
        showManualModal: false,
        products: [],
        posSettings: {},
        manualSearch: '',
        manualChannel: 'wa_delivery',
        cart: [],
        customerName: '',
        notes: '',
        shippingCost: 0,
        paymentMethod: 'app',

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../auth/index.php'; return; }
            }

            await this.loadProducts();
            await this.fetchOrders();

            // Setup 10-second Polling for Live Feed
            this.pollingInterval = setInterval(() => {
                if (this.isPolling) {
                    this.fetchOrders(true);
                }
            }, 10000);
        },

        async loadProducts() {
            if (window.dbAuth) {
                this.products = await window.dbAuth.getItem('katalog_produk') || [];
                this.posSettings = await window.dbAuth.getItem('pos_settings') || {};
            }
        },

        async fetchOrders(isSilent = false) {
            if (!isSilent) this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=get_orders&channel=${this.channelFilter}&status=${this.statusFilter}`);
                const result = await response.json();

                if (result.status === 'success') {
                    const previousCount = this.orders.filter(o => o.order_status === 'new').length;
                    const newOrdersList = result.orders || [];
                    const newCount = newOrdersList.filter(o => o.order_status === 'new').length;

                    // Play sound if new incoming orders arrive
                    if (isSilent && newCount > previousCount && this.soundEnabled) {
                        this.playNotificationSound();
                    }

                    this.orders = newOrdersList;
                    this.grabConfig = result.grab_config || {};
                }
            } catch (e) {
                console.error("Gagal mengambil data pesanan online:", e);
            } finally {
                if (!isSilent) this.isLoading = false;
            }
        },

        playNotificationSound() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.4);
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.4);
            } catch (err) {}
        },

        setChannelFilter(c) {
            this.channelFilter = c;
            this.fetchOrders();
        },

        setStatusFilter(s) {
            this.statusFilter = s;
            this.fetchOrders();
        },

        get filteredOrders() {
            let list = this.orders;
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(o => 
                    (o.invoice_no && o.invoice_no.toLowerCase().includes(q)) ||
                    (o.external_order_id && o.external_order_id.toLowerCase().includes(q)) ||
                    (o.customer_name && o.customer_name.toLowerCase().includes(q)) ||
                    (o.driver_name && o.driver_name.toLowerCase().includes(q))
                );
            }
            return list;
        },

        // Counts per status pipeline
        getOrderCount(status) {
            return this.orders.filter(o => (o.order_status || 'new') === status).length;
        },

        async updateOrderStatus(orderId, newStatus, statusLabel = '') {
            const confirmMsg = statusLabel ? `Ubah status pesanan ke '${statusLabel}'?` : `Proses pesanan ini?`;
            
            window.customConfirm(confirmMsg, async () => {
                this.isLoading = true;
                try {
                    const response = await fetch('logic.php?action=update_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order_id: orderId, status: newStatus })
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Diperbarui!',
                            text: result.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        if (this.showDetailModal && this.selectedOrder && this.selectedOrder.id == orderId) {
                            this.selectedOrder.order_status = newStatus;
                        }
                        await this.fetchOrders(true);
                    } else {
                        Swal.fire('Gagal!', result.message, 'error');
                    }
                } catch (e) {
                    Swal.fire('Error!', 'Gagal memperbarui status.', 'error');
                } finally {
                    this.isLoading = false;
                }
            });
        },

        async simulateOrder(channelName = 'grab') {
            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=simulate_incoming&channel=${channelName}`);
                const result = await response.json();
                if (result.status === 'success') {
                    this.playNotificationSound();
                    Swal.fire({
                        icon: 'success',
                        title: 'Simulasi Berhasil!',
                        text: result.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    this.statusFilter = 'new';
                    await this.fetchOrders();
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal membuat simulasi pesanan.', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        openDetail(order) {
            this.selectedOrder = order;
            this.showDetailModal = true;
        },

        printReceipt(invoiceNo) {
            window.open(`print_receipt.php?invoice=${invoiceNo}&auto_print_usb=1`, '_blank', 'width=400,height=600');
        },

        // Manual Order Drawer Logic
        get filteredProducts() {
            if (!this.manualSearch.trim()) return this.products;
            const q = this.manualSearch.toLowerCase();
            return this.products.filter(p => p.name.toLowerCase().includes(q));
        },

        calculateMarkupPrice(product) {
            let basePrice = parseFloat(product.offline_price || product.price || 0);
            let markupPercent = 0;
            if (this.manualChannel === 'grab') markupPercent = parseFloat(this.posSettings.markup_grab || 0);
            if (this.manualChannel === 'gojek') markupPercent = parseFloat(this.posSettings.markup_gojek || 0);

            if (markupPercent > 0) {
                return basePrice + ((basePrice * markupPercent) / 100);
            }
            return parseFloat(product.online_price) > 0 ? parseFloat(product.online_price) : basePrice;
        },

        addToCart(product) {
            const price = this.calculateMarkupPrice(product);
            const existing = this.cart.find(item => item.id === product.id && !item.is_custom);

            if (existing) {
                existing.qty++;
                existing.subtotal = existing.qty * existing.price;
            } else {
                this.cart.push({ id: product.id, name: product.name, price: price, qty: 1, subtotal: price, is_custom: false });
            }
        },

        updateQty(index, change) {
            this.cart[index].qty += change;
            if (this.cart[index].qty <= 0) this.cart.splice(index, 1);
            else this.cart[index].subtotal = this.cart[index].qty * this.cart[index].price;
        },

        removeItem(index) { this.cart.splice(index, 1); },

        get subtotal() { return this.cart.reduce((sum, item) => sum + item.subtotal, 0); },
        get totalAmount() { return this.subtotal + parseFloat(this.shippingCost || 0); },

        async processManualCheckout() {
            if (this.cart.length === 0) return;
            this.isLoading = true;

            const payload = {
                channel: this.manualChannel,
                customer_name: this.customerName || 'Pelanggan Online',
                notes: this.notes,
                subtotal: this.subtotal,
                shipping_cost: parseFloat(this.shippingCost || 0),
                total_amount: this.totalAmount,
                payment_method: this.paymentMethod,
                amount_paid: this.totalAmount,
                change_amount: 0,
                items: this.cart
            };

            try {
                const response = await fetch('logic.php?action=checkout', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.status === 'success') {
                    Swal.fire('Berhasil!', 'Pesanan Manual Online Berhasil Dicatat!', 'success');
                    this.showManualModal = false;
                    this.resetCart();
                    this.statusFilter = 'new';
                    await this.fetchOrders();
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal memproses checkout manual.', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        resetCart() {
            this.cart = [];
            this.customerName = '';
            this.notes = '';
            this.shippingCost = 0;
            this.paymentMethod = 'app';
        },

        formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(angka || 0);
        },

        formatTime(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
        }
    }));
});