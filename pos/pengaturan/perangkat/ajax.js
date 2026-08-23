document.addEventListener('alpine:init', () => {
    Alpine.data('deviceSettingsApp', () => ({
        isLoading: true,
        devices: [],
        warehouses: [],
        enableRestriction: false,
        passcode: '',
        newPasscode: '',
        isSavingPasscode: false,
        searchQuery: '',
        
        editModal: {
            show: false,
            id: 0,
            device_name: '',
            warehouse_id: 1
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            this.isLoading = true;
            try {
                const res = await fetch(`logic.php?action=get_data&nocache=${Date.now()}`);
                const result = await res.json();
                if (result.status === 'success') {
                    this.devices = result.devices || [];
                    this.warehouses = result.warehouses || [];
                    this.enableRestriction = !!result.enable_restriction;
                    this.passcode = result.passcode || '';
                    this.newPasscode = this.passcode;
                }
            } catch (e) {
                console.error("Gagal memuat data perangkat:", e);
            } finally {
                this.isLoading = false;
            }
        },

        get filteredDevices() {
            if (!this.searchQuery || this.searchQuery.trim() === '') {
                return this.devices;
            }
            const q = this.searchQuery.toLowerCase();
            return this.devices.filter(d => 
                (d.device_name && d.device_name.toLowerCase().includes(q)) ||
                (d.store_name && d.store_name.toLowerCase().includes(q)) ||
                (d.registered_ip && d.registered_ip.toLowerCase().includes(q))
            );
        },

        get activeDevicesCount() {
            return this.devices.filter(d => parseInt(d.is_active) === 1).length;
        },

        async toggleGlobalRestriction() {
            const nextState = !this.enableRestriction;
            const confirmMsg = nextState 
                ? "Apakah Anda yakin ingin MENGAKTIFKAN Pembatasan Perangkat Kasir? Seluruh perangkat yang belum didaftarkan tidak akan bisa mengakses kasir."
                : "Apakah Anda yakin ingin MENONAKTIFKAN Pembatasan Perangkat Kasir? Siapa saja yang login bisa membuka kasir (Mode Publik).";

            const swalRes = await Swal.fire({
                title: nextState ? 'Aktifkan Pembatasan?' : 'Nonaktifkan Pembatasan?',
                text: confirmMsg,
                icon: nextState ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: nextState ? '#2563eb' : '#64748b',
                confirmButtonText: nextState ? 'Ya, Aktifkan!' : 'Ya, Nonaktifkan',
                cancelButtonText: 'Batal'
            });

            if (!swalRes.isConfirmed) return;

            try {
                const res = await fetch('logic.php?action=toggle_restriction', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ enable: nextState ? 1 : 0 })
                });
                const result = await res.json();
                if (result.status === 'success') {
                    this.enableRestriction = result.enable_restriction;
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: result.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Terjadi kesalahan sistem saat mengubah saklar global.', 'error');
            }
        },

        async savePasscode() {
            if (!this.newPasscode.trim()) {
                Swal.fire('Peringatan', 'Sandi Aktivasi tidak boleh kosong!', 'warning');
                return;
            }

            this.isSavingPasscode = true;
            try {
                const res = await fetch('logic.php?action=update_passcode', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ passcode: this.newPasscode.trim() })
                });
                const result = await res.json();
                if (result.status === 'success') {
                    this.passcode = result.passcode;
                    Swal.fire({
                        icon: 'success',
                        title: 'Sandi Diperbarui!',
                        text: result.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal memperbarui sandi aktivasi.', 'error');
            } finally {
                this.isSavingPasscode = false;
            }
        },

        async toggleDeviceStatus(device) {
            const nextStatus = parseInt(device.is_active) === 1 ? 0 : 1;
            const actionText = nextStatus === 1 ? 'mengaktifkan kembali' : 'memblokir / menonaktifkan';

            const swalRes = await Swal.fire({
                title: nextStatus === 1 ? 'Aktifkan Perangkat?' : 'Blokir Akses Perangkat?',
                text: `Apakah Anda yakin ingin ${actionText} perangkat "${device.device_name}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: nextStatus === 1 ? '#10b981' : '#ef4444',
                confirmButtonText: nextStatus === 1 ? 'Ya, Aktifkan' : 'Ya, Blokir Akses',
                cancelButtonText: 'Batal'
            });

            if (!swalRes.isConfirmed) return;

            try {
                const res = await fetch('logic.php?action=toggle_device_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: device.id, is_active: nextStatus })
                });
                const result = await res.json();
                if (result.status === 'success') {
                    device.is_active = nextStatus;
                    Swal.fire({
                        icon: 'success',
                        title: 'Status Berhasil Diubah!',
                        text: result.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Gagal', result.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Gagal memperbarui status perangkat.', 'error');
            }
        },

        openEditModal(device) {
            this.editModal = {
                show: true,
                id: device.id,
                device_name: device.device_name,
                warehouse_id: parseInt(device.warehouse_id) || 1
            };
        },

        async saveDeviceEdit() {
            if (!this.editModal.device_name.trim()) {
                Swal.fire('Peringatan', 'Nama Perangkat wajib diisi!', 'warning');
                return;
            }

            try {
                const res = await fetch('logic.php?action=update_device', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: this.editModal.id,
                        device_name: this.editModal.device_name.trim(),
                        warehouse_id: this.editModal.warehouse_id
                    })
                });
                const result = await res.json();
                if (result.status === 'success') {
                    this.editModal.show = false;
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
                Swal.fire('Error', 'Gagal menyimpan perubahan perangkat.', 'error');
            }
        },

        async deleteDevice(device) {
            const swalRes = await Swal.fire({
                title: 'Hapus Perangkat?',
                text: `Hapus perangkat "${device.device_name}" secara permanen? Perangkat ini tidak akan bisa membuka kasir lagi.`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus Permanen!',
                cancelButtonText: 'Batal'
            });

            if (!swalRes.isConfirmed) return;

            try {
                const res = await fetch('logic.php?action=delete_device', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: device.id })
                });
                const result = await res.json();
                if (result.status === 'success') {
                    this.devices = this.devices.filter(d => d.id !== device.id);
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
                Swal.fire('Error', 'Gagal menghapus perangkat.', 'error');
            }
        },

        formatDateTime(str) {
            if (!str) return '-';
            const d = new Date(str);
            if (isNaN(d)) return str;
            return d.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }));
});
