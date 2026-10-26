<?php

require_once __DIR__ . '/config.php';

use Aws\Sqs\SqsClient;

$campaignId = $argv[1] ?? 'CAMP-001';

$queueUrl = $_ENV['SQS_QUEUE_URL'];
$awsRegion = $_ENV['AWS_REGION'];
$awsProfile = $_ENV['AWS_PROFILE'];

try {
    if ($queueUrl === '') {
        throw new RuntimeException('Set the SQS_QUEUE_URL environment variable before dispatching messages.');
    }

    $db = (new Lab\Database())->connect();

    $sqsClient = new SqsClient([
        'region'  => $awsRegion,
        'version' => 'latest',
        'profile' => $awsProfile,
    ]);

    $dispatcher = new Lab\QueueDispatcher($db, $sqsClient, $queueUrl);
    $count = $dispatcher->dispatchPending($campaignId);

    echo "Successfully dispatched {$count} messages to SQS.\n";
} catch (Exception $e) {
    echo "Dispatch error: " . $e->getMessage() . "\n";
}