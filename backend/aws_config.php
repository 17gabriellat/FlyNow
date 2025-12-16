<?php
require_once __DIR__ . '/../env.php';

return [
    "region" => getenv("AWS_REGION"),
    "bucket" => getenv("AWS_BUCKET_NAME"),
    "access_key" => getenv("AWS_ACCESS_KEY_ID"),
    "secret_key" => getenv("AWS_SECRET_ACCESS_KEY"),
    "session_token" => getenv("AWS_SESSION_TOKEN"),
];
