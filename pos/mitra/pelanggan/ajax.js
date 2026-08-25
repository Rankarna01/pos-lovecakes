document.addEventListener('alpine:init', () => {
    Alpine.data('customerApp', () => ({
        customers: [],
        templates: [],
        storeInfo: { store_name: 'Love Cakes', store_phone: '' },
        stats: { total: 0, birthday_today: 0, birthday_month: 0 },
        searchQuery: '',
        filterTab: 'all', // 'all' | 'birthday_today' | 'birthday_month' | 'top_points'
        isLoading: false,
        
        // Pagination
        currentPage: 1,
        itemsPerPage: 10,
        
        // Modal Customer Form
        showModal: false,
        isEdit: false,
        form: { id: '', name: '', phone: '', birth_date: '', address: '', points: 0, custom_notes: '' },

        // Modal WhatsApp Dynamic Scripting
        showWAModal: false,
        activeCustomer: null,
        selectedTemplateId: '',
        waMessageText: '',

        // Modal Template Manager
        showTemplateManagerModal: false,
        templateForm: { id: '', title: '', template_text: '', category: 'general' },
        isEditingTemplate: false,
        isSavingTemplate: false,

        async init() {
            this.$watch('searchQuery', () => this.currentPage = 1);
            this.$watch('filterTab', () => this.currentPage = 1);
            await this.fetchData();
        },

        // Tarik data dari server
        async fetchData() {
            this.isLoading = true;
            try {
                const response = await fetch(`logic.php?action=read&nocache=${new Date().getTime()}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.customers = result.data || [];
                    this.templates = result.templates || [];
                    this.storeInfo = result.store_info || { store_name: 'Love Cakes' };
                    this.stats = result.stats || { total: this.customers.length, birthday_today: 0, birthday_month: 0 };
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Error', result.message, 'error');
                    else alert('Gagal memuat data: ' + result.message);
                }
            } catch (error) {
                console.error("Error loading customers", error);
            } finally {
                this.isLoading = false;
            }
        },

        get filteredData() {
            let list = this.customers;

            // 1. Filter by Tab
            if (this.filterTab === 'birthday_today') {
                list = list.filter(c => c.is_birthday_today);
            } else if (this.filterTab === 'birthday_month') {
                list = list.filter(c => c.is_birthday_this_month);
            } else if (this.filterTab === 'top_points') {
                list = [...list].sort((a, b) => (parseInt(b.points) || 0) - (parseInt(a.points) || 0));
            }

            // 2. Filter by Search Query
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(c => 
                    (c.name && c.name.toLowerCase().includes(q)) || 
                    (c.phone && c.phone.toLowerCase().includes(q)) ||
                    (c.address && c.address.toLowerCase().includes(q)) ||
                    (c.custom_notes && c.custom_notes.toLowerCase().includes(q))
                );
            }

            return list;
        },

        get paginatedData() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            return this.filteredData.slice(start, start + this.itemsPerPage);
        },

        get totalPages() {
            return Math.ceil(this.filteredData.length / this.itemsPerPage) || 1;
        },

        // --- CUSTOMER FORM MODAL ---
        openModal(item = null) {
            if (item) {
                this.isEdit = true;
                this.form = { 
                    id: item.id, 
                    name: item.name, 
                    phone: item.phone || '', 
                    birth_date: item.birth_date || '', 
                    address: item.address || '', 
                    points: item.points || 0,
                    custom_notes: item.custom_notes || ''
                };
            } else {
                this.isEdit = false;
                this.form = { id: '', name: '', phone: '', birth_date: '', address: '', points: 0, custom_notes: '' };
            }
            this.showModal = true;
        },

        closeModal() { 
            this.showModal = false; 
        },

        async simpanData() {
            if (!this.form.name) { 
                if (typeof Swal !== 'undefined') Swal.fire('Perhatian', 'Nama Pelanggan wajib diisi!', 'warning');
                else alert('Nama Pelanggan wajib diisi!');
                return; 
            }

            this.isLoading = true;
            try {
                const fd = new FormData();
                for (const key in this.form) fd.append(key, this.form[key] ?? '');

                const response = await fetch('logic.php?action=save', { method: 'POST', body: fd });
                const result = await response.json();

                if (result.status === 'success') {
                    this.closeModal();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: result.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                    await this.fetchData();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
                    else alert(result.message);
                }
            } catch (error) {
                console.error("Error saving customer", error);
            } finally {
                this.isLoading = false;
            }
        },

        async hapusData(id) {
            const doDelete = async () => {
                this.isLoading = true;
                try {
                    const fd = new FormData();
                    fd.append('id', id);
                    const response = await fetch('logic.php?action=delete', { method: 'POST', body: fd });
                    const result = await response.json();

                    if (result.status === 'success') {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Dihapus!',
                                text: result.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                        await this.fetchData();
                    } else {
                        if (typeof Swal !== 'undefined') Swal.fire('Gagal', result.message, 'error');
                    }
                } catch (error) {
                    console.error("Error deleting", error);
                } finally {
                    this.isLoading = false;
                }
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Hapus Pelanggan?',
                    text: "Data pelanggan dan riwayat poin akan dihapus permanen.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((res) => {
                    if (res.isConfirmed) doDelete();
                });
            } else {
                if (confirm('Yakin ingin menghapus pelanggan ini?')) doDelete();
            }
        },

        // --- WHATSAPP DYNAMIC SCRIPTING MODAL ---
        openWAModal(customer) {
            if (!customer.phone || customer.phone.trim() === '') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Nomor WA Kosong', 'Pelanggan ini belum memiliki nomor WhatsApp.', 'warning');
                } else {
                    alert('Nomor WhatsApp pelanggan belum diisi!');
                }
                return;
            }

            this.activeCustomer = customer;

            // Prioritas Template: Jika ulang tahun hari ini -> template birthday
            let chosenTpl = null;
            if (customer.is_birthday_today) {
                chosenTpl = this.templates.find(t => t.category === 'birthday') || this.templates[0];
            } else {
                chosenTpl = this.templates.find(t => t.is_default == 1) || this.templates[0];
            }

            if (chosenTpl) {
                this.selectedTemplateId = chosenTpl.id;
                this.waMessageText = chosenTpl.template_text;
            } else {
                this.selectedTemplateId = '';
                this.waMessageText = `Halo Kak {nama} dari *{toko}*! 👋\n\nTerima kasih telah menjadi member setia kami. Total Poin Loyalitas Kakak saat ini: *{poin} Poin*.`;
            }

            this.showWAModal = true;
        },

        closeWAModal() {
            this.showWAModal = false;
            this.activeCustomer = null;
        },

        applyTemplate(tplId) {
            this.selectedTemplateId = tplId;
            const tpl = this.templates.find(t => t.id == tplId);
            if (tpl) {
                this.waMessageText = tpl.template_text;
            }
        },

        insertTag(tag) {
            const textarea = document.getElementById('waTextarea');
            if (textarea) {
                const start = textarea.selectionStart || 0;
                const end = textarea.selectionEnd || 0;
                const currentText = this.waMessageText || '';
                this.waMessageText = currentText.substring(0, start) + tag + currentText.substring(end);
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.setSelectionRange(start + tag.length, start + tag.length);
                });
            } else {
                this.waMessageText = (this.waMessageText || '') + ' ' + tag;
            }
        },

        // Render preview pesan dinamis dengan tag diganti nilai aktual
        get renderedWAMessage() {
            if (!this.activeCustomer) return '';
            let text = this.waMessageText || '';
            const c = this.activeCustomer;
            const storeName = this.storeInfo.store_name || 'Love Cakes';
            const memberId = '#' + String(c.id).padStart(4, '0');
            const birthDate = c.formatted_birth_date || '-';

            text = text.replaceAll('{nama}', c.name || 'Pelanggan');
            text = text.replaceAll('{customer_name}', c.name || 'Pelanggan');
            text = text.replaceAll('{poin}', c.points || 0);
            text = text.replaceAll('{points}', c.points || 0);
            text = text.replaceAll('{toko}', storeName);
            text = text.replaceAll('{store_name}', storeName);
            text = text.replaceAll('{tgl_lahir}', birthDate);
            text = text.replaceAll('{birth_date}', birthDate);
            text = text.replaceAll('{member_id}', memberId);
            text = text.replaceAll('{alamat}', c.address || '-');

            return text;
        },

        // Eksekusi redirect ke WhatsApp Web / WhatsApp Desktop / App
        sendWhatsAppRedirect() {
            if (!this.activeCustomer || !this.activeCustomer.phone) {
                if (typeof Swal !== 'undefined') Swal.fire('Error', 'Nomor WhatsApp tidak valid.', 'error');
                return;
            }

            let phone = this.activeCustomer.phone.replace(/[^0-9]/g, '');
            if (phone.startsWith('0')) {
                phone = '62' + phone.substring(1);
            } else if (phone.startsWith('8')) {
                phone = '62' + phone;
            }

            const message = this.renderedWAMessage;
            const waUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;

            window.open(waUrl, '_blank');
        },

        // --- TEMPLATE MANAGER ---
        openTemplateManager() {
            this.templateForm = { id: '', title: '', template_text: '', category: 'general' };
            this.isEditingTemplate = false;
            this.showTemplateManagerModal = true;
        },

        closeTemplateManager() {
            this.showTemplateManagerModal = false;
        },

        editTemplate(tpl) {
            this.templateForm = { ...tpl };
            this.isEditingTemplate = true;
        },

        cancelEditTemplate() {
            this.templateForm = { id: '', title: '', template_text: '', category: 'general' };
            this.isEditingTemplate = false;
        },

        async saveTemplate() {
            if (!this.templateForm.title || !this.templateForm.template_text) {
                if (typeof Swal !== 'undefined') Swal.fire('Perhatian', 'Judul dan isi template wajib diisi!', 'warning');
                return;
            }

            this.isSavingTemplate = true;
            try {
                const fd = new FormData();
                for (const k in this.templateForm) fd.append(k, this.templateForm[k]);

                const res = await fetch('logic.php?action=save_template', { method: 'POST', body: fd });
                const json = await res.json();

                if (json.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: json.message, timer: 1200, showConfirmButton: false });
                    }
                    this.cancelEditTemplate();
                    await this.fetchData();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal', json.message, 'error');
                }
            } catch (err) {
                console.error("Error saving template", err);
            } finally {
                this.isSavingTemplate = false;
            }
        },

        async deleteTemplate(id) {
            if (confirm('Yakin ingin menghapus template ini?')) {
                try {
                    const fd = new FormData();
                    fd.append('id', id);
                    const res = await fetch('logic.php?action=delete_template', { method: 'POST', body: fd });
                    const json = await res.json();
                    if (json.status === 'success') {
                        await this.fetchData();
                    }
                } catch (e) {
                    console.error("Error deleting template", e);
                }
            }
        },

        // --- IMPORT CSV HANDLER ---
        async handleImport(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            this.isLoading = true;
            try {
                const response = await fetch('logic.php?action=import_csv', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.status === 'success') {
                    if (typeof Swal !== 'undefined') Swal.fire('Import Berhasil!', result.message, 'success');
                    else alert(result.message);
                    await this.fetchData();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire('Gagal Import', result.message, 'error');
                    else alert('Gagal: ' + result.message);
                }
            } catch (error) {
                console.error("Import error", error);
            } finally {
                this.isLoading = false;
                event.target.value = '';
            }
        }
    }));
});