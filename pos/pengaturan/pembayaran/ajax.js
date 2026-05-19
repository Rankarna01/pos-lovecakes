function pembayaranApp() {
    return {
        isLoading: false,
        paymentMethods: [],
        form: {
            id: '',
            type: 'Cash',
            name: '',
            fee_name: '',
            fee_percent: 0
        },

        init() {
            this.loadMethods();
        },

        async loadMethods() {
            this.isLoading = true;
            try {
                const response = await fetch('logic.php?action=get_methods');
                const res = await response.json();
                if (res.status === 'success') {
                    this.paymentMethods = res.data;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoading = false;
            }
        },

        editMethod(item) {
            this.form.id = item.id;
            this.form.type = item.type;
            this.form.name = item.name;
            this.form.fee_name = item.fee_name;
            this.form.fee_percent = item.fee_percent;
        },

        resetForm() {
            this.form = {
                id: '',
                type: 'Cash',
                name: '',
                fee_name: '',
                fee_percent: 0
            };
        },

        async saveMethod() {
            if (!this.form.name) {
                Swal.fire('Error', 'Nama metode wajib diisi', 'error');
                return;
            }

            this.isLoading = true;
            try {
                let formData = new FormData();
                formData.append('id', this.form.id);
                formData.append('type', this.form.type);
                formData.append('name', this.form.name);
                formData.append('fee_name', this.form.fee_name);
                formData.append('fee_percent', this.form.fee_percent);

                const response = await fetch('logic.php?action=save_method', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    this.resetForm();
                    this.loadMethods();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal terhubung ke server', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        async toggleStatus(id, currentStatus) {
            let newStatus = currentStatus == 1 ? 0 : 1;
            try {
                let formData = new FormData();
                formData.append('id', id);
                formData.append('is_active', newStatus);

                const response = await fetch('logic.php?action=toggle_status', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                if (res.status === 'success') {
                    this.loadMethods();
                }
            } catch (e) {
                console.error(e);
            }
        },
        
        async deleteMethod(id) {
            Swal.fire({
                title: 'Hapus Metode?',
                text: 'Data yang dihapus tidak bisa dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        let formData = new FormData();
                        formData.append('id', id);
                        
                        const response = await fetch('logic.php?action=delete_method', {
                            method: 'POST',
                            body: formData
                        });
                        const res = await response.json();
                        if (res.status === 'success') {
                            this.loadMethods();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
            });
        },

        getIconClass(type) {
            switch(type.toLowerCase()) {
                case 'cash': return 'fa-solid fa-money-bill-wave text-emerald-500';
                case 'debit': return 'fa-solid fa-credit-card text-blue-500';
                case 'qris': return 'fa-solid fa-qrcode text-blue-600';
                case 'transfer': return 'fa-solid fa-building-columns text-purple-500';
                case 'hutang': return 'fa-solid fa-hand-holding-dollar text-amber-500';
                default: return 'fa-solid fa-wallet text-slate-500';
            }
        }
    }
}
