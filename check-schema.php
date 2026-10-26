<?php

require_once __DIR__ . '/config.php';

try {
    $db = (new Lab\Database())->connect();

    echo "--- sent_marketing_emails Columns ---\n";
    $cols = $db->query("SHOW COLUMNS FROM sent_marketing_emails")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }

    echo "\n--- marketing_outbox Columns ---\n";
    $cols = $db->query("SHOW COLUMNS FROM marketing_outbox")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}