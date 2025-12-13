<?php
session_start();
$email = $_GET['email'] ?? "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify OTP - FLYNOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow">
        <h2 class="text-2xl font-bold mb-4 text-center">Verify Code</h2>


        <!-- ALERT ERROR -->
        <?php if (isset($_SESSION['error'])): ?>
            <div id="alert-error"
                class="my-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow">
                <div class="flex justify-between items-center">
                    <span class="font-semibold"><?= $_SESSION['error']; ?></span>
                    <button onclick="$('#alert-error').fadeOut();" class="text-red-600 font-bold text-xl">&times;</button>
                </div>
            </div>
        <?php unset($_SESSION['error']);
        endif; ?>
        <!-- ALERT SUCCESS -->
        <?php if (isset($_SESSION['success'])): ?>
            <div id="alert-success"
                class="my-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg shadow">
                <div class="flex justify-between items-center">
                    <span class="font-semibold"><?= $_SESSION['success']; ?></span>
                    <button onclick="$('#alert-success').fadeOut();" class="text-green-700 font-bold text-xl">&times;</button>
                </div>
            </div>
        <?php unset($_SESSION['success']);
        endif; ?>

        <form action="backend/verify_reset_otp.php" method="POST">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">

            <label class="block text-sm font-medium">6-Digit Code</label>
            <input type="text" name="otp" maxlength="6" required
                class="w-full p-3 border rounded mb-4 text-center tracking-widest">

            <button class="w-full bg-blue-600 text-white py-3 rounded">
                Reset Password
            </button>
        </form>
    </div>

</body>

</html>

<script>
    // Auto hide alert after 3 seconds
    $(document).ready(function() {
        setTimeout(() => {
            $("#alert-error", "#alert-success").fadeOut();
        }, 3000);
    });
</script>