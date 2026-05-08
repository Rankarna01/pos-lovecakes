document.addEventListener('alpine:init', () => {
    Alpine.data('posApp', () => ({
        products: [],
        customers: [],
        loyaltyRules: { is_active: 0, earn_point_ratio: 0, points_required: 0, discount_amount: 0, discount_type: 'IDR' },
        posSettings: {}, 
        
        searchQuery: '',
        isLoading: false,

        orderType: 'offline', 
        cart: [],
        selectedCustomerId: '',
        voucherCode: '',
        appliedVoucher: null,
        usePoints: false,
        discountManual: 0, 
        
        paymentMethod: 'cash',
        paymentStatus: 'lunas',
        amountPaid: 0,
        dpAmount: 0,
        changeAmount: 0,

        showStatusModal: false,
        isFetchingStatus: false,
        activeOrders: [],

        showSuccessModal: false,
        lastInvoice: '',
        totalAmountSaved: 0,
        paymentStatusSaved: '',
        dpAmountSaved: 0,
        amountPaidSaved: 0,
        changeAmountSaved: 0,
        paymentMethodSaved: '',

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../auth/index.php'; return; }
            }
            await this.loadLocalData(false);
        },

        // PARAMETER isManualSync agar pop-up "Berhasil" hanya muncul saat tombol ditekan
        async loadLocalData(isManualSync = false) {
            this.isLoading = true;
            try {
                // 1. Tarik data terbaru dari MySQL via logic.php
                const response = await fetch(`logic.php?action=get_master_data&nocache=${Date.now()}`);
                const result = await response.json(); 
                
                if (result.status === 'success') {
                    // BUG FIX: Parse JSON agar Proxy Alpine mati dan IndexedDB mau menyimpan array
                    const rawProducts = JSON.parse(JSON.stringify(result.products || []));
                    const rawCustomers = JSON.parse(JSON.stringify(result.customers || []));

                    if (window.dbAuth) {
                        await window.dbAuth.setItem('katalog_produk', rawProducts);
                        await window.dbAuth.setItem('customers_data', rawCustomers);
                    }
                    
                    this.products = rawProducts;
                    this.customers = rawCustomers;

                    // Jika dipencet manual, munculkan Toast
                    if(isManualSync) {
                        Swal.fire({
                            toast: true, position: 'top-end', icon: 'success',
                            title: `Database Berhasil Disinkronisasi!`,
                            showConfirmButton: false, timer: 1500,
                            customClass: { popup: 'rounded-xl shadow-lg border border-slate-100 mt-4 mr-4' }
                        });
                    }
                } else {
                    console.error("Gagal Tarik Data MySQL:", result.message);
                    throw new Error("Gagal");
                }
            } catch (error) {
                console.log("Gagal tarik dari server, pakai data memori lokal.");
                if (window.dbAuth) {
                    this.products = await window.dbAuth.getItem('katalog_produk') || [];
                    this.customers = await window.dbAuth.getItem('customers_data') || [];
                }
                if (isManualSync) {
                    Swal.fire('Mode Offline', 'Gagal menyambung ke server. Memakai data lokal.', 'warning');
                }
            } finally {
                // 2. Tarik Pengaturan (Diskon dll) dari lokal
                if (window.dbAuth) {
                    this.posSettings = await window.dbAuth.getItem('pos_settings') || {};
                    this.loyaltyRules = await window.dbAuth.getItem('loyalty_rules') || this.loyaltyRules;
                }
                this.isLoading = false;
            }
        },

        get filteredProducts() {
            if (this.searchQuery.trim() === '') return this.products;
            const q = this.searchQuery.toLowerCase();
            return this.products.filter(p => p.name && p.name.toLowerCase().includes(q));
        },

        get selectedCustomer() {
            return this.selectedCustomerId ? this.customers.find(c => c.id == this.selectedCustomerId) : null;
        },

        setOrderType(type) { this.orderType = type; },

        async addCustomItem() {
            const { value: formValues } = await Swal.fire({
                title: 'Tambah Item Custom',
                html: `
                    <input id="swal-custom-name" class="swal2-input" placeholder="Nama Pesanan Khusus">
                    <input id="swal-custom-price" type="number" class="swal2-input" placeholder="Harga Satuan (Rp)">
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Tambahkan',
                preConfirm: () => {
                    const name = document.getElementById('swal-custom-name').value;
                    const price = document.getElementById('swal-custom-price').value;
                    if (!name || !price) { Swal.showValidationMessage('Nama dan Harga wajib diisi!'); }
                    return { name: name, price: parseFloat(price) };
                }
            });

            if (formValues) {
                this.cart.push({ 
                    id: 'custom_' + Date.now(), 
                    name: formValues.name, 
                    price: formValues.price, 
                    qty: 1, 
                    subtotal: formValues.price,
                    is_custom: true 
                });
            }
        },

        addToCart(product) {
            // BUG FIX: Menggunakan product.price jika product.offline_price kosong
            const price = parseFloat(product.price || product.offline_price || 0);
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
            if (this.cart[index].qty <= 0) this.removeItem(index);
            else this.cart[index].subtotal = this.cart[index].qty * this.cart[index].price;
        },

        removeItem(index) { this.cart.splice(index, 1); },
        onCustomerSelect() { this.usePoints = false; },
        togglePoints() { this.usePoints = !this.usePoints; },

        async openStatusModal() {
            this.showStatusModal = true;
            this.isFetchingStatus = true;
            try {
                const response = await fetch(`logic.php?action=get_active_orders&nocache=${Date.now()}`);
                const result = await response.json();
                if(result.status === 'success') {
                    this.activeOrders = result.data;
                }
            } catch(e) {
                console.error("Gagal menarik data status", e);
            } finally {
                this.isFetchingStatus = false;
            }
        },

        async applyManualDiscount() {
            if (this.subtotal <= 0) { window.alert('Keranjang masih kosong!'); return; }
            
            const realPin = this.posSettings.pin_supervisor || '123456';

            const { value: inputPin } = await Swal.fire({
                title: 'Otorisasi Supervisor',
                input: 'password',
                inputPlaceholder: 'Masukkan PIN',
                showCancelButton: true,
                confirmButtonText: 'Validasi'
            });

            if (inputPin === realPin) {
                const { value: discVal } = await Swal.fire({
                    title: 'Diskon Manual',
                    input: 'number',
                    inputPlaceholder: 'Masukkan Nominal (Rp)',
                    showCancelButton: true
                });
                if (discVal && parseFloat(discVal) > 0) {
                    this.discountManual = parseFloat(discVal);
                }
            } else if (inputPin) {
                Swal.fire('Akses Ditolak', 'PIN Supervisor Salah!', 'error');
            }
        },

        async applyVoucher() {
            if (!this.voucherCode) return;
            try {
                const fd = new FormData(); fd.append('code', this.voucherCode); fd.append('subtotal', this.subtotal);
                const response = await fetch('logic.php?action=check_voucher', { method: 'POST', body: fd });
                const result = await response.json();
                if(result.status === 'success') { this.appliedVoucher = result.data; window.alert('Voucher berhasil dipasang!'); } 
                else { this.appliedVoucher = null; window.alert(result.message); }
            } catch(e) { window.alert('Gagal mengecek voucher.'); }
        },

        get subtotal() { return this.cart.reduce((sum, item) => sum + item.subtotal, 0); },
        get discountVoucher() {
            if (!this.appliedVoucher) return 0;
            if (this.subtotal < this.appliedVoucher.min_purchase) { this.appliedVoucher = null; return 0; }
            let d = parseFloat(this.appliedVoucher.discount_amount);
            return this.appliedVoucher.discount_type === 'PERCENT' ? (this.subtotal * d) / 100 : d;
        },
        get discountPoints() {
            if (!this.usePoints || !this.selectedCustomer || !this.loyaltyRules?.is_active) return 0;
            if (this.selectedCustomer.points < this.loyaltyRules.points_required) return 0;
            let d = parseFloat(this.loyaltyRules.discount_amount);
            return this.loyaltyRules.discount_type === 'PERCENT' ? (this.subtotal * d) / 100 : d;
        },
        get totalAmount() {
            let total = this.subtotal - this.discountVoucher - this.discountPoints - this.discountManual;
            return total > 0 ? total : 0;
        },
        get pointsEarned() {
            if (!this.selectedCustomer || !this.loyaltyRules?.is_active || this.loyaltyRules.earn_point_ratio <= 0) return 0;
            return Math.floor(this.totalAmount / this.loyaltyRules.earn_point_ratio);
        },

        async processCheckout() {
            const { value: statusBayar } = await Swal.fire({
                title: 'Metode Pelunasan',
                input: 'radio',
                inputOptions: { 'lunas': 'Bayar Lunas', 'dp': 'DP / Kasbon (Bayar Sebagian)' },
                inputValue: 'lunas',
                showCancelButton: true,
                confirmButtonText: 'Lanjut'
            });

            if (!statusBayar) return;
            this.paymentStatus = statusBayar;

            if (this.paymentStatus === 'dp') {
                if(!this.selectedCustomerId) {
                    Swal.fire('Perhatian', 'Transaksi DP/Kasbon wajib memilih nama Pelanggan!', 'warning');
                    return;
                }
                const { value: dpVal } = await Swal.fire({
                    title: 'Masukkan Jumlah DP',
                    html: `<p class="mb-2">Total Tagihan: Rp ${this.formatRupiah(this.totalAmount)}</p>`,
                    input: 'number',
                    inputPlaceholder: 'Nominal DP (Rp)',
                    showCancelButton: true
                });
                if (dpVal) {
                    this.dpAmount = parseFloat(dpVal);
                    this.amountPaid = this.dpAmount;
                    this.changeAmount = 0;
                    this.executeCheckout();
                }
            } else {
                this.dpAmount = 0;
                if (this.paymentMethod === 'qris') {
                    this.amountPaid = this.totalAmount;
                    this.changeAmount = 0;
                    this.executeCheckout();
                } else {
                    const { value: formValues } = await Swal.fire({
                        title: 'Pembayaran Cash',
                        html: `<p class="font-bold mb-2">Total: Rp ${this.formatRupiah(this.totalAmount)}</p><input id="swal-input-uang" type="number" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-3 text-center font-black text-xl" placeholder="Uang diterima...">`,
                        focusConfirm: false, showCancelButton: true, confirmButtonText: 'Proses',
                        preConfirm: () => {
                            const uang = document.getElementById('swal-input-uang').value;
                            if (!uang || parseFloat(uang) < this.totalAmount) Swal.showValidationMessage('Uang kurang!');
                            return parseFloat(uang);
                        }
                    });
                    if (formValues) {
                        this.amountPaid = formValues;
                        this.changeAmount = this.amountPaid - this.totalAmount;
                        this.executeCheckout();
                    }
                }
            }
        },

        async executeCheckout() {
            this.isLoading = true;
            const payload = {
                order_type: this.orderType, customer_id: this.selectedCustomerId, subtotal: this.subtotal,
                discount_voucher: this.discountVoucher, voucher_code: this.appliedVoucher ? this.appliedVoucher.voucher_code : null,
                discount_points: this.discountPoints, discount_manual: this.discountManual,
                points_used: this.usePoints ? this.loyaltyRules.points_required : 0, points_earned: this.pointsEarned, 
                total_amount: this.totalAmount, payment_method: this.paymentMethod, payment_status: this.paymentStatus,
                dp_amount: this.dpAmount, amount_paid: this.amountPaid, change_amount: this.changeAmount, items: this.cart
            };

            try {
                const response = await fetch('logic.php?action=checkout', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.status === 'success') {
                    this.lastInvoice = result.invoice;
                    this.totalAmountSaved = this.totalAmount;
                    this.paymentStatusSaved = this.paymentStatus;
                    this.dpAmountSaved = this.dpAmount;
                    this.amountPaidSaved = this.amountPaid;
                    this.changeAmountSaved = this.changeAmount;
                    this.paymentMethodSaved = this.paymentMethod;

                    this.showSuccessModal = true;
                } else { window.alert(result.message); }
            } catch (e) { window.alert('Gagal memproses transaksi.'); } finally { this.isLoading = false; }
        },

        printReceipt() {
            if(this.lastInvoice) window.open(`print_receipt.php?invoice=${this.lastInvoice}`, '_blank', 'width=400,height=600');
            this.resetCart();
        },

        resetCart() {
            this.cart = []; this.selectedCustomerId = ''; this.voucherCode = ''; this.appliedVoucher = null;
            this.usePoints = false; this.discountManual = 0; this.paymentMethod = 'cash'; this.paymentStatus = 'lunas';
            this.amountPaid = 0; this.dpAmount = 0; this.changeAmount = 0; this.showSuccessModal = false;
        },

        formatRupiah(angka) { 
            const val = parseFloat(angka);
            if (isNaN(val)) return '0';
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val); 
        }
    }));
});