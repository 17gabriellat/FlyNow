<?php
require_once __DIR__ . '/../env.php';

function publishToSNS(array $payload)
{
    $region   = getenv("AWS_REGION");
    $service  = getenv("AWS_SNS_SERVICE");

    $topicArn = getenv("AWS_SNS_TOPIC_ARN_RESET_PASSWORD"); // contoh: arn:aws:sns:us-east-1:xxxx:flynow-email-topic

    // Learner Lab Credentials (ganti tiap lab restart)
    $accessKey = getenv("AWS_ACCESS_KEY_ID");
    $secretKey = getenv("AWS_SECRET_ACCESS_KEY");
    $sessionToken = getenv("AWS_SESSION_TOKEN");

    // ====== ENDPOINT ======
    $host = "$service.$region.amazonaws.com";
    $endpoint = "https://$host/";

    // ====== REQUEST PARAMS (x-www-form-urlencoded) ======
    $messageJson = json_encode($payload, JSON_UNESCAPED_SLASHES);

    $params = [
        "Action"  => "Publish",
        "TopicArn"=> $topicArn,
        "Message" => $messageJson,
        "Version" => "2010-03-31"
    ];

    // form body (sorted)
    ksort($params);
    $body = http_build_query($params);

    // ====== SIGV4 ======
    $amzDate = gmdate("Ymd\THis\Z");
    $dateStamp = gmdate("Ymd");

    $canonicalUri = "/";
    $canonicalQueryString = "";
    $canonicalHeaders =
        "content-type:application/x-www-form-urlencoded\n" .
        "host:$host\n" .
        "x-amz-date:$amzDate\n" .
        "x-amz-security-token:$sessionToken\n";

    $signedHeaders = "content-type;host;x-amz-date;x-amz-security-token";

    $payloadHash = hash("sha256", $body);

    $canonicalRequest =
        "POST\n" .
        $canonicalUri . "\n" .
        $canonicalQueryString . "\n" .
        $canonicalHeaders . "\n" .
        $signedHeaders . "\n" .
        $payloadHash;

    $algorithm = "AWS4-HMAC-SHA256";
    $credentialScope = "$dateStamp/$region/$service/aws4_request";
    $stringToSign =
        $algorithm . "\n" .
        $amzDate . "\n" .
        $credentialScope . "\n" .
        hash("sha256", $canonicalRequest);

    // signing key
    $kDate = hash_hmac("sha256", $dateStamp, "AWS4" . $secretKey, true);
    $kRegion = hash_hmac("sha256", $region, $kDate, true);
    $kService = hash_hmac("sha256", $service, $kRegion, true);
    $kSigning = hash_hmac("sha256", "aws4_request", $kService, true);

    $signature = hash_hmac("sha256", $stringToSign, $kSigning);

    $authorizationHeader =
        $algorithm . " " .
        "Credential=" . $accessKey . "/" . $credentialScope . ", " .
        "SignedHeaders=" . $signedHeaders . ", " .
        "Signature=" . $signature;

    // ====== CURL ======
    $headers = [
        "Content-Type: application/x-www-form-urlencoded",
        "Host: $host",
        "X-Amz-Date: $amzDate",
        "X-Amz-Security-Token: $sessionToken",
        "Authorization: $authorizationHeader"
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Debug file biar gampang cek
    file_put_contents(__DIR__ . "/sns_publish_debug.txt",
        "HTTP: $httpCode\nERR: $err\nRESPONSE:\n$response\n\n", FILE_APPEND
    );

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("SNS Publish failed. HTTP=$httpCode. Check sns_publish_debug.txt");
    }

    return $response;
}
