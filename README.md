## Excel to MySQL Web Importer
```
Yon pake PHP ki pèmèt ou enpòte done soti nan fichye Excel (`.xls` / `.xlsx`)
nan yon baz done MySQL avèk tout opsyon sa yo ki nan pwen kle
Li fèt pou devlopè ak itilizatè ki bezwen yon travay rapid, epi li ofri
yon UI senp ak fonctionnalités avanse tankou live logs,
progress bar, ak filtre logs.
```
## 🛠 Features
```
- Kreye baz done otomatik si li pa egziste.
- Kreye tab otomatik selon headers nan Excel.
- Li sipote fichye ki gen plizye sheets
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

```
Enstale via Composer:
```
composer require frantzley/excel-to-mysql
```
## Estrikti Projet an
```

project-root/
├─ public/
│  ├─ index.php # Upload form ak UI
   ├─ app.js     #js file
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
## 📦 Example pou test PHP Usage
```

<?php
require __DIR__ . '/vendor/autoload.php';

use Frantzley\ExcelToMySQL;

// Koneksyon PDO
$pdo = new PDO("mysql:host=localhost;dbname=testdb;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Inisyalize importer
$importer = new ExcelToMySQL("chemin/fiche.xlsx", $pdo);

// Opsyonèl: mete non tab la ak kle inik
$importer->setTableName('users');
$importer->setUniqueKey('email');

// Kreye tab si li pa egziste
$importer->createTableIfNotExists();

// Insert / Update done
$rows = $importer->getRowsFromExcel();
foreach ($rows as $row) {
    $importer->insertOrUpdateRow($row);
}

// Summary
print_r($importer->getSummary());


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

wap sèlman bezwen lanse proje an epi navigue nan index.php pou lanse paj web lan epi ranpli champ pou upload fichye Excel, epi log ap montre yo insert / exists / error.
Tout folder nesesè (uploads/) kreye otomatikman si li pa egziste.
```
Pake a sipòte:
```
1. Kreye Baz Done Otomatik

Si baz done a pa egziste → li kreye li epi voye log Baz done 'xxx' pa t egziste, li te kreye otomatikman ✅.

Si li egziste → pa kreye li ankò epi voye log Baz done 'xxx' deja egziste ⚠️.

Si gen erè koneksyon → voye log Erè koneksyon ak baz done 'xxx': [detay erè] ❌.

2. Kreye Tab Otomatik

Tab kreye selon headers nan Excel.

Headers vid yo ignore.

Ranje vid totalman retire.

3. Insert / Update

Insert nouvo liy si li pa egziste.

Update si kle inik deja egziste.

Logs montre chak aksyon.

4. Logs Dinamik

Filtre logs selon tip (insert, update, exists, error, info).

Wotè logs ajiste selon kantite log.

Scroll otomatik pou dènye log.

5. Progress Bar

Montre pwogrè a selon kantite ranje enpòte.

