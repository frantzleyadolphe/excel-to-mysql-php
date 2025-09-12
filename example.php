<?php
require 'vendor/autoload.php';

use YourNamespace\ExcelToMySQL\ExcelToMySQL;
use PDO;

$pdo = new PDO("mysql:host=localhost;dbname=testdb;charset=utf8mb4", "root", "");
$importer = new ExcelToMySQL($pdo, 'data.xlsx', 'users');
$importer->setMapping(
    ['Name' => 'name', 'Email' => 'email', 'Age' => 'age'],
    ['email' => 'email', 'age' => 'int']
);
$importer->run();
echo "Done importing!";
?>