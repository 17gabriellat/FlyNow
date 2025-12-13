<?php
session_start();
require_once "db.php";
require_once "aws_sns.php";

$email = $_POST['email'] ?? "";

if (!$email) {
    $_SESSION['error'] = "Email is required.";
    header("Location: ../forgot_password.php");
    exit;
}

// Find user
$stmt = $conn->prepare("SELECT id_user, name, email FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = "Email not found.";
    header("Location: ../forgot_password.php");
    exit;
}

// Generate OTP
$otp = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);
$expired_at = date("Y-m-d H:i:s", strtotime("+30 minutes"));

// Delete previous OTP
$conn->query("DELETE FROM password_reset_requests WHERE id_user = {$user['id_user']}");

// Save OTP
$stmt = $conn->prepare("
    INSERT INTO password_reset_requests (id_user, otp, expired_at)
    VALUES (?, ?, ?)
");
$stmt->bind_param("iss", $user['id_user'], $otp, $expired_at);
$stmt->execute();

// die($otp);

// Send to SNS
publishToSNS([
    "type" => "OTP_RESET",
    "to_email" => $user['email'],
    "subject" => "Your Password Reset Code - FLYNOW",
    "body" => "Hello {$user['name']},\n\nYour password reset code is: {$otp}\nThis code will expire in 30 minutes.\n\nIf you did not request a password reset, please ignore this email.\n\nBest regards,\nFLYNOW Team"
]);

$_SESSION['success'] = "Verification code sent to your email.";
header("Location: ../verify_otp.php?email=" . urlencode($email));
exit;
