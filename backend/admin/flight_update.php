<?php
session_start();
require_once "../db.php";
require_once "../akses_admin.php";

// ==========================================================
// PENGAMBILAN POST: Tambahkan 'airline_prefix'
// ==========================================================
$id_flight           = $_POST['id_flight'] ?? '';
$airline_id          = $_POST['airline_id'] ?? '';
$airline_prefix      = $_POST['airline_prefix'] ?? '';
$flight_number       = $_POST['flight_number'] ?? '';
$origin_airport      = $_POST['origin_airport'] ?? '';
$destination_airport = $_POST['destination_airport'] ?? '';
$departure_date      = $_POST['departure_date'] ?? '';
$departure_time      = $_POST['departure_time'] ?? '';
$travel_duration     = $_POST['travel_duration'] ?? '';
$price               = $_POST['price'] ?? '';
$seats               = $_POST['seats'] ?? '';


// ==========================================================
// VALIDASI DASAR DAN NUMERIK YANG DIPERBAIKI
// ==========================================================

// Validasi ID Update (Wajib Ada)
if ($id_flight === '') {
    $_SESSION['error'] = "Invalid flight data: Missing Flight ID.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

// Cek Kekosongan Field Utama
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

// Cek Tipe Data Numerik (Setelah dipastikan tidak kosong)

// Flight number harus angka
if (!ctype_digit($flight_number)) {
    $_SESSION['error'] = "Flight number must contain digits only.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

// Durasi harus angka positif
if (!is_numeric($travel_duration) || $travel_duration <= 0) {
    $_SESSION['error'] = "Travel duration must be a number greater than 0.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

// Harga harus angka positif
if (!is_numeric($price) || $price <= 0) {
    $_SESSION['error'] = "Invalid price.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

// Kuota harus angka positif
if (!is_numeric($seats) || $seats <= 0) {
    $_SESSION['error'] = "Invalid seat quota.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

// Origin =? destination
if ($origin_airport == $destination_airport) {
    $_SESSION['error'] = "Origin and destination cannot be the same.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}


// ==========================================================
// DEPARTURE TIME MINIMUM 1 HOUR FROM NOW (ONLY IF SAME DAY)
// ==========================================================
date_default_timezone_set("Asia/Jakarta");
$today = date("Y-m-d");

if ($departure_date == $today) {

    $now = new DateTime();
    $minDeparture = new DateTime();
    $minDeparture->modify("+1 hour");

    $userDeparture = new DateTime($departure_date . " " . $departure_time);

    if ($userDeparture < $minDeparture) {
        $_SESSION['error'] = "Departure time must be at least 1 hour from now.";
        header("Location: ../../admin/penerbangan.php");
        exit;
    }
}


// ==========================================================
// GET AIRLINE PREFIX (KITA TETAP AMBIL DARI DB UNTUK KEAMANAN)
// ==========================================================
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
$flight_code = $prefix . $flight_number; // KODE FLIGHT LENGKAP


// ==========================================================
// VALIDATE DUPLICATE (EXCEPT THIS FLIGHT)
// ==========================================================
$check = $conn->prepare("
    SELECT id_flight 
    FROM flights
    WHERE flight_code = ?
      AND airline_id = ?
      AND departure_date = ?
      AND id_flight != ?
");
$check->bind_param("sisi", $flight_code, $airline_id, $departure_date, $id_flight);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $_SESSION['error'] = "Flight code $flight_code is already used by this airline on $departure_date.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}


// ==========================================================
// CALCULATE ARRIVAL TIME
// ==========================================================
$departure_datetime = $departure_date . " " . $departure_time;

$dt = new DateTime($departure_datetime);
$dt->modify("+{$travel_duration} minutes");

$arrival_date = $dt->format("Y-m-d");
$arrival_time = $dt->format("H:i:s");


// ==========================================================
// UPDATE DATABASE
// ==========================================================
$stmt = $conn->prepare("
    UPDATE flights SET 
        flight_code = ?, 
        airline_id = ?,
        origin_airport = ?, 
        destination_airport = ?, 
        price = ?, 
        seat_quota = ?, 
        departure_date = ?, 
        departure_time = ?, 
        travel_duration = ?, 
        arrival_date = ?, 
        arrival_time = ?
    WHERE id_flight = ?
");

$stmt->bind_param(
    "siiiisssissi",
    $flight_code,
    $airline_id,
    $origin_airport,
    $destination_airport,
    $price,
    $seats,
    $departure_date,
    $departure_time,
    $travel_duration,
    $arrival_date,
    $arrival_time,
    $id_flight
);

if ($stmt->execute()) {
    $_SESSION['success'] = "Flight $flight_code has been successfully updated.";
} else {
    $_SESSION['error'] = "Failed to update flight data. DB Error: " . $conn->error;
}

header("Location: ../../admin/penerbangan.php");
exit;
?>