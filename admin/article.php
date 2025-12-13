<?php
require_once '../layouts/admin_header.php'; 
require_once '../layouts/admin_sidebar.php';
?>

<main class="flex-1 p-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Article Management</h1>
        <a href="article_form.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            + Add New Article
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <table class="w-full min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created At</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                
                <?php 
                // Asumsi: $articles_result sudah berisi data dari database
                if (isset($articles_result) && $articles_result->num_rows > 0): 
                    while($article = $articles_result->fetch_assoc()): 
                ?>
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($article['title']) ?></td>
                        <td class="px-6 py-4">
                            <?php if ($article['image_url']): ?>
                                <img src="../uploads/<?= htmlspecialchars($article['image_url']) ?>" alt="Article Image" class="w-16 h-12 object-cover rounded">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <a href="article.php?action=toggle&id=<?= $article['id_article'] ?>"
                               class="px-3 py-1 text-xs font-semibold rounded-full 
                               <?= $article['is_enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $article['is_enabled'] ? 'Enabled (Show)' : 'Disabled (Hide)' ?>
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500"><?= date('d M Y', strtotime($article['created_at'])) ?></td>
                        <td class="px-6 py-4 text-center text-sm font-medium space-x-2">
                            <a href="article_form.php?id=<?= $article['id_article'] ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                            <a href="article.php?action=delete&id=<?= $article['id_article'] ?>"
                               onclick="return confirm('Are you sure you want to delete this article?')" 
                               class="text-red-600 hover:text-red-900">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No articles found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>