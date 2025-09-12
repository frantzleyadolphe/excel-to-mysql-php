# Excel to MySQL - Frantzley

A professional PHP package that imports Excel data directly into MySQL with UPSERT support.

## Installation
```bash
composer require phpoffice/phpspreadsheet
```
## Usage
```
use Frantzley\ExcelToMySQL;

$pdo = new PDO("mysql:host=localhost;dbname=testdb;charset=utf8", "root", "");

$importer = new ExcelToMySQL("data.xlsx", $pdo);

$importer->setMapping([
    "Nom"   => "users.name",
    "Email" => "users.email",
    "Age"   => "users.age"
]);

// Unique key for upsert
$importer->setUniqueKey("email");

$importer->run();
```
## SQL Example
```
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    age INT
);

```
## Features
```
✅ Import Excel to MySQL

✅ UPSERT (insert or update existing records)

✅ Exception handling

✅ Logging errors into a .log file

✅ Simple and professional PHP package
