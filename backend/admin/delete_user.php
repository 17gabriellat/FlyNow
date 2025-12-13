<?php
session_start();
require_once "../db.php";
require_once "../akses_admin.php";

$id = $_GET["id"] ?? "";

if ($id === "") {
    $_SESSION["error"] = "Invalid user ID.";
    header("Location: ../../admin/pengguna.php");
    exit;
}

// ======================================
// GET USER NAME FIRST
// ======================================
$getUser = $conn->prepare("SELECT name FROM users WHERE id_user = ?");
$getUser->bind_param("i", $id);
$getUser->execute();
$user = $getUser->get_result()->fetch_assoc();

if (!$user) {
    $_SESSION["error"] = "User not found.";
    header("Location: ../../admin/pengguna.php");
    exit;
}

$userName = $user["name"];

// ======================================
// DELETE USER
// ======================================
$q = $conn->prepare("DELETE FROM users WHERE id_user = ?");
$q->bind_param("i", $id);

if ($q->execute()) {
    $_SESSION["success"] = "User $userName deleted successfully.";
} else {
    $_SESSION["error"] = "Failed to delete user.";
}

header("Location: ../../admin/pengguna.php");
exit;
