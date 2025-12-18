<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * Upload file ke AWS S3
 * Pakai IAM Role EC2 (tanpa Access Key manual)
 */
function s3_put_object(string $key, string $filePath, string $contentType): array
{
    $bucket = getenv("AWS_S3_BUCKET");

    if (!$bucket) {
        return [
            "ok" => false,
            "error" => "AWS_S3_BUCKET belum diset"
        ];
    }

    if (!file_exists($filePath)) {
        return [
            "ok" => false,
            "error" => "File tidak ditemukan"
        ];
    }

    // S3 Client TANPA credentials → pakai IAM Role EC2
    $s3 = new S3Client([
        'version' => 'latest'
    ]);

    try {
        $result = $s3->putObject([
            'Bucket'      => $bucket,
            'Key'         => $key,
            'SourceFile'  => $filePath,
            'ContentType' => $contentType,
            'ACL'         => 'public-read', // hapus kalau bucket private
        ]);

        return [
            "ok"  => true,
            "key" => $key,
            "url" => $result['ObjectURL']
        ];

    } catch (AwsException $e) {

        // Debug log (konsisten dengan SNS)
        file_put_contents(
            __DIR__ . "/s3_upload_debug.txt",
            date('Y-m-d H:i:s') . "\n" .
            "ERROR: " . $e->getAwsErrorMessage() . "\n" .
            "CODE : " . $e->getAwsErrorCode() . "\n\n",
            FILE_APPEND
        );

        return [
            "ok"    => false,
            "error" => "S3 upload failed: " . $e->getAwsErrorMessage()
        ];
    }
}
