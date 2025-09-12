<?php

require __DIR__ . '/../vendor/autoload.php';

use Frantzley\ExcelToMySQL;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=testdb;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Koneksyon ak DB an mache!\n";
} catch (PDOException $e) {
    echo "❌ Erè koneksyon ak DB: " . $e->getMessage() . "\n";
    exit;
}

$importer = new ExcelToMySQL(__DIR__ . "/data.xlsx", $pdo, __DIR__ . "/import.log");

$importer->setMapping([
    "Nom"   => "users.name",
    "Email" => "users.email",
    "Age"   => "users.age",
]);

$importer->setUniqueKey("email");

$importer->run();

$summary = $importer->getSummary();
echo "✅ Done imported! Nouvo: {$summary['inserted']} | Mizajou: {$summary['updated']}\n";
echo "Log file: " . __DIR__ . "/import.log\n";
