<?php
$page_title = 'My Account - FLYNOW';

require_once "backend/db.php";
require_once "layouts/header.php";

// Check if user is logged in
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// If Save button is clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $telepon  = trim($_POST['telepon']);

    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id_user = ?");
    $stmt->bind_param("ssi", $fullname, $telepon, $id_user);

    if ($stmt->execute()) {
        // Update session so header name changes instantly
        $_SESSION['name'] = $fullname;

        $_SESSION['success'] = "Profile successfully updated!";
    } else {
        $_SESSION['error'] = "Failed to update profile!";
    }

    header("Location: akun_saya.php");
    exit;
}

// Get user data
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$data_user = $result->fetch_assoc();

$name  = $data_user['name'] ?? "";
$email = $data_user['email'] ?? "";
$phone = $data_user['phone'] ?? "";
?>

<div class="container mx-auto px-6 py-8">
    <h1 class="text-3xl font-bold mb-8">My Account</h1>

    <!-- ALERT ERROR -->
    <?php if (isset($_SESSION['error'])): ?>
        <div id="alert-error"
            class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['error']; ?></span>
                <button onclick="document.getElementById('alert-error').style.display='none';"
                        class="text-red-600 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['error']); endif; ?>

    <!-- ALERT SUCCESS -->
    <?php if (isset($_SESSION['success'])): ?>
        <div id="alert-success"
            class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['success']; ?></span>
                <button onclick="document.getElementById('alert-success').style.display='none';"
                        class="text-green-700 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['success']); endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <ul class="space-y-1">
                    <li><a href="akun_saya.php" class="block px-4 py-2 bg-blue-100 text-blue-700 font-semibold rounded-md">Edit Profile</a></li>
                    <li><a href="reset_pass.php" class="block px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-md">Change Password</a></li>
                    <li><a href="backend/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50 rounded-md">Logout</a></li>
                </ul>
            </div>
        </div>

        <div class="md:col-span-2">
            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold mb-6">Edit Profile</h2>

                <form action="" method="POST" class="space-y-6">
                    <div>
                        <label for="fullname" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" id="fullname" name="fullname"
                               class="mt-1 w-full p-3 border border-gray-300 rounded-md"
                               value="<?= htmlspecialchars($name) ?>" required>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email"
                               class="mt-1 w-full p-3 border border-gray-300 rounded-md bg-gray-100 cursor-not-allowed"
                               value="<?= htmlspecialchars($email) ?>" readonly>
                        <p class="text-xs text-gray-500 mt-1">Email cannot be changed.</p>
                    </div>

                    <div>
                        <label for="telepon" class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="tel" id="telepon" name="telepon"
                               class="mt-1 w-full p-3 border border-gray-300 rounded-md"
                               value="<?= htmlspecialchars($phone) ?>">
                    </div>

                    <button type="submit"
                            class="w-auto bg-blue-600 text-white font-bold px-6 py-3 rounded-md shadow-lg hover:bg-blue-700">
                        Save Changes
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php
require_once 'layouts/footer.php';
?>
