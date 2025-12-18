<?php
// File: admin/detail_penjualan_rute.php

require_once "../backend/db.php"; 
require_once "../backend/akses_admin.php";

$route_id = $_GET['route'] ?? ''; 
$back_params = $_GET['back_params'] ?? ''; 
$back_url = "laporan.php";

// Pastikan back_params di-decode agar bisa diparsing dengan benar nanti
$decoded_back_params = $back_params ? urldecode($back_params) : "";


if (empty($route_id) || !strpos($route_id, '-')) {
    // Redirect jika parameter tidak valid, kembali ke laporan umum atau menggunakan filter jika tersedia
    header('Location: laporan.php?' . $decoded_back_params); 
    exit;
}

list($origin_code, $dest_code) = explode('-', $route_id);
$admin_page_title = "Route Sales Details: $origin_code &rarr; $dest_code";

// --- KONFIGURASI PAGINATION DETAIL ---
$limit = 10; // Jumlah transaksi per halaman detail
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;


// --- QUERY UNTUK DETAIL TRANSAKSI ---

// Base Query
$query_base = "
    FROM transactions t
    JOIN users u ON u.id_user = t.user_id
    JOIN flights f ON f.id_flight = t.departure_flight_id
    LEFT JOIN flights f2 ON f2.id_flight = t.return_flight_id
    JOIN airports oa ON oa.id_airport = f.origin_airport
    LEFT JOIN airports oa2 ON oa2.id_airport = f2.origin_airport
    LEFT JOIN airports da2 ON da2.id_airport = f2.destination_airport
    JOIN airports da ON da.id_airport = f.destination_airport
    WHERE ((oa.airport_code = ? AND da.airport_code = ?) OR (oa2.airport_code = ? AND da2.airport_code = ?)) AND t.payment_status = 'Paid' 
";

// Data yang ingin diambil
$select_data = "
    SELECT 
        t.id_transaction, t.booking_code, t.total_price, t.created_at, t.total_passengers,
        u.name AS customer_name, u.email AS customer_email,
        f.departure_date, f.departure_time 
";

$params = [$origin_code, $dest_code, $origin_code, $dest_code];
$types = 'ssss'; 

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
        <h1 class="text-3xl font-bold"><?= $admin_page_title; ?></h1>

        <?php 
        // Konstruksi URL kembali dengan mempertahankan semua filter yang sebelumnya dikirim
        $back_url_with_params = "laporan.php?" . ($back_params ? htmlspecialchars($decoded_back_params) : "type=route"); 
        ?>
        <a href="<?= $back_url_with_params ?>"
        class="text-blue-600 hover:text-blue-800 text-sm font-semibold flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="2"
                    d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passengers</th>
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
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo number_format($buyer['total_passengers']); ?></td>
                            <td class="px-6 py-4 text-sm font-semibold text-right">Rp <?php echo number_format($buyer['total_price'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada customer yang tampil untuk rute ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages >= 1): ?>
    <?php
        $route_param = urlencode($route_id);
        $encoded_back = urlencode($back_params);
        $pagination_base = "?route={$route_param}&back_params={$encoded_back}";
    ?>

    <div class="mt-6 flex justify-between items-center">
        <p class="text-sm text-gray-600">
            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_rows) ?>
            of <?= $total_rows ?> transactions
        </p>

        <nav class="inline-flex rounded-md shadow-sm -space-x-px">
            <?php if ($page > 1): ?>
                <a href="<?= $pagination_base ?>&page=<?= $page - 1 ?>"
                   class="px-3 py-2 border bg-white text-sm hover:bg-gray-50">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="<?= $pagination_base ?>&page=<?= $i ?>"
                   class="px-4 py-2 border text-sm <?= $i == $page ? 'bg-blue-50 text-blue-600 border-blue-500' : 'bg-white hover:bg-gray-50' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="<?= $pagination_base ?>&page=<?= $page + 1 ?>"
                   class="px-3 py-2 border bg-white text-sm hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</main>

<?php 
require_once '../layouts/admin_footer.php'; 
?>