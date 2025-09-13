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
    $insertedCount = 0;
    $existsCount   = 0;

    $uploadDir = __DIR__ . '/uploads';
    if (! is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filePath = $uploadDir . '/' . basename($_FILES['excel_file']['name']);
    move_uploaded_file($_FILES['excel_file']['tmp_name'], $filePath);

    $pdo = new PDO("mysql:host=localhost;dbname=testdb;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $importer = new ExcelToMySQL($filePath, $pdo);
    $importer->setTableName($tableName);
    if ($uniqueKey) {
        $importer->setUniqueKey($uniqueKey);
    }

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $sheet       = $spreadsheet->getActiveSheet();
    $rows        = $sheet->toArray();

    if (empty($rows)) {
        throw new Exception("Fichye Excel la vid.");
    }

    // Retire headers
    $headers = array_shift($rows);
    $headers = array_map('trim', $headers);

    // Filtre headers vid
    $mapping = [];
    foreach ($headers as $header) {
        if ($header !== '') {
            $mapping[$header] = $header;
        }

    }
    $importer->setMapping($mapping);

    // Retire ranje ki vid totalman
    $rows = array_filter($rows, function ($row) {
        foreach ($row as $cell) {
            if (trim($cell) !== '') {
                return true;
            }

        }
        return false;
    });

    $totalRows = count($rows);

    foreach ($rows as $rowIndex => $row) {
        $data = [];
        foreach ($headers as $i => $header) {
            if (isset($mapping[$header])) {
                $data[$mapping[$header]] = $row[$i];
            }
        }

        // Kreye tab la si li pa egziste
        $importer->createTableIfNotExists($importer->getMapping() ? array_values($importer->getMapping()) : $headers);
        // Eseye insert oubyen update

        try {
            $result = $importer->insertOrUpdateRow($data);
            // Ogmante kontè yo
            match ($result) {
                'insert' => $insertedCount++,
                'exists' => $existsCount++,
                default  => null,
            };

            // Fè log ak writeLog()
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

    // Send summary nan fen
    $summary = $importer->getSummary();
    echo json_encode([
        'log' => writeLog("Import fini! Nouvo: {$summary['inserted']}, Deja egziste: {$summary['exists']}"),
        'type'    => 'info',
        'summary' => $summary,
    ]);

} catch (\Exception $e) {
    echo json_encode([
        'log'  => writeLog("Erè: " . $e->getMessage()),
        'type' => 'error',
    ]);
}
