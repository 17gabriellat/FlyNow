<?php
require_once "db.php";

$q = $conn->query("SELECT city, airport_code FROM airports ORDER BY city ASC");

$data = [];
while ($row = $q->fetch_assoc()) {
    $data[] = [
        "name" => $row["city"],
        "code" => $row["airport_code"]
    ];
}

echo json_encode($data);
exit;
