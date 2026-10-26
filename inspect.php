<?php

require_once __DIR__ . '/config.php';

try {
    $db = (new Lab\Database())->connect();

    // Customer summary
    $totalCustomers = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $optedOut = $db->query("SELECT COUNT(*) FROM customers WHERE marketing_opt_in = 0")->fetchColumn();
    $optedIn = $db->query("SELECT COUNT(*) FROM customers WHERE marketing_opt_in = 1")->fetchColumn();

    echo "Total Customers: {$totalCustomers}\n";
    echo "Opted-Out (0): {$optedOut}\n";
    echo "Opted-In (1): {$optedIn}\n\n";

    // Outbox summary
    $outboxQueued = $db->query("SELECT COUNT(*) FROM marketing_outbox WHERE status = 'QUEUED'")->fetchColumn();
    $outboxPending = $db->query("SELECT COUNT(*) FROM marketing_outbox WHERE status = 'PENDING'")->fetchColumn();

    echo "--- Outbox Status ---\n";
    echo "PENDING: {$outboxPending}\n";
    echo "QUEUED: {$outboxQueued}\n\n";

    // Sent marketing emails summary
    $totalSent = $db->query("SELECT COUNT(*) FROM sent_marketing_emails WHERE status = 'SENT'")->fetchColumn();
    $totalSkipped = $db->query("SELECT COUNT(*) FROM sent_marketing_emails WHERE status = 'SKIPPED'")->fetchColumn();

    echo "--- Sent Emails Table ---\n";
    echo "SENT: {$totalSent}\n";
    echo "SKIPPED: {$totalSkipped}\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}