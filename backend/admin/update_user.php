<?php
session_start();
require_once "../db.php";

$id_user = $_POST["id_user"];
$name = trim($_POST["name"]);
$email = trim($_POST["email"]);
$phone = trim($_POST["phone"]);
$role = intval($_POST["role_id"]);

$current_password = trim($_POST["current_password"]);
$new_password = trim($_POST["new_password"]);

// ============================================
// 1. VALIDATE REQUIRED FIELDS
// ============================================
if ($name === "" || $email === "") {
    $_SESSION["error"] = "Name and email cannot be empty.";
    header("Location: ../../admin/pengguna.php");
    exit;
}

// ============================================
// 2. CHECK IF EMAIL EXISTS (EXCLUDING CURRENT USER)
// ============================================
$checkEmail = $conn->prepare("SELECT id_user FROM users WHERE email = ? AND id_user != ?");
$checkEmail->bind_param("si", $email, $id_user);
$checkEmail->execute();
$resultEmail = $checkEmail->get_result();

if ($resultEmail->num_rows > 0) {
    $_SESSION["error"] = "Email already exists. Please use a different email.";
    header("Location: ../../admin/pengguna.php");
    exit;
}

// ============================================
// 3. HANDLE PASSWORD LOGIC
// ============================================

// CASE A — both empty → do not update password
if ($current_password === "" && $new_password === "") {
    $updatePassword = false;
}

// CASE B — one is empty → error
else if ($current_password === "" || $new_password === "") {
    $_SESSION["error"] = "Both current password and new password must be filled to change password.";
    header("Location: ../../admin/pengguna.php");
    exit;
}

// CASE C — both filled → verify current password
else {
    $updatePassword = true;

    // get existing password
    $q = $conn->prepare("SELECT password_hash FROM users WHERE id_user = ?");
    $q->bind_param("i", $id_user);
    $q->execute();
    $user = $q->get_result()->fetch_assoc();

    if (!$user) {
        $_SESSION["error"] = "User not found.";
        header("Location: ../../admin/pengguna.php");
        exit;
    }

    // verify current password
    if (!password_verify($current_password, $user["password_hash"])) {
        $_SESSION["error"] = "Current password is incorrect.";
        header("Location: ../../admin/pengguna.php");
        exit;
    }

    // hash new password
    $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);
}


// ============================================
// 4. UPDATE QUERY — WITH OR WITHOUT PASSWORD
// ============================================

if ($updatePassword) {
    // query with password change
    $update = $conn->prepare("
        UPDATE users
        SET name = ?, email = ?, phone = ?, role_id = ?, password_hash = ?
        WHERE id_user = ?
    ");
    $update->bind_param("sssisi", $name, $email, $phone, $role, $hashedPassword, $id_user);
} else {
    // query without password change
    $update = $conn->prepare("
        UPDATE users
        SET name = ?, email = ?, phone = ?, role_id = ?
        WHERE id_user = ?
    ");
    $update->bind_param("sssii", $name, $email, $phone, $role, $id_user);
}

if ($update->execute()) {
    $_SESSION["success"] = $updatePassword
        ? "User $name updated successfully. Password has been changed."
        : "User $name updated successfully.";
} else {
    $_SESSION["error"] = "Failed to update user $name.";
}

header("Location: ../../admin/pengguna.php");
exit;
