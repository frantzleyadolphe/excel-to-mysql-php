<?php
session_start();
require './vendor/autoload.php';
require './src/ExcelToMySQL.php';

use Frantzley\ExcelToMySQL;

header('Content-Type: application/json');

$response = ['logs' => [], 'summary' => null, 'error' => null];

try {
    if (! isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Pa gen fichye upload oswa gen yon erè.");
    }

    $tableName = $_POST['table_name'] ?? null;
    $uniqueKey = $_POST['unique_key'] ?? null;

    $uploadDir = __DIR__ . '/uploads';
    if (! is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filePath = $uploadDir . '/' . basename($_FILES['excel_file']['name']);
    move_uploaded_file($_FILES['excel_file']['tmp_name'], $filePath);

    $pdo = new PDO("mysql:host=localhost;dbname=testdb;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $importer = new ExcelToMySQL($filePath, $pdo);
    $importer->setTableName($tableName);
    if (! empty($uniqueKey)) {
        $importer->setUniqueKey($uniqueKey);
    }

    // Chaje Excel la
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $headers     = $spreadsheet->getActiveSheet()->rangeToArray('A1:' . $spreadsheet->getActiveSheet()->getHighestColumn() . '1')[0];
    $mapping     = [];
    foreach ($headers as $header) {
        $mapping[$header] = $tableName . '.' . $header;
    }
    $importer->setMapping($mapping);

    $rows              = $spreadsheet->getActiveSheet()->toArray();
    $totalRows         = count($rows) - 1; // retire headers
    $response['total'] = $totalRows;

    array_shift($rows);     // retire headers
    $_SESSION['logs'] = []; // initial logs pou pagination/filter

    foreach ($rows as $rowIndex => $row) {
        $data = [];
        foreach ($headers as $index => $header) {
            if (isset($mapping[$header])) {
                $data[$mapping[$header]] = $row[$index];
            }
        }

        try {
            // itilize importRow() pito
            $type     = $importer->importRow($data);
            $logEntry = ['log' => "Liy #" . ($rowIndex + 2) . " processed", 'type' => $type];
        } catch (\Exception $e) {
            $logEntry = ['log' => "Error nan liy #" . ($rowIndex + 2) . ": " . $e->getMessage(), 'type' => 'error'];
        }

        $_SESSION['logs'][] = $logEntry;

        // voye log live pou JS
        echo json_encode([
            'log'     => $logEntry['log'],
            'type'    => $logEntry['type'],
            'current' => $rowIndex + 1,
            'total'   => $totalRows,
        ]) . "\n";
        flush();
    }

    $response['summary'] = $importer->getSummary();

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    echo json_encode($response);
}
