<?php
session_start();
require_once "../db.php";
require_once "../akses_admin.php";

$id = $_POST["id_flight"] ?? 0;

if (!$id) {
    $_SESSION['error'] = "Flight not found.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

// Ambil flight_code dulu
$q = $conn->prepare("SELECT flight_code FROM flights WHERE id_flight = ?");
$q->bind_param("i", $id);
$q->execute();
$res = $q->get_result()->fetch_assoc();

if (!$res) {
    $_SESSION['error'] = "Flight not found.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}


$flight_code = $res['flight_code'];


// Cek apakah penerbangan ini bisa dihapus
$check = $conn->prepare("
    SELECT id_flight 
    FROM flights 
    WHERE flight_code = ? 
      AND status IN ('Arrived', 'Ongoing')
");
$check->bind_param("s", $flight_code);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $_SESSION['error'] = "Flight $flight_code cannot be deleted because it has already departed.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

//cek apakah ticket sudah terjual
$checkTickets = $conn->prepare("
    SELECT boocked_seats
    FROM flights 
    WHERE id_flight = ?
");
$checkTickets->bind_param("i", $id);
$checkTickets->execute();
$resTickets = $checkTickets->get_result()->fetch_assoc();

if ($resTickets['boocked_seats'] > 0) {
    $_SESSION['error'] = "Flight $flight_code cannot be deleted because tickets have already been sold.";
    header("Location: ../../admin/penerbangan.php");
    exit;
}

// Lakukan delete
$del = $conn->prepare("DELETE FROM flights WHERE id_flight = ?");
$del->bind_param("i", $id);

if ($del->execute()) {
    $_SESSION['success'] = "Flight $flight_code successfully deleted.";
} else {
    $_SESSION['error'] = "Failed to delete flight $flight_code.";
}

header("Location: ../../admin/penerbangan.php");
exit;
