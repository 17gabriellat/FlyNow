<?php
session_start();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FLYNOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-md">
        <div class="text-center mb-8">
            <a href="index.php" class="text-3xl font-bold text-blue-600">FLYNOW</a>
            <h2 class="text-2xl font-semibold text-gray-800 mt-2">Selamat Datang Kembali</h2>
            <p class="text-gray-600">Silakan login ke akun Anda.</p>

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
        </div>

        <form action="backend/login_process.php" method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" required
                    class="mt-1 w-full p-3 border border-gray-300 rounded-md"
                    placeholder="anda@email.com">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required
                    class="mt-1 w-full p-3 border border-gray-300 rounded-md"
                    placeholder="••••••••">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center space-x-2">
                </label>
                <a href="forgot_password.php" class="text-sm text-blue-600 hover:underline">Forgot your password?</a>
            </div>

            <div>
                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold text-lg px-6 py-3 rounded-md shadow-lg hover:bg-blue-700">
                    LOGIN
                </button>
            </div>
        </form>

        <div class="text-center mt-6">
            <p class="text-sm text-gray-600">
                Belum punya akun?
                <a href="register.php" class="text-blue-600 font-medium hover:underline">Daftar di sini</a>
            </p>
        </div>
    </div>

</body>

</html>

<script>
    // Auto hide alert after 3 seconds
    $(document).ready(function() {
        setTimeout(() => {
            $("#alert-error, #alert-success").fadeOut();
        }, 3000);
    });
</script>