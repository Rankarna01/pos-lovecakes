localforage.config({
    name: 'POS_Sistem_Kue',
    version: 1.0,
    storeName: 'pos_data',
    description: 'Database offline untuk Aplikasi POS'
});

const dbKatalog = localforage.createInstance({ name: "POS_Sistem_Kue", storeName: "katalog" });
const dbKeranjang = localforage.createInstance({ name: "POS_Sistem_Kue", storeName: "keranjang" });
const dbTransaksiOffline = localforage.createInstance({ name: "POS_Sistem_Kue", storeName: "transaksi_pending" });

console.log("Local Database POS Ready!");