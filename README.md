## Excel to MySQL Web Importer
```
Yon pake PHP ki pèmèt ou enpòte done soti nan fichye Excel (`.xls` / `.xlsx`)
nan yon baz done MySQL avèk tout opsyon sa yo ki nan pwen kle
Li fèt pou devlopè ak itilizatè ki bezwen yon travay rapid, epi li ofri
yon UI senp ak fonctionnalités avanse tankou live logs,
progress bar, ak filtre logs.




```
## Enstalasyon via Composer

```
bash
composer require frantzley/excel-to-mysql

```
## 🛠 Features
```
- Kreye baz done otomatik si li pa egziste.
- Kreye tab otomatik selon headers nan Excel.
- Insert / Update done otomatik.
- Kle inik pou evite doublon.
- Logs dinamik ak filtraj (`insert`, `update`, `exists`, `error`, `info`).
- Progress bar pou montre pwogrè.
- Mesaj erè koneksyon nan logs UI.
- Responsiv UI ak Tailwind CSS ak glassmorphism effect pou logs.

---
```
## 💻 Installation
```
Enstale via Composer:
```

```
composer require frantzley/excel-to-mysql
```
## Estrikti Projet an
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
```
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

$importer->setMapping([
    "Excel Name"  => "db_name",
    "Excel Email" => "db_email"
]);

Tab la kreye otomatikman selon mapping lan.
```
## 📦 Example PHP Usage
```
Ou ka itilize klas ExcelToMySQL nan pwòp kòd PHP ou tou:

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
