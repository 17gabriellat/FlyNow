<?php
session_start();
require "db.php";

// Ambil data dari form
$fullname = trim($_POST['fullname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');
$phone = trim($_POST['phonenumber'] ?? '');

// Validasi input kosong
if ($fullname == '' || $email == '' || $password == '' || $confirm_password == '' || $phone == '') {
    $_SESSION['error'] = "Semua field wajib diisi.";
    header("Location: ../register.php");
    exit;
}

// Validasi password sama
if ($password !== $confirm_password) {
    $_SESSION['error'] = "Password dan konfirmasi password tidak sama.";
    header("Location: ../register.php");
    exit;
}

// Validasi email sudah ada atau belum
$check = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $_SESSION['error'] = "Email sudah terdaftar. Gunakan email lain.";
    header("Location: ../register.php");
    exit;
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// $phone = "081234567890";

// Default role user
$role_id = 2;

// Simpan user baru
$query = $conn->prepare("
    INSERT INTO users (role_id, name, email, phone, password_hash, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
if (!$query) {
    die("SQL Error saat prepare: " . $conn->error);
}
$query->bind_param("issss", $role_id, $fullname, $email, $phone, $password_hash);

if ($query->execute()) {
    header("Location: ../login.php");
    exit;
} else {
    $_SESSION['error'] = "Terjadi kesalahan saat mendaftar. Coba lagi.";
    header("Location: ../register.php");
    exit;
}
?>
