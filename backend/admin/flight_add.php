<?php
session_start();
require_once "../db.php";
require_once "../akses_admin.php";

// Ambil POST
$airline_id         = $_POST['airline_id'] ?? '';
$flight_number      = $_POST['flight_number'] ?? '';
$origin_airport     = $_POST['origin_airport'] ?? '';
$destination_airport = $_POST['destination_airport'] ?? '';
$departure_date     = $_POST['departure_date'] ?? '';
$departure_time     = $_POST['departure_time'] ?? '';
$travel_duration    = $_POST['travel_duration'] ?? '';
$price              = $_POST['price'] ?? '';
$seats              = $_POST['seats'] ?? '';


// BASIC VALIDATION
if (
    $airline_id === '' ||
    $flight_number === '' ||
    $origin_airport === '' ||
    $destination_airport === '' ||
    $departure_date === '' ||
    $departure_time === '' ||
    $travel_duration === '' ||
    $price === '' ||
    $seats === ''
) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

if (!ctype_digit($flight_number)) {
    $_SESSION['error'] = "Flight number must contain digits only.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

if (!is_numeric($travel_duration) || $travel_duration <= 0) {
    $_SESSION['error'] = "Travel duration must be a number greater than 0.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

if (!is_numeric($price) || $price <= 0) {
    $_SESSION['error'] = "Invalid price.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

if (!is_numeric($seats) || $seats <= 0) {
    $_SESSION['error'] = "Invalid seat quota.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

// ORIGIN ≠ DESTINATION
if ($origin_airport == $destination_airport) {
    $_SESSION['error'] = "Origin and destination cannot be the same.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}


// TIME RULE: must be 1 hour after now
$now = new DateTime();
$limitTime = new DateTime("+1 hour");

$selected = new DateTime($departure_date . " " . $departure_time);

if ($selected < $limitTime) {
    $_SESSION['error'] = "Departure time must be at least 1 hour from now.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}


// GET AIRLINE PREFIX
$q = $conn->prepare("SELECT airline_code FROM airlines WHERE id_airline = ?");
$q->bind_param("i", $airline_id);
$q->execute();
$airline = $q->get_result()->fetch_assoc();

if (!$airline) {
    $_SESSION['error'] = "Airline not found.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

$prefix = $airline['airline_code'];
$flight_code = $prefix . $flight_number;   // contoh: GA402 (tanpa tanda - seperti maskapai asli)


// UNIQUE FLIGHT CODE (same airline & same date)
// HANYA ditolak jika:
// - flight_code sama
// - airline sama
// - departure_date sama
// (jam berapapun → tetap ditolak)
$check = $conn->prepare("
    SELECT id_flight 
    FROM flights 
    WHERE flight_code = ? 
      AND airline_id = ?
      AND departure_date = ?
");
$check->bind_param("sis", $flight_code, $airline_id, $departure_date);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $_SESSION['error'] = "Flight code $flight_code is already used by this airline on $departure_date.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}


// ==========================================================
// HITUNG ARRIVAL DATE & TIME OTOMATIS
// ==========================================================

// Gabungkan departure
$departure_datetime = $departure_date . " " . $departure_time;

// Hitung arrival
$dep = new DateTime($departure_datetime);
$dep->modify("+{$travel_duration} minutes");

$arrival_date = $dep->format("Y-m-d");
$arrival_time = $dep->format("H:i:s");


// ==========================================================
// INSERT KE DATABASE
// ==========================================================

$stmt = $conn->prepare("
    INSERT INTO flights 
    (flight_code, airline_id, origin_airport, destination_airport, 
     price, seat_quota, booked_seats,
     departure_date, departure_time, 
     travel_duration,
     arrival_date, arrival_time)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "siiiiisssiss",
    $flight_code,
    $airline_id,
    $origin_airport,
    $destination_airport,
    $price,
    $seats,
    0, // booked_seats awal 0
    $departure_date,
    $departure_time,
    $travel_duration,
    $arrival_date,
    $arrival_time
);

if ($stmt->execute()) {
    $_SESSION['success'] = "Flight $flight_code has been successfully added.";
} else {
    $_SESSION['error'] = "Failed to add flight.";
}

header("Location: ../../admin/penerbangan.php");
exit;
?>
