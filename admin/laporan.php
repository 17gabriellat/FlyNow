<?php 
// File: admin/laporan_penjualan.php

require_once "../backend/db.php"; 
require_once "../backend/akses_admin.php";

$admin_page_title = 'Laporan Penjualan per Rute';

// --- TANGGAL FILTER AWAL (Untuk tampilan default) ---
$start_date = null; 
$end_date = null;
$end_date_sql = null;

// --- KONFIGURASI PAGINATION RUTE UTAMA (Tampilan Awal) ---
$limit = 10; // Jumlah RUTE per halaman
$page = 1; // Halaman default saat pemuatan pertama
$offset = 0;

// ------------------------------------------------
// --- FUNGSI AGREGASI (DIPERBAIKI UNTUK MENGATASI WARNING BIND_PARAM) ---
// ------------------------------------------------

// Fungsi pembantu untuk mengikat parameter sebagai referensi (mengatasi Warning)
function bind_parameters_safely($stmt, $types, $params) {
    if (!$types) return;
    
    // Siapkan array argumen: $types diikuti oleh semua nilai di $params
    $bind_args = array_merge([$types], $params); 
    
    $references = [];
    // Ambil string tipe data (nilai biasa)
    $references[] = $bind_args[0]; 
    
    // Ambil referensi dari variabel-variabel data
    for ($i = 1; $i < count($bind_args); $i++) {
         $references[] = &$bind_args[$i];
    }
    
    call_user_func_array([$stmt, 'bind_param'], $references);
}


// --- FUNGSI MENGAMBIL TOTAL PENDAPATAN KESELURUHAN (Menggunakan Filter) ---
function getTotalOrdersRevenue($conn, $start_date = null, $end_date_sql = null) {
    $params = [];
    $types = '';

    $sql = "
        SELECT SUM(t.total_price) AS grand_total 
        FROM transactions t
        JOIN flights f ON t.departure_flight_id = f.id_flight
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
    
    $stmt = $conn->prepare($sql);
    if ($types) {
         bind_parameters_safely($stmt, $types, $params);
    } else {
        // Jika tidak ada filter, gunakan query non-prepared
        $sql_no_filter = "SELECT SUM(total_price) AS grand_total FROM transactions WHERE payment_status = 'Paid'";
        $result = $conn->query($sql_no_filter)->fetch_assoc();
        return $result['grand_total'] ?? 0;
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['grand_total'] ?? 0;
}


// --- FUNGSI MENGAMBIL DATA AGREGASI PER RUTE (DENGAN FILTER TANGGAL) ---
function getOrdersByRoute($conn, $limit, $offset, $start_date = null, $end_date_sql = null) {
    $where_date = '';
    $params = [];
    $types = '';

    if ($start_date) {
        $where_date .= " AND f.departure_date >= ? ";
        $params[] = $start_date;
        $types .= 's';
    }
    if ($end_date_sql) {
        $where_date .= " AND f.departure_date <= ? ";
        $params[] = $end_date_sql;
        $types .= 's';
    }
    
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;

    $sql = "
        SELECT 
            oa.airport_code AS origin_airport_code, 
            da.airport_code AS destination_airport_code, 
            SUM(t.total_price) AS total_pendapatan,
            SUM(t.total_passengers) AS total_tiket_terjual,
            MAX(f.departure_date) AS latest_departure_date 
        FROM 
            transactions t
        JOIN 
            flights f ON t.departure_flight_id = f.id_flight
        JOIN 
            airports oa ON oa.id_airport = f.origin_airport     
        JOIN 
            airports da ON da.id_airport = f.destination_airport 
        WHERE 
            t.payment_status = 'Paid'
            " . $where_date . " 
        GROUP BY 
            oa.airport_code, da.airport_code 
        ORDER BY 
            total_pendapatan DESC 
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    
    if ($types) {
        bind_parameters_safely($stmt, $types, $params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// --- FUNGSI MENGAMBIL TOTAL RUTE (untuk Pagination) ---
function getTotalRoutes($conn, $start_date = null, $end_date_sql = null) {
    $where_date_total = '';
    $params_total = [];
    $types_total = '';

    if ($start_date) {
        $where_date_total .= " AND f.departure_date >= ? ";
        $params_total[] = $start_date;
        $types_total .= 's';
    }
    if ($end_date_sql) {
        $where_date_total .= " AND f.departure_date <= ? ";
        $params_total[] = $end_date_sql;
        $types_total .= 's';
    }

    $sql_total = "
        SELECT COUNT(DISTINCT CONCAT(oa.airport_code, da.airport_code)) AS total_rute 
        FROM transactions t 
        JOIN flights f ON t.departure_flight_id = f.id_flight
        JOIN airports oa ON oa.id_airport = f.origin_airport     
        JOIN airports da ON da.id_airport = f.destination_airport 
        WHERE t.payment_status = 'Paid' " . $where_date_total;

    $stmt_total = $conn->prepare($sql_total);
    if ($types_total) {
        bind_parameters_safely($stmt_total, $types_total, $params_total);
    }
    $stmt_total->execute();
    return $stmt_total->get_result()->fetch_assoc()['total_rute'] ?? 0;
}


// --- EKSEKUSI PENGAMBILAN DATA AWAL ---
$total_rows = getTotalRoutes($conn, $start_date, $end_date_sql);
$total_pages = ceil($total_rows / $limit);
$route_sales = getOrdersByRoute($conn, $limit, $offset, $start_date, $end_date_sql);
$total_pendapatan_semua = getTotalOrdersRevenue($conn, $start_date, $end_date_sql); 

require_once '../layouts/admin_header.php'; 
require_once '../layouts/admin_sidebar.php'; 
?>

<main class="flex-1 p-10">
    <h1 class="text-3xl font-bold mb-8"><?php echo $admin_page_title; ?></h1>

    <div class="bg-white p-6 rounded-lg shadow-md mb-8 flex items-center space-x-4">
        <div>
            <label for="start_date" class="block text-sm font-medium">From Date</label>
            <input type="date" id="start_date" class="mt-1 p-2 border rounded-md">
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium">Until Date</label>
            <input type="date" id="end_date" class="mt-1 p-2 border rounded-md">
        </div>
        <button type="button" id="filter-button" class="bg-blue-600 text-white px-5 py-2 rounded-md self-end">Show</button>
        <button type="button" id="export-button" class="bg-green-600 text-white px-5 py-2 rounded-md self-end">Export to Excel</button>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Flight Routes</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Latest Flight Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tickets Sold</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Income</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody id="sales-table-body" class="bg-white divide-y divide-gray-200">
                <?php if ($route_sales->num_rows > 0): ?>
                    <?php while($report = $route_sales->fetch_assoc()): 
                        $rute_kode = htmlspecialchars($report['origin_airport_code'] . ' → ' . $report['destination_airport_code']);
                        $route_id = htmlspecialchars($report['origin_airport_code'] . '-' . $report['destination_airport_code']);
                    ?>
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo $rute_kode; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo date('d M Y', strtotime($report['latest_departure_date'])); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo number_format($report['total_tiket_terjual']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-900">Rp <?php echo number_format($report['total_pendapatan'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <a 
                                    href="detail_penjualan_rute.php?route=<?php echo urlencode($route_id); ?>" 
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold">
                                    See Customer
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No sales data per route.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot id="sales-table-footer" class="bg-gray-50">
                <tr>
                    <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-900">GRAND TOTAL INCOME</td>
                    <td class="px-6 py-4 text-sm font-bold text-blue-600">Rp <?php echo number_format($total_pendapatan_semua, 0, ',', '.'); ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="mt-4 flex justify-between items-center">
        <div id="record-info">
            <?php 
                $start_record = $offset + 1;
                $end_record = min($offset + $limit, $total_rows);
                if ($total_rows == 0) { $start_record = 0; $end_record = 0; } // Handle 0 rows
            ?>
            <p class="text-sm text-gray-700">
                Show <?php echo $start_record; ?> to <?php echo $end_record; ?> from <?php echo $total_rows; ?> routes
            </p>
        </div>
        <nav id="pagination-controls" class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <?php 
            $prev_class = $page > 1 ? 'hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-default';
            $prev_link = $page > 1 ? "javascript:fetchReportData(" . ($page - 1) . ")" : '#';
            echo '<a href="' . $prev_link . '" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 ' . $prev_class . '">Previous</a>';

            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = ($i == $page) ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
                echo '<a href="javascript:fetchReportData(' . $i . ')" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ' . $active_class . '">' . $i . '</a>';
            }

            $next_class = $page < $total_pages ? 'hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-default';
            $next_link = $page < $total_pages ? "javascript:fetchReportData(" . ($page + 1) . ")" : '#';
            echo '<a href="' . $next_link . '" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 ' . $next_class . '">Next</a>';
            ?>
        </nav>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButton = document.getElementById('filter-button');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const tableBody = document.getElementById('sales-table-body');
    const tableFooter = document.getElementById('sales-table-footer');
    const recordInfo = document.getElementById('record-info');
    const paginationControls = document.getElementById('pagination-controls');
    const exportButton = document.getElementById('export-button');
    
    // Fungsi utama untuk mengambil data via AJAX
    window.fetchReportData = function(page = 1) {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        
        const params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate,
            page: page
        });
        
        const url = '../backend/admin/fetch_laporan_rute.php?' + params.toString();
        
        tableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Memuat data...</td></tr>';
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                // 1. Update Tabel Body dan Footer
                tableBody.innerHTML = data.table_body_html;
                tableFooter.innerHTML = data.table_footer_html;

                // 2. Update Informasi Rekaman
                const startRecord = (data.page - 1) * data.limit + 1;
                let endRecord = Math.min((data.page * data.limit), data.total_rows);
                if (data.total_rows === 0) {
                    endRecord = 0;
                }
                
                recordInfo.innerHTML = `
                    <p class="text-sm text-gray-700">
                        Show ${data.total_rows === 0 ? 0 : startRecord} to ${endRecord} from ${data.total_rows} route
                    </p>
                `;

                // 3. Update Kontrol Pagination
                renderPagination(data.total_pages, data.page);
            })
            .catch(error => {
                console.error('Error fetching report:', error);
                tableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center text-red-500">Failed to load data. Check the browser console.</td></tr>';
            });
    }

    // Fungsi untuk me-render ulang kontrol pagination
    function renderPagination(totalPages, currentPage) {
        let paginationHtml = '';

        // Previous Button
        const prevPage = currentPage > 1 ? currentPage - 1 : 1;
        const prevClass = currentPage > 1 ? 'hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-default';
        const prevLink = currentPage > 1 ? `javascript:fetchReportData(${prevPage})` : '#';
        paginationHtml += `<a href="${prevLink}" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 ${prevClass}">Previous</a>`;

        // Page Numbers
        for (let i = 1; i <= totalPages; i++) {
            const activeClass = (i == currentPage) ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50';
            paginationHtml += `<a href="javascript:fetchReportData(${i})" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ${activeClass}">${i}</a>`;
        }

        // Next Button
        const nextPage = currentPage < totalPages ? currentPage + 1 : totalPages;
        const nextClass = currentPage < totalPages ? 'hover:bg-gray-50' : 'bg-gray-100 text-gray-400 cursor-default';
        const nextLink = currentPage < totalPages ? `javascript:fetchReportData(${nextPage})` : '#';
        paginationHtml += `<a href="${nextLink}" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 ${nextClass}">Next</a>`;

        paginationControls.innerHTML = paginationHtml;
    }

    // Event Listener untuk tombol filter
    filterButton.addEventListener('click', () => fetchReportData(1)); 

    exportButton.addEventListener('click', () => {
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;
        const params = new URLSearchParams({
            start_date: startDate,
            end_date: endDate
        });
        
        const exportUrl = '../backend/admin/export_laporan_rute.php?' + params.toString();
        window.location.href = exportUrl;
    });
});
</script>

<?php 
require_once '../layouts/admin_footer.php'; 
?>