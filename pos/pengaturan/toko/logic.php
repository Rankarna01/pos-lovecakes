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

// --- LIST PIN SUPERVISOR OTP ---
if ($action === 'get_supervisor_pins') {
    try {
        $pins = $pdo->query("SELECT id, pin, is_used, used_at, created_at FROM supervisor_pins_pos ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'pins' => $pins]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// --- GENERATE PIN SUPERVISOR OTP BARU ---
if ($action === 'generate_supervisor_pins') {
    $qty = min((int)($_POST['qty'] ?? 5), 20); // max 20 sekaligus
    $generated = [];
    $attempts = 0;
    try {
        $stmt_ins = $pdo->prepare("INSERT IGNORE INTO supervisor_pins_pos (pin) VALUES (?)");
        while (count($generated) < $qty && $attempts < 100) {
            $attempts++;
            $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            // Cek dulu tidak duplikat
            $stmt_chk = $pdo->prepare("SELECT id FROM supervisor_pins_pos WHERE pin = ?");
            $stmt_chk->execute([$pin]);
            if ($stmt_chk->fetch()) continue;
            
            $stmt_ins->execute([$pin]);
            $generated[] = $pin;
        }
        $pins = $pdo->query("SELECT id, pin, is_used, used_at, created_at FROM supervisor_pins_pos ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'message' => count($generated) . ' PIN baru berhasil dibuat!', 'pins' => $pins, 'generated' => $generated]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// --- HAPUS PIN SUPERVISOR OTP ---
if ($action === 'delete_supervisor_pin') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']); exit; }
    try {
        $pdo->prepare("DELETE FROM supervisor_pins_pos WHERE id = ?")->execute([$id]);
        $pins = $pdo->query("SELECT id, pin, is_used, used_at, created_at FROM supervisor_pins_pos ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'message' => 'PIN berhasil dihapus!', 'pins' => $pins]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// --- HAPUS SEMUA PIN YANG SUDAH DIPAKAI ---
if ($action === 'delete_used_pins') {
    try {
        $pdo->query("DELETE FROM supervisor_pins_pos WHERE is_used = 1");
        $pins = $pdo->query("SELECT id, pin, is_used, used_at, created_at FROM supervisor_pins_pos ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'message' => 'Semua PIN yang sudah dipakai berhasil dibersihkan!', 'pins' => $pins]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>