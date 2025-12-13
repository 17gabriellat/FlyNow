<?php
session_start();
require_once "db.php"; // koneksi $conn

/* =============================================================
   1. MUST LOGIN
============================================================= */
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "You must login before booking.";
    header("Location: ../login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id_user'];

/* =============================================================
   2. VALIDATE INPUT
============================================================= */
$departure_id = $_POST['departure_id'] ?? null;
$return_id    = $_POST['return_id'] ?? null;

$payment_method = $_POST['payment_method'] ?? null;
$credit_card_number = $_POST['credit_card_number'] ?? null;

$passenger_type = $_POST['passenger_type'] ?? [];
$title          = $_POST['title'] ?? [];
$full_name      = $_POST['full_name'] ?? [];
$nik            = $_POST['nik'] ?? [];
$mother_name    = $_POST['mother_name'] ?? []; // only for child

if (!$departure_id || !$payment_method) {
    $_SESSION['error'] = "Missing required booking data.";
    header("Location: ../index.php");
    exit;
}

if ($payment_method === "credit_card" && empty($credit_card_number)) {
    $_SESSION['error'] = "Credit card number is required.";
    header("Location: ../booking.php?departure_id=$departure_id&return_id=$return_id");
    exit;
}

$passenger_count = count($passenger_type);

if ($passenger_count == 0) {
    $_SESSION['error'] = "Please add at least 1 passenger.";
    header("Location: ../booking.php?departure_id=$departure_id&return_id=$return_id");
    exit;
}

/* =============================================================
   3. GET FLIGHT PRICES
============================================================= */
$conn->begin_transaction();
function getFlightDataLocked($id, $conn) {
    $stmt = $conn->prepare("
        SELECT f.*, 
               a.airline_name, a.airline_code,
               o.airport_code AS origin_code,
               d.airport_code AS dest_code
        FROM flights f
        JOIN airlines a ON f.airline_id = a.id_airline
        JOIN airports o ON f.origin_airport = o.id_airport
        JOIN airports d ON f.destination_airport = d.id_airport
        WHERE f.id_flight = ?
        FOR UPDATE
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$departure_data = getFlightDataLocked($departure_id, $conn);

$available_departure = 
    $departure_data['seat_quota'] - $departure_data['booked_seats'];

if ($available_departure < $passenger_count) {
    $conn->rollback();
    $_SESSION['error'] = "Not enough seats available for departure flight.";
    header("Location: ../booking.php?departure_id=$departure_id&return_id=$return_id");
    exit;
}

$return_data = null;

if ($return_id) {
    $return_data = getFlightDataLocked($return_id, $conn);

    $available_return =
        $return_data['seat_quota'] - $return_data['booked_seats'];

    if ($available_return < $passenger_count) {
        $conn->rollback();
        $_SESSION['error'] = "Not enough seats available for return flight.";
        header("Location: ../booking.php?departure_id=$departure_id&return_id=$return_id");
        exit;
    }
}

$conn->commit();

$price_departure = $departure_data['price'];
$price_return = $return_data ? $return_data['price'] : 0;

$total_base_price = ($price_departure + $price_return) * $passenger_count;

// ------------------------------------------------------------
// EXTRA. GENERATE BOOKING CODE
// ------------------------------------------------------------
// date_default_timezone_set("Asia/Jakarta");

$todayCode = date("dmy");       // DDMMYY
$userCode  = $user_id;          // gunakan seluruh user_id
$today     = date("Y-m-d");

// Hitung order ke berapa user hari ini
$stmtCount = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM transactions 
    WHERE user_id = ? AND DATE(created_at) = ?
");
$stmtCount->bind_param("is", $user_id, $today);
$stmtCount->execute();
$count = $stmtCount->get_result()->fetch_assoc()['total'];
$orderNumber = $count + 1;

// format final
$booking_code = "FLYN{$todayCode}{$userCode}{$orderNumber}";


/* =============================================================
   4. INSERT INTO transactions
============================================================= */
$stmt = $conn->prepare("
    INSERT INTO transactions 
    (user_id, booking_code, departure_flight_id, return_flight_id, 
     total_passengers, total_price, payment_method, 
     payment_status, credit_card_number)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'paid', ?)
");

$stmt->bind_param(
    "isiiisss",
    $user_id,
    $booking_code,
    $departure_id,
    $return_id,
    $passenger_count,
    $total_base_price,
    $payment_method,
    $credit_card_number
);

$stmt->execute();
$transaction_id = $stmt->insert_id;

/* =============================================================
   5. INSERT transaction_flights
============================================================= */
$stmt_f = $conn->prepare("
    INSERT INTO transaction_flights
    (transaction_id, flight_id, direction, total_price)
    VALUES (?, ?, ?, ?)
");

// departure
$direction = "departure";
$stmt_f->bind_param("iiss", $transaction_id, $departure_id, $direction, $price_departure);
$stmt_f->execute();

// return
if ($return_id) {
    $direction = "return";
    $stmt_f->bind_param("iiss", $transaction_id, $return_id, $direction, $price_return);
    $stmt_f->execute();
}

/* =============================================================
   6. INSERT PASSENGERS
============================================================= */
$stmt_p = $conn->prepare("
    INSERT INTO transaction_passengers
    (transaction_id, passenger_type, title, full_name, nik, mother_name)
    VALUES (?, ?, ?, ?, ?, ?)
");

$child = 0;
foreach ($passenger_type as $i => $pt) {

    $ti = $title[$i];
    $fn = $full_name[$i];
    $nk = $nik[$i];
    $mn = ($pt === "child") ? ($mother_name[$child++] ?? null) : null;

    $stmt_p->bind_param(
        "isssss",
        $transaction_id,
        $pt,
        $ti,
        $fn,
        $nk,
        $mn
    );

    $stmt_p->execute();
}

$stmtSeat = $conn->prepare("
    UPDATE flights
    SET booked_seats = booked_seats + ?
    WHERE id_flight = ?
");

$stmtSeat->bind_param("ii", $passenger_count, $departure_id);
$stmtSeat->execute();

if ($return_id) {
    $stmtSeat->bind_param("ii", $passenger_count, $return_id);
    $stmtSeat->execute();
}



/* =============================================================
   7. PREPARE FULL PAYLOAD FOR EMAIL (SNS / Lambda)
============================================================= */

// Fetch all passengers inserted
$passenger_list = [];
$res_p = $conn->query("SELECT * FROM transaction_passengers WHERE transaction_id = $transaction_id");
while ($row = $res_p->fetch_assoc()) {
    $passenger_list[] = $row;
}

if($payment_method === "va_bca") {
    $payment_method = "Virtual Account BCA";
} else if($payment_method === "credit_card") {
    $payment_method = "Credit Card";
}

$emailPayload = [
    "transaction_id" => $transaction_id,
    "user_email"     => $user['email'],
    "user_name"      => $user['full_name'],

    "booking_code" => $booking_code,

    "payment_method" => $payment_method,
    "total_price"    => $total_base_price,
    "total_passengers" => $passenger_count,

    "departure" => $departure_data,
    "return"    => $return_data,
    "passengers" => $passenger_list
];

// Save JSON ke file debug lokal (tidak di production)
file_put_contents(__DIR__."/../debug_email_payload.json", json_encode($emailPayload, JSON_PRETTY_PRINT));

/* =============================================================
   8. SEND TO SNS  (MASIH DIMATIKAN SEMENTARA)
============================================================= */

// TODO: nanti hidupkan kembali setelah Lambda siap

function signRequest($method, $service, $region, $host, $uri, $payload, $aws_key, $aws_secret, $aws_token)
{
    $t = gmdate("Ymd\THis\Z");
    $d = gmdate("Ymd");

    $canonical_headers = "content-type:application/x-www-form-urlencoded\nhost:$host\nx-amz-date:$t\nx-amz-security-token:$aws_token\n";
    $signed_headers = "content-type;host;x-amz-date;x-amz-security-token";

    $hashed_payload = hash("sha256", $payload);

    $canonical_request = "$method\n$uri\n\n$canonical_headers\n$signed_headers\n$hashed_payload";
    $hashed_canonical_request = hash("sha256", $canonical_request);

    $credential_scope = "$d/$region/$service/aws4_request";
    $string_to_sign = "AWS4-HMAC-SHA256\n$t\n$credential_scope\n$hashed_canonical_request";

    // signing key
    $kDate = hash_hmac("sha256", $d, "AWS4" . $aws_secret, true);
    $kRegion = hash_hmac("sha256", $region, $kDate, true);
    $kService = hash_hmac("sha256", $service, $kRegion, true);
    $kSigning = hash_hmac("sha256", "aws4_request", $kService, true);

    $signature = hash_hmac("sha256", $string_to_sign, $kSigning);

    $authorization_header =
        "AWS4-HMAC-SHA256 Credential=$aws_key/$credential_scope, SignedHeaders=$signed_headers, Signature=$signature";

    return [
        "x-amz-date" => $t,
        "x-amz-security-token" => $aws_token,
        "Authorization" => $authorization_header
    ];
}



$topicArn = getenv("AWS_SNS_TOPIC_ARN_TRANSACTION");

$region   = getenv("AWS_REGION");
$service  = getenv("AWS_SNS_SERVICE");

$messageJson = json_encode($emailPayload);

$sns_url = "https://$service.$region.amazonaws.com/";

$aws_key    = getenv("AWS_ACCESS_KEY_ID");
$aws_secret = getenv("AWS_SECRET_ACCESS_KEY");
$aws_token  = getenv("AWS_SESSION_TOKEN");

$host = "$service.$region.amazonaws.com";
$uri = "/";

$messageJson = json_encode($emailPayload);

$payload = http_build_query([
    "Action" => "Publish",
    "TopicArn" => $topicArn,
    "Message" => $messageJson,
    "Version" => "2010-03-31"
]);

$headers = signRequest(
    "POST",
    $service,
    $region,
    $host,
    $uri,
    $payload,
    $aws_key,
    $aws_secret,
    $aws_token
);

$curl = curl_init("https://$host");

curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Content-Type: application/x-www-form-urlencoded",
    "Host: $host",
    "X-Amz-Date: {$headers['x-amz-date']}",
    "X-Amz-Security-Token: $aws_token",
    "Authorization: {$headers['Authorization']}"
]);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);
$error = curl_error($curl);

file_put_contents(__DIR__ . "/../sns_debug_response.txt", "RESPONSE:\n$response\nERROR:\n$error");

curl_close($curl);



/* =============================================================
   9. ALL DONE → Redirect to success page
============================================================= */
$_SESSION['success'] = "Booking successful! Your E-ticket has been sent to your email.";

header("Location: ../success_payment.php?id=$transaction_id");
exit;

?>
