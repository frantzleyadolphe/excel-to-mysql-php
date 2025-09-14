<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/ExcelToMySQL.php';

use Frantzley\ExcelToMySQL;

// Fonksyon pou ekri log
function writeLog(string $message): string
{
    error_log($message); // ekri nan log PHP
    return $message;     // retounen mesaj la pou JSON
}

header('Content-Type: application/json');

try {
    if (! isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Pa gen fichye upload oswa gen yon erè.");
    }

    $tableName     = $_POST['table_name'] ?? 'sheet';
    $uniqueKey     = $_POST['unique_key'] ?? null;
    $sheetName     = $_POST['sheet_name'] ?? null; // sheet chwazi pa itilizatè a
    $insertedCount = 0;
    $existsCount   = 0;
    $dbHost        = $_POST['db_host'] ?? 'localhost';
    $dbUser        = $_POST['db_user'] ?? 'root';
    $dbPass        = $_POST['db_pass'] ?? '';
    $dbName        = $_POST['db_name'] ?? 'testdb';

    $uploadDir = __DIR__ . '/uploads';
    if (! is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filePath = $uploadDir . '/' . basename($_FILES['excel_file']['name']);
    move_uploaded_file($_FILES['excel_file']['tmp_name'], $filePath);

    // Koneksyon PDO san baz done pou ka kreye li si bezwen
    $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tcheke si baz done a egziste deja
    $stmt   = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $logMessage = writeLog("Baz done '$dbName' deja egziste, pa kreye li ankò ⚠️");
    } else {
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $logMessage = writeLog("Baz done '$dbName' pa t egziste, li te kreye otomatikman ✅");
    }
    echo json_encode(['log' => $logMessage, 'type' => 'info', 'current' => 0, 'total' => 0]) . "\n";
    flush();

    $pdo->exec("USE `$dbName`");

    $importer = new ExcelToMySQL($filePath, $pdo);
    $importer->setTableName($tableName);
    if ($uniqueKey) {
        $importer->setUniqueKey($uniqueKey);
    }

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

    // **Si itilizatè chwazi sheet, pran li, sinon pran active sheet**
    if ($sheetName && in_array($sheetName, $spreadsheet->getSheetNames())) {
        $sheet = $spreadsheet->getSheetByName($sheetName);
    } else {
        $sheet = $spreadsheet->getActiveSheet();
    }

    $rows = $sheet->toArray();

    if (empty($rows)) {
        throw new Exception("Fichye Excel la vid.");
    }

    $headers = array_map('trim', array_shift($rows));

    // Filtre headers vid
    $mapping = [];
    foreach ($headers as $header) {
        if ($header !== '') {
            $mapping[$header] = $header;
        }
    }

    $importer->setMapping($mapping);

    $rows = array_filter($rows, fn($row) => array_filter($row, fn($cell) => trim($cell) !== ''));

    $createdTable = $importer->createTableIfNotExists(array_values($mapping));
    if ($createdTable) {
        $logMessage = writeLog("Tab la '$tableName' pa t egziste, li te kreye otomatikman ✅");
        echo json_encode(['log' => $logMessage, 'type' => 'info', 'current' => 0, 'total' => 0]) . "\n";
        flush();
    }

    $totalRows = count($rows);

    foreach ($rows as $rowIndex => $row) {
        $data = [];
        foreach ($headers as $i => $header) {
            if (isset($mapping[$header])) {
                $data[$mapping[$header]] = $row[$i];
            }
        }

        try {
            $result = $importer->insertOrUpdateRow($data);
            match ($result) {
                'insert' => $insertedCount++,
                'exists' => $existsCount++,
                default  => null,
            };

            $logMessage = match ($result) {
                'insert' => writeLog("Liy #" . ($rowIndex + 2) . " ajoute nan DB, | Nouvo: $insertedCount"),
                'exists' => writeLog("Liy #" . ($rowIndex + 2) . " deja egziste, li sote"),
                'error'  => writeLog("Liy #" . ($rowIndex + 2) . " gen erè pandan insert oubyen li deja egziste "),
            };

        } catch (\Exception $e) {
            $result     = 'error';
            $logMessage = writeLog("Error nan liy #" . ($rowIndex + 2) . ": " . $e->getMessage());
        }

        echo json_encode([
            'log'     => $logMessage,
            'type'    => $result,
            'current' => $rowIndex + 1,
            'total'   => $totalRows,
        ]) . "\n";
        flush();
    }

    $summary = $importer->getSummary();
    echo json_encode([
        'log' => writeLog("Import fini! Nouvo: {$summary['inserted']}, Deja egziste: {$summary['exists']}"),
        'type'    => 'info',
        'summary' => $summary,
    ]);

} catch (\Exception $e) {
    $logMessage = writeLog("Erè koneksyon ak baz done '$dbName': " . $e->getMessage());
    echo json_encode([
        'log'     => $logMessage,
        'type'    => 'error',
        'current' => 0,
        'total'   => 0,
    ]) . "\n";
    flush();
    exit;
}
