<?php
function getTotalSalesToday($conn) {
    $today = date('Y-m-d');
    $sql = "SELECT SUM(total_price) AS total FROM transactions WHERE DATE(created_at) = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result['total'] ?? 0;
}
function getTotalTicketsSoldToday($conn) {
    $today = date('Y-m-d');
    $sql = "SELECT SUM(total_passengers) AS total_tiket FROM transactions WHERE DATE(created_at) = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return $result['total_tiket'] ?? 0;
}

function countTotalUsers($conn) {
    $sql = "SELECT COUNT(*) AS total_users FROM users"; 
    $result = $conn->query($sql)->fetch_assoc();

    return $result['total_users'] ?? 0;
}

function getSalesLast12Months($conn) {
    $date_limit = date('Y-m-01', strtotime('-11 months')); 
    $sql = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') AS tahun_bulan,
            SUM(total_price) AS total_penjualan
        FROM 
            transactions  /* MENGGUNAKAN NAMA TABEL 'transactions' */
        WHERE 
            DATE(created_at) >= ?
        GROUP BY 
            tahun_bulan
        ORDER BY
            tahun_bulan ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date_limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data_penjualan_db = [];
    while ($row = $result->fetch_assoc()) {
        $data_penjualan_db[$row['tahun_bulan']] = (int)$row['total_penjualan'];
    }
    $data_grafik = [];
    $tanggal_sekarang = time();
    for ($i = 11; $i >= 0; $i--) {
        $bulan_tahun_key = date('Y-m', strtotime("-$i months", $tanggal_sekarang));
        $label = date('M Y', strtotime($bulan_tahun_key . '-01')); 
        $penjualan = $data_penjualan_db[$bulan_tahun_key] ?? 0;

        $data_grafik[] = [
            'label' => $label,
            'penjualan' => $penjualan
        ];
    }

    return $data_grafik;
}

?>