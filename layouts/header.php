<?php
session_start();

$is_logged_in = false; 
if(isset($_SESSION['id_user'])){
    $is_logged_in = true;
    $user_name = $_SESSION['name']; 
};
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
<body class="bg-gray-100">

    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-blue-600">FLYNOW</a>
            <div class="space-x-4 flex items-center">
                <a href="index.php" class="text-gray-600 hover:text-blue-600">Home</a>
                
                <?php if ($is_logged_in): ?>
                    <a href="pesanan_saya.php" class="text-gray-600 hover:text-blue-600">Pesanan Saya</a>
                    <a href="akun_saya.php" class="text-gray-600 hover:text-blue-600">
                        Akun Saya (<?php echo $user_name; ?>)
                    </a>
                    <a href="backend/logout.php" class="text-red-600 hover:text-red-800 ml-4">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Login</a>
                <?php endif; ?>

            </div>
        </div>
    </nav>
    
    <main>