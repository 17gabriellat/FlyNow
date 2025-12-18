<?php 
// File: admin/laporan_penjualan.php

require_once "../backend/db.php"; 
require_once "../backend/akses_admin.php";
$active_tab = $_GET['type'] ?? 'route';

// SET JUDUL HALAMAN BERDASARKAN TAB AKTIF
if ($active_tab === 'flight') {
    $admin_page_title = 'Sales Report per Flight Code';
} else {
    $admin_page_title = 'Sales Report per Route';
}

// --- TANGGAL & KEYWORD FILTER (Ambil dari GET) ---
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$keyword = $_GET['keyword'] ?? null; // FILTER BARU

$end_date_sql = $end_date; // Variabel ini digunakan di dalam fungsi SQL

// --- KONFIGURASI PAGINATION RUTE UTAMA (Tampilan Awal) ---
$limit = 10; // Jumlah RUTE/FLIGHT per halaman
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;
$active_tab = $_GET['type'] ?? 'route';

// ------------------------------------------------
// --- FUNGSI AGREGASI (DIREVISI UNTUK MENAMPILKAN KEYWORD) ---
// ------------------------------------------------

// Fungsi pembantu untuk mengikat parameter sebagai referensi (mengatasi Warning)
function bind_parameters_safely($stmt, $types, $params) {
    if (!$types || empty($params)) return;
    
    $bind_args = array_merge([$types], $params); 
    
    $references = [];
    $references[] = $bind_args[0]; 
    
    for ($i = 1; $i < count($bind_args); $i++) {
         $references[] = &$bind_args[$i];
    }
    
    call_user_func_array([$stmt, 'bind_param'], $references);
}


// --- FUNGSI MENGAMBIL TOTAL PENDAPATAN KESELURUHAN (Menggunakan Filter) ---
function getTotalOrdersRevenue($conn, $start_date = null, $end_date_sql = null, $keyword = null) {
    $params = [];
    $types = '';

    $sql = "
        SELECT SUM(t.total_price) AS grand_total 
        FROM transactions t
        JOIN flights f ON t.departure_flight_id = f.id_flight
        JOIN airports oa ON oa.id_airport = f.origin_airport     
        JOIN airports da ON da.id_airport = f.destination_airport 
        WHERE t.payment_status = 'Paid'";

    if ($start_date) {
        $sql .= " AND f.departure_date >= ?";
        $params[] = $start_date;
        $types .= 's';
    }
    if ($end_date_sql) {
        $sql .= " AND f.departure_date <= ?";
        $params[] = $end_date_sql;
        $types .= 's';
    }
    if ($keyword) {
        $sql .= " AND (f.flight_code LIKE ? OR CONCAT(oa.airport_code, '-', da.airport_code) LIKE ?)";
        // PERBAIKAN: Masukkan wildcard (%) ke dalam parameter sebelum binding
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
        $types .= 'ss';
    }
    
    $stmt = $conn->prepare($sql);

    if ($types) {
        bind_parameters_safely($stmt, $types, $params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['grand_total'] ?? 0;
}


// --- FUNGSI MENGAMBIL DATA AGREGASI PER RUTE (DENGAN FILTER) ---
function getOrdersByRoute($conn, $limit, $offset, $start_date = null, $end_date_sql = null, $keyword = null) {
    $where = '';
    $params = [];
    $types = '';

    if ($start_date) {
        $where .= " AND f.departure_date >= ? ";
        $params[] = $start_date;
        $types .= 's';
    }
    if ($end_date_sql) {
        $where .= " AND f.departure_date <= ? ";
        $params[] = $end_date_sql;
        $types .= 's';
    }
    if ($keyword) {
        $where .= " AND (f.flight_code LIKE ? 
                     OR CONCAT(oa.airport_code,'-',da.airport_code) LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
        $types .= 'ss';
    }

    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;

    $sql = "
        SELECT
            oa.airport_code AS origin_airport_code,
            da.airport_code AS destination_airport_code,
            SUM(f.booked_seats) AS total_tiket_terjual,
            SUM(f.booked_seats * f.price) AS total_pendapatan,
            MAX(f.departure_date) AS latest_departure_date
        FROM flights f
        JOIN airports oa ON oa.id_airport = f.origin_airport
        JOIN airports da ON da.id_airport = f.destination_airport
        WHERE 1=1
        $where
        GROUP BY f.origin_airport, f.destination_airport
        ORDER BY total_pendapatan DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    bind_parameters_safely($stmt, $types, $params);
    $stmt->execute();
    return $stmt->get_result();
}


// --- FUNGSI MENGAMBIL DATA AGREGASI PER FLIGHT (DENGAN FILTER) ---
function getOrdersByFlight($conn, $limit, $offset, $start_date = null, $end_date_sql = null, $keyword = null) {
    $where = '';
    $params = [];
    $types = '';

    if ($start_date) {
        $where .= " AND f.departure_date >= ? ";
        $params[] = $start_date;
        $types .= 's';
    }
    if ($end_date_sql) {
        $where .= " AND f.departure_date <= ? ";
        $params[] = $end_date_sql;
        $types .= 's';
    }
    if ($keyword) {
        $where .= " AND f.flight_code LIKE ? ";
        $params[] = "%$keyword%";
        $types .= 's';
    }

    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;

    $sql = "
        SELECT
            f.id_flight,
            f.flight_code,
            oa.airport_code AS origin_airport_code,
            da.airport_code AS destination_airport_code,
            SUM(f.booked_seats) AS total_tiket_terjual,
            SUM(f.booked_seats * f.price) AS total_pendapatan,
            MAX(f.departure_date) AS latest_departure_date
        FROM flights f
        JOIN airports oa ON oa.id_airport = f.origin_airport
        JOIN airports da ON da.id_airport = f.destination_airport
        WHERE 1=1
        $where
        GROUP BY 
            f.flight_code,
            f.origin_airport,
            f.destination_airport
        ORDER BY total_pendapatan DESC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    bind_parameters_safely($stmt, $types, $params);
    $stmt->execute();
    return $stmt->get_result();
}


// --- FUNGSI MENGAMBIL TOTAL RUTE (untuk Pagination) ---
function getTotalRoutes($conn, $start_date = null, $end_date_sql = null, $keyword = null) {
    $where_total = '';
    $params_total = [];
    $types_total = '';

    if ($start_date) {
        $where_total .= " AND f.departure_date >= ? ";
        $params_total[] = $start_date;
        $types_total .= 's';
    }
    if ($end_date_sql) {
        $where_total .= " AND f.departure_date <= ? ";
        $params_total[] = $end_date_sql;
        $types_total .= 's';
    }
    if ($keyword) {
        $where_total .= " AND (f.flight_code LIKE ? OR CONCAT(oa.airport_code, '-', da.airport_code) LIKE ?)";
        $params_total[] = "%$keyword%";
        $params_total[] = "%$keyword%";
        $types_total .= 'ss';
    }

    $sql_total = "
        SELECT COUNT(DISTINCT CONCAT(oa.airport_code, da.airport_code)) AS total_rute 
        FROM transactions t 
        JOIN flights f ON t.departure_flight_id = f.id_flight
        JOIN airports oa ON oa.id_airport = f.origin_airport     
        JOIN airports da ON da.id_airport = f.destination_airport 
        WHERE t.payment_status = 'Paid' " . $where_total;

    $stmt_total = $conn->prepare($sql_total);
    if ($types_total) {
        bind_parameters_safely($stmt_total, $types_total, $params_total);
    }
    $stmt_total->execute();
    return $stmt_total->get_result()->fetch_assoc()['total_rute'] ?? 0;
}

// --- FUNGSI MENGAMBIL TOTAL FLIGHT (untuk Pagination) ---
function getTotalFlights($conn, $start_date = null, $end_date_sql = null, $keyword = null) {
    $where_total = '';
    $params_total = [];
    $types_total = '';

    if ($start_date) {
        $where_total .= " AND f.departure_date >= ? ";
        $params_total[] = $start_date;
        $types_total .= 's';
    }
    if ($end_date_sql) {
        $where_total .= " AND f.departure_date <= ? ";
        $params_total[] = $end_date_sql;
        $types_total .= 's';
    }
    if ($keyword) {
        $where_total .= " AND (f.flight_code LIKE ? OR CONCAT(oa.airport_code, '-', da.airport_code) LIKE ?)";
        $params_total[] = "%$keyword%";
        $params_total[] = "%$keyword%";
        $types_total .= 'ss';
    }


    $sql_total = "
        SELECT COUNT(DISTINCT f.id_flight) AS total_flights
        FROM transactions t 
        JOIN flights f ON t.departure_flight_id = f.id_flight
        JOIN airports oa ON oa.id_airport = f.origin_airport     
        JOIN airports da ON da.id_airport = f.destination_airport 
        WHERE t.payment_status = 'Paid' " . $where_total;

    $stmt_total = $conn->prepare($sql_total);
    if ($types_total) {
        bind_parameters_safely($stmt_total, $types_total, $params_total);
    }
    $stmt_total->execute();
    return $stmt_total->get_result()->fetch_assoc()['total_flights'] ?? 0;
}


// --- EKSEKUSI PENGAMBILAN DATA AWAL BERDASARKAN TAB AKTIF & FILTER ---

if ($active_tab === 'flight') {
    $total_rows = getTotalFlights($conn, $start_date, $end_date_sql, $keyword);
    $total_pages = ceil($total_rows / $limit);
    $flight_sales = getOrdersByFlight($conn, $limit, $offset, $start_date, $end_date_sql, $keyword);
    $route_sales = null;
} else { // active_tab === 'route' (default)
    $total_rows = getTotalRoutes($conn, $start_date, $end_date_sql, $keyword);
    $total_pages = ceil($total_rows / $limit);
    $route_sales = getOrdersByRoute($conn, $limit, $offset, $start_date, $end_date_sql, $keyword);
    $flight_sales = null;
}

$total_pendapatan_semua = getTotalOrdersRevenue($conn, $start_date, $end_date_sql, $keyword); 
$back_params = http_build_query([
    'type'       => $active_tab,
    'start_date' => $start_date ?? '',
    'end_date'   => $end_date ?? '',
    'page'       => $page ?? 1,
    'keyword'    => $keyword ?? ''
]);
require_once '../layouts/admin_header.php'; 
require_once '../layouts/admin_sidebar.php'; 
?>
<main class="flex-3 p-10">
<div class="mb-6 ml-4 border-b border-gray-200">
        <nav class="flex space-x-6" id="report-tabs">

            <a href="laporan.php?type=route" data-type="route"
            class="tab-btn py-2 px-1 border-b-2 font-semibold
            <?= $active_tab === 'route'
                    ? 'border-blue-600 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
                Based on Route
            </a>

            <a href="laporan.php?type=flight" data-type="flight"
            class="tab-btn py-2 px-1 border-b-2 font-semibold
            <?= $active_tab === 'flight'
                    ? 'border-blue-600 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700' ?>">
                Based on Flight Code
            </a>

        </nav>
    </div>


    <h1 class="text-3xl font-bold mb-4"><?php echo $admin_page_title; ?></h1>
    <p class="mb-6 text-xl text-gray-700">Total Revenue: <span class="font-bold text-blue-600">Rp <?= number_format($total_pendapatan_semua, 0, ',', '.') ?></span></p>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8 flex items-center space-x-4">
        <form id="filter-form" action="laporan.php" method="GET" class="flex items-center space-x-4">
            <input type="hidden" name="type" id="active-tab-input" value="<?= htmlspecialchars($active_tab) ?>">

            <div>
                <label class="block text-sm font-medium">Search (Code/Route)</label>
                <input type="text" name="keyword" id="keyword" class="mt-1 p-2 border rounded-md w-64"
                       placeholder="e.g. GA123 or JKT-DPS"
                       value="<?= htmlspecialchars($keyword ?? '') ?>">
            </div>
            
            <div>
                <label class="block text-sm font-medium">From Date</label>
                <input type="date" name="start_date" id="start_date" class="mt-1 p-2 border rounded-md"
                       value="<?= htmlspecialchars($start_date ?? '') ?>">
            </div>
            <div>
                <label class="block text-sm font-medium">Until Date</label>
                <input type="date" name="end_date" id="end_date" class="mt-1 p-2 border rounded-md"
                       value="<?= htmlspecialchars($end_date ?? '') ?>">
            </div>
            
            <button type="submit" class="bg-gradient-to-t from-blue-800 to-blue-400 text-white px-5 py-2 rounded-md self-end hover:bg-gradient-to-r from-blue-800 to-blue-400">
                Show
            </button>
        </form>

        <a href="laporan.php?type=<?= htmlspecialchars($active_tab) ?>" 
           class="bg-gradient-to-t from-gray-800 to-gray-400 text-white px-5 py-2 rounded-md self-end hover:bg-gradient-to-r from-gray-800 to-gray-400">
            Reset Filter
        </a>
        
        <button id="export-button" class="bg-gradient-to-t from-green-800 to-green-400 text-white px-5 py-2 rounded-md self-end hover:bg-gradient-to-r from-green-800 to-green-400">
            Export to CSV
        </button>
    </div>

    <div id="table-route"
     class="bg-white rounded-lg shadow-md overflow-x-auto
     <?= $active_tab === 'route' ? '' : 'hidden' ?>">
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flight Routes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Latest Flight Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tickets Sold</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Income</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if ($route_sales && $route_sales->num_rows > 0): ?>
                    <?php while ($report = $route_sales->fetch_assoc()): 
                        $route_id = $report['origin_airport_code'].'-'.$report['destination_airport_code'];
                    ?>
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                <?= $report['origin_airport_code'] ?> &rarr;
                                <?= $report['destination_airport_code'] ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= date('d M Y', strtotime($report['latest_departure_date'])) ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-right">
                                <?= number_format($report['total_tiket_terjual']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-right text-blue-600">
                                Rp <?= number_format($report['total_pendapatan'],0,',','.') ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="detail_penjualan_rute.php?route=<?= urlencode($route_id) ?>&back_params=<?= urlencode($back_params) ?>"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold">
                                        See Customers
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No sales data per route.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($active_tab === 'route' && $total_pages >= 1): ?>
            <div class="mt-4 flex justify-center p-4">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php 
                    $base_url = "laporan.php?type=$active_tab";
                    // Tambahkan filter ke base URL
                    if (!empty($start_date)) { $base_url .= "&start_date=" . urlencode($start_date); }
                    if (!empty($end_date)) { $base_url .= "&end_date=" . urlencode($end_date); }
                    if (!empty($keyword)) { $base_url .= "&keyword=" . urlencode($keyword); }

                    // Tombol Previous
                    $prev_page = $page > 1 ? $page - 1 : 1;
                    $prev_link = $base_url . "&page=" . $prev_page;
                    $prev_class = $page > 1 ? 'hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-default';
                    echo '<a href="' . $prev_link . '" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 ' . $prev_class . '">Previous</a>';

                    // Tautan Halaman
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active_class = ($i == $page) ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
                        $page_link = $base_url . "&page=" . $i;
                        echo '<a href="' . $page_link . '" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ' . $active_class . '">' . $i . '</a>';
                    }

                    // Tombol Next
                    $next_page = $page < $total_pages ? $page + 1 : $total_pages;
                    $next_link = $base_url . "&page=" . $next_page;
                    $next_class = $page < $total_pages ? 'hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-default';
                    echo '<a href="' . $next_link . '" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 ' . $next_class . '">Next</a>';
                    ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <div id="table-flight"
     class="bg-white rounded-lg shadow-md overflow-x-auto
     <?= $active_tab === 'flight' ? '' : 'hidden' ?>">
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flight Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Latest Flight Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tickets Sold</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Income</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody id="flight-table-body" class="bg-white divide-y divide-gray-200">
                <?php if ($flight_sales && $flight_sales->num_rows > 0): ?>
                    <?php while ($row = $flight_sales->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                <?= htmlspecialchars($row['flight_code']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= $row['origin_airport_code'] ?> &rarr;
                                <?= $row['destination_airport_code'] ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= date('d M Y', strtotime($row['latest_departure_date'])) ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-right">
                                <?= number_format($row['total_tiket_terjual']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-right text-blue-600">
                                Rp <?= number_format($row['total_pendapatan'], 0, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="detail_penjualan_flight.php?id_flight=<?= urlencode($row['id_flight']) ?>&back_params=<?= urlencode($back_params) ?>"
                            class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold">
                                See Customers
                            </a>



                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No sales data per flight.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>

        </table>
         <?php if ($active_tab === 'flight' && $total_pages >= 1): ?>
            <div class="mt-4 flex justify-center p-4">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php 
                    $base_url = "laporan.php?type=$active_tab";
                    // Tambahkan filter ke base URL
                    if (!empty($start_date)) { $base_url .= "&start_date=" . urlencode($start_date); }
                    if (!empty($end_date)) { $base_url .= "&end_date=" . urlencode($end_date); }
                    if (!empty($keyword)) { $base_url .= "&keyword=" . urlencode($keyword); }

                    // Tombol Previous
                    $prev_page = $page > 1 ? $page - 1 : 1;
                    $prev_link = $base_url . "&page=" . $prev_page;
                    $prev_class = $page > 1 ? 'hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-default';
                    echo '<a href="' . $prev_link . '" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 ' . $prev_class . '">Previous</a>';

                    // Tautan Halaman
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active_class = ($i == $page) ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
                        $page_link = $base_url . "&page=" . $i;
                        echo '<a href="' . $page_link . '" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ' . $active_class . '">' . $i . '</a>';
                    }

                    // Tombol Next
                    $next_page = $page < $total_pages ? $page + 1 : $total_pages;
                    $next_link = $base_url . "&page=" . $next_page;
                    $next_class = $page < $total_pages ? 'hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-default';
                    echo '<a href="' . $next_link . '" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 ' . $next_class . '">Next</a>';
                    ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. TANGANI KLIK TAB (untuk mempertahankan filter tanggal dan keyword)
    const tabLinks = document.querySelectorAll('.tab-btn');
    tabLinks.forEach(tabLink => {
        tabLink.addEventListener('click', (e) => {
            e.preventDefault(); 
            
            // Ambil filter yang sedang aktif dari form sebelum redirect
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const keyword = document.getElementById('keyword').value;

            const newTabType = tabLink.getAttribute('data-type');
            
            // Bangun URL baru sambil mempertahankan filter
            let newUrl = `laporan.php?type=${newTabType}&page=1`;
            if (startDate) {
                newUrl += `&start_date=${startDate}`;
            }
            if (endDate) {
                newUrl += `&end_date=${endDate}`;
            }
            if (keyword) {
                // Encode keyword agar aman dalam URL
                newUrl += `&keyword=${encodeURIComponent(keyword)}`; 
            }

            window.location.href = newUrl; // Redirect ke URL baru
        });
    });

    // 2. TANGANI EXPORT
    document.getElementById('export-button')?.addEventListener('click', () => {
        const currentActiveTab = document.getElementById('active-tab-input').value; 
        const currentStartDate = document.getElementById('start_date')?.value || '';
        const currentEndDate   = document.getElementById('end_date')?.value || '';
        const currentKeyword   = document.getElementById('keyword')?.value || '';

        let exportUrl = '';
        
        // Export URL harus mencakup semua filter yang aktif
        const filterParams = `start_date=${currentStartDate}&end_date=${currentEndDate}&keyword=${encodeURIComponent(currentKeyword)}`;

        if (currentActiveTab === 'route') {
            exportUrl = `../backend/admin/export_laporan_rute.php?${filterParams}`;
        } else {
            exportUrl = `../backend/admin/export_laporan_flight.php?${filterParams}`;
        }

        window.location.href = exportUrl;
    });
});
</script>

<?php 
require_once '../layouts/admin_footer.php'; 
?>