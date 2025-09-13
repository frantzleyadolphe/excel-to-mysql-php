## Excel to MySQL Web Importer
```
Yon aplikasyon web ki la pou ede w fasilman enpòte fichye Excel (.xls / .xlsx)
dirèkteman nan yon baz done MySQL.
Li fèt pou devlopè ak itilizatè ki bezwen yon travay rapid, epi li ofri
yon UI senp ak fonctionnalités avanse tankou live logs,
progress bar, ak filtre logs.
```
## Pwen kle
```
Backend ki otomatikman trete fichye Excel la → MySQL.
Kreye tab otomatik si li pa egziste.
Insert oswa update done selon yon kle inik men li opsyonel.
Mapping kolòn Excel pou kreye non kolonn yo → kolòn DB otomatik oswa ou kapab fel ou menm manyèl.
Log pou we jan pwosedi an ye ak koulè: ble (insert), jòn (exists), wouj (erè).
```
## 🛠 Features
```
Fully ready-to-use: pa bezwen kreye process.php oswa modifye kòd.

Mapping kolòn otomatik soti nan header Excel.

Opsyon pou mapping manyèl.

Afichaj log vivan pandan import.

Travay ak .xlsx Excel files.
```
## 💻 Installation
```
Enstale via Composer:

composer require frantzley/excel-to-mysql

Kopi public/ folder ki enkli tout frontend + backend nan pwojè w la (pa bezwen modifye li). Estrikti a ap sanble konsa:
```
project-root/
├─ public/
│  ├─ index.php      # Upload form ak UI
│  ├─ process.php    # Backend processing (pre-bati)
├─ src/
│  └─ ExcelToMySQL.php
├─ uploads/          # Folder pou fichye upload (kreye otomatik si li pa egziste)
├─ vendor/           # Composer dependencies
```

Asire w gen PhpSpreadsheet enstale:

composer require phpoffice/phpspreadsheet
```
## ⚡ Usage
```
Louvri browser ou sou public/index.php.

Chwazi fichye Excel ou a, antre non tab la nan DB, ak kle inik si ou vle.

Klike Upload & Import.

Log yo ap parèt vivan ak koulè selon aksyon yo:

Ble → nouvo insert

Jòn → deja egziste

Wouj → erè
```
## 🔧 Column Mapping
```
Otomatik: Premye ranje nan Excel la sèvi kòm header; kolòn DB yo pran menm non.

Manyèl: Ou ka chanje kolòn DB yo si bezwen:
```
$importer->setMapping([
    "Excel Name"  => "db_name",
    "Excel Email" => "db_email"
]);
```

Tab la kreye otomatikman selon mapping lan.
```
## 📦 Example PHP Usage
```
Ou ka itilize klas ExcelToMySQL nan pwòp kòd PHP ou tou:
```
require __DIR__ . '/vendor/autoload.php';

use Frantzley\ExcelToMySQL;

$pdo = new PDO("mysql:host=localhost;dbname=testdb;charset=utf8mb4", "root", "");
$importer = new ExcelToMySQL("uploads/data.xlsx", $pdo);

$importer->setTableName("users");
$importer->setUniqueKey("email");
$importer->setMapping([
    "Name"  => "name",
    "Email" => "email",
    "Phone" => "phone"
]);

$importer->createTableIfNotExists(array_values($importer->getMapping()));
$result = $importer->insertOrUpdateRow([
    "name"  => "John Doe",
    "email" => "john@example.com",
    "phone" => "123456789"
]);

print_r($result);
```
```
## ⚙ Requirements
```
PHP >= 8.0

MySQL

PhpSpreadsheet
```
## ✅ Key Point pou Itilizatè
```
Pa bezwen kreye process.php. Tout backend processing deja enkli nan package la.

Yo sèlman bezwen navigue nan index.php, upload fichye Excel, epi log ap montre yo insert / exists / error vivan.

Tout folder nesesè (uploads/) kreye otomatikman si li pa egziste.
