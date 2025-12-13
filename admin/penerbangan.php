<?php
require_once "../backend/akses_admin.php";
require_once "../backend/db.php";

$admin_page_title = 'Manajemen Penerbangan';
require_once '../layouts/admin_header.php';
require_once '../layouts/admin_sidebar.php';

// Ambil maskapai
$airlines = $conn->query("SELECT * FROM airlines ORDER BY airline_name ASC");

// Ambil asal & tujuan
$airports1 = $conn->query("SELECT * FROM airports ORDER BY city ASC");
$airports2 = $conn->query("SELECT * FROM airports ORDER BY city ASC");
?>

<main class="flex-1 p-10">

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


    <div id="deleteModal"
        class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">

        <div class="bg-white p-6 rounded-lg shadow-lg w-80">
            <h3 class="text-lg font-semibold mb-4 text-center">Delete Flight</h3>
            <p class="text-sm text-gray-700 text-center mb-6">
                Are you sure you want to delete this flight?
            </p>

            <div class="flex justify-center gap-3">
                <button id="btn_cancel_delete"
                    class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 text-sm">
                    Cancel
                </button>

                <form id="deleteForm" action="../backend/admin/flight_delete.php" method="POST">
                    <input type="hidden" name="id_flight" id="delete_flight_id">
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 text-sm">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>




    <h1 class="text-3xl font-bold mb-8">Flight Management</h1>

    <!-- ========== FORM TAMBAH FLIGHT ========== -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-semibold mb-4">Add New Flight</h2>

        <form id="flightForm" action="../backend/admin/flight_add.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <!-- MASKAPAI -->
            <div class="md:col-span-3">
                <label class="block text-sm font-medium">Airline</label>
                <select name="airline_id" id="airline_select"
                    class="w-full mt-1 p-2 border rounded-md text-sm" required>
                    <option value="">-- Choose Airline --</option>
                    <?php while ($a = $airlines->fetch_assoc()): ?>
                        <option value="<?= $a['id_airline']; ?>" data-code="<?= $a['airline_code']; ?>">
                            <?= $a['airline_name']; ?> (<?= $a['airline_code']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- KODE FLIGHT -->
            <div>
                <label class="block text-sm font-medium">Flight Code</label>
                <div class="flex">
                    <input type="text" id="airline_prefix" name="airline_prefix" class="w-14 p-2 border rounded-l-md bg-gray-100 text-center text-sm" readonly placeholder="XX">
                    <input type="number" name="flight_number" class="flex-1 p-2 border rounded-r-md text-sm" placeholder="123" required>
                </div>
            </div>

            <!-- ASAL -->
            <div>
                <label class="block text-sm font-medium">Origin</label>
                <select name="origin_airport" class="w-full mt-1 p-2 border rounded-md text-sm" required>
                    <option value="">-- Choose Origin --</option>
                    <?php while ($o = $airports1->fetch_assoc()): ?>
                        <option value="<?= $o['id_airport']; ?>"><?= $o['city']; ?> (<?= $o['airport_code']; ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- TUJUAN -->
            <div>
                <label class="block text-sm font-medium">Destination</label>
                <select name="destination_airport" class="w-full mt-1 p-2 border rounded-md text-sm" required>
                    <option value="">-- Choose Destination --</option>
                    <?php while ($d = $airports2->fetch_assoc()): ?>
                        <option value="<?= $d['id_airport']; ?>"><?= $d['city']; ?> (<?= $d['airport_code']; ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- TANGGAL -->
            <div>
                <label class="block text-sm font-medium">Departure Date</label>
                <input type="date" name="departure_date" class="w-full mt-1 p-2 border rounded-md text-sm" required>
            </div>

            <!-- WAKTU -->
            <div>
                <label class="block text-sm font-medium">Departure time</label>
                <input type="time" name="departure_time" class="w-full mt-1 p-2 border rounded-md text-sm" required>
            </div>

            <!-- DURASI -->
            <div>
                <label class="block text-sm font-medium">Duration (min)</label>
                <input type="number" name="travel_duration" placeholder="120" class="w-full mt-1 p-2 border rounded-md text-sm" required>
            </div>

            <!-- HARGA -->
            <div>
                <label class="block text-sm font-medium">Price</label>
                <input type="number" name="price" placeholder="1000000" class="w-full mt-1 p-2 border rounded-md text-sm" required>
            </div>

            <!-- KUOTA -->
            <div>
                <label class="block text-sm font-medium">Seat Quota</label>
                <input type="number" name="seats" placeholder="150" class="w-full mt-1 p-2 border rounded-md text-sm" required>
            </div>

            <div class="md:col-span-3 flex gap-2">
                <button type="submit"
                    class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm">
                    Save Flight
                </button>

                <!-- Tombol Cancel Edit -->
                <button type="button" id="btn_cancel_edit"
                    class="w-full bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 text-sm hidden">
                    Cancel Edit
                </button>
            </div>

        </form>
    </div>


    <?php
    // formatting date menjadi teks
    function formatTanggal($date)
    {
        return date("d F Y", strtotime($date));
    }

    // remove detik dari time
    function cutTime($time)
    {
        return date("H:i", strtotime($time));
    }

    // badge status
    function statusTag($status)
    {
        switch ($status) {
            case "Upcoming":
                return '<span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs">' . $status . '</span>';
            case "Ongoing":
                return '<span class="px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs">' . $status . '</span>';
            default:
                return '<span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs">' . $status . '</span>';
        }
    }
    ?>

    <div class="bg-white rounded-lg shadow-md p-6">

        <!-- FILTER -->
        <div class="flex items-center gap-3 mb-6 text-sm">
            <label class="font-medium w-32">Filter Date:</label>
            <input type="date" id="filter_date" class="p-2 border rounded-md text-sm">
            <button id="btn_filter" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                Filter
            </button>
            <button id="btn_reset" class="px-4 py-2 bg-gray-500 text-white rounded-md text-sm hover:bg-gray-600">
                Reset
            </button>
        </div>

        <!-- TABLE -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-gray-700">
                <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Airline</th>
                        <th class="px-4 py-3">Route</th>
                        <th class="px-4 py-3">Schedule</th>
                        <th class="px-4 py-3">Price</th>
                        <th class="px-4 py-3">Quota</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>

                <tbody id="flight_table" class="divide-y divide-gray-200">
                    <tr>
                        <td colspan="8" class="p-4 text-center text-gray-500">Loading Flight...</td>
                    </tr>
                </tbody>

            </table>
        </div>

        <div class="flex justify-end mt-4" id="paginationContainer"></div>

    </div>


    <script>
        function formatTanggalText(dateStr) {
            const d = new Date(dateStr);
            const options = {
                day: "numeric",
                month: "long",
                year: "numeric"
            };
            return d.toLocaleDateString("en-US", options);
        }

        function cutTime(t) {
            return t.substring(0, 5);
        }

        function statusBadge(status) {
            const map = {
                "Upcoming": "bg-blue-100 text-blue-700",
                "Ongoing": "bg-yellow-100 text-yellow-700",
                "Arrived": "bg-green-100 text-green-700",
                // "Sold Out": "bg-red-100 text-red-700"
            };
            return `<span class="px-3 py-1 rounded-full text-xs ${map[status]}">${status}</span>`;
        }

        function statusBadgeSoldOut(status) {
            const map = {
                "Sold Out": "bg-red-100 text-red-700"
            };
            return `<span class="px-3 py-1 rounded-full text-xs ${map[status]}">${status}</span>`;
        }

        function openDeleteModal(id) {
            $("#delete_flight_id").val(id);
            $("#deleteModal").removeClass("hidden");
        }

        let currentPage = 1;
        let currentDate = "";

        function loadFlights(page = 1) {

            currentPage = page;
            $("#flight_table").html("");

            $("#flight_table").html(`
                <tr>
                    <td colspan="8" class="p-4 text-center text-gray-500">Loading Flight...</td>
                </tr>
            `);

            $.ajax({
                url: "../backend/admin/flight_fetch.php",
                type: "GET",
                data: {
                    page: page,
                    date: currentDate
                },
                dataType: "json",
                success: function(res) {

                    $("#flight_table").html("");

                    if (res.flights.length === 0) {
                        $("#flight_table").html(`
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                            No flights found.
                        </td>
                    </tr>
                `);
                        return;
                    }

                    res.flights.forEach(f => {

                        let isLocked = (f.status === "Ongoing" || f.status === "Arrived");

                        let canEdit = f.can_edit;
                        let canDelete = f.can_delete;

                        let editBtn = `
                <button ${!canEdit ? "disabled" : `onclick="editFlight(${f.id_flight})"`}
                    class="px-3 py-1 rounded-md text-xs 
                    ${!canEdit ? "bg-gray-300 text-gray-500 cursor-not-allowed" : "bg-blue-600 text-white hover:bg-blue-700"}">
                    Edit
                </button>`;

                        let deleteBtn = `
                <button ${!canDelete ? "disabled" : `onclick="openDeleteModal(${f.id_flight})"`}
                    class="px-3 py-1 rounded-md text-xs 
                    ${!canDelete ? "bg-gray-300 text-gray-500 cursor-not-allowed" : "bg-red-600 text-white hover:bg-red-700"}">
                    Delete
                </button>`;

                        $("#flight_table").append(`
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">${f.flight_code}</td>
                        <td class="px-4 py-3">${f.airline_name}</td>
                        <td class="px-4 py-3">
                            ${f.origin_city} (${f.origin_code}) → ${f.dest_city} (${f.dest_code})
                        </td>
                        <td class="px-4 py-3">
                            ${formatTanggalText(f.departure_date)}<br>
                            <span class="font-semibold">${cutTime(f.departure_time)} - ${cutTime(f.arrival_time)}</span><br>
                            <span class="text-xs text-gray-500">(${f.travel_duration} minutes)</span>
                        </td>
                        <td class="px-4 py-3">Rp ${Number(f.price).toLocaleString("id-ID")}</td>
                        <td class="px-4 py-3">${f.booked_seats} / ${f.seat_quota}</td>
                        <td class="px-4 py-3">
                            ${statusBadge(f.status)}
                            ${f.status_sold_out
                                ? `<br><br>${statusBadgeSoldOut(f.status_sold_out)}`
                                : ""
                            }
                            </td>                        
                        <td class="px-4 py-3">
                            <div class="flex gap-2 justify-center">
                                ${editBtn}
                                ${deleteBtn}
                            </div>
                        </td>
                    </tr>
                `);
                    });

                    renderPagination(
                        res.page,
                        res.total_pages,
                        res.total,
                        res.limit
                    );
                }
            });
        }



        function editFlight(id) {
            $.ajax({
                url: "../backend/admin/flight_get.php",
                type: "GET",
                data: {
                    id: id
                },
                dataType: "json",
                success: function(f) {

                    // Ubah judul
                    $("h2").text("Edit Penerbangan");

                    // Ubah action form
                    $("#flightForm").attr("action", "../backend/admin/flight_update.php");

                    // Tambah hidden input ID flight jika belum ada
                    if ($("#flight_id").length === 0) {
                        $("#flightForm").prepend(`<input type="hidden" name="id_flight" id="flight_id">`); // Targetkan flightForm
                    }

                    $("#flight_id").val(f.id_flight);

                    // Maskapai
                    $("#airline_select").val(f.airline_id).trigger("change");

                    // Prefix airline supaya muncul di input kiri
                    $("#airline_prefix").val(f.airline_code);

                    // Nomor flight
                    $("input[name='flight_number']").val(f.flight_number);

                    // Asal & Tujuan
                    $("select[name='origin_airport']").val(f.origin_airport);
                    $("select[name='destination_airport']").val(f.destination_airport);

                    // Date & Time
                    $("input[name='departure_date']").val(f.departure_date);
                    $("input[name='departure_time']").val(f.departure_time);

                    // Durasi
                    $("input[name='travel_duration']").val(f.travel_duration);

                    // Price
                    $("input[name='price']").val(f.price);

                    // Seats
                    $("input[name='seats']").val(f.seat_quota);

                    $("#btn_cancel_edit").removeClass("hidden");

                    // Scroll ke atas
                    window.scrollTo({
                        top: 0,
                        behavior: "smooth"
                    });
                }
            });
        }

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


        $(document).ready(function() {
            loadFlights();

            $("#airline_select").on("change", function() {
                let prefix = $(this).find(":selected").data("code");
                $("#airline_prefix").val(prefix);
            });

            setTimeout(() => {
                $("#alert-error, #alert-success").fadeOut();
            }, 3000);

            $("#btn_filter").click(() => {
                currentDate = $("#filter_date").val();
                loadFlights(1);
            });
            $("#btn_reset").click(() => {
                $("#filter_date").val("");
                currentDate = "";
                loadFlights(1);
            });

            $("#btn_cancel_edit").click(function() {
                $("h2").text("Tambah Penerbangan Baru");
                $("#flightForm").attr("action", "../backend/admin/flight_add.php"); // Targetkan flightForm

                $("#flightForm")[0].reset();
                $("#airline_prefix").val("");

                $("#btn_cancel_edit").addClass("hidden");
                $("#flight_id").remove();
            });

            $("#btn_cancel_delete").click(function() {
                $("#deleteModal").addClass("hidden");
            });
        });
    </script>


    <?php require_once "../layouts/admin_footer.php"; ?>