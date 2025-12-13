<?php
session_start();

// Jika tidak ada session user → tidak boleh masuk
if (!isset($_SESSION['id_user'])) {
    $_SESSION['login_error'] = "Silakan login terlebih dahulu.";
    header("Location: ../login.php");
    exit;
}

// Cek apakah role_id adalah admin (misalnya 1)
if ($_SESSION['role_id'] != 1) {
    $_SESSION['login_error'] = "Anda tidak memiliki akses ke halaman admin.";
    header("Location: ../login.php");
    exit;
}
?>
