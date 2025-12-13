<?php
session_start();
require_once "../db.php";

header("Content-Type: application/json");

// Ambil ID
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ($id <= 0) {
    echo json_encode(["error" => "Invalid flight ID"]);
    exit;
}

// Query ambil data flight
$q = $conn->prepare("
    SELECT 
        f.id_flight,
        f.flight_code,
        f.airline_id,
        f.origin_airport,
        f.destination_airport,
        f.departure_date,
        f.departure_time,
        f.arrival_date,
        f.arrival_time,
        f.travel_duration,
        f.price,
        f.seat_quota,
        a.airline_name,
        a.airline_code,

        o.city AS origin_city,
        o.airport_code AS origin_code,

        d.city AS dest_city,
        d.airport_code AS dest_code

    FROM flights f
    JOIN airlines a ON a.id_airline = f.airline_id
    JOIN airports o ON o.id_airport = f.origin_airport
    JOIN airports d ON d.id_airport = f.destination_airport
    WHERE f.id_flight = ?
");
$q->bind_param("i", $id);
$q->execute();
$data = $q->get_result()->fetch_assoc();

if (!$data) {
    echo json_encode(["error" => "Flight not found"]);
    exit;
}

// ===============
// SPLIT FLIGHT CODE
// GA402 → GA + 402
// ===============
$code = $data["flight_code"];
$prefix = substr($code, 0, 2);           // GA
$number = substr($code, 2);             // 402

// Response lengkap
$response = [
    "id_flight"         => $data["id_flight"],
    "flight_code"       => $data["flight_code"],

    "airline_id"        => $data["airline_id"],
    "airline_name"      => $data["airline_name"],
    "airline_code"      => $prefix,
    "flight_number"     => $number,

    "origin_airport"    => $data["origin_airport"],
    "origin_city"       => $data["origin_city"],
    "origin_code"       => $data["origin_code"],

    "destination_airport" => $data["destination_airport"],
    "dest_city"         => $data["dest_city"],
    "dest_code"         => $data["dest_code"],

    "departure_date"    => $data["departure_date"],
    "departure_time"    => $data["departure_time"],
    "arrival_date"      => $data["arrival_date"],
    "arrival_time"      => $data["arrival_time"],

    "travel_duration"   => $data["travel_duration"],
    "price"             => $data["price"],
    "seat_quota"        => $data["seat_quota"]
];

echo json_encode($response);
exit;
