<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * Upload file ke AWS S3
 * - Local: pakai env
 * - EC2: pakai IAM Role
 */
function s3_put_object(string $key, string $filePath, string $contentType): array
{
    $bucket = getenv("AWS_BUCKET_NAME");
    $region = getenv("AWS_REGION") ?: 'us-east-1';

    if (!$bucket) {
        return [
            "ok" => false,
            "error" => "AWS_BUCKET_NAME belum diset"
        ];
    }

    if (!file_exists($filePath)) {
        return [
            "ok" => false,
            "error" => "File tidak ditemukan: $filePath"
        ];
    }

    // ✅ REGION WAJIB ADA
    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => $region
        // ❗ credentials TIDAK PERLU → IAM Role EC2 otomatis
    ]);

    try {
        $result = $s3->putObject([
            'Bucket'      => $bucket,
            'Key'         => $key,
            'SourceFile'  => $filePath,
            'ContentType' => $contentType
            // ❌ JANGAN pakai ACL dulu
        ]);

        return [
            "ok"  => true,
            "key" => $key,
            "url" => $result['ObjectURL']
        ];

    } catch (AwsException $e) {

        file_put_contents(
            __DIR__ . "/s3_upload_debug.txt",
            date('Y-m-d H:i:s') . "\n" .
            "MESSAGE: " . $e->getMessage() . "\n" .
            "AWS ERROR: " . $e->getAwsErrorMessage() . "\n" .
            "CODE: " . $e->getAwsErrorCode() . "\n\n",
            FILE_APPEND
        );

        return [
            "ok" => false,
            "error" => $e->getAwsErrorMessage()
        ];
    }
}
