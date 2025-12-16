<?php
require_once __DIR__ . '/../env.php';

$region = getenv('AWS_REGION');
$bucket = getenv('AWS_BUCKET_NAME');

$url = "https://$bucket.s3.$region.amazonaws.com/";
