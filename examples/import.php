<?php

require __DIR__ . '/../vendor/autoload.php';

use Frantzley\ExcelToMySQL;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=testdb;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $importer = new ExcelToMySQL(__DIR__ . "/data.xlsx", $pdo, __DIR__ . "/import.log");
// Mapping Excel headers -> tab MySQL/kolòn
    $importer->setMapping([
        "Nom"   => "users.name",
        "Email" => "users.email",
        "Age"   => "users.age",
    ]);
// Nou di "email" se unique key pou evite doublon si tout fwa se ta nan yon table ki gen email as yon
//chan men ou kapab mete nenpòt chan ki inik nan tab la si w vle wap jis chanje nom an nan paramèt la
    $importer->setUniqueKey("email"); // upsert sou email

// Egzekite importation ak UPSERT
    $importer->run();

    $summary = $importer->getSummary();
    echo "✅ Done imported with UPSERT (insert or update)! Nouvo: {$summary['inserted']} | Mizajou: {$summary['updated']}" . PHP_EOL;
    echo "Log file: " . __DIR__ . "/import.log" . PHP_EOL;

} catch (Exception $e) {
    echo "❌ Erè: " . $e->getMessage() . PHP_EOL;
}
