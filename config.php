<?php

require_once __DIR__ . '/vendor/autoload.php';

// Set default environment variables if not present in the environment
$_ENV['DB_HOST'] = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
$_ENV['DB_PORT'] = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'marketing_lab';
$_ENV['DB_USER'] = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$_ENV['DB_PASSWORD'] = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

$_ENV['AWS_PROFILE'] = $_ENV['AWS_PROFILE'] ?? getenv('AWS_PROFILE') ?: 'default';
$_ENV['AWS_REGION'] = $_ENV['AWS_REGION'] ?? getenv('AWS_REGION') ?: 'ap-southeast-2';
$_ENV['SQS_QUEUE_URL'] = $_ENV['SQS_QUEUE_URL'] ?? getenv('SQS_QUEUE_URL') ?: '';