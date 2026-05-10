document.addEventListener('alpine:init', () => {
    Alpine.data('accountingApp', () => ({
        isLoading: false,
        activeFilter: 'today',
        filterLabel: 'Hari Ini',
        startDate: '',
        endDate: '',
        selectedShift: '',
        
        masterShifts: [],
        summary: { income: 0, expense: 0, net_profit: 0 },
        journal: [],

        async init() {
            // ❌ CEK SESI dbAuth DIHAPUS TOTAL!
            // Keamanan sudah diamankan 100% oleh config/auth.php di server.

            this.setQuickFilter('today');
            
            // Jalankan penarikan data awal
            await this.loadMasterShifts();
            this.loadData();
        },

        async loadMasterShifts() {
            // 🛡️ CEGAT JIKA OFFLINE
            if (!navigator.onLine) return;

            try {
                const res = await fetch(`logic.php?action=get_master_shifts`);
                const result = await res.json();
                if(result.status === 'success') this.masterShifts = result.data;
            } catch(e) { 
                console.error("Gagal load shifts", e); 
            }
        },

        setQuickFilter(type) {
            this.activeFilter = type;
            const today = new Date();
            
            if (type === 'today') {
                const d = today.toISOString().split('T')[0];
                this.startDate = d; this.endDate = d;
                this.filterLabel = 'Hari Ini';
            } 
            else if (type === 'week') {
                const first = today.getDate() - today.getDay() + 1; // Senin
                const last = first + 6; // Minggu
                const startStr = new Date(today.setDate(first)).toISOString().split('T')[0];
                const endStr = new Date(today.setDate(last)).toISOString().split('T')[0];
                this.startDate = startStr; this.endDate = endStr;
                this.filterLabel = 'Minggu Ini';
            } 
            else if (type === 'month') {
                const y = today.getFullYear();
                const m = String(today.getMonth() + 1).padStart(2, '0');
                const lastDay = new Date(y, today.getMonth() + 1, 0).getDate();
                
                this.startDate = `${y}-${m}-01`;
                this.endDate = `${y}-${m}-${lastDay}`;
                this.filterLabel = `Bulan Ini (${y}-${m})`;
            }
        },

        async loadData() {
            // 🛡️ CEGAT JIKA OFFLINE
            if (!navigator.onLine) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Offline Mode', 'Laporan Akuntansi membutuhkan koneksi internet!', 'warning');
                }
                this.isLoading = false;
                return;
            }

            if(!this.startDate || !this.endDate) return;
            this.isLoading = true;

            // Reset Active Filter button jika user ubah tanggal manual
            if(this.activeFilter && (this.activeFilter !== 'custom')) {
                // Pengecekan sederhana apakah input manual berbeda dengan quick filter
                // (Optional: Bisa ditambahkan logika pendeteksi input manual di sini)
            }

            try {
                const url = `logic.php?action=get_accounting&start=${this.startDate}&end=${this.endDate}&shift_id=${this.selectedShift}&nocache=${Date.now()}`;
                const res = await fetch(url);
                const rawText = await res.text();
                
                try {
                    const result = JSON.parse(rawText);
                    if(result.status === 'success') {
                        this.summary = result.summary;
                        this.journal = result.journal;
                    } else {
                        if (typeof Swal !== 'undefined') Swal.fire('Error', result.message, 'error');
                    }
                } catch(err) {
                    console.error("❌ ERROR PHP:", rawText);
                    if (typeof Swal !== 'undefined') Swal.fire('CRASH!', 'Terdapat kesalahan pada respon server. Cek Console.', 'error');
                }
            } catch(e) { 
                if (typeof Swal !== 'undefined') Swal.fire('Error Jaringan', 'Koneksi jaringan terputus atau server tidak merespon.', 'error'); 
            } 
            finally { 
                // WAJIB: Matikan spinner loading apapun yang terjadi
                this.isLoading = false; 
            }
        },

        formatRupiah(angka) { 
            return new Intl.NumberFormat('id-ID').format(parseFloat(angka) || 0); 
        }
    }));
});