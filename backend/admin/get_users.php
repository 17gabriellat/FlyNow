<?php
require_once "../db.php"; 

// Ambil parameter
$page       = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$search     = isset($_GET['search']) ? $_GET['search'] : "";
$role       = isset($_GET['role']) ? $_GET['role'] : "";
$limit      = 50;
$offset     = ($page - 1) * $limit;

// Build query dasar
$query = "SELECT * FROM users WHERE 1";

// Tambah SEARCH
if (!empty($search)) {
    $safe = "%".$conn->real_escape_string($search)."%";
    $query .= " AND (name LIKE '$safe' OR email LIKE '$safe' OR phone LIKE '$safe')";
}

// Tambah FILTER ROLE
if (!empty($role)) {
    $role_id = (int)$role;
    $query .= " AND role_id = $role_id";
}

// Hitung total
$countQuery = str_replace("SELECT *", "SELECT COUNT(*) AS total", $query);
$total_rows = $conn->query($countQuery)->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Tambah ORDER + LIMIT
$query .= " ORDER BY id_user DESC LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Return JSON
header("Content-Type: application/json");
echo json_encode([
    "users" => $users,
    "page" => $page,
    "limit" => $limit,
    "total" => $total_rows,
    "total_pages" => $total_pages
]);
