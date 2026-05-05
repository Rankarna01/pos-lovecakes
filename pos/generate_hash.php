<?php
// Ganti tulisan "password123" ini dengan password apapun yang kamu inginkan
$password_baru = "password123"; 

// Fungsi PHP untuk mengamankan password
$password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generator Password Hash</title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #f8fafc; }
        .box { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 500px; }
        textarea { width: 100%; padding: 10px; margin-top: 10px; border: 1px solid #cbd5e1; border-radius: 5px; font-family: monospace; font-size: 16px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>🔑 Generator Password</h2>
        <p><strong>Password Asli:</strong> <code><?= $password_baru ?></code></p>
        
        <p><strong>Hasil Hash (Copy teks di bawah ini):</strong></p>
        <textarea rows="3" readonly><?= $password_hash ?></textarea>
        
        <p style="font-size: 14px; color: #64748b; margin-top: 15px;">
            📝 <strong>Cara Pakai:</strong><br>
            1. Copy semua teks acak di dalam kotak di atas.<br>
            2. Buka phpMyAdmin, masuk ke database <code>pos-lovely</code>, buka tabel <code>users</code>.<br>
            3. Klik tombol <strong>Edit</strong> pada baris akun owner.<br>
            4. Paste teks tadi ke dalam kolom <code>password</code>, lalu klik <strong>Go / Simpan</strong>.
        </p>
    </div>
</body>
</html>