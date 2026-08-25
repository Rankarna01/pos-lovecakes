document.addEventListener('alpine:init', () => {
    Alpine.data('platformPaymentApp', () => ({
        isLoading: true,
        activeTab: 'grabfood',
        platforms: [],
        methods: [],
        searchQuery: '',

        modal: {
            show: false,
            isEdit: false,
            id: 0,
            platform_code: 'grabfood',
            name: '',
            type: 'Digital',
            fee_percent: 0,
            isSaving: false
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            this.isLoading = true;
            try {
                const res = await fetch(`logic.php?action=get_methods&nocache=${Date.now()}`);
                const result = await res.json();
                if (result.status === 'success') {
                    this.methods = result.methods || [];
                    this.platforms = result.platforms || [];
                    if (this.platforms.length > 0 && !this.platforms.some(p => p.platform_code === this.activeTab)) {
                        this.activeTab = this.platforms[0].platform_code;
                    }
                }
            } catch (e) {
                console.error("Gagal memuat metode pembayaran platform:", e);
            } finally {
                this.isLoading = false;
            }
        },

        get currentPlatformMethods() {
            let list = this.methods.filter(m => m.platform_code === this.activeTab);
            if (this.searchQuery && this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                list = list.filter(m => m.name.toLowerCase().includes(q) || (m.type && m.type.toLowerCase().includes(q)));
            }
            return list;
        },

        get activePlatformInfo() {
            return this.platforms.find(p => p.platform_code === this.activeTab) || { platform_name: this.activeTab.toUpperCase() };
        },

        openAddModal() {
            this.modal = {
                show: true,
                isEdit: false,
                id: 0,
                platform_code: this.activeTab,
                name: '',
                type: 'Digital',
                fee_percent: 0,
                isSaving: false
            };
        },

        openEditModal(method) {
            this.modal = {
                show: true,
                isEdit: true,
                id: method.id,
                platform_code: method.platform_code,
                name: method.name,
                type: method.type || 'Digital',
                fee_percent: parseFloat(method.fee_percent || 0),
                isSaving: false
            };
        },

        async saveMethod() {
            if (!this.modal.name.trim()) {
                Swal.fire('Peringatan', 'Nama metode pembayaran wajib diisi!', 'warning');
                return;
            }

            this.modal.isSaving = true;
            try {
                const res = await fetch('logic.php?action=save_method', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: this.modal.id,
                        platform_code: this.modal.platform_code,
                        name: this.modal.name.trim(),
                        type: this.modal.type,
                        fee_percent: this.modal.fee_percent
                    })
                });
                const result = await res.json();
                if (result.status === 'success') {
                    this.modal.show = false;
                    await this.loadData();
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: result.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal menyimpan metode pembayaran.', 'error');
            } finally {
                this.modal.isSaving = false;
            }
        },

        async toggleStatus(method) {
            const nextStatus = parseInt(method.is_active) === 1 ? 0 : 1;
            try {
                const res = await fetch('logic.php?action=toggle_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: method.id, is_active: nextStatus })
                });
                const result = await res.json();
                if (result.status === 'success') {
                    method.is_active = nextStatus;
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    Toast.fire({
                        icon: 'success',
                        title: result.message
                    });
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal mengubah status metode.', 'error');
            }
        },

        async deleteMethod(method) {
            const swalRes = await Swal.fire({
                title: 'Hapus Metode?',
                text: `Hapus metode pembayaran "${method.name}" dari platform ${this.activePlatformInfo.platform_name}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            });

            if (!swalRes.isConfirmed) return;

            try {
                const res = await fetch('logic.php?action=delete_method', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: method.id })
                });
                const result = await res.json();
                if (result.status === 'success') {
                    this.methods = this.methods.filter(m => m.id !== method.id);
                    Swal.fire({
                        icon: 'success',
                        title: 'Dihapus!',
                        text: result.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal menghapus metode.', 'error');
            }
        }
    }));
});
