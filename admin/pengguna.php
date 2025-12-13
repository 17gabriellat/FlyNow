<?php
require_once "../backend/akses_admin.php";

$admin_page_title = "User Management";
require_once "../layouts/admin_header.php";
require_once "../layouts/admin_sidebar.php";
?>

<main class="flex-1 p-10">

    <!-- ALERT ERROR -->
    <?php if (isset($_SESSION['error'])): ?>
        <div id="alert-error"
            class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['error']; ?></span>
                <button onclick="$('#alert-error').fadeOut();"
                    class="text-red-600 font-bold text-xl">&times;</button>
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
                <button onclick="$('#alert-success').fadeOut();"
                    class="text-green-700 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['success']);
    endif; ?>



    <h1 class="text-3xl font-bold mb-8">User Management</h1>

    <!-- SEARCH & FILTER -->
    <div class="flex justify-between items-center mb-6">

        <!-- LEFT: Search + Filter -->
        <div class="flex items-center gap-3">

            <!-- Search -->
            <input id="searchInput"
                type="text"
                placeholder="Search name, email or phone..."
                class="p-2 border rounded-md w-64 text-sm">

            <!-- Filter Role -->
            <select id="filterRole" class="p-2 border rounded-md text-sm" onchange="applyFilters()">
                <option value="">All Roles</option>
                <option value="1">Admin</option>
                <option value="2">User</option>
            </select>

            <button onclick="applyFilters()"
                class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">
                Search
            </button>

            <button onclick="resetFilters()"
                class="bg-gray-600 text-white px-4 py-2 rounded-md text-sm hover:bg-gray-700">
                Reset
            </button>
        </div>

        <!-- RIGHT: Add Button -->
        <button id="showAddFormBtn"
            class="bg-green-600 text-white px-5 py-2 rounded-md shadow hover:bg-green-700 text-sm">
            + Add New User
        </button>
    </div>

    <!-- ⭐ ADD USER FORM (HIDDEN) -->
    <div id="addUserForm" class="bg-white rounded-lg shadow-md p-6 mb-8 hidden">

        <h2 class="text-xl font-bold mb-4">Add New User</h2>

        <form id="addUser" method="POST" action="../backend/admin/add_user.php">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>
                    <label class="block text-sm font-medium">Full Name</label>
                    <input type="text" name="name" required
                        class="w-full p-2 border rounded-md">
                </div>

                <div>
                    <label class="block text-sm font-medium">Email</label>
                    <input type="email" name="email" required
                        class="w-full p-2 border rounded-md">
                </div>

                <div>
                    <label class="block text-sm font-medium">Phone</label>
                    <input type="text" name="phone"
                        class="w-full p-2 border rounded-md">
                </div>

                <div>
                    <label class="block text-sm font-medium">Role</label>
                    <select name="role_id" class="w-full p-2 border rounded-md">
                        <option value="1">Admin</option>
                        <option value="2">User</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium">Password</label>
                    <input type="password" name="password" required
                        class="w-full p-2 border rounded-md">
                </div>

            </div>

            <div class="flex justify-end mt-6 gap-3">
                <button type="button" id="cancelAddBtn"
                    class="px-5 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Cancel</button>
                <button type="submit"
                    class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save User</button>
            </div>

        </form>

    </div>


    <!-- TABLE WRAPPER -->
    <div class="bg-white rounded-lg shadow-md p-6">

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-700">
                <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="p-4 text-left">NO</th>
                        <th class="p-4 text-left">FULL NAME</th>
                        <th class="p-4 text-left">EMAIL</th>
                        <th class="p-4 text-left">PHONE</th>
                        <th class="p-4 text-left">ROLE</th>
                        <th class="p-4 text-left">ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="userTable">
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500">Loading users...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination (Right aligned) -->
        <div class="flex justify-end mt-4" id="paginationContainer"></div>
    </div>

</main>

<!-- jQuery + SweetAlert -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let currentSearch = "";
    let currentRole = "";
    let currentPage = 1;

    $(document).ready(function() {
        loadUsers(1);

        $("#searchInput").keypress(function(e) {
            if (e.which == 13) applyFilters();
        });

        $("#showAddFormBtn").click(function() {
            $("#addUserForm").removeClass("hidden");
        });

        // CANCEL FORM
        $("#cancelAddBtn").click(function() {
            $("#addUserForm").addClass("hidden");
            $("#addUser")[0].reset();
        });


        setTimeout(() => {
            $("#alert-error").fadeOut();
            $("#alert-success").fadeOut();
        }, 3000);
    });

    // =====================================
    // APPLY FILTERS
    // =====================================
    function applyFilters() {
        currentSearch = $("#searchInput").val();
        currentRole = $("#filterRole").val();
        loadUsers(1);
    }

    // RESET FILTERS
    function resetFilters() {
        $("#searchInput").val("");
        $("#filterRole").val("");

        currentSearch = "";
        currentRole = "";

        loadUsers(1);
    }

    // =====================================
    // LOAD USERS
    // =====================================
    function loadUsers(page = 1) {

        currentPage = page;

        $("#userTable").html(`
            <tr>
                <td colspan="6" class="p-4 text-center text-gray-500">Loading users...</td>
            </tr>
        `);

        $.get("../backend/admin/get_users.php", {
            page: page,
            search: currentSearch,
            role: currentRole
        }, function(res) {

            renderUserTable(res.users, res.total, res.page, res.limit);
            renderPagination(res.page, res.total_pages, res.total, res.limit);
        });
    }

    // =====================================
    // RENDER TABLE
    // =====================================
    function renderUserTable(users, total, page, limit) {

        let html = "";

        if (users.length === 0) {
            $("#userTable").html(`<tr><td colspan="6" class="p-4 text-center text-gray-500">No users found.</td></tr>`);
            return;
        }

        // Hitung nomor urut global
        let start = (page - 1) * limit + 1;
        let no = start;

        users.forEach(u => {

            html += `
        <tr class="border-b hover:bg-gray-50" id="row-${u.id_user}">
            <td class="p-4 font-medium">${no++}</td>
                <td class="p-4">${u.name}</td>
                <td class="p-4">${u.email}</td>
                <td class="p-4">${u.phone ?? "-"}</td>
                <td class="p-4">${u.role_id == 1 ? "Admin" : "User"}</td>
                <td class="p-4 flex gap-2">

                    <button onclick="openEditForm(${u.id_user})"
                        class="px-4 py-1 rounded-md text-white"
                        style="background:#4169E1;">
                        Edit
                    </button>

                    <button onclick="deleteUser(${u.id_user})"
                        class="px-4 py-1 rounded-md text-white"
                        style="background:#C62828;">
                        Delete
                    </button>

                </td>
            </tr>

            <!-- HIDDEN EDIT FORM (INLINE) -->
            <tr id="editForm-${u.id_user}" class="hidden bg-gray-50 border-b">
                <td colspan="5" class="p-6">
                    <form class="editUserForm" data-id="${u.id_user}" method="POST"
                          action="../backend/admin/update_user.php">

                        <input type="hidden" name="id_user" value="${u.id_user}">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <div>
                                <label class="block text-sm font-medium">Full Name</label>
                                <input type="text" name="name" value="${u.name}" required
                                    class="w-full p-2 border rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Email</label>
                                <input type="email" name="email" value="${u.email}" required
                                    class="w-full p-2 border rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Phone</label>
                                <input type="text" name="phone" value="${u.phone ?? ""}"
                                    class="w-full p-2 border rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Role</label>
                                <select name="role_id" class="w-full p-2 border rounded-md">
                                    <option value="1" ${u.role_id == 1 ? "selected" : ""}>Admin</option>
                                    <option value="2" ${u.role_id == 2 ? "selected" : ""}>User</option>
                                </select>
                            </div>

                            <!-- CURRENT PASSWORD -->
                            <div>
                                <label class="block text-sm font-medium">Current Password (optional)</label>
                                <input type="password" name="current_password"
                                    placeholder="Leave blank to keep old password"
                                    class="w-full p-2 border rounded-md">
                            </div>

                            <!-- NEW PASSWORD -->
                            <div>
                                <label class="block text-sm font-medium">New Password (optional)</label>
                                <input type="password" name="new_password"
                                    placeholder="Leave blank to keep old password"
                                    class="w-full p-2 border rounded-md">
                            </div>

                        </div>

                        <div class="flex justify-end mt-4 gap-3">
                            <button type="button" onclick="closeEditForm(${u.id_user})"
                                class="px-5 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                                Cancel
                            </button>

                            <button type="submit"
                                class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Update User
                            </button>
                        </div>

                    </form>
                </td>
            </tr>
        `;
        });

        $("#userTable").html(html);
    }

    function openEditForm(id) {

        // Tutup semua edit form lainnya
        $("[id^='editForm-']").addClass("hidden");

        // Buka form yang dipilih
        $("#editForm-" + id).removeClass("hidden");

        // Scroll otomatis ke row tsb (optional)
        document.getElementById("row-" + id).scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
    }

    function closeEditForm(id) {
        $("#editForm-" + id).addClass("hidden");
    }



    // =====================================
    // PAGINATION
    // =====================================
    function renderPagination(current, totalPages, total, limit) {

        let start = (current - 1) * limit + 1;
        let end = Math.min(current * limit, total);

        let html = `
        <div class="flex justify-between items-center w-full">
            <div class="text-gray-600 text-sm">
                Showing <span class="font-semibold">${start}</span> -
                <span class="font-semibold">${end}</span> /
                <span class="font-semibold">${total}</span>
            </div>

            <div class="flex gap-2">
                <button ${current == 1 ? "disabled" : ""}
                    onclick="loadUsers(${current - 1})"
                    class="px-3 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-40">
                    Prev
                </button>
    `;

        // === PAGE BUTTON LOGIC (3 NUMBERS ONLY) ===
        let pages = [];

        if (totalPages <= 3) {
            // Case: total pages <= 3 -> show all
            for (let i = 1; i <= totalPages; i++) pages.push(i);

        } else {
            if (current <= 2) {
                // Case: near start → 1,2,3,...,last
                pages = [1, 2, 3, '...', totalPages];

            } else if (current >= totalPages - 1) {
                // Case: near end → 1,...,last-2,last-1,last
                pages = [1, '...', totalPages - 2, totalPages - 1, totalPages];

            } else {
                // Case: middle → 1,...,current,...,last
                pages = [1, '...', current, '...', totalPages];
            }
        }

        // === RENDER PAGE BUTTONS ===
        pages.forEach(p => {
            if (p === "...") {
                html += `
                <span class="px-3 py-1 text-xs text-gray-400">...</span>
            `;
            } else {
                html += `
                <button onclick="loadUsers(${p})"
                    class="px-3 py-1 text-xs rounded
                    ${p == current ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300'}">
                    ${p}
                </button>
            `;
            }
        });

        html += `
                <button ${current == totalPages ? "disabled" : ""}
                    onclick="loadUsers(${current + 1})"
                    class="px-3 py-1 text-xs bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-40">
                    Next
                </button>
            </div>
        </div>
    `;

        $("#paginationContainer").html(html);
    }



    // =====================================
    // DELETE USER
    // =====================================
    function deleteUser(id) {

        Swal.fire({
            title: "Delete User?",
            text: "This action cannot be undone.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete"
        }).then((result) => {

            if (result.isConfirmed) {
                window.location.href = "../backend/admin/delete_user.php?id=" + id;
            }
        });
    }
</script>

<?php require_once "../layouts/admin_footer.php"; ?>