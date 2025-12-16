<?php
require_once "../db.php";
require_once "../akses_admin.php";

$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// =======================
// COUNT TOTAL
// =======================
$total = $conn->query("SELECT COUNT(*) AS total FROM articles")
              ->fetch_assoc()['total'];

$total_pages = ceil($total / $limit);

// =======================
// FETCH DATA
// =======================
$stmt = $conn->prepare("
    SELECT id_article, title, image, status, created_at
    FROM articles
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$articles = [];
while ($row = $result->fetch_assoc()) {
    $articles[] = $row;
}

header("Content-Type: application/json");
echo json_encode([
    "articles"     => $articles,
    "page"         => $page,
    "limit"        => $limit,
    "total"        => $total,
    "total_pages"  => $total_pages
]);
exit;
