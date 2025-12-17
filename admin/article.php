<?php
session_start();
require_once '../layouts/admin_header.php';
require_once '../layouts/admin_sidebar.php';
require_once "../backend/admin/article_image.php";
?>

<main class="flex-1 p-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Article Management</h1>
        <a href="article_form.php" class="bg-gradient-to-t from-blue-800 to-blue-400 text-white px-4 py-2 rounded-md hover:bg-gradient-to-r from-blue-800 to-blue-400">
            + Add New Article
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
            <tbody id="articleTable" class="bg-white divide-y divide-gray-200">
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        Loading articles...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="flex justify-end mt-4" id="paginationContainer"></div>

    <!-- DELETE MODAL -->
    <div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-md p-6 shadow-lg">
            <h2 class="text-lg font-semibold mb-3 text-gray-800">Delete Article</h2>
            <p class="text-sm text-gray-600 mb-6">
                Are you sure you want to delete this article? This action cannot be undone.
            </p>

            <div class="flex justify-end gap-3">
                <button
                    onclick="closeDeleteModal()"
                    class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                    Cancel
                </button>

                <button
                    id="confirmDeleteBtn"
                    class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">
                    Delete
                </button>
            </div>
        </div>
    </div>

</main>

<script>
    const BASE_IMAGE_URL = <?= json_encode($url); ?>;
    let currentPage = 1;

    function loadArticles(page = 1) {
        currentPage = page;

        $("#articleTable").html(`
        <tr>
            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                Loading articles...
            </td>
        </tr>
    `);

        $.get("../backend/admin/article_fetch.php", {
            page
        }, function(res) {

            $("#articleTable").html("");

            if (res.articles.length === 0) {
                $("#articleTable").html(`
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        No articles found.
                    </td>
                </tr>
            `);
                return;
            }

            res.articles.forEach(a => {

                let checked = a.status === "published" ? "checked" : "";

                $("#articleTable").append(`
                <tr>
                    <td class="px-6 py-4 text-sm font-medium">${a.title}</td>

                    <td class="px-6 py-4">
                        ${a.image ? `
                            <img src="${a.image.startsWith('http') 
                                ? a.image 
                                : BASE_IMAGE_URL + a.image}"
                                class="w-20 h-14 object-cover rounded border">
                        ` : `
                            <span class="text-gray-400 text-sm">No Image</span>
                        `}
                    </td>

                    <td class="px-6 py-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                class="sr-only"
                                ${a.status === "published" ? "checked" : ""}
                                onchange="toggleStatus(${a.id_article}, this)">
                            <div class="w-11 h-6 bg-gray-200 rounded-full transition
                                ${a.status === "published" ? "bg-green-500" : ""}">
                                <div class="absolute top-0.5 left-0.5 bg-white w-5 h-5 rounded-full transition
                                    ${a.status === "published" ? "translate-x-5" : ""}">
                                </div>
                            </div>
                        </label>
                    </td>


                    <td class="px-6 py-4 text-sm text-gray-500">
                        ${new Date(a.created_at).toLocaleDateString()}
                    </td>

                    <td class="px-6 py-4 text-center text-sm">
                    <button
                        type="button"
                        onclick="window.location.href='article_form.php?id=${a.id_article}'"
                        class="px-3 py-1 text-sm rounded bg-gradient-to-t from-indigo-800 to-indigo-400 text-white hover:bg-gradient-to-r from-indigo-800 to-indigo-400">
                        Edit
                    </button>

                    <button
                        type="button"
                        onclick="openDeleteModal(${a.id_article})"
                        class="px-3 py-1 text-sm rounded bg-gradient-to-t from-red-800 to-red-400 text-white hover:bg-gradient-to-r from-red-500 to-red-400">
                        Delete
                    </button>

                </td>

                </tr>
            `);
            });

            renderPagination(res.page, res.total_pages, res.total, res.limit);
        });
    }

    function toggleStatus(id, el) {
        // console.log("click");
        let status = el.checked ? "published" : "archived";

        $.ajax({
            url: "../backend/admin/article_toggle_status.php",
            type: "POST",
            data: {
                id,
                status
            },
            success: function() {
                // optional: toast / log
                // console.log("Article status updated");
                loadArticles(currentPage);
            },
            error: function() {
                alert("Failed to update article status");
                el.checked = !el.checked; // rollback UI
            }
        });
    }


    function renderPagination(current, totalPages, total, limit) {

        let start = (current - 1) * limit + 1;
        let end = Math.min(current * limit, total);

        let html = `
    <div class="flex justify-between items-center w-full">
        <div class="text-sm text-gray-600">
            Showing ${start} - ${end} of ${total}
        </div>
        <div class="flex gap-2">
            <button ${current == 1 ? "disabled" : ""}
                onclick="loadArticles(${current - 1})"
                class="px-3 py-1 text-xs bg-gray-200 rounded disabled:opacity-40">
                Prev
            </button>
    `;

        for (let i = 1; i <= totalPages; i++) {
            html += `
            <button onclick="loadArticles(${i})"
                class="px-3 py-1 text-xs rounded
                ${i == current ? 'bg-blue-600 text-white' : 'bg-gray-200'}">
                ${i}
            </button>
        `;
        }

        html += `
            <button ${current == totalPages ? "disabled" : ""}
                onclick="loadArticles(${current + 1})"
                class="px-3 py-1 text-xs bg-gray-200 rounded disabled:opacity-40">
                Next
            </button>
        </div>
    </div>
    `;

        $("#paginationContainer").html(html);
    }

    $(document).ready(function() {
        loadArticles();

        setTimeout(() => {
            $("#alert-error, #alert-success").fadeOut();
        }, 3000);
    });

    let deleteId = null;

    function openDeleteModal(id) {
        deleteId = id;
        $("#deleteModal").removeClass("hidden").addClass("flex");

        $("#confirmDeleteBtn").off("click").on("click", function() {
            confirmDelete();
        });
    }

    function closeDeleteModal() {
        deleteId = null;
        $("#deleteModal").addClass("hidden").removeClass("flex");
    }

    function confirmDelete() {
        if (!deleteId) return;

        $("#confirmDeleteBtn").prop("disabled", true).text("Deleting...");

        $.ajax({
            url: "../backend/admin/article_delete.php",
            type: "POST",
            dataType: "json",
            data: {
                id: deleteId
            },
            success: function(res) {
                closeDeleteModal();
                $("#confirmDeleteBtn").prop("disabled", false).text("Delete");

                if (res.success) {
                    loadArticles(currentPage);
                } else {
                    alert(res.message || "Failed to delete article");
                }
            },
            error: function() {
                closeDeleteModal();
                $("#confirmDeleteBtn").prop("disabled", false).text("Delete");
                alert("Server error");
            }
        });
    }
</script>