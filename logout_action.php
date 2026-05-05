<?php
// POS-LOVECAKES/logout_action.php
session_start();

// 1. Hancurkan semua data session PHP di server
session_unset();
session_destroy();

// 2. Tendang user kembali ke halaman Login (Path relatif ke auth)
header("Location: auth/index.php");
exit();
?>