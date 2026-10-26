<?php

require_once __DIR__ . '/config.php';

try {
    $db = (new Lab\Database())->connect();

    // Clear the staging outbox records since SQS processing is complete
    $deleted = $db->exec("DELETE FROM marketing_outbox");

    echo "Cleared {$deleted} items from marketing_outbox.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}