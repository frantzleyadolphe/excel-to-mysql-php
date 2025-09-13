<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/ExcelToMySQL.php';

use Frantzley\ExcelToMySQL;

header('Content-Type: application/json');

$response = ['logs' => [], 'summary' => null, 'error' => null];

try {
    if (! isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Pa gen fichye upload oswa gen yon erè.");
    }

    $tableName = $_POST['table_name'] ?? 'sheet';
    $uniqueKey = $_POST['unique_key'] ?? null;

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

    // Retire ranje ki totalman vid
    $rows = array_filter($rows, function ($row) {
        foreach ($row as $cell) {
            if (trim($cell) !== '') {
                return true;
            }

        }
        return false;
    });

    $totalRows        = count($rows);
    $_SESSION['logs'] = [];

    foreach ($rows as $rowIndex => $row) {
        $data = [];
        foreach ($headers as $index => $header) {
            if (isset($mapping[$header])) {
                $data[$mapping[$header]] = $row[$index];
            }
        }

        try {
            $type     = $importer->insertOrUpdateRow($data); // retounen 'insert', 'exists', 'error'
            $logEntry = [
                'log'  => match ($type) {
                    'insert' => "Liy #" . ($rowIndex + 2) . " ajoute nan DB",
                    'error'  => "Liy #" . ($rowIndex + 2) . " deja egziste nan DB li sote l",

                },
                'type' => $type,
            ];
        } catch (\Exception $e) {
            $logEntry = [
                'log'  => "Error nan liy #" . ($rowIndex + 2) . ": " . $e->getMessage(),
                'type' => 'error',
            ];
        }

        $_SESSION['logs'][] = $logEntry;

        echo json_encode([
            'log'     => $logEntry['log'],
            'type'    => $logEntry['type'],
            'current' => $rowIndex + 1,
            'total'   => $totalRows,
        ]) . "\n";
        flush();
    }
    $response['summary'] = $importer->getSummary();
    echo json_encode($response);

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    echo json_encode($response);
}
