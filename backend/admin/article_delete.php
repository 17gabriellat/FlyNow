<?php
require_once "../db.php";
require_once "../../env.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

/* =======================
   FETCH ARTICLE
======================= */
$stmt = $conn->prepare("SELECT image FROM articles WHERE id_article = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$article = $res->fetch_assoc();
$stmt->close();

if (!$article) {
    echo json_encode(['success' => false, 'message' => 'Article not found']);
    exit;
}

$imageKey = $article['image'];

/* =======================
   DELETE DB FIRST
======================= */
$stmt = $conn->prepare("DELETE FROM articles WHERE id_article = ?");
$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'DB delete failed']);
    exit;
}
$stmt->close();

/* =======================
   DELETE S3 OBJECT (NO SDK)
======================= */
if ($imageKey) {
    deleteS3Object($imageKey);
    error_log("S3 DELETE KEY = " . $imageKey);
}

echo json_encode(['success' => true]);
exit;


/* =======================
   FUNCTION: DELETE S3
======================= */
function deleteS3Object($key)
{
    $bucket = getenv("AWS_BUCKET_NAME");
    $region = getenv("AWS_REGION");
    $accessKey = getenv("AWS_ACCESS_KEY_ID");
    $secretKey = getenv("AWS_SECRET_ACCESS_KEY");

    $host = "{$bucket}.s3.{$region}.amazonaws.com";
    $uri = "/" . ltrim($key, "/");

    $amzDate = gmdate("Ymd\THis\Z");
    $date = gmdate("Ymd");

    $payloadHash = hash("sha256", "");

    $canonicalHeaders =
        "host:$host\n" .
        "x-amz-content-sha256:$payloadHash\n" .
        "x-amz-date:$amzDate\n";

    $signedHeaders = "host;x-amz-content-sha256;x-amz-date";

    $canonicalRequest =
        "DELETE\n" .
        "$uri\n" .
        "\n" .
        "$canonicalHeaders\n" .
        "$signedHeaders\n" .
        "$payloadHash";

    $scope = "$date/$region/s3/aws4_request";

    $stringToSign =
        "AWS4-HMAC-SHA256\n" .
        "$amzDate\n" .
        "$scope\n" .
        hash("sha256", $canonicalRequest);

    $kDate = hash_hmac("sha256", $date, "AWS4$secretKey", true);
    $kRegion = hash_hmac("sha256", $region, $kDate, true);
    $kService = hash_hmac("sha256", "s3", $kRegion, true);
    $kSigning = hash_hmac("sha256", "aws4_request", $kService, true);

    $signature = hash_hmac("sha256", $stringToSign, $kSigning);

    $authorization =
        "AWS4-HMAC-SHA256 Credential=$accessKey/$scope, " .
        "SignedHeaders=$signedHeaders, Signature=$signature";

    $headers = [
        "Authorization: $authorization",
        "x-amz-date: $amzDate",
        "x-amz-content-sha256: $payloadHash"
    ];

    $ch = curl_init("https://$host$uri");
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // OPTIONAL DEBUG
    error_log("S3 DELETE HTTP CODE = $httpCode");
    error_log($response);
}
