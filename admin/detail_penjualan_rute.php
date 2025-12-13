<?php
// File: admin/detail_penjualan_rute.php

require_once "../backend/db.php"; 
require_once "../backend/akses_admin.php";

$route_id = $_GET['route'] ?? ''; 

if (empty($route_id) || !strpos($route_id, '-')) {
    header('Location: laporan.php'); // Redirect jika parameter tidak valid
    exit;
}

list($origin_code, $dest_code) = explode('-', $route_id);
$admin_page_title = "Route Sales Details: $origin_code &rarr; $dest_code";

// --- KONFIGURASI PAGINATION DETAIL ---
$limit = 15; // Jumlah transaksi per halaman detail
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;


// --- QUERY UNTUK DETAIL TRANSAKSI (Sama seperti API sebelumnya) ---

// Base Query
$query_base = "
    FROM transactions t
    JOIN users u ON u.id_user = t.user_id
    JOIN flights f ON f.id_flight = t.departure_flight_id
    JOIN airports oa ON oa.id_airport = f.origin_airport
    JOIN airports da ON da.id_airport = f.destination_airport
    WHERE oa.airport_code = ? AND da.airport_code = ? AND t.payment_status = 'Paid'
";

// Data yang ingin diambil
$select_data = "
    SELECT 
        t.id_transaction, t.booking_code, t.total_price, t.created_at,
        u.name AS customer_name, u.email AS customer_email,
        f.departure_date, f.departure_time 
";

$params = [$origin_code, $dest_code];
$types = 'ss'; 

// 1. Ambil Total Baris
$sql_count = "SELECT COUNT(t.id_transaction) AS total_rows " . $query_base;
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total_rows'];
$stmt_count->close();
$total_pages = ceil($total_rows / $limit);

// 2. Ambil Data Aktual
$sql_data = $select_data . $query_base;
$sql_data .= " ORDER BY t.created_at DESC "; // Diurutkan berdasarkan transaksi terbaru
$sql_data .= " LIMIT ? OFFSET ?";

$types_data = $types . 'ii'; 
$params_data = array_merge($params, [$limit, $offset]);

$stmt_data = $conn->prepare($sql_data);
$stmt_data->bind_param($types_data, ...$params_data); 
$stmt_data->execute();
$route_buyers = $stmt_data->get_result();

require_once '../layouts/admin_header.php'; 
require_once '../layouts/admin_sidebar.php'; 
?>

<main class="flex-1 p-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold"><?php echo $admin_page_title; ?></h1>
        <a href="laporan.php" class="text-blue-600 hover:text-blue-800 text-sm font-semibold flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Report
        </a>
    </div>

    <p class="mb-4 text-gray-700">Total transactions paid for this route: <?php echo number_format($total_rows); ?></p>

    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code Booking</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flight Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Buyer Name</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total price</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($route_buyers->num_rows > 0): ?>
                    <?php while($buyer = $route_buyers->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo date('d M Y H:i', strtotime($buyer['created_at'])); ?></td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($buyer['booking_code']); ?></td>
                            <td class="px-6 py-4 text-sm text-blue-600 font-semibold"><?php echo date('d M Y', strtotime($buyer['departure_date'])); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <strong><?php echo htmlspecialchars($buyer['customer_name']); ?></strong><br>
                                <span class="text-xs text-gray-500"><?php echo htmlspecialchars($buyer['customer_email']); ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-right">Rp <?php echo number_format($buyer['total_price'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">There are no paid transactions for this route.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="mt-4 flex justify-center">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <?php 
            $route_param = urlencode($route_id);
            $prev_page = $page > 1 ? $page - 1 : 1;
            $prev_class = $page > 1 ? 'hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-default';
            $prev_link = "?route=$route_param&page=$prev_page";
            echo '<a href="' . $prev_link . '" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 ' . $prev_class . '">Previous</a>';

            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = ($i == $page) ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
                echo '<a href="?route=' . $route_param . '&page=' . $i . '" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ' . $active_class . '">' . $i . '</a>';
            }

            $next_page = $page < $total_pages ? $page + 1 : $total_pages;
            $next_class = $page < $total_pages ? 'hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-default';
            $next_link = "?route=$route_param&page=$next_page";
            echo '<a href="' . $next_link . '" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 ' . $next_class . '">Next</a>';
            ?>
        </nav>
    </div>
    <?php endif; ?>
</main>

<?php 
require_once '../layouts/admin_footer.php'; 
?>