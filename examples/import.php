<?php

require __DIR__ . '/../vendor/autoload.php';

use Frantzley\ExcelToMySQL;

$pdo = new PDO("mysql:host=localhost;dbname=testdb;charset=utf8", "root", "");

// Chaje Excel la
$importer = new ExcelToMySQL(__DIR__ . "/data.xlsx", $pdo);

// Mapping Excel headers -> tab MySQL
$importer->setMapping([
    "Nom"   => "users.name",
    "Email" => "users.email",
    "Age"   => "users.age",
]);

// Nou di "email" se unique key pou evite doublon
$importer->setUniqueKey("email");

// Egzekite importation ak UPSERT
$importer->run();

echo "✅ Done imported with UPSERT (insert or update)!";
