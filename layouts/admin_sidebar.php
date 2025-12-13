<?php
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page === "index.php") $current_page = "dashboard.php";
$active_page = ($current_page === "detail_pesanan.php") ? "pesanan.php" : $current_page;
?>

<!-- Toggle Sidebar Button (Mobile) -->
<button id="sidebar-toggle" class="lg:hidden fixed top-4 left-4 z-50 p-2 bg-gray-800 text-white rounded-md shadow-md">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>

<!-- Sidebar -->
<aside id="main-sidebar"
    class="w-64 bg-gray-800 text-white p-5 min-h-screen
    fixed top-0 left-0 z-40 transform transition-transform duration-300 ease-in-out
    -translate-x-full lg:translate-x-0 lg:sticky">

    <div class="text-2xl font-bold mb-8 px-4">FLYNOW ADMIN</div>
    <nav class="space-y-1">

        <a href="dashboard.php"
           class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700
           <?= ($active_page === 'dashboard.php') ? 'bg-gray-700' : '' ?>">
            Dashboard
        </a>

        <a href="penerbangan.php"
           class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700
           <?= ($active_page === 'penerbangan.php') ? 'bg-gray-700' : '' ?>">
            Flight Management
        </a>

        <a href="pesanan.php"
           class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700
           <?= ($active_page === 'pesanan.php') ? 'bg-gray-700' : '' ?>">
            Order Management
        </a>

        <a href="pengguna.php"
           class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700
           <?= ($active_page === 'pengguna.php') ? 'bg-gray-700' : '' ?>">
            User Management
        </a>

        <a href="laporan.php"
           class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700
           <?= ($active_page === 'laporan.php') ? 'bg-gray-700' : '' ?>">
            Report
        </a>

        <a href="../backend/logout.php"
           class="text-red-300 block py-2.5 px-4 rounded transition duration-200 hover:bg-red-900">
            Logout
        </a>
    </nav>
</aside>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('main-sidebar');
    const toggleButton = document.getElementById('sidebar-toggle');
    const menuLinks = sidebar.querySelectorAll('a');

    // Toggle sidebar on mobile
    toggleButton.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
    });

    // Auto hide sidebar after clicking menu on mobile
    menuLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                sidebar.classList.add('-translate-x-full');
            }
        });
    });
});
</script>
