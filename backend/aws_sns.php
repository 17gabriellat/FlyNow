<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;

/**
 * Publish message ke AWS SNS
 * Pakai IAM Role EC2 (LabInstanceProfile)
 */
function publishToSNS(array $payload)
{
    $region   = getenv("AWS_REGION");
    $topicArn = getenv("AWS_SNS_TOPIC_ARN_RESET_PASSWORD");

    if (!$region || !$topicArn) {
        throw new Exception("AWS_REGION atau TOPIC_ARN belum diset");
    }

    // SNS Client TANPA credentials → pakai IAM Role EC2
    $sns = new SnsClient([
        'region'  => $region,
        'version' => 'latest'
    ]);

    try {
        $result = $sns->publish([
            'TopicArn' => $topicArn,
            'Message'  => json_encode($payload, JSON_UNESCAPED_SLASHES),
        ]);

        return $result;

    } catch (AwsException $e) {

        // Debug log
        file_put_contents(
            __DIR__ . "/sns_publish_debug.txt",
            date('Y-m-d H:i:s') . "\n" .
            "ERROR: " . $e->getAwsErrorMessage() . "\n" .
            "CODE : " . $e->getAwsErrorCode() . "\n\n",
            FILE_APPEND
        );

        throw new Exception("SNS Publish failed: " . $e->getAwsErrorMessage());
    }
}
