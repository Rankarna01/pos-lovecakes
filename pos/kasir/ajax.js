// --- HELPER INDEXEDDB PWA OFFLINE ---
const POS_DB_NAME = 'LoveCakesPOSDB';
const POS_DB_VERSION = 1;

const idbPos = {
    db: null,
    async init() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(POS_DB_NAME, POS_DB_VERSION);
            req.onupgradeneeded = (e) => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains('master_data')) db.createObjectStore('master_data', { keyPath: 'key' });
                if (!db.objectStoreNames.contains('offline_transactions')) db.createObjectStore('offline_transactions', { keyPath: 'id' });
            };
            req.onsuccess = (e) => { this.db = e.target.result; resolve(); };
            req.onerror = (e) => reject(e);
        });
    },
    async setMasterData(key, value) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('master_data', 'readwrite');
            // Menghapus sifat Proxy bawaan Alpine.js agar bisa disimpan di IndexedDB
            const rawValue = JSON.parse(JSON.stringify(value));
            tx.objectStore('master_data').put({ key: key, value: rawValue });
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject();
        });
    },
    async getMasterData(key) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('master_data', 'readonly');
            const req = tx.objectStore('master_data').get(key);
            req.onsuccess = () => resolve(req.result ? req.result.value : null);
            req.onerror = () => reject();
        });
    },
    async saveOfflineTransaction(payload) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('offline_transactions', 'readwrite');
            const rawPayload = JSON.parse(JSON.stringify(payload));
            rawPayload.id = 'OFFLINE-' + Date.now();
            rawPayload.created_at_local = new Date().toISOString();
            tx.objectStore('offline_transactions').put(rawPayload);
            tx.oncomplete = () => resolve(rawPayload.id);
            tx.onerror = () => reject();
        });
    },
    async getOfflineTransactions() {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('offline_transactions', 'readonly');
            const req = tx.objectStore('offline_transactions').getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject();
        });
    },
    async deleteOfflineTransaction(id) {
        if (!this.db) await this.init();
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction('offline_transactions', 'readwrite');
            tx.objectStore('offline_transactions').delete(id);
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject();
        });
    }
};

document.addEventListener('alpine:init', () => {
    Alpine.data('posApp', () => ({
        // --- DATA MASTER ---
        statusFilterDate: new Date().toISOString().split('T')[0],
        statusFilterMode: 'nunggak',
        products: [], 
        savedCustoms: [], // Menyimpan data template menu custom (tabel saved_custom_items_pos)
        customers: [], 
        searchCustomer: '',
        isCustomerDropdownOpen: false,
        posSettings: {}, 
        validSupervisorPins: [],
        loyaltyRules: { is_active: 0, earn_point_ratio: 0, points_required: 0, discount_amount: 0, discount_type: 'IDR' },
        paymentMethods: [],
        
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
        voucherCode: '', appliedVoucher: null, usePoints: false, discountManual: 0, discountManualType: 'NOMINAL', discountManualInput: 0, promosBuyGet: [], promosAutoDisc: [], appliedAutoDisc: null,
        orderNotes: '',
        poForm: { channel: 'toko', pickup_date: '', pickup_time: '', ongkir: 0, notes: '' },

        // --- REGULER FORM (tgl ambil, jam ambil, delivery opsional) ---
        regulerForm: { pickup_date: '', pickup_time: '', is_delivery: false, ongkir: 0, channel: 'toko' },
        
        // --- STATE MODAL CHECKOUT MEWAH ---
        showCheckoutModal: false, inputUang: '', paymentReference: '',
        paymentMethod: 'Cash', paymentFeeName: '', paymentStatus: 'lunas', amountPaid: 0, dpAmount: 0, changeAmount: 0,

        // --- STATE MODAL ITEM CUSTOM & CATATAN ---
        showCustomItemModal: false,
        showCustomRegulerModal: false,
        customItemForm: { template: '', name: '', price: '' },
        showNotesModal: false,

        // --- TAMBAH PELANGGAN ---
        showAddCustomerModal: false, isSavingCustomer: false,
        newCustomerForm: { name: '', phone: '', address: '', birth_date: '' },

        // --- MODAL STATUS & SUCCESS ---
        showStatusModal: false, isFetchingStatus: false, activeOrders: [],
        showSuccessModal: false, lastInvoice: '', totalAmountSaved: 0, paymentStatusSaved: '', dpAmountSaved: 0, amountPaidSaved: 0, changeAmountSaved: 0, paymentMethodSaved: '',

        // --- DRAFT / HOLD BILL ---
        drafts: [], showDraftModal: false, showSaveDraftModal: false, draftReferenceName: '',

        // --- OFFLINE STATE ---
        isOnline: navigator.onLine,
        pendingSyncCount: 0,
        isSyncing: false,

        async init() {
            if (window.dbAuth) {
                const user = await window.dbAuth.getItem('user_session');
                if (!user) { window.location.href = '../../auth/index.php'; return; }
            }
            
            window.addEventListener('online', () => { this.isOnline = true; this.syncOfflineTransactions(); });
            window.addEventListener('offline', () => { this.isOnline = false; });
            await this.updatePendingCount();

            await this.checkShiftStatus();
            if(!this.needsShiftOpen) {
                await this.loadLocalData(false);
                this.loadDrafts();
                setTimeout(() => { if(this.$refs.barcodeScanner) this.$refs.barcodeScanner.focus() }, 500);
            }

            // 💡 BERSIHKAN MODE BLUETOOTH LAMA JIKA TIDAK AKTIF & DETEKSI PRINTER THERMAL USB
            if (localStorage.getItem('pos_auto_print_mode') === 'bluetooth' && localStorage.getItem('pos_bt_active') !== '1') {
                localStorage.setItem('pos_auto_print_mode', 'usb');
            }

            if (navigator.usb) {
                navigator.usb.addEventListener('connect', (event) => {
                    const usbName = event.device.productName || 'Thermal Printer USB';
                    localStorage.setItem('pos_auto_print_mode', 'usb');
                    localStorage.setItem('pos_usb_printer_name', usbName);
                    localStorage.removeItem('pos_bt_active');
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true, position: 'bottom-end', icon: 'success',
                            title: '🖨️ USB Printer Terdeteksi!',
                            text: `Perangkat "${usbName}" terhubung & siap digunakan.`,
                            showConfirmButton: false, timer: 4500
                        });
                    }
                });
            }

            // 🔄 AUTO-REFRESH: Sinkronisasi produk baru setiap 60 detik secara silent (tanpa loading spinner)
            this._autoRefreshInterval = setInterval(async () => {
                if (!navigator.onLine || this.needsShiftOpen) return;
                try {
                    const res = await fetch(`logic_kasir.php?action=get_master_data&nocache=${Date.now()}`);
                    if (!res.ok) return;
                    const result = await res.json();
                    if (result.status === 'success') {
                        this.products = result.products;
                        this.customers = result.customers;
                        this.savedCustoms = result.saved_customs || [];
                        this.savedCustomsReguler = result.saved_customs_reguler || [];
                        this.paymentMethods = result.payment_methods || [];
                        this.promosBuyGet = result.promos_buy_get || [];
                        this.promosAutoDisc = result.promos_auto_disc || [];
                        this.foodDeliveryPrices = result.food_delivery_prices || [];
                        this.posSettings = result.settings || {};
                        this.validSupervisorPins = result.valid_supervisor_pins || [];
                        
                        // Update IndexedDB cache juga
                        await idbPos.setMasterData('products', this.products);
                        await idbPos.setMasterData('customers', this.customers);
                        await idbPos.setMasterData('settings', this.posSettings);
                        await idbPos.setMasterData('valid_supervisor_pins', this.validSupervisorPins);
                    }
                } catch(e) { /* Silent fail - jangan ganggu kasir */ }
            }, 60000); // 60 detik
        },

        async updatePendingCount() {
            const pending = await idbPos.getOfflineTransactions();
            this.pendingSyncCount = pending.length;
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
                const fd = new FormData(); 

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
                        Swal.fire('Tutup Kasir Sukses', result.message, 'success').then(() => { 
                            if (result.shift_id) {
                                window.open(`print_shift.php?id=${result.shift_id}`, '_blank', 'width=400,height=600');
                            }
                            setTimeout(() => { window.location.reload(); }, 500);
                        });
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
                if (!navigator.onLine) throw new Error("Offline");
                const response = await fetch(`logic_kasir.php?action=get_master_data&nocache=${Date.now()}`);
                if (!response.ok) throw new Error("Server Error");
                const result = await response.json(); 
                if (result.status === 'success') {
                    this.products = result.products; 
                    this.customers = result.customers;
                    this.savedCustoms = result.saved_customs || []; 
                    this.savedCustomsReguler = result.saved_customs_reguler || [];
                    this.paymentMethods = result.payment_methods || [];
                    this.loyaltyRules = result.loyalty_rules;
                    this.promosBuyGet = result.promos_buy_get || [];
                    this.promosAutoDisc = result.promos_auto_disc || [];
                    this.foodDeliveryPrices = result.food_delivery_prices || [];
                    this.posSettings = result.settings || {};
                    this.validSupervisorPins = result.valid_supervisor_pins || [];
                    
                    if(result.default_start_cash && !this.shiftForm.start_cash) {
                        this.shiftForm.start_cash = result.default_start_cash;
                    }

                    // Save to IndexedDB
                    await idbPos.setMasterData('products', this.products);
                    await idbPos.setMasterData('customers', this.customers);
                    await idbPos.setMasterData('saved_customs', this.savedCustoms);
                    await idbPos.setMasterData('saved_customs_reguler', this.savedCustomsReguler);
                    await idbPos.setMasterData('payment_methods', this.paymentMethods);
                    await idbPos.setMasterData('promos_buy_get', this.promosBuyGet);
                    await idbPos.setMasterData('promos_auto_disc', this.promosAutoDisc);
                    await idbPos.setMasterData('default_start_cash', result.default_start_cash);
                    await idbPos.setMasterData('settings', this.posSettings);
                    
                    if(isManualSync) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `Database Tersinkronisasi!`, showConfirmButton: false, timer: 1500 });
                }
            } catch (error) {
                console.error("❌ ERROR SINKRONISASI KASIR:", error);
                // Tarik dari IndexedDB jika offline atau error
                this.products = (await idbPos.getMasterData('products')) || [];
                this.customers = (await idbPos.getMasterData('customers')) || [];
                this.savedCustoms = (await idbPos.getMasterData('saved_customs')) || [];
                this.savedCustomsReguler = (await idbPos.getMasterData('saved_customs_reguler')) || [];
                this.paymentMethods = (await idbPos.getMasterData('payment_methods')) || [];
                this.promosBuyGet = (await idbPos.getMasterData('promos_buy_get')) || [];
                this.promosAutoDisc = (await idbPos.getMasterData('promos_auto_disc')) || [];
                this.posSettings = (await idbPos.getMasterData('settings')) || {};
                this.validSupervisorPins = (await idbPos.getMasterData('valid_supervisor_pins')) || [];
                const defCash = await idbPos.getMasterData('default_start_cash');
                if (defCash && !this.shiftForm.start_cash) this.shiftForm.start_cash = defCash;

                if (isManualSync) Swal.fire('Mode Offline', 'Sinkronisasi gagal. Menggunakan data lokal (Cek Console untuk detail error).', 'warning');
            } finally { this.isLoading = false; }
        },

        get filteredProducts() {
            if (this.searchQuery.trim() === '') return this.products;
            const q = this.searchQuery.toLowerCase();
            return this.products.filter(p => p.name && p.name.toLowerCase().includes(q));
        },
        get filteredCustomers() {
            if (this.searchCustomer.trim() === '') return this.customers;
            const q = this.searchCustomer.toLowerCase();
            return this.customers.filter(c => c.name && c.name.toLowerCase().includes(q));
        },
        get selectedCustomer() { return this.selectedCustomerId ? this.customers.find(c => c.id == this.selectedCustomerId) : null; },
        
        selectCustomer(id) {
            this.selectedCustomerId = id;
            this.isCustomerDropdownOpen = false;
            this.searchCustomer = '';
            this.onCustomerSelect();
        },

        async submitNewCustomer() {
            if(!this.newCustomerForm.name) {
                Swal.fire('Perhatian', 'Nama pelanggan wajib diisi!', 'warning'); return;
            }
            this.isSavingCustomer = true;
            try {
                const fd = new FormData();
                fd.append('name', this.newCustomerForm.name);
                fd.append('phone', this.newCustomerForm.phone || '');
                fd.append('birth_date', this.newCustomerForm.birth_date || '');
                fd.append('address', this.newCustomerForm.address || '');
                
                const res = await fetch('logic_kasir.php?action=add_customer', { method: 'POST', body: fd });
                const result = await res.json();
                
                if (result.status === 'success') {
                    this.customers = result.customers;
                    this.selectCustomer(result.new_id);
                    this.showAddCustomerModal = false;
                    this.newCustomerForm = { name: '', phone: '', address: '', birth_date: '' };
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: result.message, showConfirmButton: false, timer: 1500 });
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            } catch(e) {
                Swal.fire('Error', 'Gagal menyimpan pelanggan.', 'error');
            } finally {
                this.isSavingCustomer = false;
            }
        },

        scanBarcode() {
            const code = this.barcodeInput.trim().toUpperCase();
            if (!code) return;
            const product = this.products.find(p => (p.code || '').toUpperCase() === code);
            if (product) { this.addToCart(product); } else { Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'SKU Tidak Ditemukan!', showConfirmButton: false, timer: 1000 }); }
            this.barcodeInput = ''; setTimeout(() => { if(this.$refs.barcodeScanner) this.$refs.barcodeScanner.focus(); }, 10);
        },

        // --- FUNGSI KERANJANG ---
        addToCart(product) {
            let price = parseFloat(product.price || product.offline_price || 0);

            // Check if current channel is Food Delivery platform (GrabFood, GoFood, ShopeeFood, etc.)
            const currentChannel = (this.activeTab === 'po' ? this.poForm.channel : (this.regulerForm.is_delivery ? this.regulerForm.channel : 'toko'));
            if (currentChannel && !['toko', 'delivery'].includes(currentChannel.toLowerCase())) {
                const platformCode = currentChannel.toLowerCase();
                const fdMatch = (this.foodDeliveryPrices || []).find(fd => 
                    fd.platform_code === platformCode && 
                    fd.item_id == product.id && 
                    fd.item_type === (product.is_custom ? 'custom_reguler' : 'product')
                );
                if (fdMatch && parseFloat(fdMatch.final_price) > 0) {
                    price = parseFloat(fdMatch.final_price);
                }
            }

            const isCustomPrice = product.is_custom_price == 1;
            const existing = this.cart.find(item => item.id === product.id && !item.is_custom && !item.is_promo_free);
            if (existing) { 
                existing.qty++; 
                this.calcItemSubtotal(existing); 
            } else { 
                const newItem = { id: product.id, name: product.name, price: price, qty: 1, subtotal: price, is_custom: false, is_custom_price: isCustomPrice, discount_type: 'none', discount_value: 0 };
                this.calcItemSubtotal(newItem);
                this.cart.push(newItem); 
            }
            this.applyAutoPromos();
        },
        updateQty(index, change) {
            if (this.cart[index].is_promo_free) return;
            this.cart[index].qty += change;
            if (this.cart[index].qty <= 0) {
                this.removeItem(index);
                return;
            }
            this.calcItemSubtotal(this.cart[index]);
            this.applyAutoPromos();
        },
        updatePrice(index) {
            let p = parseFloat(this.cart[index].price);
            if (isNaN(p) || p < 0) p = 0;
            this.cart[index].price = p;
            this.calcItemSubtotal(this.cart[index]);
            this.applyAutoPromos();
        },
        removeItem(index) { 
            this.cart.splice(index, 1); 
            this.applyAutoPromos();
        },

        calcItemSubtotal(item) {
            if (item.is_promo_free) { item.subtotal = 0; return; }
            let gross = item.qty * item.price;
            let disc = 0;
            if (item.discount_type === 'percent') {
                disc = (gross * parseFloat(item.discount_value || 0)) / 100;
            } else if (item.discount_type === 'nominal') {
                disc = parseFloat(item.discount_value || 0);
            }
            let net = gross - disc;
            item.subtotal = net > 0 ? net : 0;
        },

        async setItemDiscount(index) {
            const item = this.cart[index];
            if (item.is_promo_free) {
                Swal.fire('Info', 'Barang gratis promo tidak dapat didiskon.', 'info');
                return;
            }
            const { value: formValues } = await Swal.fire({
                title: `Diskon Item: ${item.name}`,
                html: `
                    <div class="space-y-3 text-left">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Tipe Diskon</label>
                            <select id="swal-disc-type" class="w-full border rounded-xl px-3 py-2 text-sm font-bold">
                                <option value="percent" ${item.discount_type === 'percent' ? 'selected' : ''}>Persentase (%)</option>
                                <option value="nominal" ${item.discount_type === 'nominal' ? 'selected' : ''}>Nominal (Rp)</option>
                                <option value="none" ${!item.discount_type || item.discount_type === 'none' ? 'selected' : ''}>Tanpa Diskon</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Besaran Diskon</label>
                            <input id="swal-disc-val" type="number" min="0" value="${item.discount_value || 0}" class="w-full border rounded-xl px-3 py-2 text-sm font-bold" placeholder="Misal: 3 atau 5000">
                        </div>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Terapkan',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    return {
                        type: document.getElementById('swal-disc-type').value,
                        val: parseFloat(document.getElementById('swal-disc-val').value || 0)
                    }
                }
            });

            if (formValues) {
                item.discount_type = formValues.type;
                item.discount_value = formValues.type === 'none' ? 0 : formValues.val;
                this.calcItemSubtotal(item);
                this.applyAutoPromos();
            }
        },

        applyAutoPromos() {
            // 1. Hapus item gratis promo sebelumnya agar dikalkulasi ulang
            this.cart = this.cart.filter(item => !item.is_promo_free);

            // 2. Kalkulasi Promo Beli X Gratis Y
            if (this.promosBuyGet && this.promosBuyGet.length > 0) {
                this.promosBuyGet.forEach(rule => {
                    let totalBuyQty = 0;
                    this.cart.forEach(item => {
                        if (!item.is_custom && item.id == rule.buy_product_id) {
                            totalBuyQty += item.qty;
                        }
                    });
                    if (totalBuyQty >= rule.buy_qty && rule.buy_qty > 0) {
                        const multiplier = Math.floor(totalBuyQty / rule.buy_qty);
                        const freeQty = multiplier * rule.get_qty;
                        if (freeQty > 0) {
                            const freeProd = this.products.find(p => p.id == rule.get_product_id);
                            const prodName = freeProd ? freeProd.name : (rule.get_product_name || 'Item Gratis');
                            this.cart.push({
                                id: rule.get_product_id,
                                name: '[GRATIS] ' + prodName,
                                price: 0,
                                qty: freeQty,
                                subtotal: 0,
                                is_custom: false,
                                is_promo_free: true,
                                discount_type: 'none',
                                discount_value: 0
                            });
                        }
                    }
                });
            }

            // 3. Kalkulasi Diskon Otomatis Minimal Belanja
            const rawSubtotal = this.cart.reduce((sum, item) => sum + item.subtotal, 0);
            this.appliedAutoDisc = null;
            if (this.promosAutoDisc && this.promosAutoDisc.length > 0) {
                for (let rule of this.promosAutoDisc) {
                    if (rawSubtotal >= rule.min_purchase) {
                        this.appliedAutoDisc = rule;
                        break;
                    }
                }
            }
        },

        // --- FUNGSI ITEM CUSTOM BARU ---
        addCustomItem() {
            this.customItemForm = { template: '', name: '', price: '', is_custom_price: 1, showSuggestions: false, searchQuery: '' };
            if (this.activeTab === 'po') {
                this.showCustomItemModal = true;
            } else {
                this.showCustomRegulerModal = true;
            }
        },
        getFilteredTemplates(type) {
            const list = type === 'reguler' ? this.savedCustomsReguler : this.savedCustoms;
            const q = (this.customItemForm.searchQuery || this.customItemForm.name || '').toLowerCase().trim();
            if (!q) return list;
            return list.filter(t => (t.name || '').toLowerCase().includes(q));
        },
        chooseTemplate(t) {
            if (!t) {
                this.customItemForm.template = '';
                this.customItemForm.is_custom_price = 1;
                this.customItemForm.showSuggestions = false;
                return;
            }
            this.customItemForm.template = t.id;
            this.customItemForm.name = t.name;
            this.customItemForm.price = t.price;
            this.customItemForm.is_custom_price = (parseInt(t.is_custom_price) === 1 ? 1 : 0);
            this.customItemForm.showSuggestions = false;
        },
        selectCustomTemplate(e, type) {
            const id = e.target.value;
            if (!id) { 
                this.customItemForm.name = ''; 
                this.customItemForm.price = ''; 
                this.customItemForm.is_custom_price = 1;
                this.customItemForm.showSuggestions = false;
                return; 
            }
            const list = type === 'reguler' ? this.savedCustomsReguler : this.savedCustoms;
            const t = list.find(x => x.id == id);
            if (t) { 
                this.chooseTemplate(t);
            }
        },
        async saveCustomItem() {
            if (!this.customItemForm.name || !this.customItemForm.price) { Swal.fire('Error', 'Nama & Harga wajib diisi!', 'error'); return; }
            this.isSavingCustomItem = true;
            const fd = new FormData();
            fd.append('name', this.customItemForm.name); fd.append('price', this.customItemForm.price);
            fd.append('template_id', this.customItemForm.template);

            try {
                if (this.activeTab === 'reguler') {
                    // Logic Reguler
                    const res = await fetch('logic_kasir.php?action=save_custom_reguler_item', { method: 'POST', body: fd });
                    const result = await res.json();
                    if (result.status === 'success') {
                        this.showCustomRegulerModal = false;
                        
                        const customPrice = parseFloat(this.customItemForm.price);
                        const isDynamic = (this.customItemForm.is_custom_price == 1);
                        this.cart.push({
                            id: 'custom_reg_' + Date.now(),
                            name: this.customItemForm.name,
                            price: customPrice,
                            qty: 1,
                            subtotal: customPrice,
                            is_custom: true,
                            is_custom_price: isDynamic ? 1 : 0,
                            discount_type: 'none',
                            discount_value: 0
                        });
                        this.applyAutoPromos();

                        Swal.fire({ icon: 'success', title: 'Ditambahkan!', text: 'Item Custom masuk ke keranjang & tersimpan ke laporan!', timer: 1500, showConfirmButton: false });
                        fetch(`logic_kasir.php?action=get_master_data&nocache=${Date.now()}`)
                            .then(r => r.json()).then(resData => { if (resData.status === 'success') this.savedCustomsReguler = resData.saved_customs_reguler; });
                    } else { Swal.fire('Error', result.message, 'error'); }
                } else {
                    // Logic PO (Dapur)
                    if (this.customItemForm.template) {
                        try {
                            const fdCek = new FormData();
                            fdCek.append('custom_item_id', this.customItemForm.template);
                            const resCek = await fetch('logic_kasir.php?action=check_custom_recipe', { method: 'POST', body: fdCek });
                            const cekResult = await resCek.json();

                            if (cekResult.status === 'success' && !cekResult.has_recipe) {
                                await Swal.fire({
                                    icon: 'warning',
                                    title: '⚠️ Belum Ada Resep!',
                                    html: `Item custom <b>${this.customItemForm.name}</b> belum memiliki resep bahan baku.<br><br>Transaksi tetap bisa dilanjutkan, namun <b>bahan baku tidak akan terpotong otomatis</b>.`,
                                    confirmButtonText: 'Mengerti, Lanjutkan',
                                    confirmButtonColor: '#f59e0b'
                                });
                            }
                        } catch(e) {}
                    }

                    const res = await fetch('logic_kasir.php?action=save_custom_item', { method: 'POST', body: fd });
                    const result = await res.json();
                    if (result.status === 'success') {
                        this.showCustomItemModal = false;
                        const isDynamic = (this.customItemForm.is_custom_price == 1);
                        this.cart.push({ id: result.custom_id, name: this.customItemForm.name + ' (c)', price: parseFloat(this.customItemForm.price), qty: 1, subtotal: parseFloat(this.customItemForm.price), is_custom: true, is_po: true, template_id: result.custom_id, is_custom_price: isDynamic ? 1 : 0, discount_type: 'none', discount_value: 0 });
                        this.applyAutoPromos();
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: 'Item Custom PO masuk ke keranjang!', timer: 1000, showConfirmButton: false });
                        fetch(`logic_kasir.php?action=get_master_data&nocache=${Date.now()}`)
                            .then(r => r.json()).then(resData => { if (resData.status === 'success') this.savedCustoms = resData.saved_customs; });
                    } else { Swal.fire('Error', result.message, 'error'); }
                }
            } catch (e) {
                console.error(e); Swal.fire('Error', 'Gagal menyimpan item custom', 'error');
            } finally { this.isSavingCustomItem = false; }
        },


        onCustomerSelect() { this.usePoints = false; },
        togglePoints() { this.usePoints = !this.usePoints; },

        // --- FUNGSI DISKON & VOUCHER ---
        async openDiscountMenu() {
            if (this.subtotal <= 0) { window.alert('Keranjang masih kosong!'); return; }
            
            const result = await Swal.fire({
                title: 'Opsi Diskon',
                text: 'Pilih jenis diskon yang ingin digunakan',
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fa-solid fa-ticket mr-1"></i> Promo Dinamis',
                confirmButtonColor: '#1e293b', 
                denyButtonText: '<i class="fa-solid fa-user-shield mr-1"></i> Diskon Manual SPV',
                denyButtonColor: '#f43f5e', 
                cancelButtonText: 'Batal'
            });

            if (result.isConfirmed) {
                // Promo Dinamis
                const { value: code } = await Swal.fire({
                    title: 'Voucher Promo',
                    input: 'text',
                    inputPlaceholder: 'Masukkan Kode Promo',
                    showCancelButton: true,
                    confirmButtonText: 'Terapkan'
                });
                
                if (code) {
                    this.voucherCode = code.toUpperCase();
                    this.applyVoucher();
                }
            } else if (result.isDenied) {
                // Diskon Manual SPV — OTP PIN System
                const validPins = this.validSupervisorPins || [];
                const { value: inputPin } = await Swal.fire({ 
                    title: '🔐 Otorisasi Supervisor', 
                    input: 'password', 
                    inputPlaceholder: 'Masukkan PIN 6 Digit',
                    inputAttributes: { maxlength: 6, autocomplete: 'one-time-code' },
                    showCancelButton: true, 
                    confirmButtonText: 'Validasi',
                    cancelButtonText: 'Batal',
                    footer: '<span class="text-xs text-slate-400">Gunakan PIN yang telah digenerate oleh Admin</span>'
                });
                
                if (!inputPin) return; // user cancel

                // Validasi dari daftar PIN OTP
                if (validPins.includes(inputPin)) {
                    // Hapus PIN dari state lokal supaya tidak bisa dipakai dua kali (langsung)
                    this.validSupervisorPins = validPins.filter(p => p !== inputPin);
                    await idbPos.setMasterData('settings', { ...this.posSettings, _pins: this.validSupervisorPins });

                    // Kirim ke server untuk di-mark as used
                    try {
                        const fd = new FormData();
                        fd.append('action', 'use_supervisor_pin');
                        fd.append('pin', inputPin);
                        fetch('logic_kasir.php', { method: 'POST', body: fd });
                    } catch(e) { /* silent fail - PIN tetap hangus di lokal */ }

                    // Lanjutkan ke form diskon
                    const { value: formValues } = await Swal.fire({ 
                        title: 'Diskon Manual Kasir', 
                        html: `
                            <div class="space-y-3 text-left">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Tipe Diskon</label>
                                    <select id="swal-man-type" class="w-full border rounded-xl px-3 py-2 text-sm font-bold">
                                        <option value="NOMINAL" ${this.discountManualType === 'NOMINAL' ? 'selected' : ''}>Nominal (Rp)</option>
                                        <option value="PERCENT" ${this.discountManualType === 'PERCENT' ? 'selected' : ''}>Persentase (%)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Besaran Diskon</label>
                                    <input id="swal-man-val" type="number" min="0" value="${this.discountManualInput || 0}" class="w-full border rounded-xl px-3 py-2 text-sm font-bold" placeholder="Misal: 10 atau 15000">
                                </div>
                            </div>
                        `,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Terapkan',
                        cancelButtonText: 'Batal',
                        preConfirm: () => {
                            return {
                                type: document.getElementById('swal-man-type').value,
                                val: parseFloat(document.getElementById('swal-man-val').value || 0)
                            }
                        }
                    });
                    if (formValues && formValues.val >= 0) { 
                        this.discountManualType = formValues.type;
                        this.discountManualInput = formValues.val;
                    }
                } else { 
                    Swal.fire({
                        title: 'Akses Ditolak',
                        html: validPins.length === 0 
                            ? '<p>Belum ada PIN aktif.<br><span class="text-xs text-slate-500">Minta Admin untuk generate PIN terlebih dahulu di menu Pengaturan Toko.</span></p>'
                            : 'PIN tidak valid atau sudah pernah digunakan!',
                        icon: 'error'
                    }); 
                }
            }
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
        get discountManual() {
            if (!this.discountManualInput || this.discountManualInput <= 0) return 0;
            if (this.discountManualType === 'PERCENT') {
                return (this.subtotal * this.discountManualInput) / 100;
            }
            return this.discountManualInput;
        },
        get discountAuto() {
            if (!this.appliedAutoDisc) return 0;
            if (this.subtotal < this.appliedAutoDisc.min_purchase) { this.appliedAutoDisc = null; return 0; }
            let d = parseFloat(this.appliedAutoDisc.discount_value);
            return this.appliedAutoDisc.discount_type === 'PERCENT' ? (this.subtotal * d) / 100 : d;
        },
        get totalAmountBase() {
            let ongkir = 0;
            if (this.activeTab === 'po') ongkir = parseFloat(this.poForm.ongkir || 0);
            else if (this.activeTab === 'reguler' && this.regulerForm.is_delivery) ongkir = parseFloat(this.regulerForm.ongkir || 0);
            let total = this.subtotal + ongkir - this.discountVoucher - this.discountPoints - this.discountManual - this.discountAuto;
            return total > 0 ? total : 0;
        },
        get paymentFeeAmount() {
            if (!this.paymentMethods) return 0;
            const method = this.paymentMethods.find(m => m.name === this.paymentMethod);
            if (method && method.fee_percent > 0) {
                return this.totalAmountBase * (method.fee_percent / 100);
            }
            return 0;
        },
        get totalAmount() {
            return this.totalAmountBase + this.paymentFeeAmount;
        },
        get pointsEarned() {
            if (!this.selectedCustomer || !this.loyaltyRules?.is_active || this.loyaltyRules.earn_point_ratio <= 0) return 0;
            return Math.floor(this.totalAmount / this.loyaltyRules.earn_point_ratio);
        },
        get cashSuggestions() {
            if (!this.totalAmount) return [];
            const amount = this.totalAmount;
            let suggestions = [amount]; // Uang Pas
            
            const roundUpTo = (amt, multiple) => Math.ceil(amt / multiple) * multiple;
            
            const s1 = roundUpTo(amount, 10000);
            if (s1 > amount && !suggestions.includes(s1)) suggestions.push(s1);
            
            const s2 = roundUpTo(amount, 50000);
            if (s2 > amount && !suggestions.includes(s2)) suggestions.push(s2);
            
            const s3 = roundUpTo(amount, 100000);
            if (s3 > amount && !suggestions.includes(s3)) suggestions.push(s3);
            
            return suggestions.slice(0, 4);
        },

        // --- FUNGSI CHECKOUT ---
        processCheckout() {
            if(this.activeTab === 'po' && (!this.poForm.pickup_date || !this.poForm.pickup_time)) {
                Swal.fire('Perhatian', 'Tanggal dan Jam Pengambilan Pesanan PO wajib diisi!', 'warning'); return;
            }
            this.paymentStatus = 'lunas'; 
            this.paymentMethod = this.paymentMethods.length > 0 ? this.paymentMethods[0].name : 'Cash'; 
            this.inputUang = this.totalAmount; 
            this.showCheckoutModal = true;
            this.focusNominal();
        },
        setPaymentStatus(status) {
            this.paymentStatus = status;
            if(status === 'lunas') {
                this.inputUang = this.totalAmount;
            } else {
                this.inputUang = '';
            }
            this.focusNominal();
        },
        focusNominal() {
            setTimeout(() => {
                const el1 = document.getElementById('inputNominalLunas');
                const el2 = document.getElementById('inputNominalDp');
                const el = this.paymentStatus === 'lunas' ? el1 : el2;
                if(el) {
                    el.focus();
                    el.select();
                }
            }, 100);
        },

        submitCheckout() {
            const selectedMethod = this.paymentMethods.find(m => m.name === this.paymentMethod);
            this.paymentFeeName = selectedMethod ? selectedMethod.fee_name : '';

            if (this.paymentStatus === 'dp') {
                if(!this.selectedCustomerId) { Swal.fire('Perhatian', 'Transaksi DP/Kasbon wajib memilih nama Pelanggan di sidebar!', 'warning'); return; }
                if(!this.inputUang || this.inputUang <= 0 || this.inputUang > this.totalAmount) { Swal.fire('Perhatian', 'Nominal DP tidak valid!', 'warning'); return; }
                this.dpAmount = parseFloat(this.inputUang); this.amountPaid = this.dpAmount; this.changeAmount = 0; 
            } else {
                this.dpAmount = 0;
                if (this.paymentMethod === 'Cash') {
                    if (!this.inputUang || parseFloat(this.inputUang) < this.totalAmount) { Swal.fire('Perhatian', 'Uang diterima kurang dari total tagihan!', 'warning'); return; }
                    this.amountPaid = parseFloat(this.inputUang); this.changeAmount = this.amountPaid - this.totalAmount;
                } else {
                    if (!this.paymentReference || this.paymentReference.trim() === '') {
                        Swal.fire('Perhatian', 'Ref. Pembayaran wajib diisi untuk metode non-tunai!', 'warning');
                        return;
                    }
                    this.amountPaid = this.totalAmount; this.changeAmount = 0;
                }
            }
            this.showCheckoutModal = false; this.executeCheckout();
        },

        async executeCheckout() {
            this.isLoading = true;
            // Gabungkan notes dapur (PO) dan notes umum
            let finalNotes = this.orderNotes;

            // Tentukan channel berdasarkan tab
            let channel = 'toko';
            if (this.activeTab === 'po') channel = this.poForm.channel;
            else if (this.activeTab === 'reguler' && this.regulerForm.is_delivery) channel = 'delivery';

            const payload = {
                is_po: this.activeTab === 'po',
                channel: channel,
                pickup_date: this.activeTab === 'po' ? this.poForm.pickup_date : (this.regulerForm.pickup_date || null),
                pickup_time: this.activeTab === 'po' ? this.poForm.pickup_time : (this.regulerForm.pickup_time || null),
                ongkir: this.activeTab === 'po' ? this.poForm.ongkir : (this.regulerForm.is_delivery ? this.regulerForm.ongkir : 0),
                notes: finalNotes,
                order_type: this.orderType, customer_id: this.selectedCustomerId, subtotal: this.subtotal,
                discount_voucher: this.discountVoucher, voucher_code: this.appliedVoucher ? this.appliedVoucher.voucher_code : null,
                discount_points: this.discountPoints, discount_manual: this.discountManual, discount_auto: this.discountAuto, points_used: this.usePoints ? this.loyaltyRules.points_required : 0, points_earned: this.pointsEarned, 
                total_amount: this.totalAmount, payment_method: this.paymentMethod, payment_fee_name: this.paymentFeeName, payment_fee_amount: this.paymentFeeAmount, payment_reference: this.paymentReference, payment_status: this.paymentStatus,
                dp_amount: this.dpAmount, amount_paid: this.amountPaid, change_amount: this.changeAmount, items: this.cart
            };
            try {
                if (!this.isOnline) throw new Error("Offline");
                const response = await fetch('logic_kasir.php?action=checkout', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                if (!response.ok) throw new Error("Server Error");
                const result = await response.json();
                if (result.status === 'success') {
                    this.lastInvoice = result.invoice; this.totalAmountSaved = this.totalAmount; this.paymentStatusSaved = this.paymentStatus;
                    this.dpAmountSaved = this.dpAmount; this.amountPaidSaved = this.amountPaid; this.changeAmountSaved = this.changeAmount; this.paymentMethodSaved = this.paymentMethod;
                    this.showSuccessModal = true;
                    this.triggerSmartAutoPrint();
                } else { window.alert(result.message); }
            } catch (e) {
                // Mode Offline: Simpan ke IndexedDB
                console.warn("Koneksi offline, menyimpan transaksi ke memori lokal...");
                const offlineId = await idbPos.saveOfflineTransaction(payload);
                this.lastInvoice = offlineId; this.totalAmountSaved = this.totalAmount; this.paymentStatusSaved = this.paymentStatus;
                this.dpAmountSaved = this.dpAmount; this.amountPaidSaved = this.amountPaid; this.changeAmountSaved = this.changeAmount; this.paymentMethodSaved = this.paymentMethod;
                this.showSuccessModal = true;
                this.triggerSmartAutoPrint();
                await this.updatePendingCount();
            } finally { this.isLoading = false; }
        },

        async syncOfflineTransactions() {
            if (this.isSyncing || !this.isOnline) return;
            const pending = await idbPos.getOfflineTransactions();
            if (pending.length === 0) return;

            this.isSyncing = true;
            let successCount = 0;
            
            for (let i = 0; i < pending.length; i++) {
                const tx = pending[i];
                try {
                    const response = await fetch('logic_kasir.php?action=checkout', { 
                        method: 'POST', 
                        headers: { 'Content-Type': 'application/json' }, 
                        body: JSON.stringify(tx) 
                    });
                    const result = await response.json();
                    if (result.status === 'success') {
                        await idbPos.deleteOfflineTransaction(tx.id);
                        successCount++;
                    }
                } catch(e) { console.error("Sync failed for", tx.id); }
            }
            
            this.isSyncing = false;
            await this.updatePendingCount();
            
            if (successCount > 0) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `${successCount} Transaksi Offline Berhasil Diunggah!`, showConfirmButton: false, timer: 3000 });
                this.loadLocalData(false); // Update list stok lokal (opsional)
            }
        },

        triggerSmartAutoPrint() {
            if (!this.lastInvoice) return;
            const autoMode = localStorage.getItem('pos_auto_print_mode');
            const btActive = localStorage.getItem('pos_bt_active');

            // Cek mode: default selalu ke USB Thermal Printer kecuali bluetooth aktif secara eksplisit
            let mode = (autoMode === 'bluetooth' && btActive === '1') ? 'bluetooth' : 'usb';
            if (mode === 'usb') localStorage.setItem('pos_auto_print_mode', 'usb');

            // Tampilkan alert pintar di sudut kanan bawah (toast)
            if (typeof Swal !== 'undefined') {
                const usbName = localStorage.getItem('pos_usb_printer_name') || 'Thermal Printer (USB)';
                Swal.fire({
                    toast: true,
                    position: 'bottom-end',
                    icon: 'success',
                    title: mode === 'bluetooth' ? '📶 Otomatis mencetak ke Bluetooth...' : '🖨️ Printer USB Terdeteksi & Membaca...',
                    text: mode === 'usb' ? `Mencetak struk ke ${usbName}` : '',
                    showConfirmButton: false,
                    timer: 3500
                });
            }

            this.printReceipt(true, mode);
        },

        printReceipt(isAuto = true, mode = '') {
            let url = `print_receipt.php?invoice=${this.lastInvoice}`;
            if (!mode) {
                const autoMode = localStorage.getItem('pos_auto_print_mode');
                const btActive = localStorage.getItem('pos_bt_active');
                mode = (autoMode === 'bluetooth' && btActive === '1') ? 'bluetooth' : 'usb';
            }
            if (mode === 'bluetooth') url += `&auto_print_bt=1`;
            else url += `&auto_print_usb=1`;
            if(this.lastInvoice) window.open(url, '_blank', 'width=400,height=600');
            this.resetCart();
        },

        // --- DRAFT / HOLD BILL ---
        loadDrafts() {
            const saved = localStorage.getItem('pos_drafts');
            if (saved) {
                try { this.drafts = JSON.parse(saved); } catch(e) { this.drafts = []; }
            }
        },
        openSaveDraftModal() {
            if (this.cart.length === 0) return;
            this.draftReferenceName = this.selectedCustomer?.name || '';
            this.showSaveDraftModal = true;
            setTimeout(() => document.getElementById('draftRefInput')?.focus(), 100);
        },
        saveDraft() {
            if (!this.draftReferenceName.trim()) {
                Swal.fire('Perhatian', 'Nama referensi wajib diisi!', 'warning');
                return;
            }
            if (this.cart.length === 0) { Swal.fire('Perhatian', 'Keranjang kosong tidak dapat disimpan!', 'warning'); return; }
            this.draftCustomerName = this.selectedCustomerId ? (this.customers.find(c => c.id == this.selectedCustomerId)?.name || '') : '';
            this.draftNotes = this.orderNotes || '';
            this.showSaveDraftModal = true;
        },
        confirmSaveDraft() {
            const draft = {
                id: 'draft_' + Date.now(),
                timestamp: new Date().toISOString(),
                tab: this.activeTab,
                cart: JSON.parse(JSON.stringify(this.cart)),
                customerId: this.selectedCustomerId,
                customerName: this.draftCustomerName || 'Pelanggan Umum',
                notes: this.draftNotes,
                poForm: JSON.parse(JSON.stringify(this.poForm)),
                regulerForm: JSON.parse(JSON.stringify(this.regulerForm)),
                voucherCode: this.voucherCode,
                appliedVoucher: this.appliedVoucher ? JSON.parse(JSON.stringify(this.appliedVoucher)) : null,
                usePoints: this.usePoints,
                discountManual: this.discountManual
            };
            this.drafts.push(draft);
            localStorage.setItem('pos_drafts', JSON.stringify(this.drafts));
            this.showSaveDraftModal = false;
            this.resetCart();
        },
        restoreDraft(draftId) {
            const draftIndex = this.drafts.findIndex(d => d.id === draftId);
            if (draftIndex === -1) return;
            const draft = this.drafts[draftIndex];
            
            if (this.cart.length > 0) {
                Swal.fire({
                    title: 'Keranjang Tidak Kosong',
                    text: "Memuat draft akan menimpa keranjang saat ini. Lanjutkan?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Lanjutkan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) this.applyDraft(draft, draftIndex);
                });
            } else {
                this.applyDraft(draft, draftIndex);
            }
        },
        applyDraft(draft, draftIndex) {
            this.activeTab = draft.tab || 'reguler';
            this.cart = draft.cart || [];
            this.selectedCustomerId = draft.customerId || '';
            this.orderNotes = draft.notes || '';
            if(draft.poForm) this.poForm = draft.poForm;
            if(draft.regulerForm) this.regulerForm = draft.regulerForm;
            this.voucherCode = draft.voucherCode || '';
            this.appliedVoucher = draft.appliedVoucher || null;
            this.usePoints = draft.usePoints || false;
            this.discountManual = draft.discountManual || 0;

            this.drafts.splice(draftIndex, 1);
            localStorage.setItem('pos_drafts', JSON.stringify(this.drafts));
            this.showDraftModal = false;
            this.applyAutoPromos();
            Swal.fire({ icon: 'success', title: 'Dimuat', text: 'Draft berhasil dimuat kembali ke kasir!', timer: 1500, showConfirmButton: false });
        },
        deleteDraft(draftId) {
            this.drafts = this.drafts.filter(d => d.id !== draftId);
            localStorage.setItem('pos_drafts', JSON.stringify(this.drafts));
        },
        formatDraftTime(isoString) {
            const date = new Date(isoString);
            return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' - ' + date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
        },

        resetCart() {
            this.cart = []; this.selectedCustomerId = ''; this.voucherCode = ''; this.appliedVoucher = null;
            this.usePoints = false; this.discountManual = 0; this.discountManualInput = 0; this.appliedAutoDisc = null; this.paymentMethod = 'Cash'; this.paymentFeeName = ''; this.paymentStatus = 'lunas';
            this.amountPaid = 0; this.dpAmount = 0; this.changeAmount = 0; this.inputUang = 0;
            this.orderNotes = '';
            this.poForm = { channel: 'toko', pickup_date: '', pickup_time: '', ongkir: 0, notes: '' };
            this.regulerForm = { pickup_date: '', pickup_time: '', is_delivery: false, ongkir: 0, channel: 'toko' };
            this.showSuccessModal = false;
        },

        // --- FUNGSI STATUS PO ---
        async openStatusModal(mode = null) {
            if (mode) this.statusFilterMode = mode;
            this.showStatusModal = true; this.isFetchingStatus = true;
            try {
                const response = await fetch(`logic_kasir.php?action=get_active_orders&mode=${this.statusFilterMode}&date=${this.statusFilterDate}&nocache=${Date.now()}`);
                const result = await response.json();
                if(result.status === 'success') { this.activeOrders = result.data; }
            } catch(e) { console.error(e); } finally { this.isFetchingStatus = false; }
        },
        async updateProductionStatus(orderId, newStatus) {
            try {
                const res = await fetch(`logic_kasir.php?action=update_production_status`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: orderId, status: newStatus })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Status Diperbarui!', showConfirmButton: false, timer: 1500 });
                    this.openStatusModal();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', data.message || 'Gagal update status', 'error');
                }
            } catch(e) { console.error(e); }
        },

        formatRupiah(angka) { 
            const val = parseFloat(angka); if (isNaN(val)) return '0';
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val); 
        }
    }));
});