<?php
require_once "backend/db.php";
require_once 'backend/get_image.php';

$page_title = 'FLYNOW - Article Detail';
require_once 'layouts/header.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("
    SELECT title, content, image, created_at
    FROM articles
    WHERE id_article = ? AND status = 'published'
");
$stmt->bind_param("i", $id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();

if (!$article) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($article['title']) ?> - FLYNOW</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

<div class="container mx-auto px-6 py-12 max-w-4xl">

    <a href="index.php"
       class="text-blue-600 font-semibold hover:underline mb-6 inline-block">
        ← Back to Home
    </a>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">

        <?php if ($article['image']): ?>
            <img src="<?= $url . $article['image'] ?>"
                 class="w-full h-80 object-cover">
        <?php endif; ?>

        <div class="p-8">
            <h1 class="text-3xl font-bold mb-2">
                <?= htmlspecialchars($article['title']) ?>
            </h1>

            <p class="text-sm text-gray-500 mb-6">
                <?= date("l, d F Y", strtotime($article['created_at'])) ?>
            </p>

            <div class="prose max-w-none text-gray-800">
                <?= nl2br($article['content']) ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>
