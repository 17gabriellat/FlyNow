<?php

function s3_put_object(array $aws, string $key, string $filePath, string $contentType): array
{
    $region = $aws["region"];
    $bucket = $aws["bucket"];
    $accessKey = $aws["access_key"];
    $secretKey = $aws["secret_key"];
    $sessionToken = $aws["session_token"];

    if (!$region || !$bucket || !$accessKey || !$secretKey || !$sessionToken) {
        return ["ok" => false, "error" => "AWS credentials or bucket config is missing."];
    }

    if (!file_exists($filePath)) {
        return ["ok" => false, "error" => "File not found."];
    }

    $fileBody = file_get_contents($filePath);
    if ($fileBody === false) {
        return ["ok" => false, "error" => "Failed to read file."];
    }

    $service = "s3";
    $host = "{$bucket}.s3.{$region}.amazonaws.com";

    $amzDate = gmdate("Ymd\THis\Z");
    $dateStamp = gmdate("Ymd");

    $payloadHash = hash("sha256", $fileBody);

    $encodedKey = implode("/", array_map("rawurlencode", explode("/", $key)));
    $canonicalUri = "/" . $encodedKey;

    $canonicalQueryString = "";

    $canonicalHeaders =
        "content-type:" . $contentType . "\n" .
        "host:" . $host . "\n" .
        "x-amz-content-sha256:" . $payloadHash . "\n" .
        "x-amz-date:" . $amzDate . "\n" .
        "x-amz-security-token:" . $sessionToken . "\n";

    $signedHeaders = "content-type;host;x-amz-content-sha256;x-amz-date;x-amz-security-token";

    $canonicalRequest =
        "PUT\n" .
        $canonicalUri . "\n" .
        $canonicalQueryString . "\n" .
        $canonicalHeaders . "\n" .
        $signedHeaders . "\n" .
        $payloadHash;

    $algorithm = "AWS4-HMAC-SHA256";
    $credentialScope = $dateStamp . "/" . $region . "/" . $service . "/aws4_request";
    $stringToSign =
        $algorithm . "\n" .
        $amzDate . "\n" .
        $credentialScope . "\n" .
        hash("sha256", $canonicalRequest);

    $signingKey = getSignatureKey($secretKey, $dateStamp, $region, $service);
    $signature = hash_hmac("sha256", $stringToSign, $signingKey);

    $authorizationHeader =
        $algorithm .
        " Credential=" . $accessKey . "/" . $credentialScope .
        ", SignedHeaders=" . $signedHeaders .
        ", Signature=" . $signature;

    $url = "https://" . $host . $canonicalUri;

    $headers = [
        "Content-Type: " . $contentType,
        "Host: " . $host,
        "X-Amz-Date: " . $amzDate,
        "X-Amz-Content-Sha256: " . $payloadHash,
        "X-Amz-Security-Token: " . $sessionToken,
        "Authorization: " . $authorizationHeader,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileBody);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $responseBody = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($curlErr) {
        return ["ok" => false, "error" => "cURL error: " . $curlErr];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            "ok" => true,
            "key" => $key,
            "url" => "https://" . $host . "/" . $encodedKey,
            "http" => $httpCode
        ];
    }

    return [
        "ok" => false,
        "error" => "S3 upload failed",
        "http" => $httpCode,
        "response" => $responseBody
    ];
}

function getSignatureKey(string $key, string $dateStamp, string $regionName, string $serviceName)
{
    $kDate = hash_hmac("sha256", $dateStamp, "AWS4" . $key, true);
    $kRegion = hash_hmac("sha256", $regionName, $kDate, true);
    $kService = hash_hmac("sha256", $serviceName, $kRegion, true);
    return hash_hmac("sha256", "aws4_request", $kService, true);
}
