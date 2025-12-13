<?php
session_start();
require_once "db.php";
require_once "aws_sns.php";

$email = $_POST['email'] ?? "";
$otp   = $_POST['otp'] ?? "";

if (!$email || !$otp) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../verify_otp.php");
    exit;
}


// Get user
$stmt = $conn->prepare("SELECT id_user, name, email FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: ../login.php");
    exit;
}

// Check OTP
$stmt = $conn->prepare("
    SELECT * FROM password_reset_requests
    WHERE id_user = ? AND otp = ? AND expired_at >= NOW()
");
$stmt->bind_param("is", $user['id_user'], $otp);
$stmt->execute();
$otpData = $stmt->get_result()->fetch_assoc();

if (!$otpData) {
    $_SESSION['error'] = "Invalid or expired code.";
    header("Location: ../verify_otp.php?email=".$email);
    exit;
}

// Generate new password
$newPass = substr(bin2hex(random_bytes(4)), 0, 8);
$hash = password_hash($newPass, PASSWORD_DEFAULT);

// Update password
$stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id_user = ?");
$stmt->bind_param("si", $hash, $user['id_user']);
$stmt->execute();

// Delete OTP
$conn->query("DELETE FROM password_reset_requests WHERE id_user = {$user['id_user']}");

// Send password email
publishToSNS([
    "type" => "RESET_PASSWORD",
    "to_email" => $user['email'],
    "subject" => "Your New Password - FLYNOW",
    "body" => "Hello {$user['name']},\n\nYour password has been reset. Your new password is: {$newPass}\n\nPlease log in and change your password immediately for security reasons.\n\nBest regards,\nFLYNOW Team"
]);

$_SESSION['success'] = "New password has been sent to your email.";
$_SESSION['reset_success'] = true;
$_SESSION['reset_user_id'] = $user['id_user'];
$_SESSION['reset_email']   = $email;

header("Location: ../reset_success.php");
exit;
