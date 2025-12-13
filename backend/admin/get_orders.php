<?php

/**
 * Mengambil data transaksi pesanan dengan informasi penerbangan dan pelanggan, 
 * mendukung fitur pencarian dan pagination.
 * * @param mysqli $conn Koneksi database MySQLi.
 * @param string $search_query String pencarian untuk kode booking, nama, atau email.
 * @param int $page Nomor halaman yang diminta.
 * @param int $limit Jumlah baris per halaman.
 * @return array|null Array yang berisi data pesanan dan metadata pagination, atau NULL jika terjadi kegagalan fatal.
 */
function getOrders($conn, $search_query = '', $page = 1, $limit = 10) {
    
    // Safety check untuk koneksi
    if (!$conn) {
        // Jika koneksi gagal, kembalikan NULL
        return NULL; 
    }

    $offset = ($page - 1) * $limit;
    
    // QUERY BASE: Menentukan semua JOIN yang dibutuhkan.
    $query_base = "
        FROM transactions t
        JOIN users u ON u.id_user = t.user_id
        JOIN flights f ON f.id_flight = t.departure_flight_id
        /* Wajib JOIN ke tabel airports dua kali untuk mendapatkan kode bandara */
        JOIN airports oa ON oa.id_airport = f.origin_airport      /* Airport Asal (oa) */
        JOIN airports da ON da.id_airport = f.destination_airport /* Airport Tujuan (da) */
    ";

    // SELECT DATA: Kolom yang akan diambil.
    $select_data = "
        SELECT 
            t.id_transaction, t.booking_code, t.total_price, t.payment_status, t.created_at,
            t.total_passengers,
            u.name AS customer_name, u.email AS customer_email, 
            oa.airport_code AS origin_code, /* Menggunakan alias oa untuk kode bandara asal */
            da.airport_code AS dest_code    /* Menggunakan alias da untuk kode bandara tujuan */
    ";
    
    $where_clauses = [];
    $params = [];
    $types = '';

    // Logika Search
    if (!empty($search_query)) {
        $search = "%" . $search_query . "%";
        // Cari berdasarkan kode booking, nama, atau email
        $where_clauses[] = "(t.booking_code LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'sss';
    }

    $where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

    // 1. Ambil Total Baris (Untuk Pagination)
    $sql_count = "SELECT COUNT(t.id_transaction) AS total_rows " . $query_base . $where_sql;
    
    $stmt_count = $conn->prepare($sql_count);
    if ($stmt_count === FALSE) { 
        error_log("MySQLi Prepare Error (Count): " . $conn->error);
        return NULL; 
    }
    
    if (!empty($params)) {
        // Menggunakan bind_param dengan operator spread
        $stmt_count->bind_param($types, ...$params); 
    }
    $stmt_count->execute();
    $total_rows = $stmt_count->get_result()->fetch_assoc()['total_rows'] ?? 0;
    $stmt_count->close();

    // 2. Ambil Data Aktual
    $sql_data = $select_data . $query_base . $where_sql;
    $sql_data .= " ORDER BY t.created_at DESC "; 
    $sql_data .= " LIMIT ? OFFSET ?";

    // Tipe data untuk LIMIT dan OFFSET adalah integer ('ii')
    $types_data = $types . 'ii';
    // Gabungkan parameter search dengan parameter pagination
    $params_data = array_merge($params, [$limit, $offset]);

    $stmt_data = $conn->prepare($sql_data);
    if ($stmt_data === FALSE) { 
        error_log("MySQLi Prepare Error (Data): " . $conn->error);
        return NULL; 
    }
    
    // Binding semua parameter
    if (!empty($types_data)) {
        $stmt_data->bind_param($types_data, ...$params_data); 
    }
    
    $stmt_data->execute();
    $result = $stmt_data->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    $stmt_data->close();
    
    // Kembalikan hasil dalam bentuk array terstruktur
    return [
        'orders' => $orders,
        'total_rows' => $total_rows,
        'total_pages' => ceil($total_rows / $limit),
        'current_page' => $page,
        'limit' => $limit
    ];
}
?>