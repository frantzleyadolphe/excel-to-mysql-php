# Excel to MySQL - Frantzley

A professional PHP package that imports Excel data directly into MySQL with UPSERT support.

## Installation
```bash
composer require frantzley/excel-to-mysql
```
## Usage with limit and specific tableName
```
use Frantzley\ExcelToMySQL;

$importer = new ExcelToMySQL(__DIR__ . "/data.xlsx", $pdo, __DIR__ . "/import.log");

// Defini mapping
$importer->setMapping([
    "Nom"   => "users.name",
    "Email" => "users.email",
    "Age"   => "users.age"
]);

// Fòse li pran tab "users" menm si mapping gen lòt non
$importer->setTableName("users");

// Set unique key pou upsert
$importer->setUniqueKey("email");

// Limite a sèlman 10 liy
$importer->setLimitRows(10);

// Kouri import
$importer->run();

$summary = $importer->getSummary();
echo "✅ Done imported! Nouvo: {$summary['inserted']} | Mizajou: {$summary['updated']}\n";
echo "Log file: " . __DIR__ . "/import.log\n";

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
