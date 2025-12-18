<?php
session_start();

$is_logged_in = false; 
if(isset($_SESSION['id_user'])){
    $is_logged_in = true;
    $user_name = $_SESSION['name']; 
};
// determine current page for active nav link styling
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <title><?php echo $page_title ?? 'FLYNOW'; ?></title>
</head>
<nav class="bg-white shadow-md">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">

            <!-- LOGO -->
            <a href="index.php" class="text-2xl font-bold text-blue-600">
                FLYNOW
            </a>

            <!-- HAMBURGER (Mobile) -->
            <button id="menu-btn"
                class="lg:hidden text-gray-600 focus:outline-none focus:text-blue-600">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- MENU DESKTOP -->
            <div class="hidden lg:flex space-x-4 items-center">

                <a href="index.php"
                    class="<?= $current_page === 'index.php'
                        ? 'text-blue-600'
                        : 'text-gray-600 hover:text-blue-600'; ?>">
                    Home
                </a>

                <?php if ($is_logged_in): ?>
                    <a href="pesanan_saya.php"
                        class="<?= $current_page === 'pesanan_saya.php'
                            ? 'text-blue-600'
                            : 'text-gray-600 hover:text-blue-600'; ?>">
                        My Orders
                    </a>

                    <a href="akun_saya.php"
                        class="<?= $current_page === 'akun_saya.php'
                            ? 'text-blue-600'
                            : 'text-gray-600 hover:text-blue-600'; ?>">
                        My Account (<?= $user_name; ?>)
                    </a>

                    <a href="backend/logout.php"
                        class="text-red-600 hover:text-red-800 ml-4">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="login.php"
                        class="bg-gradient-to-t from-blue-700 to-blue-400 text-white px-4 py-2 rounded-md shadow-md hover:shadow-lg transition">
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- MENU MOBILE -->
        <div id="mobile-menu"
            class="hidden lg:hidden mt-4 space-y-3">

            <a href="index.php"
                class="block text-gray-600 hover:text-blue-600">
                Home
            </a>

            <?php if ($is_logged_in): ?>
                <a href="pesanan_saya.php"
                    class="block text-gray-600 hover:text-blue-600">
                    My Orders
                </a>

                <a href="akun_saya.php"
                    class="block text-gray-600 hover:text-blue-600">
                    My Account (<?= $user_name; ?>)
                </a>

                <a href="backend/logout.php"
                    class="block text-red-600 hover:text-red-800">
                    Logout
                </a>
            <?php else: ?>
                <a href="login.php"
                    class="block bg-gradient-to-t from-blue-700 to-blue-400 text-white px-4 py-2 rounded-md text-center">
                    Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<script>
    const menuBtn = document.getElementById('menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    menuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });
</script>
