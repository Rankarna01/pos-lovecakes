/**
 * LoveCakes POS - Device Authorization & Lock Guard
 * Memeriksa status pembatasan perangkat & menangani pendaftaran perangkat via Sandi Aktivasi
 */

(function () {
    const API_URL = (typeof BASE_URL !== 'undefined' ? BASE_URL : '../../') + 'pos/api/device_auth.php';

    async function checkDeviceAuthorization() {
        const token = localStorage.getItem('pos_device_token') || '';
        
        try {
            const res = await fetch(`${API_URL}?action=check_status&device_token=${encodeURIComponent(token)}&nocache=${Date.now()}`);
            const data = await res.json();

            if (data.status === 'locked' || (data.is_restricted && !data.is_valid)) {
                renderDeviceLockScreen(data.message);
            } else {
                // Perangkat valid atau proteksi nonaktif
                const existingModal = document.getElementById('device-lock-overlay');
                if (existingModal) existingModal.remove();
            }
        } catch (e) {
            console.warn("Device Auth Check Warning:", e);
        }
    }

    function renderDeviceLockScreen(customMessage) {
        if (document.getElementById('device-lock-overlay')) return;

        const defaultDeviceName = detectDeviceName();

        const overlay = document.createElement('div');
        overlay.id = 'device-lock-overlay';
        overlay.className = 'fixed inset-0 z-[99999] bg-slate-900/90 backdrop-blur-md flex items-center justify-center p-4 antialiased';
        overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 99999;';

        overlay.innerHTML = `
            <div class="bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden border border-slate-100 animate-fade-in p-7 flex flex-col text-slate-800">
                
                <!-- HEADER ICON -->
                <div class="text-center mb-5">
                    <div class="w-16 h-16 rounded-3xl bg-rose-50 text-rose-500 border border-rose-100 flex items-center justify-center text-3xl mx-auto mb-3 shadow-inner">
                        <i class="fa-solid fa-mobile-screen-button"></i>
                    </div>
                    <h2 class="text-xl font-black text-slate-800">Akses Kasir Terkunci</h2>
                    <p class="text-xs text-slate-500 font-bold mt-1 max-w-xs mx-auto">
                        ${customMessage || 'Perangkat ini belum didaftarkan sebagai Perangkat Kasir Resmi Toko.'}
                    </p>
                </div>

                <!-- FORM PENDAFTARAN -->
                <form id="device-reg-form" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">NAMA PERANGKAT INI</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid fa-tablet"></i></span>
                            <input type="text" id="dev-reg-name" required value="${defaultDeviceName}" placeholder="Misal: Tablet Kasir Depan" 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs font-bold text-slate-800 outline-none focus:border-blue-500 focus:bg-white transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">SANDI AKTIVASI DARI ADMIN</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"><i class="fa-solid fa-key"></i></span>
                            <input type="password" id="dev-reg-passcode" required placeholder="Masukkan Sandi Aktivasi..." 
                                   class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-xs font-black tracking-widest text-slate-800 outline-none focus:border-blue-500 focus:bg-white transition-all">
                        </div>
                        <p class="text-[10px] text-slate-400 font-medium mt-1">Tanyakan Sandi Aktivasi Perangkat kepada Admin / Pemilik Toko.</p>
                    </div>

                    <div id="dev-reg-error" class="hidden p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold rounded-xl flex items-center gap-2">
                        <i class="fa-solid fa-circle-exclamation text-sm shrink-0"></i>
                        <span id="dev-reg-error-msg"></span>
                    </div>

                    <button type="submit" id="dev-reg-submit" 
                            class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 active:scale-[0.98] text-white rounded-xl font-black text-xs shadow-lg shadow-blue-500/25 transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-shield-check"></i> Daftarkan & Buka Akses Kasir
                    </button>
                </form>

                <div class="mt-5 pt-4 border-t border-slate-100 text-center">
                    <button type="button" onclick="window.location.href='${typeof BASE_URL !== 'undefined' ? BASE_URL : '../../'}pos/dashboard/'" 
                            class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fa-solid fa-arrow-left mr-1"></i> Kembali ke Menu Utama
                    </button>
                </div>

            </div>
        `;

        document.body.appendChild(overlay);

        const form = document.getElementById('device-reg-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('dev-reg-submit');
            const errorBox = document.getElementById('dev-reg-error');
            const errorMsg = document.getElementById('dev-reg-error-msg');
            const nameInput = document.getElementById('dev-reg-name').value.trim();
            const passcodeInput = document.getElementById('dev-reg-passcode').value.trim();

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memverifikasi...';
            errorBox.classList.add('hidden');

            try {
                const res = await fetch(`${API_URL}?action=register_device`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        device_name: nameInput,
                        passcode: passcodeInput,
                        warehouse_id: (typeof selectedStoreId !== 'undefined' ? selectedStoreId : 1)
                    })
                });
                const result = await res.json();

                if (result.status === 'success') {
                    localStorage.setItem('pos_device_token', result.device_token);
                    localStorage.setItem('pos_device_name', result.device_name);

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Perangkat Terdaftar!',
                            text: 'Perangkat ini berhasil didaftarkan sebagai Perangkat Kasir Resmi Toko.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        alert('Perangkat berhasil didaftarkan!');
                        window.location.reload();
                    }
                } else {
                    errorMsg.innerText = result.message || 'Sandi aktivasi salah!';
                    errorBox.classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-shield-check"></i> Daftarkan & Buka Akses Kasir';
                }
            } catch (err) {
                errorMsg.innerText = 'Terjadi kesalahan jaringan/server.';
                errorBox.classList.remove('hidden');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-shield-check"></i> Daftarkan & Buka Akses Kasir';
            }
        });
    }

    function detectDeviceName() {
        const ua = navigator.userAgent;
        let name = "Perangkat Kasir";
        if (/iPad|Tablet/i.test(ua)) name = "Tablet Kasir Toko";
        else if (/iPhone|Android/i.test(ua) && /Mobile/i.test(ua)) name = "HP Kasir Toko";
        else if (/Macintosh/i.test(ua)) name = "Mac Kasir Toko";
        else if (/Windows/i.test(ua)) name = "PC Kasir Toko";
        return name;
    }

    // Jalankan pemeriksaan otomatis saat DOM siap
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkDeviceAuthorization);
    } else {
        checkDeviceAuthorization();
    }

    window.checkDeviceAuthorization = checkDeviceAuthorization;
})();
