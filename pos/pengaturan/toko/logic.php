<?php
session_start();
require_once '../../../config/database.php';

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'get_settings') {
    try {
        // 1. Tarik Data Profil Toko
        $stmt_store = $pdo->query("SELECT * FROM store_settings_pos WHERE id = 1");
        $store = $stmt_store->fetch(PDO::FETCH_ASSOC);

        // 2. Tarik Data Konfigurasi Sistem (pos_settings)
        $stmt_sys = $pdo->query("SELECT setting_key, setting_value FROM pos_settings");
        $sys_rows = $stmt_sys->fetchAll(PDO::FETCH_ASSOC);
        
        $system = [];
        foreach ($sys_rows as $row) {
            $system[$row['setting_key']] = $row['setting_value'];
        }

        echo json_encode(['status' => 'success', 'data' => ['store' => $store, 'system' => $system]]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_settings') {
    try {
        $pdo->beginTransaction();

        // 1. UPDATE DATA TOKO (store_settings_pos)
        $store_name = $_POST['store_name'] ?? '';
        $store_address = $_POST['store_address'] ?? '';
        $store_phone = $_POST['store_phone'] ?? '';
        $receipt_footer = $_POST['receipt_footer'] ?? '';

        // Handle Upload Logo (jika ada)
        $logo_query = "";
        $params_store = [$store_name, $store_address, $store_phone, $receipt_footer];

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_name = 'logo_toko_' . time() . '.' . $ext;
            // Pastikan folder assets/img/ sudah ada di root project kamu
            $upload_path = '../../../assets/img/' . $new_name; 
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_query = ", logo = ?";
                $params_store[] = $new_name;
            }
        }

        $params_store[] = 1; // Untuk WHERE id = 1
        $stmt_update_store = $pdo->prepare("UPDATE store_settings_pos SET store_name = ?, store_address = ?, store_phone = ?, receipt_footer = ? $logo_query WHERE id = ?");
        $stmt_update_store->execute($params_store);

        // 2. UPDATE KONFIGURASI SISTEM (pos_settings)
        // Kita tangkap array setting dinamis dari frontend
        $system_settings = json_decode($_POST['system_settings'], true);
        
        if (is_array($system_settings)) {
            $stmt_update_sys = $pdo->prepare("UPDATE pos_settings SET setting_value = ? WHERE setting_key = ?");
            foreach ($system_settings as $key => $value) {
                $stmt_update_sys->execute([$value, $key]);
            }
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Pengaturan berhasil disimpan!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
    exit;
}
?>