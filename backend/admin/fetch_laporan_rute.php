<?php
// File: backend/fetch_laporan_rute.php
// Endpoint ini harus diletakkan di folder backend/

require_once "../db.php"; 
// require_once "akses_admin.php"; // Tambahkan jika perlu validasi admin di endpoint ini

// Ambil data filter dari AJAX GET
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$page = (int)($_GET['page'] ?? 1); 

// Konfigurasi Pagination
$limit = 10;
$offset = ($page - 1) * $limit;
$end_date_sql = $end_date ? $end_date . ' 23:59:59' : null;

// ------------------------------------------------
// --- FUNGSI AGREGASI (DIPERBAIKI UNTUK MENGATASI WARNING BIND_PARAM) ---
// ------------------------------------------------

// Fungsi pembantu untuk mengikat parameter sebagai referensi (mengatasi Warning)
function bind_parameters_safely($stmt, $types, $params) {
    if (!$types) return;
    
    $bind_args = array_merge([$types], $params); 
    
    $references = [];
    $references[] = $bind_args[0]; 
    
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

// ------------------------------------------------
// --- EKSEKUSI DAN RENDER DATA ---
// ------------------------------------------------
$total_rows = getTotalRoutes($conn, $start_date, $end_date_sql);
$total_pages = ceil($total_rows / $limit);
$route_sales = getOrdersByRoute($conn, $limit, $offset, $start_date, $end_date_sql);
$total_pendapatan_semua = getTotalOrdersRevenue($conn, $start_date, $end_date_sql);

// --- RENDER TABLE BODY (HTML) ---
ob_start();
?>
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
                    href="detail_penjualan_rute.php?route=<?php echo urlencode($route_id); ?>&start_date=<?php echo urlencode($start_date ?? ''); ?>&end_date=<?php echo urlencode($end_date ?? ''); ?>" 
                    class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold">
                    Lihat Pembeli
                </a>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada data penjualan per rute yang sesuai dengan filter.</td>
    </tr>
<?php endif; ?>
<?php
$table_body_html = ob_get_clean();

// --- RENDER TABLE FOOTER (HTML) ---
ob_start();
?>
<tr>
    <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-900">TOTAL PENDAPATAN DARI DATA YANG DITAMPILKAN</td>
    <td class="px-6 py-4 text-sm font-bold text-blue-600">Rp <?php echo number_format($total_pendapatan_semua, 0, ',', '.'); ?></td>
    <td></td>
</tr>
<?php
$table_footer_html = ob_get_clean();

// ------------------------------------------------
// --- KIRIM RESPON DALAM FORMAT JSON ---
// ------------------------------------------------
header('Content-Type: application/json');
echo json_encode([
    'table_body_html' => $table_body_html,
    'table_footer_html' => $table_footer_html,
    'total_rows' => $total_rows,
    'total_pages' => $total_pages,
    'page' => $page,
    'limit' => $limit,
]);
?>