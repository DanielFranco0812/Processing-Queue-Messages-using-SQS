<?php

require_once __DIR__ . '/config.php';

use Aws\Sqs\SqsClient;

$db = (new Lab\Database())->connect();

$sqsClient = new SqsClient([
    'region'  => $_ENV['AWS_REGION'],
    'version' => 'latest',
    'profile' => $_ENV['AWS_PROFILE'],
]);
$queueUrl = $_ENV['SQS_QUEUE_URL'];
if ($queueUrl === '') {
    throw new RuntimeException('Set the SQS_QUEUE_URL environment variable before running the worker.');
}

$mailtrapUsername = getenv('MAILTRAP_USERNAME');
$mailtrapPassword = getenv('MAILTRAP_PASSWORD');
if ($mailtrapUsername === false || $mailtrapUsername === '' || $mailtrapPassword === false || $mailtrapPassword === '') {
    throw new RuntimeException('Set the MAILTRAP_USERNAME and MAILTRAP_PASSWORD environment variables before running the worker.');
}

// Configure EmailSender
$emailSender = new Lab\EmailSender(
    getenv('MAILTRAP_HOST') ?: 'sandbox.smtp.mailtrap.io',
    (int)(getenv('MAILTRAP_PORT') ?: 2525),
    $mailtrapUsername,
    $mailtrapPassword,
    getenv('MAIL_FROM_ADDRESS') ?: 'marketing@example.com'
);

$worker = new Lab\QueueWorker($db, $sqsClient, $queueUrl, $emailSender);

echo "Starting worker loop...\n";
$processed = $worker->processMessages();
echo "Worker finished processing. Total processed: {$processed}\n";