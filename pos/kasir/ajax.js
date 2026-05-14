document.addEventListener('alpine:init', () => {
    Alpine.data('posApp', () => ({
        // --- DATA MASTER ---
        products: [], 
        savedCustoms: [], // Menyimpan data template menu custom (tabel saved_custom_items_pos)
        customers: [], 
        posSettings: {}, 
        loyaltyRules: { is_active: 0, earn_point_ratio: 0, points_required: 0, discount_amount: 0, discount_type: 'IDR' },
        
        searchQuery: '', barcodeInput: '', isLoading: false,
        
        // --- TAB & SHIFT ---
        activeTab: 'reguler', 
        needsShiftOpen: false, isLoadingShift: false, masterShifts: [],
        shiftForm: { shift_id: '', start_cash: '' },
        showCloseShiftModal: false, closeShiftCash: '',

        // --- KAS KELUAR ---
        showKasKeluarModal: false, isSavingKas: false,
        kasKeluarForm: { amount: '', description: '' },

        // --- KERANJANG & CHECKOUT ---
        orderType: 'offline', cart: [], selectedCustomerId: '',
        voucherCode: '', appliedVoucher: null, usePoints: false, discountManual: 0, 
        poForm: { channel: 'toko', pickup_date: '', pickup_time: '', ongkir: 0 },
        
        // --- STATE MODAL CHECKOUT MEWAH ---
        showCheckoutModal: false, inputUang: '',
        paymentMethod: 'cash', paymentStatus: 'lunas', amountPaid: 0, dpAmount: 0, changeAmount: 0,

        // --- STATE MODAL ITEM CUSTOM ---
        showCustomItemModal: false,
        customItemForm: { template: '', name: '', price: '' },

        // --- MODAL STATUS & SUCCESS ---
        showStatusModal: false, isFetchingStatus: false, activeOrders: [],
        showSuccessModal: false, lastInvoice: '', totalAmountSaved: 0, paymentStatusSaved: '', dpAmountSaved: 0, amountPaidSaved: 0, changeAmountSaved: 0, paymentMethodSaved: '',

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../auth/index.php'; return; }
            }
            await this.checkShiftStatus();
            if(!this.needsShiftOpen) {
                await this.loadLocalData(false);
                setTimeout(() => { if(this.$refs.barcodeScanner) this.$refs.barcodeScanner.focus() }, 500);
            }
        },

        // --- FUNGSI SHIFT ---
      async checkShiftStatus() {
            try {
                // ARAHKAN KE logic_kasir.php
                const res = await fetch(`logic_kasir.php?action=check_shift&nocache=${Date.now()}`); 
                const rawText = await res.text();
                try {
                    const result = JSON.parse(rawText);
                    if (result.status === 'success') {
                        this.needsShiftOpen = !result.has_open_shift;
                    }
                } catch(err) { console.error("❌ ERROR PHP (Check Shift):", rawText); }
            } catch (e) { console.error("Error Cek Shift:", e); }
        },

       async openShift() {
            this.isLoadingShift = true;
            try {
                const fd = new FormData(); fd.append('start_cash', this.shiftForm.start_cash);
                // ARAHKAN KE logic_kasir.php
                const res = await fetch('logic_kasir.php?action=open_shift', { method: 'POST', body: fd });
                const rawText = await res.text();
                try {
                    const result = JSON.parse(rawText);
                    if (result.status === 'success') {
                        this.needsShiftOpen = false; 
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: result.message, showConfirmButton: false, timer: 1500 });
                        await this.loadLocalData(false);
                    } else { Swal.fire('Error', result.message, 'error'); }
                } catch(err) { console.error("❌ ERROR PHP (Open Shift):", rawText); }
            } catch (e) { Swal.fire('Error', 'Gagal membuka kasir.', 'error'); } 
            finally { this.isLoadingShift = false; }
        },

      openCloseShiftModal() { this.closeShiftCash = ''; this.showCloseShiftModal = true; },

        async closeShift() {
            this.isLoadingShift = true;
            try {
                const fd = new FormData(); fd.append('end_cash', this.closeShiftCash);
                // ARAHKAN KE logic_kasir.php
                const res = await fetch('logic_kasir.php?action=close_shift', { method: 'POST', body: fd });
                const rawText = await res.text(); // X-RAY ERROR HANDLER
                try {
                    const result = JSON.parse(rawText);
                    if (result.status === 'success') {
                        Swal.fire('Tutup Kasir Sukses', result.message, 'success').then(() => { window.location.reload(); });
                    } else { Swal.fire('Gagal', result.message, 'error'); }
                } catch(err) { 
                    console.error("❌ ERROR PHP (Close Shift):", rawText); 
                    Swal.fire('Error Database', 'Cek Console (Cmd+Option+I) untuk melihat penyebab error dari PHP.', 'error');
                }
            } catch (e) { Swal.fire('Error', 'Gagal menutup kasir.', 'error'); } 
            finally { this.isLoadingShift = false; }
        },

        // --- FUNGSI KAS KELUAR ---
        openKasKeluarModal() {
            this.kasKeluarForm = { amount: '', description: '' };
            this.showKasKeluarModal = true;
        },

        async submitKasKeluar() {
            if(!this.kasKeluarForm.amount || !this.kasKeluarForm.description) {
                Swal.fire('Perhatian', 'Nominal dan Keterangan wajib diisi!', 'warning'); return;
            }
            this.isSavingKas = true;
            try {
                const fd = new FormData(); 
                fd.append('amount', this.kasKeluarForm.amount); 
                fd.append('description', this.kasKeluarForm.description);
                
                // Pastikan nembak ke logic_kasir.php
                const res = await fetch('logic_kasir.php?action=save_kas_keluar', { method: 'POST', body: fd });
                
                // X-Ray Error Handler (Menangkap pesan PHP murni jika crash)
                const rawText = await res.text();
                try {
                    const result = JSON.parse(rawText);
                    if(result.status === 'success') {
                        this.showKasKeluarModal = false;
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: result.message, showConfirmButton: false, timer: 2000 });
                    } else {
                        Swal.fire('Gagal', result.message, 'error');
                    }
                } catch(err) {
                    console.error("❌ ERROR DARI PHP:", rawText);
                    Swal.fire('Error Database', 'PHP mengalami crash! Cek Console (Cmd+Option+I) untuk melihat penyebabnya.', 'error');
                }
            } catch(e) { Swal.fire('Error', 'Koneksi server gagal.', 'error'); }
            finally { this.isSavingKas = false; }
        },

        // --- FUNGSI MASTER DATA ---
        async loadLocalData(isManualSync = false) {
    this.isLoading = true;
    try {
        const response = await fetch(`logic_kasir.php?action=get_master_data&nocache=${Date.now()}`);
                const result = await response.json(); 
                if (result.status === 'success') {
                    this.products = result.products; 
                    this.customers = result.customers;
                    this.savedCustoms = result.saved_customs || []; 
                    
                    if(isManualSync) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `Database Tersinkronisasi!`, showConfirmButton: false, timer: 1500 });
                }
            } catch (error) {
                if (isManualSync) Swal.fire('Mode Offline', 'Memakai data lokal memori.', 'warning');
            } finally { this.isLoading = false; }
        },

        get filteredProducts() {
            if (this.searchQuery.trim() === '') return this.products;
            const q = this.searchQuery.toLowerCase();
            return this.products.filter(p => p.name && p.name.toLowerCase().includes(q));
        },
        get selectedCustomer() { return this.selectedCustomerId ? this.customers.find(c => c.id == this.selectedCustomerId) : null; },

        scanBarcode() {
            const code = this.barcodeInput.trim().toUpperCase();
            if (!code) return;
            const product = this.products.find(p => (p.code || '').toUpperCase() === code);
            if (product) { this.addToCart(product); } else { Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'SKU Tidak Ditemukan!', showConfirmButton: false, timer: 1000 }); }
            this.barcodeInput = ''; setTimeout(() => { if(this.$refs.barcodeScanner) this.$refs.barcodeScanner.focus(); }, 10);
        },

        // --- FUNGSI KERANJANG ---
        addToCart(product) {
            const price = parseFloat(product.price || product.offline_price || 0);
            const existing = this.cart.find(item => item.id === product.id && !item.is_custom);
            if (existing) { existing.qty++; existing.subtotal = existing.qty * existing.price; } 
            else { this.cart.push({ id: product.id, name: product.name, price: price, qty: 1, subtotal: price, is_custom: false }); }
        },
        updateQty(index, change) {
            this.cart[index].qty += change;
            if (this.cart[index].qty <= 0) this.removeItem(index);
            else this.cart[index].subtotal = this.cart[index].qty * this.cart[index].price;
        },
        removeItem(index) { this.cart.splice(index, 1); },

        // --- FUNGSI ITEM CUSTOM BARU ---
        addCustomItem() {
            this.customItemForm = { template: '', name: '', price: '' };
            this.showCustomItemModal = true;
        },

        applyCustomTemplate() {
            if (this.customItemForm.template) {
                const selected = this.savedCustoms.find(c => c.id == this.customItemForm.template);
                if (selected) {
                    this.customItemForm.name = selected.name;
                    this.customItemForm.price = selected.price;
                }
            } else {
                this.customItemForm.name = '';
                this.customItemForm.price = '';
            }
        },

        submitCustomItem() {
            const name = this.customItemForm.name.trim();
            const price = parseFloat(this.customItemForm.price);

            if (!name || isNaN(price) || price <= 0) {
                Swal.fire('Perhatian', 'Nama dan Harga wajib diisi dengan benar!', 'warning');
                return;
            }

            this.cart.push({ 
                id: 'custom_' + Date.now(), 
                name: name, 
                price: price, 
                qty: 1, 
                subtotal: price, 
                is_custom: true 
            });

            this.showCustomItemModal = false;
        },

        onCustomerSelect() { this.usePoints = false; },
        togglePoints() { this.usePoints = !this.usePoints; },

        // --- FUNGSI DISKON & VOUCHER ---
        async applyManualDiscount() {
            if (this.subtotal <= 0) { window.alert('Keranjang masih kosong!'); return; }
            const realPin = this.posSettings.pin_supervisor || '123456';
            const { value: inputPin } = await Swal.fire({ title: 'Otorisasi Supervisor', input: 'password', inputPlaceholder: 'Masukkan PIN', showCancelButton: true, confirmButtonText: 'Validasi' });
            if (inputPin === realPin) {
                const { value: discVal } = await Swal.fire({ title: 'Diskon Manual', input: 'number', inputPlaceholder: 'Masukkan Nominal (Rp)', showCancelButton: true });
                if (discVal && parseFloat(discVal) > 0) { this.discountManual = parseFloat(discVal); }
            } else if (inputPin) { Swal.fire('Akses Ditolak', 'PIN Supervisor Salah!', 'error'); }
        },

        async applyVoucher() {
            if (!this.voucherCode) return;
            try {
                const fd = new FormData(); fd.append('code', this.voucherCode); fd.append('subtotal', this.subtotal);
                const response = await fetch('logic_kasir.php?action=check_voucher', { method: 'POST', body: fd });
                const result = await response.json();
                if(result.status === 'success') { this.appliedVoucher = result.data; window.alert('Voucher berhasil dipasang!'); } 
                else { this.appliedVoucher = null; window.alert(result.message); }
            } catch(e) { window.alert('Gagal mengecek voucher.'); }
        },

        // --- KALKULASI TOTAL ---
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
            let ongkir = this.activeTab === 'po' ? parseFloat(this.poForm.ongkir || 0) : 0;
            let total = this.subtotal + ongkir - this.discountVoucher - this.discountPoints - this.discountManual;
            return total > 0 ? total : 0;
        },
        get pointsEarned() {
            if (!this.selectedCustomer || !this.loyaltyRules?.is_active || this.loyaltyRules.earn_point_ratio <= 0) return 0;
            return Math.floor(this.totalAmount / this.loyaltyRules.earn_point_ratio);
        },

        // --- FUNGSI CHECKOUT ---
        processCheckout() {
            if(this.activeTab === 'po' && (!this.poForm.pickup_date || !this.poForm.pickup_time)) {
                Swal.fire('Perhatian', 'Tanggal dan Jam Pengambilan Pesanan PO wajib diisi!', 'warning'); return;
            }
            this.paymentStatus = 'lunas'; this.paymentMethod = 'cash'; this.inputUang = this.totalAmount; 
            this.showCheckoutModal = true;
        },

        submitCheckout() {
            if (this.paymentStatus === 'dp') {
                if(!this.selectedCustomerId) { Swal.fire('Perhatian', 'Transaksi DP/Kasbon wajib memilih nama Pelanggan di sidebar!', 'warning'); return; }
                if(!this.inputUang || this.inputUang <= 0 || this.inputUang > this.totalAmount) { Swal.fire('Perhatian', 'Nominal DP tidak valid!', 'warning'); return; }
                this.dpAmount = parseFloat(this.inputUang); this.amountPaid = this.dpAmount; this.changeAmount = 0; this.paymentMethod = 'cash';
            } else {
                this.dpAmount = 0;
                if (this.paymentMethod === 'cash') {
                    if (!this.inputUang || parseFloat(this.inputUang) < this.totalAmount) { Swal.fire('Perhatian', 'Uang diterima kurang dari total tagihan!', 'warning'); return; }
                    this.amountPaid = parseFloat(this.inputUang); this.changeAmount = this.amountPaid - this.totalAmount;
                } else if (this.paymentMethod === 'qris') {
                    this.amountPaid = this.totalAmount; this.changeAmount = 0;
                }
            }
            this.showCheckoutModal = false; this.executeCheckout();
        },

        async executeCheckout() {
            this.isLoading = true;
            const payload = {
                is_po: this.activeTab === 'po', channel: this.activeTab === 'po' ? this.poForm.channel : 'toko',
                pickup_date: this.activeTab === 'po' ? this.poForm.pickup_date : null, pickup_time: this.activeTab === 'po' ? this.poForm.pickup_time : null,
                ongkir: this.activeTab === 'po' ? this.poForm.ongkir : 0,
                order_type: this.orderType, customer_id: this.selectedCustomerId, subtotal: this.subtotal,
                discount_voucher: this.discountVoucher, voucher_code: this.appliedVoucher ? this.appliedVoucher.voucher_code : null,
                discount_points: this.discountPoints, discount_manual: this.discountManual, points_used: this.usePoints ? this.loyaltyRules.points_required : 0, points_earned: this.pointsEarned, 
                total_amount: this.totalAmount, payment_method: this.paymentMethod, payment_status: this.paymentStatus,
                dp_amount: this.dpAmount, amount_paid: this.amountPaid, change_amount: this.changeAmount, items: this.cart
            };
            try {
                const response = await fetch('logic_kasir.php?action=checkout', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const result = await response.json();
                if (result.status === 'success') {
                    this.lastInvoice = result.invoice; this.totalAmountSaved = this.totalAmount; this.paymentStatusSaved = this.paymentStatus;
                    this.dpAmountSaved = this.dpAmount; this.amountPaidSaved = this.amountPaid; this.changeAmountSaved = this.changeAmount; this.paymentMethodSaved = this.paymentMethod;
                    this.showSuccessModal = true;
                } else { window.alert(result.message); }
            } catch (e) { window.alert('Gagal memproses transaksi ke database.'); } finally { this.isLoading = false; }
        },

        printReceipt() {
            if(this.lastInvoice) window.open(`print_receipt.php?invoice=${this.lastInvoice}`, '_blank', 'width=400,height=600');
            this.resetCart();
        },

        resetCart() {
            this.cart = []; this.selectedCustomerId = ''; this.voucherCode = ''; this.appliedVoucher = null;
            this.usePoints = false; this.discountManual = 0; this.paymentMethod = 'cash'; this.paymentStatus = 'lunas';
            this.amountPaid = 0; this.dpAmount = 0; this.changeAmount = 0; this.inputUang = 0;
            this.poForm = { channel: 'toko', pickup_date: '', pickup_time: '', ongkir: 0 };
            this.showSuccessModal = false;
        },

        // --- FUNGSI STATUS PO ---
        async openStatusModal() {
            this.showStatusModal = true; this.isFetchingStatus = true;
            try {
                const response = await fetch(`logic_kasir.php?action=get_active_orders&nocache=${Date.now()}`);
                const result = await response.json();
                if(result.status === 'success') { this.activeOrders = result.data; }
            } catch(e) { console.error(e); } finally { this.isFetchingStatus = false; }
        },

        formatRupiah(angka) { 
            const val = parseFloat(angka); if (isNaN(val)) return '0';
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val); 
        }
    }));
});