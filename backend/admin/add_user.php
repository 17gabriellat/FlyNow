<?php
session_start();
require_once "../db.php";

$name  = $_POST['name'] ?? "";
$email = $_POST['email'] ?? "";
$phone = $_POST['phone'] ?? "";
$role  = $_POST['role_id'] ?? "";
$pass  = $_POST['password'] ?? "";

// ===== BASIC VALIDATION =====
if (!$name || !$email || !$pass) {
    $_SESSION['error'] = "All required fields must be filled.";
    header("Location: ../../admin/user_management.php");
    exit;
}

// ===== CHECK EMAIL UNIQUE =====
$check = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $_SESSION['error'] = "Email already exists. Please use another email.";
    header("Location: ../../admin/user_management.php");
    exit;
}

// ===== INSERT DATA =====
$hash = password_hash($pass, PASSWORD_BCRYPT);

$q = $conn->prepare("
    INSERT INTO users (name, email, phone, role_id, password_hash, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
");
$q->bind_param("sssis", $name, $email, $phone, $role, $hash);

if ($q->execute()) {
    $_SESSION['success'] = "New user has been added successfully.";
} else {
    $_SESSION['error'] = "Failed to add user. Please try again.";
}

header("Location: ../../admin/pengguna.php");
exit;
