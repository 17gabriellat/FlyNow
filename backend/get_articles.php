<?php
require_once 'get_image.php';
require_once "db.php";

$query = "
    SELECT id_article, title, content, image, created_at
    FROM articles
    WHERE status = 'published'
    ORDER BY created_at DESC
    LIMIT 6
";

$result = $conn->query($query);

$articles = [];

while ($row = $result->fetch_assoc()) {

    $excerpt = strip_tags($row['content']);
    if (strlen($excerpt) > 120) {
        $excerpt = substr($excerpt, 0, 120) . "...";
    }

    $articles[] = [
        "id" => $row['id_article'],
        "title" => $row['title'],
        "excerpt" => $excerpt,
        "image_url" => "{$url}{$row['image']}",
        "created_at" => date("d M Y", strtotime($row['created_at']))
    ];
}

header("Content-Type: application/json");
echo json_encode($articles);
exit;
