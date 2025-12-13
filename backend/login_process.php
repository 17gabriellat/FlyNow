<?php
session_start();
require "db.php";

$email = $_POST['email'];
$password = $_POST['password'];

// Hapus error lama
unset($_SESSION['login_error']);

$sql = "SELECT id_user, role_id, name, email, password_hash FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Jika email ditemukan
if ($result->num_rows === 1) {

    $user = $result->fetch_assoc();

    // Cek password
    if (password_verify($password, $user['password_hash'])) {

        $_SESSION['user'] = [
            "id_user"   => $user['id_user'],
            "full_name" => $user['name'],        // atau kolom full_name jika ada
            "email"     => $user['email'],       // pastikan ada di tabel users
            "role_id"   => $user['role_id']
        ];

        // Buat session user
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role_id'] = $user['role_id'];

        // Redirect berdasarkan role
        if ($user['role_id'] == 1) {
            header("Location: ../admin/dashboard.php");
            exit;
        } else {
            header("Location: ../index.php");
            exit;
        }

    } else {
        // Password salah
        $_SESSION['error'] = "Email atau password salah.";
        header("Location: ../login.php");
        exit;
    }

} else {
    // Email tidak ditemukan
    $_SESSION['error'] = "Email atau password salah.";
    header("Location: ../login.php");
    exit;
}
?>
