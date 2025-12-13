<?php
session_start();
require_once "db.php";

// -------------------------------------------------------------------
// 1. CEK LOGIN
// -------------------------------------------------------------------
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "You must login to access your ticket.";
    header("Location: login.php");
    exit;
}

// -------------------------------------------------------------------
// 2. CEK PARAMETER
// -------------------------------------------------------------------
$transaction_id = $_GET['id'] ?? null;

if (!$transaction_id) {
    echo "Invalid ticket request.";
    exit;
}

// -------------------------------------------------------------------
// 3. GET TRANSACTION DETAIL
// -------------------------------------------------------------------
$stmt = $conn->prepare("
SELECT t.*, 
       u.name AS buyer_name, u.email,
       df.flight_code AS dep_code, df.departure_time AS dep_time, df.arrival_time AS dep_arrival,
       df.departure_date AS dep_date, df.arrival_date AS dep_arrival_date, df.travel_duration AS duration,
       ao.airport_code AS dep_origin, ad.airport_code AS dep_dest,
       a1.airline_name AS dep_airline

FROM transactions t
JOIN users u ON t.user_id = u.id_user
JOIN flights df ON df.id_flight = t.departure_flight_id
JOIN airports ao ON ao.id_airport = df.origin_airport
JOIN airports ad ON ad.id_airport = df.destination_airport
JOIN airlines a1 ON df.airline_id = a1.id_airline
WHERE t.id_transaction = ?
");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if (!$transaction) {
    echo "Ticket not found.";
    exit;
}

// -------------------------------------------------------------------
// 4. GET RETURN FLIGHT (IF ANY)
// -------------------------------------------------------------------
$returnFlight = null;

if ($transaction['return_flight_id']) {
    $stmt2 = $conn->prepare("
        SELECT f.*, ao.airport_code AS origin_code, ad.airport_code AS dest_code, f.travel_duration AS duration,
               a.airline_name
        FROM flights f
        JOIN airports ao ON ao.id_airport = f.origin_airport
        JOIN airports ad ON ad.id_airport = f.destination_airport
        JOIN airlines a ON f.airline_id = a.id_airline
        WHERE id_flight = ?
    ");
    $stmt2->bind_param("i", $transaction['return_flight_id']);
    $stmt2->execute();
    $returnFlight = $stmt2->get_result()->fetch_assoc();
}

// -------------------------------------------------------------------
// 5. GET PASSENGERS
// -------------------------------------------------------------------
$stmt3 = $conn->prepare("
    SELECT * FROM transaction_passengers
    WHERE transaction_id = ?
");
$stmt3->bind_param("i", $transaction_id);
$stmt3->execute();
$passengers = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

// -------------------------------------------------------------------
// 6. FORMAT DATE
// -------------------------------------------------------------------
function format_date($date) {
    return date("l, d F Y", strtotime($date));
}

// -------------------------------------------------------------------
// 7. AUTO TRIGGER PRINT
// -------------------------------------------------------------------
?>
<!DOCTYPE html>
<html>
<head>
    <title>E-Ticket FLYNOW</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    padding: 20px;
}

.ticket {
    width: 800px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 10px;
    border: 2px solid #1a56db;
}

h2 {
    color: #1a56db;
}

.section {
    margin-top: 20px;
    padding-top: 10px;
    border-top: 1px dashed #aaa;
}

.passenger {
    padding: 8px 0;
}

.print-btn {
    margin-top: 20px;
    text-align: center;
}

@media print {
    .print-btn { display: none; }
    body { background: white; }
}
</style>

</head>
<body>

<div class="ticket">

    <h2>FLYNOW E-TICKET</h2>
    <h3>Booking Code: <?= $transaction['booking_code'] ?></h3>

    <div class="section">
        <h3>Flight Information</h3>

        <p><strong>Airline:</strong> <?= $transaction['dep_airline'] ?></p>
        <p><strong>Route:</strong> <?= $transaction['dep_origin'] ?> → <?= $transaction['dep_dest'] ?></p>
        <p><strong>Date:</strong> <?= format_date($transaction['dep_date']) ?></p>
        <p><strong>Departure:</strong> <?= substr($transaction['dep_time'],0,5) ?></p>
        <p><strong>Arrival:</strong> <?= substr($transaction['dep_arrival'],0,5) ?></p>
        <p><strong>Duration:</strong> <?= $transaction['duration'] ?> minutes</p>

        <?php if ($returnFlight): ?>
            <br><h4>Return Flight:</h4>
            <p><strong>Airline:</strong> <?= $returnFlight['airline_name'] ?></p>
            <p><strong>Route:</strong> <?= $returnFlight['origin_code'] ?> → <?= $returnFlight['dest_code'] ?></p>
            <p><strong>Date:</strong> <?= format_date($returnFlight['departure_date']) ?></p>
            <p><strong>Departure:</strong> <?= substr($returnFlight['departure_time'],0,5) ?></p>
            <p><strong>Arrival:</strong> <?= substr($returnFlight['arrival_time'],0,5) ?></p>
            <p><strong>Duration:</strong> <?= $returnFlight['duration'] ?> minutes</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Passengers</h3>

        <?php foreach ($passengers as $p): ?>
            <div class="passenger">
                <?= $p['title'] ?> <?= $p['full_name'] ?> 
                (<?= ucfirst($p['passenger_type']) ?>)
                <br>NIK: <?= $p['nik'] ?>
                <?php if ($p['mother_name']): ?>
                    <br>Mother’s Name: <?= $p['mother_name'] ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- <div class="section">
        <h3>Payment</h3>
        <p><strong>Method:</strong> <?= strtoupper($transaction['payment_method']) ?></p>
        <p><strong>Total Paid:</strong> Rp <?= number_format($transaction['total_price'], 0, ',', '.') ?></p>
    </div> -->

</div>

<div class="print-btn">
    <button onclick="window.print()"
        style="padding:10px 20px; background:#1a56db; color:white; border:none; border-radius:5px;">
        Print Ticket
    </button>
</div>

<script>
    // Auto trigger print
    window.onload = function() {
        window.print();    // aktifkan jika mau auto print
    };
</script>

</body>
</html>
