<?php

require __DIR__ . '/../vendor/autoload.php';

use Frantzley\ExcelToMySQL;

$logFile = __DIR__ . "/import.log";

// Fonksyon pou ekri nan log file ak console
function writeLog(string $message)
{
    global $logFile;
    echo $message . "\n";
    file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=testdb;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    writeLog("✅ Koneksyon ak DB an mache!");
} catch (PDOException $e) {
    writeLog("❌ Erè koneksyon ak DB: " . $e->getMessage());
    exit;
}

// Kreye importer
$importer = new ExcelToMySQL(__DIR__ . "/data.xlsx", $pdo);

// Set unique key (opsyonèl, men rekòmande)
$importer->setUniqueKey("email");

// Mete tab DB la
$importer->setTableName("users");

// Chaje Excel
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(__DIR__ . "/data.xlsx");
$sheet       = $spreadsheet->getActiveSheet();
$rows        = $sheet->toArray();

// Retire headers
$headers = array_shift($rows);
$headers = array_map('trim', $headers);

// 🚀 Auto mapping (pa bezwen mete setMapping manyèlman)
$importer->autoMap($headers, true);

// Retire ranje vid
$rows = array_filter($rows, fn($r) => count(array_filter($r, fn($c) => trim($c) !== '')) > 0);

$totalRows = count($rows);
$inserted  = 0;
$exists    = 0;

foreach ($rows as $index => $row) {
    $data = [];
    foreach ($headers as $i => $header) {
        if (isset($importer->getMapping()[$header])) {
            $data[$importer->getMapping()[$header]] = $row[$i];
        }
    }

    $result = $importer->insertOrUpdateRow($data);

    match ($result) {
        'insert' => writeLog("Liy #" . ($index + 2) . " ajoute nan DB"),
        'exists' => writeLog("Liy #" . ($index + 2) . " deja egziste, li sote"),
        'error'  => writeLog("Liy #" . ($index + 2) . " gen erè pandan insert"),
    };

    if ($result === 'insert') {
        $inserted++;
    }

    if ($result === 'exists') {
        $exists++;
    }
}

// Summary
writeLog("\n✅ Import fini! Nouvo: {$inserted} | Deja egziste: {$exists}");
writeLog("Log file: $logFile");
