<?php
require_once "../db.php";
require_once "../akses_admin.php";

$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$id || !in_array($status, ['published','archived'])) {
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare("
    UPDATE articles SET status = ? WHERE id_article = ?
");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

echo "OK";
