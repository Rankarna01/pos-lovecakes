<?php
session_start();
// Bunuh sesi PHP
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logout...</title>
    <script src="https://cdn.jsdelivr.net/npm/localforage@1.10.0/dist/localforage.min.js"></script>
</head>
<body style="background: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif;">
    <h3>Membersihkan sesi...</h3>
    <script>
        // Bunuh sesi IndexedDB
        const dbAuth = localforage.createInstance({ name: 'pos_db', storeName: 'auth_store' });
        dbAuth.removeItem('user_session').then(() => {
            // Arahkan kembali ke login
            window.location.href = 'index.php';
        }).catch(() => {
            window.location.href = 'index.php';
        });
    </script>
</body>
</html>