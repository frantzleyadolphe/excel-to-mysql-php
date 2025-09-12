<?php
namespace Frantzley;

use PDO;
use PDOException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as SpreadsheetException;

class ExcelToMySQL
{
    protected string $filePath;
    protected PDO $pdo;
    protected array $mapping     = [];
    protected ?string $uniqueKey = null; // kle inik pou upsert
    protected int $inserted      = 0;
    protected int $updated       = 0;
    protected string $logFile;

    public function __construct(string $filePath, PDO $pdo, ?string $logFile = null)
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("Fichier Excel la pa jwenn: $filePath");
        }

        $this->filePath = $filePath;
        $this->pdo      = $pdo;
        $this->logFile  = $logFile ?? __DIR__ . '/import_errors.log';
    }

    /**
     * Define the mapping between Excel columns and MySQL table/columns
     */
    public function setMapping(array $mapping): void
    {
        if (empty($mapping)) {
            $this->log("Mapping la vid");
            throw new \InvalidArgumentException("Ou dwe defini omwen yon mapping pou enpòte done yo.");
        }
        $this->mapping = $mapping;
    }

    /**
     * Set unique key for UPSERT
     */
    public function setUniqueKey(string $uniqueKey): void
    {
        $this->uniqueKey = $uniqueKey;
    }

    /**
     * Run the import process
     */
    public function run(): void
    {
        try {
            $spreadsheet = IOFactory::load($this->filePath);
        } catch (SpreadsheetException $e) {
            $this->log("Echèk pou chaje fichye Excel: " . $e->getMessage());
            throw new \RuntimeException("Echèk pou chaje fichye Excel la: " . $e->getMessage());
        }

        $worksheet = $spreadsheet->getActiveSheet();
        $rows      = $worksheet->toArray();

        if (empty($rows)) {
            $this->log("Excel la vid: " . $this->filePath);
            throw new \RuntimeException("Excel la vid: " . $this->filePath);
        }

        // Premye liy lan gen headers
        $headers = array_shift($rows);

        foreach ($rows as $rowIndex => $row) {
            $data = [];
            foreach ($headers as $index => $header) {
                if (isset($this->mapping[$header])) {
                    $data[$this->mapping[$header]] = $row[$index];
                }
            }

            if (! empty($data)) {
                try {
                    $this->insertOrUpdateRow($data);
                } catch (\RuntimeException $e) {
                    $this->log("Erè nan liy " . ($rowIndex + 2) . ": " . $e->getMessage());
                }
            }
        }
    }

    protected function insertOrUpdateRow(array $data): void
    {
        $table = explode('.', reset(array_keys($data)))[0];

        $columns      = [];
        $placeholders = [];
        $values       = [];

        foreach ($data as $column => $value) {
            $col            = explode('.', $column)[1];
            $columns[]      = $col;
            $placeholders[] = '?';
            $values[]       = $value;
        }

        try {
            if ($this->uniqueKey) {
                $updateFields = [];
                foreach ($columns as $col) {
                    if ($col !== $this->uniqueKey) {
                        $updateFields[] = "$col = VALUES($col)";
                    }
                }

                $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ")
                        VALUES (" . implode(',', $placeholders) . ")
                        ON DUPLICATE KEY UPDATE " . implode(',', $updateFields);
            } else {
                $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ")
                        VALUES (" . implode(',', $placeholders) . ")";
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);

            if ($this->uniqueKey && $stmt->rowCount() === 2) {
                $this->updated++;
            } else {
                $this->inserted++;
            }

        } catch (PDOException $e) {
            $this->log("SQL Error: " . $e->getMessage() . " | SQL: $sql");
            throw new \RuntimeException("Echèk SQL pandan enpòtasyon: " . $e->getMessage());
        }
    }

    /**
     * Get summary (konbyen insert / update)
     */
    public function getSummary(): array
    {
        return [
            'inserted' => $this->inserted,
            'updated'  => $this->updated,
        ];
    }

    /**
     * Simple logger
     */
    protected function log(string $message): void
    {
        $date = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$date] $message" . PHP_EOL, FILE_APPEND);
    }
}
