<?php
session_start();

require_once "../db.php";
require_once "../s3_put_object.php";
require_once "../akses_admin.php";

if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user']['id_user'];

// ===============================
// INPUT
// ===============================
$article_id    = $_POST['id_article'] ?? null;
$is_edit       = $article_id !== null;

$title         = trim($_POST['title'] ?? '');
$content       = trim($_POST['content'] ?? '');
$current_image = $_POST['current_image'] ?? null;
$delete_image  = isset($_POST['delete_image']);

if ($title === '' || $content === '') {
    $_SESSION['error'] = "Title and content are required.";
    header("Location: ../../admin/article_form.php" . ($is_edit ? "?id=" . $article_id : ""));
    exit;
}

// ===============================
// IMAGE HANDLING
// ===============================
$imageKey = $current_image;

// DELETE IMAGE FLAG
if ($delete_image) {
    $imageKey = null;
}

// UPLOAD NEW IMAGE
if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {

    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Image upload failed.";
        header("Location: ../../admin/article_form.php" . ($is_edit ? "?id=" . $article_id : ""));
        exit;
    }

    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        $_SESSION['error'] = "Image size must be under 5MB.";
        header("Location: ../../admin/article_form.php" . ($is_edit ? "?id=" . $article_id : ""));
        exit;
    }

    $tmpPath = $_FILES['image']['tmp_name'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);

    $allowed = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowed[$mime])) {
        $_SESSION['error'] = "Only JPG, PNG, or WEBP images are allowed.";
        header("Location: ../../admin/article_form.php" . ($is_edit ? "?id=" . $article_id : ""));
        exit;
    }

    $ext = $allowed[$mime];

    $random = bin2hex(random_bytes(8));
    $date   = date("Y/m/d");

    $imageKey = "articles/{$date}/user_{$user_id}/{$random}.{$ext}";

    // ✅ SESUAIKAN PEMANGGILAN FUNGSI
    $upload = s3_put_object(
        $imageKey,
        $tmpPath,
        $mime
    );

    if (!$upload['ok']) {
        file_put_contents(
            __DIR__ . "/../../s3_upload_error.log",
            json_encode($upload, JSON_PRETTY_PRINT)
        );

        $_SESSION['error'] = "Failed to upload image to S3.";
        header("Location: ../../admin/article_form.php" . ($is_edit ? "?id=" . $article_id : ""));
        exit;
    }
}

// ===============================
// DB SAVE
// ===============================
if ($is_edit) {

    $stmt = $conn->prepare("
        UPDATE articles
        SET title = ?, content = ?, image = ?, updated_at = NOW()
        WHERE id_article = ?
    ");

    $stmt->bind_param(
        "sssi",
        $title,
        $content,
        $imageKey,
        $article_id
    );

} else {

    $stmt = $conn->prepare("
        INSERT INTO articles (id_user, title, content, image, status)
        VALUES (?, ?, ?, ?, 'published')
    ");

    $stmt->bind_param(
        "isss",
        $user_id,
        $title,
        $content,
        $imageKey
    );
}

if (!$stmt->execute()) {
    $_SESSION['error'] = "Database error: " . $stmt->error;
    header("Location: ../../admin/article_form.php" . ($is_edit ? "?id=" . $article_id : ""));
    exit;
}

$stmt->close();

$_SESSION['success'] = $is_edit
    ? "Article updated successfully."
    : "Article created successfully.";

header("Location: ../../admin/article.php");
exit;
