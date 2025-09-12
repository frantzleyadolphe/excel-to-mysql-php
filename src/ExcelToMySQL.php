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
    protected ?string $uniqueKey = null;
    protected int $inserted      = 0;
    protected int $updated       = 0;
    protected string $logFile;
    protected ?int $limitRows    = null; // kantite liy maks pou enpòte
    protected ?string $tableName = null; // tab fòse si ou vle

    public function __construct(string $filePath, PDO $pdo, ?string $logFile = null)
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("Fichier Excel la pa jwenn: $filePath");
        }

        $this->filePath = $filePath;
        $this->pdo      = $pdo;
        $this->logFile  = $logFile ?? __DIR__ . '/import_errors.log';
    }

    public function setMapping(array $mapping): void
    {
        if (empty($mapping)) {
            $this->log("Mapping la vid", "WARNING");
            throw new \InvalidArgumentException("Ou dwe defini omwen yon mapping pou enpòte done yo.");
        }
        $this->mapping = $mapping;
    }

    public function setUniqueKey(string $uniqueKey): void
    {
        $this->uniqueKey = $uniqueKey;
    }

    public function setLimitRows(int $limit): void
    {
        $this->limitRows = $limit;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    // ===============================
// Metòd piblik pou insert/update yon sèl liy
// ===============================
    public function importRow(array $data): string
    {
        $this->insertOrUpdateRow($data);

        // Detèmine tip log pou liy sa a
        return ($this->updated > 0 && $this->updated + $this->inserted === count($data)) ? 'update' : 'insert';
    }

    public function run(): void
    {
        try {
            $spreadsheet = IOFactory::load($this->filePath);
        } catch (SpreadsheetException $e) {
            $this->log("Echèk pou chaje fichye Excel: " . $e->getMessage(), "ERROR");
            throw new \RuntimeException("Echèk pou chaje fichye Excel la: " . $e->getMessage());
        }

        $worksheet = $spreadsheet->getActiveSheet();
        $rows      = $worksheet->toArray();

        if (empty($rows)) {
            $this->log("Excel la vid: " . $this->filePath, "ERROR");
            throw new \RuntimeException("Excel la vid: " . $this->filePath);
        }

        $headers = array_shift($rows);

        // Limite kantite liy si limitRows defini
        if ($this->limitRows !== null) {
            $rows = array_slice($rows, 0, $this->limitRows);
        }

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
                    $this->log("Erè nan liy " . ($rowIndex + 2) . ": " . $e->getMessage(), "ERROR");
                }
            }
        }
    }

    protected function insertOrUpdateRow(array $data): void
    {
        $keys     = array_keys($data);
        $tableKey = reset($keys);

        // Si tableName defini, li ap itilize li, sinon li sòti nan mapping
        $table = $this->tableName ?? explode('.', $tableKey)[0];

        // Kreye tab otomatik si li pa egziste
        $columnsSQL = [];
        foreach ($data as $col => $val) {
            $colName      = explode('.', $col)[1];
            $columnsSQL[] = "$colName VARCHAR(255)";
        }
        $uniqueSQL      = $this->uniqueKey ? ", UNIQUE({$this->uniqueKey})" : "";
        $createTableSQL = "CREATE TABLE IF NOT EXISTS $table (id INT AUTO_INCREMENT PRIMARY KEY, " . implode(',', $columnsSQL) . $uniqueSQL . ")";
        $this->pdo->exec($createTableSQL);

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

            $this->log("Liy insert/update nan tab $table: " . json_encode($data), "INFO");

        } catch (PDOException $e) {
            $this->log("SQL Error: " . $e->getMessage() . " | SQL: $sql", "ERROR");
            throw new \RuntimeException("Echèk SQL pandan enpòtasyon: " . $e->getMessage());
        }
    }

    public function getSummary(): array
    {
        return [
            'inserted' => $this->inserted,
            'updated'  => $this->updated,
        ];
    }

    protected function log(string $message, string $level = 'INFO'): void
    {
        $date = date('Y-m-d H:i:s');

        $logDir = dirname($this->logFile);
        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $formatted = sprintf("[%s] [%s] %s", $date, strtoupper($level), $message);
        file_put_contents($this->logFile, $formatted . PHP_EOL, FILE_APPEND);
    }
}
