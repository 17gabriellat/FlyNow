<?php
require_once "../backend/db.php"; 
require_once "../backend/akses_admin.php";
require_once '../layouts/admin_header.php'; 
require_once '../layouts/admin_sidebar.php';
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
<main class="flex-1 p-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold"><?= $admin_page_title; ?></h1>
        <a href="article.php" class="text-blue-600 hover:text-blue-800 text-sm font-semibold">
            &larr; Back to Articles
        </a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <form method="POST" enctype="multipart/form-data">
            
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($article['image_url'] ?? '') ?>">

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

            <?php 
            // Tampilkan gambar saat ini hanya jika sedang mode edit dan gambar ada
            if (isset($article) && $article['image_url']): 
            ?>
            <div class="mb-4 p-4 border rounded-md bg-gray-50 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium mb-2">Current Image:</p>
                    <img src="../uploads/<?= htmlspecialchars($article['image_url']) ?>" alt="Current Image" class="w-24 h-20 object-cover rounded">
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="delete_image" id="delete_image" value="1" class="h-4 w-4 text-red-600 border-gray-300 rounded">
                    <label for="delete_image" class="ml-2 block text-sm text-red-600 font-medium">Delete Current Image</label>
                </div>
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