## Excel to MySQL Web Importer
```
Yon aplikasyon web ki la pou ede w fasilman enpòte fichye Excel (.xls / .xlsx)
dirèkteman nan yon baz done MySQL.
Li fèt pou devlopè ak itilizatè ki bezwen yon travay rapid, epi li ofri
yon UI senp ak fonctionnalités avanse tankou live logs,
progress bar, ak filtre logs.
```
Karakteristik prensipal
```
Upload yon fichye Excel ak antre table name ak optional unique key.

Live logs pandan chak liy ap trete, ak koulè diferan pou insert, update, ak error.

Progress bar ki montre pwogrè import nan tan reyèl.

Filtrage logs pou wè sèlman insert, update, error oswa tout logs.

Stream logs soti nan PHP (process.php) san rete,kreye pou sipote gwo fichye Excel.

Kenbe enkapsulasyon klas ExcelToMySQL ak metòd importRow() pou insert/update chak liy.

Konplètman styled ak Tailwind CSS.
```
##Teknoloji itilize
``` 
✅ PHP 8+

✅ MySQL

✅ PhpSpreadsheet

✅ Tailwind CSS

✅ Vanilla JavaScript (fetch + streaming JSON)
```
## Itilizasyon
```
---> Upload yon fichye Excel.

---> Antre non tab la ak kolòn kle inik (si genyen).

---> Klike Kòmanse Import.

---> Swiv logs ak progress bar an tan reyèl.

---> Sèvi ak dropdown filtre logs pou wè sèlman sa ou vle.
```
## Installation
```bash
composer require frantzley/excel-to-mysql
```
## Usage with limit and specific tableName
```
```
kreye yon fichier process.php ou kapab rele li janw vle tou
epi mete kod sa yo ak estrikti ou vle tab lan genyen ki match ak excel file lan
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

//  import
$importer->run();

$summary = $importer->getSummary();
echo "✅ Done imported! Nouvo: {$summary['inserted']} | Mizajou: {$summary['updated']}\n";
echo "Log file: " . __DIR__ . "/import.log\n";
```
Lanse script lan ak PHP CLI siw se devlope pou pi rapid
```
php process.php

```
Pou lanse web entefas lan 
```
Ale nan folder public/:
```
---> cd public
```
epi answit
```
--->php -S localhost:8000 -t .
```
ouvri navigatè ou sou:
http://localhost:8000 oubyen port ou genyen 
Upload fichye Excel ou a, chwazi non tab ou ak unique key si ou vle.
```
## SQL Example
```
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    age INT
);


