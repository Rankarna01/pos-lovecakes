<?php
ini_set('display_errors', 0);
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../../config/database.php'; 

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

// 🛡️ 1. AUTO MIGRATION: Ensure columns in customers_pos & create wa_templates_pos table
try {
    // Check columns in customers_pos
    $cols = $pdo->query("SHOW COLUMNS FROM customers_pos")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('birth_date', $cols)) {
        $pdo->exec("ALTER TABLE customers_pos ADD COLUMN birth_date DATE NULL AFTER address");
    }
    if (!in_array('custom_notes', $cols)) {
        $pdo->exec("ALTER TABLE customers_pos ADD COLUMN custom_notes TEXT NULL AFTER birth_date");
    }

    // Create WhatsApp templates table
    $pdo->exec("CREATE TABLE IF NOT EXISTS wa_templates_pos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        template_text TEXT NOT NULL,
        category VARCHAR(50) DEFAULT 'general',
        is_default TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed default templates if empty
    $countTemplates = $pdo->query("SELECT COUNT(*) FROM wa_templates_pos")->fetchColumn();
    if ($countTemplates == 0) {
        $stmtSeed = $pdo->prepare("INSERT INTO wa_templates_pos (title, template_text, category, is_default) VALUES (?, ?, ?, ?)");
        
        $t1 = "Halo Kak {nama}! 🎉🎂\n\nSelamat Ulang Tahun dari segenap keluarga besar *{toko}*! 🥳\nSemoga panjang umur, sehat selalu, dan dilancarkan segala urusannya.\n\nSpesial di hari bahagia Kakak, kami memberikan Voucher Diskon Spesial Ulang Tahun untuk pembelian cake favoritmu! Total Poin Loyalitas Kakak saat ini: *{poin} Poin* ✨\n\nYuk rayakan hari manismu bersama kami di *{toko}*! 🍰🎂";
        $stmtSeed->execute(['🎂 Ucapan Selamat Ulang Tahun & Voucher', $t1, 'birthday', 1]);

        $t2 = "Halo Kak {nama} dari *{toko}*! 👋\n\nKami menginfokan bahwa Kakak saat ini memiliki *{poin} Poin Loyalitas* aktif yang bisa ditukarkan dengan diskon langsung saat berbelanja di outlet kami lho! 🎁\n\nAda banyak pilihan cake dan pastry fresh baru yang siap dinikmati hari ini. Ditunggu kedatangannya ya Kak! 🍰✨";
        $stmtSeed->execute(['🎁 Promo Loyalitas & Reminder Poin Member', $t2, 'promo', 0]);

        $t3 = "Halo Kak {nama}! 👋\n\nTerima kasih telah menjadi pelanggan setia *{toko}*. Kami selalu siap melayani pesanan cake, hampers, dan kue favorit untuk setiap momen spesial Kakak.\n\nJangan ragu untuk pesan atau tanya ketersediaan menu favoritmu melalui WhatsApp ini ya. Semoga harimu menyenangkan! 🌸🍰";
        $stmtSeed->execute(['✨ Sapaan Hangat & Layanan Pelanggan', $t3, 'greeting', 0]);
    }
} catch (Exception $e) {
    // Ignore migration error if already exists
}

// 📌 2. READ CUSTOMERS WITH BIRTHDAY STATUS & STORE INFO
if ($action === 'read') {
    try {
        $stmt = $pdo->query("SELECT * FROM customers_pos ORDER BY points DESC, name ASC");
        $rawCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Store info for dynamic script preview
        $storeInfo = ['store_name' => 'Love Cakes', 'store_phone' => ''];
        try {
            $stmtStore = $pdo->query("SELECT store_name, store_phone FROM store_settings_pos LIMIT 1");
            $sData = $stmtStore->fetch(PDO::FETCH_ASSOC);
            if ($sData && !empty($sData['store_name'])) {
                $storeInfo = $sData;
            }
        } catch (Exception $e) {}

        $todayMonthDay = date('m-d');
        $currentMonth = (int)date('m');
        $birthdayTodayCount = 0;
        $birthdayMonthCount = 0;

        $customers = [];
        foreach ($rawCustomers as $c) {
            $bDate = !empty($c['birth_date']) ? $c['birth_date'] : null;
            $isBdayToday = false;
            $isBdayThisMonth = false;
            $formattedBday = '-';
            $age = null;

            if ($bDate && $bDate !== '0000-00-00') {
                $time = strtotime($bDate);
                $mDay = date('m-d', $time);
                $mOnly = (int)date('m', $time);
                $yOnly = (int)date('Y', $time);

                $bulanIndo = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
                $tgl = date('j', $time);
                $bln = $bulanIndo[$mOnly] ?? date('M', $time);
                $thn = $yOnly > 1900 ? " $yOnly" : "";
                $formattedBday = "$tgl $bln$thn";

                if ($mDay === $todayMonthDay) {
                    $isBdayToday = true;
                    $birthdayTodayCount++;
                }
                if ($mOnly === $currentMonth) {
                    $isBdayThisMonth = true;
                    $birthdayMonthCount++;
                }
                if ($yOnly > 1900) {
                    $age = (int)date('Y') - $yOnly;
                }
            }

            $c['is_birthday_today'] = $isBdayToday;
            $c['is_birthday_this_month'] = $isBdayThisMonth;
            $c['formatted_birth_date'] = $formattedBday;
            $c['age'] = $age;
            $customers[] = $c;
        }

        // Also fetch WA Templates
        $stmtTemplates = $pdo->query("SELECT * FROM wa_templates_pos ORDER BY is_default DESC, id ASC");
        $templates = $stmtTemplates->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $customers,
            'templates' => $templates,
            'store_info' => $storeInfo,
            'stats' => [
                'total' => count($customers),
                'birthday_today' => $birthdayTodayCount,
                'birthday_month' => $birthdayMonthCount
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 📌 3. SAVE CUSTOMER (ADD / EDIT)
if ($action === 'save') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $points = (int)($_POST['points'] ?? 0);
    $birth_date = !empty($_POST['birth_date']) ? trim($_POST['birth_date']) : null;
    $custom_notes = trim($_POST['custom_notes'] ?? '');

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama pelanggan wajib diisi!']); 
        exit;
    }

    try {
        if (empty($id)) {
            $stmt = $pdo->prepare("INSERT INTO customers_pos (name, phone, address, points, birth_date, custom_notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $phone, $address, $points, $birth_date, $custom_notes]);
            echo json_encode(['status' => 'success', 'message' => 'Pelanggan baru berhasil didaftarkan!']);
        } else {
            $stmt = $pdo->prepare("UPDATE customers_pos SET name=?, phone=?, address=?, points=?, birth_date=?, custom_notes=? WHERE id=?");
            $stmt->execute([$name, $phone, $address, $points, $birth_date, $custom_notes, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Data pelanggan diperbarui!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
    }
    exit;
}

// 📌 4. DELETE CUSTOMER
if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM customers_pos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Data pelanggan telah dihapus permanen!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
    }
    exit;
}

// 📌 5. WHATSAPP TEMPLATES MANAGEMENT
if ($action === 'get_templates') {
    try {
        $stmt = $pdo->query("SELECT * FROM wa_templates_pos ORDER BY is_default DESC, id ASC");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_template') {
    $id = $_POST['id'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $template_text = trim($_POST['template_text'] ?? '');
    $category = trim($_POST['category'] ?? 'general');

    if (empty($title) || empty($template_text)) {
        echo json_encode(['status' => 'error', 'message' => 'Judul dan isi template wajib diisi!']);
        exit;
    }

    try {
        if (empty($id)) {
            $stmt = $pdo->prepare("INSERT INTO wa_templates_pos (title, template_text, category) VALUES (?, ?, ?)");
            $stmt->execute([$title, $template_text, $category]);
            echo json_encode(['status' => 'success', 'message' => 'Template WhatsApp berhasil disimpan!']);
        } else {
            $stmt = $pdo->prepare("UPDATE wa_templates_pos SET title = ?, template_text = ?, category = ? WHERE id = ?");
            $stmt->execute([$title, $template_text, $category, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Template WhatsApp diperbarui!']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan template: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_template') {
    $id = $_POST['id'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM wa_templates_pos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Template WhatsApp berhasil dihapus!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 📌 6. EXPORT CSV
if ($action === 'export_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Data_Pelanggan_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Nama', 'Telepon / WA', 'Tanggal Lahir (YYYY-MM-DD)', 'Alamat', 'Point', 'Catatan']);
    
    $stmt = $pdo->query("SELECT name, phone, birth_date, address, points, custom_notes FROM customers_pos ORDER BY name ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['name'], 
            $row['phone'], 
            $row['birth_date'] ?? '', 
            $row['address'], 
            $row['points'], 
            $row['custom_notes'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

// 📌 7. DOWNLOAD TEMPLATE CSV
if ($action === 'download_template') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Template_Pelanggan.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Nama', 'Telepon / WA', 'Tanggal Lahir (YYYY-MM-DD)', 'Alamat', 'Point', 'Catatan']);
    fputcsv($output, ['Siti Rahma', '081234567890', '1995-08-15', 'Jl. Sudirman No. 123', '50', 'Suka Red Velvet Cake']);
    fputcsv($output, ['Budi Santoso', '085812345678', '1990-12-05', 'Jl. Gatot Subroto No. 45', '0', '']);
    fclose($output);
    exit;
}

// 📌 8. IMPORT CSV
if ($action === 'import_csv') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengunggah file!']);
        exit;
    }

    $fileTmpPath = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($fileExtension !== 'csv') {
        echo json_encode(['status' => 'error', 'message' => 'Format file harus CSV!']);
        exit;
    }

    if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
        $headerLine = true;
        $successCount = 0;
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO customers_pos (name, phone, birth_date, address, points, custom_notes) VALUES (?, ?, ?, ?, ?, ?)");
            
            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if ($headerLine) {
                    $headerLine = false;
                    continue; // Skip header
                }
                
                $name = trim($data[0] ?? '');
                $phone = trim($data[1] ?? '');
                $bDate = trim($data[2] ?? '');
                $bDate = (!empty($bDate) && strtotime($bDate)) ? date('Y-m-d', strtotime($bDate)) : null;
                $address = trim($data[3] ?? '');
                $points = (int)($data[4] ?? 0);
                $notes = trim($data[5] ?? '');
                
                if (!empty($name)) {
                    $stmt->execute([$name, $phone, $bDate, $address, $points, $notes]);
                    $successCount++;
                }
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => "$successCount pelanggan berhasil diimport!"]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Gagal import: ' . $e->getMessage()]);
        }
        fclose($handle);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal membaca file CSV.']);
    }
    exit;
}