// File: assets/js/pos_db.js
localforage.config({
    name: 'LoveCakesPOS', // <- Bebas namanya apa aja, ini cuma buat di browser
    storeName: 'pos_data'
});

// MEMBUAT DATABASE OFFLINE UNTUK MENYIMPAN SESI KASIR (Pakai window.)
window.dbAuth = localforage.createInstance({ 
    name: 'LoveCakesPOS', 
    storeName: 'auth_session' 
});

// MEMBUAT DATABASE OFFLINE UNTUK MENYIMPAN DAFTAR PRODUK (Pakai window.)
window.dbKatalog = localforage.createInstance({ 
    name: 'LoveCakesPOS', 
    storeName: 'katalog_produk' 
});

console.log('✅ Local Database POS Ready!');