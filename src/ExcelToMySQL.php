<?php
namespace Frantzley\ExcelToMySQL;

use Frantzley\ExcelToMySQL\Exceptions\ExcelToMySQLException;
use Frantzley\ExcelToMySQL\TableManager;
use PDO;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelToMySQL
{
    private PDO $pdo;
    private string $filePath;
    private string $tableName;
    private array $mapping    = [];
    private array $validation = [];

    public function __construct(PDO $pdo, string $filePath, string $tableName)
    {
        $this->pdo       = $pdo;
        $this->filePath  = $filePath;
        $this->tableName = $tableName;
    }

    public function setMapping(array $mapping, array $validation = []): self
    {
        $this->mapping    = $mapping;
        $this->validation = $validation;
        return $this;
    }

    public function run(): void
    {
        try {
            $spreadsheet = IOFactory::load($this->filePath);
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray();
            $header      = array_shift($rows);

            $columns = [];
            foreach ($this->mapping as $excelCol => $dbCol) {
                $columns[$dbCol] = 'VARCHAR(255)';
                if (isset($this->validation[$dbCol]) && $this->validation[$dbCol] === 'int') {
                    $columns[$dbCol] = 'INT';
                }
            }
            $tableManager = new TableManager($this->pdo, $this->tableName);
            $tableManager->createTableIfNotExists($columns);

            foreach ($rows as $row) {
                $data = [];
                foreach ($this->mapping as $excelCol => $dbCol) {
                    $index = array_search($excelCol, $header);
                    if ($index !== false) {
                        $value = $row[$index];
                        if (! $this->validate($dbCol, $value)) {
                            continue 2;
                        }

                        $data[$dbCol] = $value;
                    }
                }
                $this->upsertRow($data);
            }
        } catch (\Exception $e) {
            throw new ExcelToMySQLException($e->getMessage());
        }
    }

    private function validate(string $column, $value): bool
    {
        if (! isset($this->validation[$column])) {
            return true;
        }

        $type = $this->validation[$column];
        if ($type === 'email') {
            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        }

        if ($type === 'int') {
            return is_numeric($value);
        }

        return true;
    }

    private function upsertRow(array $data): void
    {
        $columns      = implode(',', array_keys($data));
        $placeholders = implode(',', array_map(fn($c) => ":$c", array_keys($data)));
        $updates      = implode(',', array_map(fn($c) => "$c=VALUES($c)", array_keys($data)));

        $stmt = $this->pdo->prepare("INSERT INTO {$this->tableName} ($columns) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updates");
        $stmt->execute($data);
    }
}
