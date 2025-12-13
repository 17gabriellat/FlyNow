<?php
// File: backend/admin/export_laporan_rute.php
// Endpoint ini menghasilkan file CSV dari data laporan penjualan per rute yang difilter.

require_once "../db.php"; 
// require_once "../akses_admin.php"; // Aktifkan jika diperlukan validasi sesi/akses

// --- FUNGSI PEMBANTU UNTUK MENGHINDARI WARNING BIND_PARAM() ---
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

// --- FUNGSI MENGAMBIL DATA AGREGASI PER RUTE (TANPA LIMIT/OFFSET) ---
function getExportOrdersByRoute($conn, $start_date = null, $end_date_sql = null) {
    $where_date = '';
    $params = [];
    $types = '';

    // Siapkan klausa WHERE untuk filter tanggal
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
    ";

    $stmt = $conn->prepare($sql);
    
    if ($types) {
        bind_parameters_safely($stmt, $types, $params); 
    }
    $stmt->execute();
    return $stmt->get_result();
}

// --- EKSEKUSI EKSPOR ---

// 1. Ambil filter tanggal dari URL (GET)
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$end_date_sql = $end_date ? $end_date . ' 23:59:59' : null;

// 2. Ambil semua data sesuai filter (tanpa pagination)
$data_penjualan = getExportOrdersByRoute($conn, $start_date, $end_date_sql);

// 3. Konfigurasi Nama File
$filename = 'Laporan_Penjualan_Rute';
if ($start_date) { $filename .= '_' . $start_date; }
if ($end_date) { $filename .= '_to_' . $end_date; }
$filename .= '.csv';

// 4. Set Headers untuk Download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// 5. Output CSV
$output = fopen('php://output', 'w'); 
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
// Tulis Header CSV (gunakan ; sebagai delimiter untuk Excel)
$csv_headers = ['Rute Penerbangan', 'Tanggal Penerbangan Terakhir', 'Tiket Terjual', 'Total Pendapatan (Rp)'];
fputcsv($output, $csv_headers, ';');

// Tulis Data Baris
if ($data_penjualan->num_rows > 0) {
    while ($row = $data_penjualan->fetch_assoc()) {
        $rute = $row['origin_airport_code'] . ' -> ' . $row['destination_airport_code'];
        
        // Format data sesuai kebutuhan Excel
        fputcsv($output, [
            $rute,
            date('Y-m-d', strtotime($row['latest_departure_date'])),
            $row['total_tiket_terjual'],
            // Hapus format mata uang agar Excel bisa mengidentifikasi sebagai angka
            (float)$row['total_pendapatan'] 
        ], ';');
    }
}

// Tutup stream dan hentikan eksekusi
fclose($output);
exit();
?>