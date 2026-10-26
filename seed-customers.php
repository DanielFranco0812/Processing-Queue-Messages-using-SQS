<?php

require_once __DIR__ . '/config.php';

try {
    $db = (new Lab\Database())->connect();
    $faker = Faker\Factory::create('en_US');
    $faker->seed(2026);

    $seeder = new Lab\CustomerSeeder($db, $faker);
    $seeder->seed();

    echo "10,000 customers generated successfully!\n";
} catch (Exception $e) {
    echo "Seeding error: " . $e->getMessage() . "\n";
}