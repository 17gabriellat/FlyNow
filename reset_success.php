<?php
session_start();

if (
    !isset($_SESSION['reset_success']) ||
    $_SESSION['reset_success'] !== true ||
    !isset($_SESSION['reset_user_id'])
) {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: login.php");
    exit;
}

$email = $_SESSION['reset_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset Success - FLYNOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="w-full max-w-md bg-white p-8 rounded shadow text-center">
    <h2 class="text-2xl font-bold text-green-600 mb-4">Password Reset Successful</h2>
    <p class="text-gray-700 mb-6">
        A new password has been sent to <strong><?= htmlspecialchars($email) ?></strong>.
        Please log in using the new password and change it immediately.
    </p>

    <a href="login.php"
       class="inline-block bg-blue-600 text-white px-6 py-3 rounded">
        Go to Login
    </a>
</div>

</body>
</html>

<?php
// ONE-TIME ACCESS: destroy reset session
unset($_SESSION['reset_success']);
unset($_SESSION['reset_user_id']);
unset($_SESSION['reset_email']);
?>
