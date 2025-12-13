<?php
require_once 'layouts/header.php';
require_once 'backend/db.php';

$page_title = 'Success Payment - FLYNOW';

/* ============================================================
   1. CEK LOGIN
============================================================ */
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "You must login to view your ticket.";
    header("Location: login.php");
    exit;
}

/* ============================================================
   2. CEK PARAMETER ID TRANSAKSI
============================================================ */
$transaction_id = $_GET['id'] ?? null;

if (!$transaction_id) {
    $_SESSION['error'] = "Invalid access. Transaction ID missing.";
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user']['id_user'];

/* ============================================================
   3. AMBIL TRANSAKSI (HARUS MILIK USER INI)
============================================================ */
$stmt = $conn->prepare("
    SELECT t.*, 
           da.airline_name AS dep_airline,
           da.airline_code AS dep_code,
           df.flight_code AS dep_flight_code,
           ao.airport_code AS dep_origin,
           ad.airport_code AS dep_dest,
           df.departure_date AS dep_date,
           df.departure_time AS dep_time,
           df.arrival_time AS dep_arrival,

           ra.airline_name AS ret_airline,
           ra.airline_code AS ret_code,
           rf.flight_code AS ret_flight_code,
           ro.airport_code AS ret_origin,
           rd.airport_code AS ret_dest,
           rf.departure_date AS ret_date,
           rf.departure_time AS ret_time,
           rf.arrival_time AS ret_arrival

    FROM transactions t
    JOIN flights df ON t.departure_flight_id = df.id_flight
    JOIN airlines da ON df.airline_id = da.id_airline
    JOIN airports ao ON df.origin_airport = ao.id_airport
    JOIN airports ad ON df.destination_airport = ad.id_airport

    LEFT JOIN flights rf ON t.return_flight_id = rf.id_flight
    LEFT JOIN airlines ra ON rf.airline_id = ra.id_airline
    LEFT JOIN airports ro ON rf.origin_airport = ro.id_airport
    LEFT JOIN airports rd ON rf.destination_airport = rd.id_airport

    WHERE t.id_transaction = ? AND t.user_id = ?
");

$stmt->bind_param("ii", $transaction_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    $_SESSION['error'] = "Transaction not found or you don't have access.";
    header("Location: index.php");
    exit;
}

/* ============================================================
   4. AMBIL PENUMPANG
============================================================ */
$passengers = $conn->prepare("
    SELECT * FROM transaction_passengers 
    WHERE transaction_id = ?
");
$passengers->bind_param("i", $transaction_id);
$passengers->execute();
$passengers = $passengers->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!-- ALERT MODAL -->
<div class="container mx-auto mt-6">
    <?php if (isset($_SESSION['error'])): ?>
        <div id="alert-error"
            class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg shadow cursor-pointer">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['error']; ?></span>
                <button onclick="$('#alert-error').fadeOut();" class="text-red-600 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['error']); endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div id="alert-success"
            class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg shadow cursor-pointer">
            <div class="flex justify-between items-center">
                <span class="font-semibold"><?= $_SESSION['success']; ?></span>
                <button onclick="$('#alert-success').fadeOut();" class="text-green-700 font-bold text-xl">&times;</button>
            </div>
        </div>
    <?php unset($_SESSION['success']); endif; ?>
</div>

<div class="container mx-auto px-6 py-12">
    <div class="max-w-3xl mx-auto">

        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-md shadow-md mb-8">
            <h2 class="text-xl font-bold">Payment Successful!</h2>
            <p>Thank you for your payment. Your booking has been confirmed.</p>
        </div>

        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <div class="bg-blue-600 text-white p-6">
                <h2 class="text-2xl font-bold">Flight E-Ticket</h2>
            </div>

            <div class="p-8">

                <!-- BOOKING CODE -->
                <div class="mb-8">
                    <div class="text-gray-500 text-sm">Booking Code</div>
                    <div class="text-4xl font-bold text-blue-600">
                        <?= $booking['booking_code'] ?>
                    </div>
                </div>

                <!-- PASSENGERS -->
                <div class="border-t border-dashed pt-6 mb-6">
                    <div class="text-gray-500 text-sm mb-1">Passengers</div>

                    <?php foreach ($passengers as $p): ?>
                        <div class="text-lg font-semibold">
                            <?= "{$p['title']} {$p['full_name']} (" . ucfirst($p['passenger_type']) . ")" ?>
                        </div>

                        <?php if ($p['passenger_type'] === "child"): ?>
                            <div class="text-sm text-gray-600">Mother: <?= $p['mother_name'] ?></div>
                        <?php endif; ?>

                        <div class="text-sm text-gray-600 mb-3">NIK: <?= $p['nik'] ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- FLIGHT DETAILS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <div class="text-gray-500 text-sm">Departure</div>
                        <div class="font-semibold text-lg">
                            <?= $booking['dep_airline'] ?> (<?= $booking['dep_code'] ?>)
                        </div>
                        <div><?= $booking['dep_origin'] ?> → <?= $booking['dep_dest'] ?></div>
                        <div class="mt-1 text-sm text-gray-600">
                            <?= date("l, d F Y", strtotime($booking['dep_date'])) ?>
                        </div>
                        <div class="text-sm">
                            <?= substr($booking['dep_time'],0,5) ?> → <?= substr($booking['dep_arrival'],0,5) ?>
                        </div>
                    </div>

                    <?php if ($booking['return_flight_id']): ?>
                    <div>
                        <div class="text-gray-500 text-sm">Return</div>
                        <div class="font-semibold text-lg">
                            <?= $booking['ret_airline'] ?> (<?= $booking['ret_code'] ?>)
                        </div>
                        <div><?= $booking['ret_origin'] ?> → <?= $booking['ret_dest'] ?></div>
                        <div class="mt-1 text-sm text-gray-600">
                            <?= date("l, d F Y", strtotime($booking['ret_date'])) ?>
                        </div>
                        <div class="text-sm">
                            <?= substr($booking['ret_time'],0,5) ?> → <?= substr($booking['ret_arrival'],0,5) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <div class="mt-8 pt-4 border-t text-center">
                    <span class="text-lg font-semibold text-green-600">
                        PAID (E-Ticket Issued)
                    </span>
                </div>

            </div>
        </div>

        <!-- BUTTONS -->
        <div class="text-center mt-8 space-x-4">

            <a href="backend/download_ticket.php?id=<?= $transaction_id ?>"
               class="bg-gray-700 text-white px-6 py-3 rounded-md hover:bg-gray-800">
                Download PDF
            </a>

            <a href="index.php"
               class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700">
                Search Another Flight
            </a>

        </div>

    </div>
</div>

<?php require_once 'layouts/footer.php'; ?>
