<?php
// backend/admin/fetch_laporan_flight.php

require_once "../../backend/db.php";

$limit = 10;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$start_date = $_GET['start_date'] ?? null;
$end_date   = $_GET['end_date'] ?? null;

$params = [];
$types  = '';

$where_date = '';
if ($start_date) {
    $where_date .= " AND f.departure_date >= ? ";
    $params[] = $start_date;
    $types .= 's';
}
if ($end_date) {
    $where_date .= " AND f.departure_date <= ? ";
    $params[] = $end_date;
    $types .= 's';
}

$sql = "
    SELECT 
        f.flight_code,
        oa.airport_code AS origin_airport_code,
        da.airport_code AS destination_airport_code,
        MAX(f.departure_date) AS latest_departure_date,
        SUM(t.total_passengers) AS total_tiket_terjual,
        SUM(t.total_price) AS total_pendapatan
    FROM transactions t
    JOIN flights f ON t.departure_flight_id = f.id_flight
    JOIN airports oa ON oa.id_airport = f.origin_airport
    JOIN airports da ON da.id_airport = f.destination_airport
    WHERE t.payment_status = 'Paid'
    $where_date
    GROUP BY f.flight_code
    ORDER BY total_pendapatan DESC
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types   .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "
        <tr>
            <td class='px-6 py-4 font-semibold'>{$row['flight_code']}</td>
            <td class='px-6 py-4'>
                {$row['origin_airport_code']} → {$row['destination_airport_code']}
            </td>
            <td class='px-6 py-4'>
                " . date('d M Y', strtotime($row['latest_departure_date'])) . "
            </td>
            <td class='px-6 py-4'>
                " . number_format($row['total_tiket_terjual']) . "
            </td>
            <td class='px-6 py-4'>
                Rp " . number_format($row['total_pendapatan'], 0, ',', '.') . "
            </td>
        </tr>";
    }
} else {
    echo "
    <tr>
        <td colspan='5' class='px-6 py-4 text-center text-gray-500'>
            No sales data per flight.
        </td>
    </tr>";
}
