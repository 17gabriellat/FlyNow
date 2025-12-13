<?php
require_once "../db.php";
date_default_timezone_set("Asia/Jakarta");

// ================================
// PARAMETER
// ================================
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$date  = $_GET['date'] ?? "";
$limit = 10;
$offset = ($page - 1) * $limit;

// ================================
// BASE QUERY
// ================================
$baseQuery = "
FROM flights f
JOIN airlines a ON f.airline_id = a.id_airline
JOIN airports o ON f.origin_airport = o.id_airport
JOIN airports d ON f.destination_airport = d.id_airport
WHERE 1
";

// FILTER DATE
$params = [];
$types  = "";

if (!empty($date)) {
    $baseQuery .= " AND f.departure_date = ?";
    $params[] = $date;
    $types .= "s";
}

// ================================
// HITUNG TOTAL
// ================================
$stmtCount = $conn->prepare("SELECT COUNT(*) AS total " . $baseQuery);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$total = $stmtCount->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// ================================
// AMBIL DATA
// ================================
$query = "
SELECT f.*, a.airline_name,
       o.city AS origin_city, o.airport_code AS origin_code,
       d.city AS dest_city, d.airport_code AS dest_code,
       (f.seat_quota - f.booked_seats) AS remaining_seats
" . $baseQuery . "
ORDER BY f.departure_date, f.departure_time
LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);

// bind parameter dinamis
if (!empty($params)) {
    $types2 = $types . "ii";
    $params[] = $limit;
    $params[] = $offset;
    $stmt->bind_param($types2, ...$params);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

$flights = [];
$now = new DateTime();

while ($row = $result->fetch_assoc()) {

    $dep = new DateTime($row['departure_date'] . " " . $row['departure_time']);
    $arr = new DateTime($row['arrival_date']   . " " . $row['arrival_time']);
    
    if ($arr < $now) {
        $row['status'] = "Arrived";
        $row['can_edit'] = false;
        $row['can_delete'] = false;
    } elseif ($dep <= $now && $arr >= $now) {
        $row['status'] = "Ongoing";
        $row['can_edit'] = false;
        $row['can_delete'] = false;
    } else {
        $row['status'] = "Upcoming";
        $row['can_edit'] = true;
        $row['can_delete'] = ($row['booked_seats'] == 0);
    }

    if ($row['remaining_seats'] <= 0) {
        $row['status_sold_out'] = "Sold Out";
        $row['can_edit'] = false;
        $row['can_delete'] = false;
    }

    $flights[] = $row;
}

// ================================
// RESPONSE JSON (PENTING)
// ================================
header("Content-Type: application/json");
echo json_encode([
    "flights"      => $flights,
    "page"         => $page,
    "limit"        => $limit,
    "total"        => $total,
    "total_pages"  => $total_pages
]);
exit;
