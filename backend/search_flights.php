<?php
session_start();
require_once "db.php";

$from        = $_POST["from"] ?? "";
$to          = $_POST["to"] ?? "";
$departure   = $_POST["departure"] ?? "";
$return_date = $_POST["return_date"] ?? "";   // FE uses returnDate

// Basic validation
if ($from === "" || $to === "" || $departure === "") {
    $_SESSION["error"] = "Please fill all required fields.";
    header("Location: ../index.php");
    exit;
}

/* ==========================================================
   GET AIRPORT IDs (based on airport_code)
   ========================================================== */
$stmt = $conn->prepare("
    SELECT id_airport, airport_code 
    FROM airports 
    WHERE airport_code IN (?, ?)
");
$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$res = $stmt->get_result();

$airport_ids = [];
while ($row = $res->fetch_assoc()) {
    $airport_ids[$row["airport_code"]] = $row["id_airport"];
}

if (!isset($airport_ids[$from]) || !isset($airport_ids[$to])) {
    $_SESSION["error"] = "Unknown airport code.";
    header("Location: ../index.php");
    exit;
}

$origin_id = $airport_ids[$from];
$dest_id   = $airport_ids[$to];

/* ==========================================================
   BASE FLIGHT QUERY TEMPLATE
   ========================================================== */
$sql = "
    SELECT 
        f.*, 
        a.airline_name, 
        a.airline_code,
        o.airport_code AS origin_code,
        d.airport_code AS destination_code
    FROM flights f
    JOIN airlines a ON f.airline_id = a.id_airline
    JOIN airports o ON f.origin_airport = o.id_airport
    JOIN airports d ON f.destination_airport = d.id_airport
    WHERE f.origin_airport = ?
      AND f.destination_airport = ?
      AND f.departure_date = ?
      AND f.seat_quota > f.booked_seats
    ORDER BY f.departure_time ASC
";

/* ==========================================================
   ONE-WAY FLIGHTS
   ========================================================== */
$q1 = $conn->prepare($sql);
$q1->bind_param("iis", $origin_id, $dest_id, $departure);
$q1->execute();
$oneway = $q1->get_result()->fetch_all(MYSQLI_ASSOC);

/* ==========================================================
   RETURN FLIGHTS (OPTIONAL)
   ========================================================== */
$return_flights = [];

if (!empty(trim($return_date))) {
    $q2 = $conn->prepare($sql);
    // reverse direction for return trip
    $q2->bind_param("iis", $dest_id, $origin_id, $return_date);
    $q2->execute();
    $return_flights = $q2->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ==========================================================
   NO RESULT HANDLER
   ========================================================== */
if (empty($oneway) && empty($return_flights)) {
    $_SESSION["error"] = "No flights found for your search.";
    header("Location: ../index.php");
    exit;
}

/* ==========================================================
   SAVE RESULTS TO SESSION
   ========================================================== */
$_SESSION["search_results"] = [
    "from"         => $from,
    "to"           => $to,
    "departure"    => $departure,
    "return_date"  => $return_date,
    "oneway"       => $oneway,
    "return"       => $return_flights
];

header("Location: ../hasil_pencarian.php");
exit;
