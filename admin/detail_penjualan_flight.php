<?php
// admin/detail_penjualan_flight.php

require_once "../backend/db.php";
require_once "../backend/akses_admin.php";

$id_flight = isset($_GET['id_flight']) ? (int)$_GET['id_flight'] : 0;
if ($id_flight <= 0) {
    die("Invalid Flight ID");
}
$back_params = $_GET['back_params'] ?? ''; // <--- Tambahkan ini
$back_url = "laporan.php";
$flight_code = $_GET['flight_code'] ?? '';
/* =========================
   CONFIG PAGINATION
========================= */
$limit = 10;
$page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

/* =========================
   AMBIL INFO FLIGHT
========================= */
$flight_sql = "
    SELECT 
        f.flight_code,
        oa.airport_code AS origin_airport,
        da.airport_code AS destination_airport,
        f.departure_date,
        f.departure_time
    FROM flights f
    JOIN airports oa ON oa.id_airport = f.origin_airport
    JOIN airports da ON da.id_airport = f.destination_airport
    WHERE f.id_flight = ?
";
$stmt_flight = $conn->prepare($flight_sql);
$stmt_flight->bind_param("i", $id_flight);
$stmt_flight->execute();
$flight = $stmt_flight->get_result()->fetch_assoc();

if (!$flight) {
    die("Flight not found");
}

/* =========================
   HITUNG TOTAL DATA
========================= */
$count_sql = "
    SELECT COUNT(*) AS total
    FROM transactions
    WHERE departure_flight_id = ?
      AND payment_status = 'Paid'
";
$stmt_count = $conn->prepare($count_sql);
$stmt_count->bind_param("i", $id_flight);
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;

$total_pages = ceil($total_rows / $limit);

/* =========================
   AMBIL DATA CUSTOMER
========================= */
$data_sql = "
    SELECT 
        u.name,
        u.email,
        t.total_passengers,
        t.total_price,
        t.created_at
    FROM transactions t
    JOIN users u ON u.id_user = t.user_id
    WHERE t.departure_flight_id = ?
      AND t.payment_status = 'Paid'
    ORDER BY t.created_at DESC  
    LIMIT ? OFFSET ?
";

$stmt_data = $conn->prepare($data_sql);
$stmt_data->bind_param("iii", $id_flight, $limit, $offset);
$stmt_data->execute();
$customers = $stmt_data->get_result();

/* =========================
   LAYOUT
========================= */
$admin_page_title = "Detail Penjualan Flight";
require_once "../layouts/admin_header.php";
require_once "../layouts/admin_sidebar.php";
?>

<main class="flex-1 p-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold"><?= $admin_page_title; ?></h1>

        <a href="laporan.php?type=flight"
        class="text-blue-600 hover:text-blue-800 text-sm font-semibold flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="2"
                    d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Report
        </a>
    </div>

    <!-- INFO FLIGHT -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow">
        <p class="text-lg font-semibold">
            <?= htmlspecialchars($flight['flight_code']) ?>
        </p>
        <p class="text-sm text-gray-600">
            <?= $flight['origin_airport'] ?> → <?= $flight['destination_airport'] ?>
        </p>
        <p class="text-sm text-gray-600">
            Departure: <?= date('d M Y', strtotime($flight['departure_date'])) ?>
            | <?= $flight['departure_time'] ?>
        </p>
    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-lg shadow-md overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passengers</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transaction Date</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($customers->num_rows > 0): ?>
                    <?php while ($row = $customers->fetch_assoc()): ?>
                        <tr>
                            <td class="px-6 py-4"><?= htmlspecialchars($row['name']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($row['email']) ?></td>
                            <td class="px-6 py-4"><?= number_format($row['total_passengers']) ?></td>
                            <td class="px-6 py-4">
                                Rp <?= number_format($row['total_price'], 0, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4">
                                <?= date('d M Y H:i', strtotime($row['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            No customer data found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="mt-6 flex justify-between items-center">
        <p class="text-sm text-gray-600">
            Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_rows) ?>
            of <?= $total_rows ?> customers
        </p>

        <nav class="inline-flex rounded-md shadow-sm -space-x-px">
            <?php if ($page > 1): ?>
                <a href="?id_flight=<?= $id_flight ?>&page=<?= $page - 1 ?>"
                   class="px-3 py-2 border bg-white text-sm hover:bg-gray-50">Previous</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?id_flight=<?= $id_flight ?>&page=<?= $i ?>"
                   class="px-4 py-2 border text-sm
                   <?= $i == $page ? 'bg-blue-50 text-blue-600 border-blue-500' : 'bg-white hover:bg-gray-50' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?id_flight=<?= $id_flight ?>&page=<?= $page + 1 ?>"
                   class="px-3 py-2 border bg-white text-sm hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </nav>
    </div>
</main>

<?php require_once "../layouts/admin_footer.php"; ?>
