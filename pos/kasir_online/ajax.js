document.addEventListener('alpine:init', () => {
    Alpine.data('kasirOnlineApp', () => ({
        activeChannel: 'grabfood',
        selectedStoreId: 1,
        warehouses: [],
        products: [],
        filteredProducts: [],
        categories: [],
        selectedCategory: 'all',
        searchQuery: '',
        foodDeliveryPrices: [],
        platforms: [],
        paymentMethods: [],
        foodDeliveryPaymentMethods: [],
        
        cart: [],
        customerName: '',
        driverName: '',
        driverPhone: '',
        externalOrderId: '',
        notes: '',
        
        showPaymentModal: false,
        showCustomRegulerModal: false,
        showCustomPOModal: false,
        isProcessing: false,
        
        // Form Pembayaran Kasir POS
        paymentStatus: 'lunas',
        paymentMethod: '',
        inputUang: 0,
        paymentReference: '',
        
        customRegulerForm: { name: '', price: 100000 },
        customPOForm: { name: '', price: 150000 },
        lastReceipt: { invoice_no: '', driver_name: '', external_order_id: '', items: [], total_amount: 0, amount_paid: 0, change_amount: 0, payment_method: 'Digital' },

        async init() {
            // Restore draft if any
            const savedDraft = localStorage.getItem('kasir_online_cart');
            if (savedDraft) {
                try { this.cart = JSON.parse(savedDraft); } catch(e) {}
            }

            await this.loadMasterData();
        },

        get activePlatformPaymentMethods() {
            const list = this.foodDeliveryPaymentMethods.filter(m => m.platform_code === this.activeChannel && parseInt(m.is_active) === 1);
            if (list.length > 0) return list;
            return this.paymentMethods.filter(m => m.name.toLowerCase() !== 'cash' && m.name.toLowerCase() !== 'tunai');
        },

        async loadMasterData() {
            try {
                const res = await fetch(`logic.php?action=get_master_data&store_id=${this.selectedStoreId}&nocache=${Date.now()}`);
                const result = await res.json();
                if (result.status === 'success') {
                    this.selectedStoreId = result.store_id || 1;
                    this.warehouses = result.warehouses || [];
                    this.products = result.products || [];
                    this.foodDeliveryPrices = result.food_delivery_prices || [];
                    this.platforms = result.platforms || [];
                    this.paymentMethods = result.payment_methods || [];
                    this.foodDeliveryPaymentMethods = result.food_delivery_payment_methods || [];

                    const available = this.activePlatformPaymentMethods;
                    if (available.length > 0) {
                        this.paymentMethod = available[0].name;
                    }

                    // Extract categories
                    const cats = new Set(this.products.map(p => p.category).filter(Boolean));
                    this.categories = Array.from(cats);

                    this.applyFilters();
                }
            } catch(e) {
                console.error("Gagal memuat master data Kasir Online:", e);
            }
        },

        async switchStore() {
            await this.loadMasterData();
        },

        selectChannel(channelCode) {
            this.activeChannel = channelCode;
            const available = this.activePlatformPaymentMethods;
            if (available.length > 0) {
                this.paymentMethod = available[0].name;
            }
            
            // Recalculate cart items with new channel price
            this.cart.forEach(item => {
                const prod = this.products.find(p => p.id == item.id && (p.item_type || 'product') === (item.item_type || 'product'));
                if (prod) {
                    item.price = this.getProductPlatformPrice(prod);
                    item.subtotal = item.price * item.qty;
                }
            });
            this.applyFilters();
        },

        getProductPlatformPrice(product) {
            if (!product) return 0;
            const base = parseFloat(product.price || product.offline_price || 0);

            if (this.activeChannel && !['wa', 'toko', 'delivery'].includes(this.activeChannel.toLowerCase())) {
                const pCode = this.activeChannel.toLowerCase();
                const targetType = product.item_type || (product.is_custom ? 'custom_reguler' : 'product');
                
                // 1. Cek jika ada override harga spesifik di food_delivery_prices_pos
                const match = (this.foodDeliveryPrices || []).find(fd => 
                    fd.platform_code === pCode && 
                    fd.item_id == product.id && 
                    (fd.item_type === targetType || (!product.is_custom && (!fd.item_type || fd.item_type === 'product'))) &&
                    fd.is_active == 1
                );
                if (match && parseFloat(match.final_price) > 0) {
                    return parseFloat(match.final_price);
                }

                // 2. Fallback markup persentase platform (contoh: 30%) untuk produk katalog
                const platform = (this.platforms || []).find(p => p.platform_code === pCode);
                if (platform && platform.default_markup_percent && parseFloat(platform.default_markup_percent) > 0) {
                    const markup = parseFloat(platform.default_markup_percent);
                    return Math.round(base * (1 + markup / 100));
                }
            }

            return base;
        },

        getChannelMarkupBadge() {
            const platform = (this.platforms || []).find(p => p.platform_code === this.activeChannel);
            if (platform && platform.default_markup_percent) {
                return `+${platform.default_markup_percent}% Markup`;
            }
            return 'Harga Normal / Standard';
        },

        applyFilters() {
            let list = this.products || [];

            // FILTER PER PLATFORM: Produk Katalog selalu muncul, Custom Item hanya yang diatur & aktif per platform
            if (this.activeChannel && !['wa', 'toko', 'delivery'].includes(this.activeChannel.toLowerCase())) {
                const pCode = this.activeChannel.toLowerCase();
                
                const activePlatformItems = (this.foodDeliveryPrices || [])
                    .filter(fd => fd.platform_code === pCode && (fd.is_active == 1 || fd.is_active == '1'));

                const platformMap = {};
                activePlatformItems.forEach(fd => {
                    const type = fd.item_type || 'product';
                    platformMap[type + '_' + fd.item_id] = parseFloat(fd.final_price);
                });

                list = list.filter(p => {
                    const type = p.item_type || (p.is_custom ? 'custom_reguler' : 'product');
                    if (platformMap.hasOwnProperty(type + '_' + p.id)) {
                        return true;
                    }
                    // Produk Katalog selalu tampil dengan harga markup platform
                    if (!p.is_custom) {
                        return true;
                    }
                    return false;
                });
            }

            if (this.searchQuery && this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(p => 
                    (p.name && p.name.toLowerCase().includes(q)) ||
                    (p.code && p.code.toLowerCase().includes(q)) ||
                    (p.category && p.category.toLowerCase().includes(q))
                );
            }

            if (this.selectedCategory !== 'all') {
                list = list.filter(p => p.category === this.selectedCategory);
            }

            this.filteredProducts = list;
        },

        // --- FUNGSI KERANJANG ---
        addToCart(product) {
            const price = this.getProductPlatformPrice(product);
            const type = product.item_type || (product.is_custom ? 'custom_reguler' : 'product');
            const existing = this.cart.find(item => item.id == product.id && (item.item_type || 'product') === type);

            if (existing) {
                existing.qty++;
                existing.subtotal = existing.price * existing.qty;
            } else {
                this.cart.push({
                    id: product.id,
                    name: product.name,
                    price: price,
                    qty: 1,
                    subtotal: price,
                    item_type: type,
                    is_custom: product.is_custom ? true : false
                });
            }
        },

        updateQty(index, delta) {
            this.cart[index].qty += delta;
            if (this.cart[index].qty <= 0) {
                this.cart.splice(index, 1);
            } else {
                this.cart[index].subtotal = this.cart[index].price * this.cart[index].qty;
            }
        },

        removeFromCart(index) {
            this.cart.splice(index, 1);
        },

        get cartSubtotal() {
            return this.cart.reduce((sum, item) => sum + item.subtotal, 0);
        },

        get cartTotal() {
            return this.cartSubtotal;
        },

        get cashSuggestions() {
            const total = this.cartTotal;
            const sugs = [total];
            if (total < 50000) sugs.push(50000);
            if (total < 100000) sugs.push(100000);
            if (total < 200000) sugs.push(200000);
            return sugs;
        },

        // --- CUSTOM ITEMS ---
        openCustomRegulerModal() {
            this.customRegulerForm = { name: '', price: 100000 };
            this.showCustomRegulerModal = true;
        },

        submitCustomReguler() {
            if (!this.customRegulerForm.name.trim()) return;
            const price = parseFloat(this.customRegulerForm.price || 0);

            this.cart.push({
                id: 0,
                name: '[Custom] ' + this.customRegulerForm.name,
                price: price,
                qty: 1,
                subtotal: price,
                item_type: 'custom_reguler',
                is_custom: true
            });
            this.showCustomRegulerModal = false;
        },

        openCustomPOModal() {
            this.customPOForm = { name: '', price: 150000 };
            this.showCustomPOModal = true;
        },

        submitCustomPO() {
            if (!this.customPOForm.name.trim()) return;
            const price = parseFloat(this.customPOForm.price || 0);

            this.cart.push({
                id: 0,
                name: '[Custom PO] ' + this.customPOForm.name,
                price: price,
                qty: 1,
                subtotal: price,
                item_type: 'custom_po',
                is_custom: true,
                is_po: true
            });
            this.showCustomPOModal = false;
        },

        // --- PEMBAYARAN & CHECKOUT ---
        openPaymentModal() {
            if (this.cart.length === 0) return;
            const available = this.activePlatformPaymentMethods;
            if (available.length > 0) {
                if (!available.some(m => m.name === this.paymentMethod)) {
                    this.paymentMethod = available[0].name;
                }
            }
            this.inputUang = this.cartTotal;
            this.showPaymentModal = true;
        },

        setPaymentStatus(status) {
            this.paymentStatus = status;
            if (status === 'lunas') {
                this.inputUang = this.cartTotal;
            }
        },

        async processCheckout() {
            if (this.cart.length === 0 || this.isProcessing) return;

            const paid = parseFloat(this.inputUang || this.cartTotal);
            const change = paid > this.cartTotal ? (paid - this.cartTotal) : 0;

            this.isProcessing = true;
            try {
                const payload = {
                    channel: this.activeChannel,
                    store_id: this.selectedStoreId,
                    customer_name: this.driverName ? `Order ${this.activeChannel.toUpperCase()} (${this.driverName})` : 'Pelanggan Online',
                    driver_name: this.driverName,
                    driver_phone: this.driverPhone,
                    external_order_id: this.externalOrderId,
                    notes: this.notes,
                    payment_method: this.paymentMethod || 'Saldo Merchant',
                    payment_status: this.paymentStatus === 'lunas' ? 'lunas' : 'dp',
                    payment_reference: this.paymentReference,
                    subtotal: this.cartSubtotal,
                    total_amount: this.cartTotal,
                    amount_paid: paid,
                    change_amount: change,
                    items: this.cart
                };

                const res = await fetch(`logic.php?action=checkout&store_id=${this.selectedStoreId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (result.status === 'success') {
                    this.lastReceipt = {
                        invoice_no: result.invoice_no,
                        driver_name: this.driverName,
                        external_order_id: this.externalOrderId,
                        items: [...this.cart],
                        total_amount: this.cartTotal,
                        amount_paid: paid,
                        change_amount: change,
                        payment_method: this.paymentMethod
                    };

                    this.showPaymentModal = false;

                    const invoiceNo = result.invoice_no;

                    Swal.fire({
                        icon: 'success',
                        title: 'Transaksi Online Berhasil!',
                        html: `<div class="text-left text-xs font-bold space-y-1">
                                <p><strong>No Invoice:</strong> ${invoiceNo}</p>
                                <p><strong>Channel:</strong> <span class="uppercase font-black text-emerald-600">${this.activeChannel}</span></p>
                                <p><strong>Total:</strong> Rp ${this.formatRupiah(this.cartTotal)}</p>
                                <p><strong>Bayar:</strong> Rp ${this.formatRupiah(paid)}</p>
                                <p><strong>Kembali:</strong> Rp ${this.formatRupiah(change)}</p>
                               </div>`,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fa-solid fa-print"></i> Cetak Struk',
                        cancelButtonText: 'Selesai'
                    }).then((swalRes) => {
                        if (swalRes.isConfirmed) {
                            this.printReceipt(invoiceNo);
                        }
                    });

                    // Reset cart & form
                    this.cart = [];
                    this.driverName = '';
                    this.driverPhone = '';
                    this.externalOrderId = '';
                    this.notes = '';
                    this.paymentReference = '';
                    
                    // Reload master data to update stocks live!
                    await this.loadMasterData();
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Gagal memproses transaksi online.', 'error');
            } finally {
                this.isProcessing = false;
            }
        },

        // FUNGSI CETAK STRUK PRESISI KASIR OFFLINE (Buka Window thermal print_receipt.php)
        printReceipt(invoiceNo = '') {
            const inv = invoiceNo || this.lastReceipt.invoice_no;
            if (!inv) {
                Swal.fire('Perhatian', 'Nomor invoice tidak ditemukan!', 'warning');
                return;
            }
            const url = `../kasir/print_receipt.php?invoice=${encodeURIComponent(inv)}&auto_print_usb=1`;
            window.open(url, '_blank', 'width=400,height=600');
        },

        formatRupiah(val) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val || 0);
        }
    }));
});