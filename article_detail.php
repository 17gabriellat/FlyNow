<?php
require_once 'layouts/header.php';
?>
<div class="container mx-auto px-6 py-16 max-w-4xl">

    <a href="index.php" class="text-blue-600 hover:text-blue-800 text-sm font-semibold mb-6 inline-block">
        &larr; Back to Home
    </a>
    
    <div class="bg-white p-8 rounded-lg shadow-xl">
        
        <?php 
        // Asumsi: $article sudah berisi data artikel yang aktif dari database
        if (isset($article)):
        ?>

        <h1 class="text-4xl font-bold mb-4 text-gray-900"><?= htmlspecialchars($article['title']) ?></h1>
        
        <p class="text-sm text-gray-500 mb-6">Published on: <?= date('d M Y', strtotime($article['created_at'])) ?></p>

        <?php 
            $image_path = 'uploads/' . htmlspecialchars($article['image_url']);
            $image_source = ($article['image_url'] && file_exists($image_path)) ? $image_path : null;
        ?>

        <?php if ($image_source): ?>
            <img src="<?= $image_source ?>" 
                 alt="<?= htmlspecialchars($article['title']) ?>" 
                 class="w-full max-h-96 object-cover rounded-lg mb-8 shadow-md">
        <?php endif; ?>

        <div class="article-content text-gray-700 leading-relaxed space-y-4">
            <p><?= nl2br(htmlspecialchars($article['content'])) ?></p>
        </div>
        
        <?php else: ?>
            <p class="text-center text-xl text-red-500">Article not found or disabled.</p>
        <?php endif; ?>

    </div>

</div>