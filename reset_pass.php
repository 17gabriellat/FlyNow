<?php
$page_title = 'Reset Password - FLYNOW';

require_once "backend/db.php";
require_once "layouts/header.php";

// Check if user is logged in
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass  = trim($_POST['current_pass']);
    $new_pass      = trim($_POST['new_pass']);
    $confirm_pass  = trim($_POST['confirm_pass']);

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id_user = ?");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $_SESSION['error'] = "User not found!";
        header("Location: reset_pass.php");
        exit;
    }

    $old_hashed = $user['password_hash'];

    if (!password_verify($current_pass, $old_hashed)) {
        $_SESSION['error'] = "Current password is incorrect!";
        header("Location: reset_pass.php");
        exit;
    }

    // Check password confirmation
    if ($new_pass !== $confirm_pass) {
        $_SESSION['error'] = "New password confirmation does not match!";
        header("Location: reset_pass.php");
        exit;
    }

    $new_hashed = password_hash($new_pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id_user = ?");
    $stmt->bind_param("si", $new_hashed, $id_user);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Password has been successfully updated!";
    } else {
        $_SESSION['error'] = "An error occurred! Failed to update password.";
    }

    header("Location: reset_pass.php");
    exit;
}
?>

<div class="container mx-auto px-6 py-8">
    <h1 class="text-3xl font-bold mb-8">Reset Password</h1>

    <!-- ALERT ERROR -->
    <?php if (isset($_SESSION['error'])): ?>
        <div id="alert-error" class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['error']; ?></span>
                <button onclick="document.getElementById('alert-error').style.display='none';"
                        class="text-red-600 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['error']); endif; ?>

    <!-- ALERT SUCCESS -->
    <?php if (isset($_SESSION['success'])): ?>
        <div id="alert-success" class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['success']; ?></span>
                <button onclick="document.getElementById('alert-success').style.display='none';"
                        class="text-green-700 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['success']); endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

        <!-- Sidebar Menu -->
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <ul class="space-y-1">
                    <li><a href="akun_saya.php" class="block px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-md">Edit Profile</a></li>
                    <li><a href="reset_pass.php" class="block px-4 py-2 bg-blue-100 text-blue-700 font-semibold rounded-md">Change Password</a></li>
                    <li><a href="backend/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50 rounded-md">Logout</a></li>
                </ul>
            </div>
        </div>

        <!-- Main Form -->
        <div class="md:col-span-2">
            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold mb-6">Reset Password</h2>

                <form method="POST" class="space-y-6">

                    <div>
                        <label class="block mb-1 font-medium">Current Password</label>
                        <input type="password" name="current_pass" class="w-full p-3 border rounded-md" required>
                    </div>

                    <div>
                        <label class="block mb-1 font-medium">New Password</label>
                        <input type="password" name="new_pass" class="w-full p-3 border rounded-md" required>
                    </div>

                    <div>
                        <label class="block mb-1 font-medium">Confirm New Password</label>
                        <input type="password" name="confirm_pass" class="w-full p-3 border rounded-md" required>
                    </div>

                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-bold">
                        Save Changes
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once "layouts/footer.php"; ?>

<script>
    // Auto hide alert after 3 seconds
    $(document).ready(function() {
        setTimeout(() => {
            $("#alert-error, #alert-success").fadeOut();
        }, 3000);
    });
</script>
