<?php
require_once "../backend/db.php";
require_once "../backend/akses_admin.php";
require_once "../backend/admin/article_image.php";
require_once '../layouts/admin_header.php';
require_once '../layouts/admin_sidebar.php';
// die($url);
$admin_page_title = 'Add New Article';
$article = null;
$article_id = $_GET['id'] ?? null;
$is_edit = $article_id !== null;
if ($is_edit) {
    $stmt = $conn->prepare("SELECT * FROM articles WHERE id_article = ?");
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $article = $result->fetch_assoc();
    $stmt->close();

    if (!$article) {
        header('Location: article.php');
        exit;
    }
    // Nilai ini akan menimpa nilai default jika dalam mode edit
    $admin_page_title = 'Edit Article: ' . htmlspecialchars($article['title']);
}
?>

<!-- <input type="hidden" name="current_image" value="<?= htmlspecialchars($article['image'] ?? '') ?>"> -->

<main class="flex-1 p-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold"><?= $admin_page_title; ?></h1>
        <a href="article.php" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
            &larr; Back to Articles
        </a>
    </div>

    <!-- ALERT ERROR -->
    <?php if (isset($_SESSION['error'])): ?>
        <div id="alert-error"
            class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['error']; ?></span>
                <button onclick="$('#alert-error').fadeOut();" class="text-red-600 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['error']);
    endif; ?>

    <!-- ALERT SUCCESS -->
    <?php if (isset($_SESSION['success'])): ?>
        <div id="alert-success"
            class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['success']; ?></span>
                <button onclick="$('#alert-success').fadeOut();" class="text-green-700 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['success']);
    endif; ?>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <form method="POST" action="../backend/admin/article_add_process.php" enctype="multipart/form-data">

            <input type="hidden" name="current_image" value="<?= htmlspecialchars($article['image'] ?? '') ?>">
            <?php if ($is_edit): ?>
                <input type="hidden" name="id_article" value="<?= $article_id ?>">
            <?php endif; ?>

            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                <input type="text" name="title" id="title" required
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md"
                    value="<?= htmlspecialchars($article['title'] ?? '') ?>">
            </div>

            <div class="mb-4">
                <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                <textarea name="content" id="content" rows="10" required
                    class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
                    <?= htmlspecialchars($article['content'] ?? '') ?>
                </textarea>
            </div>

            <div class="mb-4">
                <label for="image" class="block text-sm font-medium text-gray-700">Upload Image (Max 5MB)</label>
                <input type="file" name="image" id="image" accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <?php if (!empty($article['image'])): ?>
                <div class="mb-4">
                    <p class="text-sm font-medium mb-2">Current Image:</p>
                    <?php $img_url = $url . $article['image']; ?>
                    <img
                        src="<?php echo htmlspecialchars($img_url); ?>"
                        class="w-48 h-32 object-cover rounded border">
                </div>
            <?php endif; ?>


            <div class="mt-6">
                <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-md hover:bg-green-700">
                    <?= $is_edit ? 'Update Article' : 'Save Article' ?>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    setTimeout(() => {
        $("#alert-error, #alert-success").fadeOut();
    }, 3000);
</script>