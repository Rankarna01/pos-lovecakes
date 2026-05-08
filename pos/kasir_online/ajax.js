document.addEventListener('alpine:init', () => {
    Alpine.data('posOnlineApp', () => ({
        products: [],
        posSettings: {}, // Berisi markup_grab dll
        
        searchQuery: '',
        isLoading: false,

        // State Order
        channel: 'wa_delivery', 
        cart: [],
        customerName: '',
        notes: '',
        shippingCost: 0,
        paymentMethod: 'app', // Default: Pembayaran via aplikasi (GrabPay/GoPay)
        
        lastInvoice: '',

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../auth/index.php'; return; }
            }
            await this.loadLocalData();
        },

        async loadLocalData() {
            this.isLoading = true;
            if (window.dbAuth) {
                this.products = await window.dbAuth.getItem('katalog_produk') || [];
                this.posSettings = await window.dbAuth.getItem('pos_settings') || {};
            }
            this.isLoading = false;
        },

        get filteredProducts() {
            if (this.searchQuery.trim() === '') return this.products;
            const q = this.searchQuery.toLowerCase();
            return this.products.filter(p => p.name.toLowerCase().includes(q));
        },

        // LOGIC MARKUP OTOMATIS
        calculateMarkupPrice(product) {
            let basePrice = parseFloat(product.offline_price); // Pakai harga toko sbg dasar
            let markupPercent = 0;

            if (this.channel === 'grab') markupPercent = parseFloat(this.posSettings.markup_grab || 0);
            if (this.channel === 'gojek') markupPercent = parseFloat(this.posSettings.markup_gojek || 0);
            
            if (markupPercent > 0) {
                let addCost = (basePrice * markupPercent) / 100;
                return basePrice + addCost;
            }
            
            // Jika channel WA/Web, bisa pakai harga online (jika di-set di master produk)
            return parseFloat(product.online_price) > 0 ? parseFloat(product.online_price) : basePrice;
        },

        setChannel(c) {
            this.channel = c;
            // Update harga semua barang di keranjang sesuai markup channel baru
            this.cart.forEach(item => {
                const prod = this.products.find(p => p.id == item.id);
                if(prod) {
                    item.price = this.calculateMarkupPrice(prod);
                    item.subtotal = item.price * item.qty;
                }
            });
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

        // PERHITUNGAN
        get subtotal() { return this.cart.reduce((sum, item) => sum + item.subtotal, 0); },
        get totalAmount() { return this.subtotal + parseFloat(this.shippingCost || 0); },

        // CHECKOUT LOGIC
        async processCheckout() {
            window.customConfirm(`Proses pesanan ${this.channel.toUpperCase()} ini?`, async () => {
                this.isLoading = true;
                const payload = {
                    channel: this.channel, customer_name: this.customerName, notes: this.notes,
                    subtotal: this.subtotal, shipping_cost: parseFloat(this.shippingCost || 0),
                    total_amount: this.totalAmount, payment_method: this.paymentMethod,
                    amount_paid: this.totalAmount, change_amount: 0, items: this.cart
                };

                try {
                    const response = await fetch('logic.php?action=checkout', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
                    });
                    const result = await response.json();

                    if (result.status === 'success') {
                        this.lastInvoice = result.invoice;
                        
                        Swal.fire('Berhasil!', 'Pesanan Online telah dicatat dan masuk antrean dapur.', 'success').then(() => {
                            // Buka tab struk khusus online
                            window.open(`print_receipt.php?invoice=${this.lastInvoice}`, '_blank', 'width=400,height=600');
                            this.resetCart();
                        });
                    } else { window.alert(result.message); }
                } catch (e) { window.alert('Gagal memproses transaksi.'); } finally { this.isLoading = false; }
            });
        },

        resetCart() {
            this.cart = []; this.customerName = ''; this.notes = ''; 
            this.shippingCost = 0; this.paymentMethod = 'app';
        },

        formatRupiah(angka) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(angka || 0); }
    }));
});